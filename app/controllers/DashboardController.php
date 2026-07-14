<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Izin.php';
require_once __DIR__ . '/../models/Presensi.php';

class DashboardController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo); // Aktifkan authGuard() lewat BaseController
        authGuard(); // Melindungi seluruh controller ini
    }

    public function index()
    {
        // 1. Decode level dari session menjadi array (contoh: [1, 2])
        // 1. Decode level dari session menjadi array (contoh: [1,2])
        $userLevels = [];
        if (isset($_SESSION['user']['level']) && is_string($_SESSION['user']['level'])) {
            $decoded_levels = base64_decode($_SESSION['user']['level']);
            $userLevels = explode(',', $decoded_levels);
        }

        if (empty($userLevels)) {
            logoutUser();
            return;
        }

        // Convert ke integer
        $userLevelsInt = array_map('intval', $userLevels);

        // ID user umum
        $id_user = $_SESSION['user']['id'] ?? null;


        if (empty($userLevels)) {
            logoutUser(); // Jika tidak ada level, logout untuk keamanan
            return;
        }

        // 2. Tentukan view yang akan ditampilkan berdasarkan prioritas level
        // 2. Tentukan view berdasarkan prioritas
        $view = '';

        if (in_array(1, $userLevelsInt)) {
            $view = 'admin/dashboard';
        } elseif (in_array(8, $userLevelsInt)) {
            // Dahulukan Satpam jika itu prioritas utama aksesnya
            $this->index2();
            return;
        } elseif (in_array(2, $userLevelsInt)) {
            $view = 'guru/dashboard';
        } elseif (in_array(3, $userLevelsInt)) {
            $view = 'walikelas/dashboard';
        } elseif (in_array(4, $userLevelsInt)) {
            $view = 'bk/dashboard';
        } elseif (in_array(5, $userLevelsInt)) {
            $view = 'kurikulum/dashboard';
        } elseif (in_array(6, $userLevelsInt)) {
            $view = 'kesiswaan/dashboard';
        } elseif (in_array(7, $userLevelsInt)) {
            $view = 'kaponpes/dashboard';
        } elseif (count(array_intersect([9, 10, 11, 12, 13, 14, 15, 16], $userLevelsInt)) > 0) {
            // Level 9-16 arahkan ke view general atau view guru jika belum punya dashboard khusus
            $view = 'guru/dashboard';
        } else {
            // Jika benar-benar tidak ada level yang dikenal
            log_message("User ID " . $id_user . " ditendang karena level tidak teridentifikasi: " . implode(',', $userLevelsInt));
            logoutUser();
            return;
        }

        // 3. Kumpulkan semua data yang dibutuhkan (logika Anda yang sudah ada)
        // Statistik
        $jumlahSiswa = $this->db->query("SELECT COUNT(*) FROM siswa")->fetchColumn();
        $jumlahGuru = $this->db->query("SELECT COUNT(*) FROM employe")->fetchColumn();
        $jumlahKelas = $this->db->query("SELECT COUNT(*) FROM kelas")->fetchColumn();

        // Ambil ID tahun pelajaran aktif
        $id_tahun_pelajaran_aktif = $this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1")->fetchColumn();

        $deadlineList = [];
        $id_guru = $_SESSION['user']['id'] ?? null;
        $semuaJurnalTerisi = true;

        if ($id_tahun_pelajaran_aktif && $id_guru) {
            // Logika untuk deadline perangkat
            $stmt = $this->db->prepare("
                SELECT d.jenis_perangkat, d.tanggal_deadline,
                       CASE WHEN pm.id IS NOT NULL THEN 1 ELSE 0 END AS sudah_upload,
                       COALESCE(pm.status_approval, 'belum upload') AS status_approval
                FROM deadline_perangkat d
                JOIN mapel_guru mg ON mg.id_guru = :id_employe
                JOIN mapel m ON mg.id_mapel = m.id_mapel
                LEFT JOIN perangkat_mengajar pm ON pm.jenis_perangkat = d.jenis_perangkat
                    AND pm.id_tahun_pelajaran = d.id_tahun_pelajaran
                    AND pm.id_employe = :id_employe
                WHERE d.id_tahun_pelajaran = :id_tahun_pelajaran
                GROUP BY d.jenis_perangkat, d.tanggal_deadline, pm.id, pm.status_approval
                ORDER BY d.tanggal_deadline ASC
            ");
            $stmt->execute(['id_tahun_pelajaran' => $id_tahun_pelajaran_aktif, 'id_employe' => $id_guru]);
            $deadlineList = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $timezone = new DateTimeZone('Asia/Jakarta');
            $today = new DateTime('now', $timezone);
            $today->setTime(0, 0, 0); // ✅ RESET JAM KE 00:00:00 agar murni hitung tanggal
            foreach ($deadlineList as &$d) {
                if ($d['status_approval'] === 'Ditolak') {
                    $d['status'] = 'ditolak';
                    $d['sisa_hari'] = null;
                } elseif ($d['sudah_upload']) {
                    $d['status'] = 'selesai';
                    $d['sisa_hari'] = null;
                } else {
                    $tglDeadline = new DateTime($d['tanggal_deadline']);
                    $diff = $today->diff($tglDeadline);
                    $d['sisa_hari'] = $diff->invert ? 0 : $diff->days; // Jika sudah lewat, sisa hari 0
                    $d['status'] = ($tglDeadline < $today) ? 'lewat' : (($d['sisa_hari'] <= 7) ? 'dekat' : 'aman');
                }
            }
            unset($d);

            // Logika untuk pengecekan jurnal harian
            // Hanya dijalankan jika user yang login memiliki level guru (2)
            if (in_array(2, $userLevels)) {
                $hari_ini = date('l'); // e.g., Monday, Tuesday
                $stmtJurnal = $this->db->prepare("
                    SELECT COUNT(jp.id_jadwal) 
                    FROM jadwal_pelajaran jp
                    JOIN mapel_guru mg ON mg.id_mapel_guru = jp.id_mapel_guru
                    LEFT JOIN jurnal j ON j.id_mapel_guru = mg.id_mapel_guru AND j.tanggal = CURDATE()
                    WHERE mg.id_guru = :id_employe AND jp.hari = :hari_ini AND j.id_jurnal IS NULL
                ");
                $stmtJurnal->execute(['id_employe' => $id_guru, 'hari_ini' => $hari_ini]);
                $jumlahJurnalBelumDiisi = $stmtJurnal->fetchColumn();

                if ($jumlahJurnalBelumDiisi > 0) {
                    $semuaJurnalTerisi = false;
                }
            }
        }
        // ===============================
        // Deadline Program Struktural (untuk level tertentu)
        // ===============================
        $levelsProgram = [5, 6, 10, 11, 12, 13, 14, 15, 16];

        // cek level user
        $showProgramDeadline = count(array_intersect($userLevelsInt, $levelsProgram)) > 0;

        // inisialisasi WAJIB
        $deadlineProgram = [];

        if ($showProgramDeadline && $id_tahun_pelajaran_aktif) {
            $stmt = $this->db->prepare("
        SELECT d.jenis_program, d.tanggal_deadline,
               COALESCE(p.file, NULL) AS file,
               COALESCE(p.status_approval, 'Belum dikirim') AS status_approval
        FROM deadline_program_struktural d
        LEFT JOIN program_struktural p
            ON p.jenis_program = d.jenis_program
            AND p.id_employe = :id_user
            AND p.id_tahun_pelajaran = d.id_tahun_pelajaran
        WHERE d.id_tahun_pelajaran = :id_tahun
        ORDER BY d.tanggal_deadline ASC
    ");

            $stmt->execute([
                ':id_user' => $id_user,
                ':id_tahun' => $id_tahun_pelajaran_aktif
            ]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $timezone = new DateTimeZone('Asia/Jakarta');
            $today = new DateTime('now', $timezone);
            $today->setTime(0, 0, 0); // ✅ RESET JAM KE 00:00:00 agar murni hitung tanggal

            foreach ($rows as $r) {
                $deadlineDate = new DateTime($r['tanggal_deadline']);

                if ($r['status_approval'] === 'Ditolak') {
                    $status = 'ditolak';
                    $daysLeft = null;
                } elseif ($r['file']) {
                    $status = 'selesai';
                    $daysLeft = null;
                } elseif ($deadlineDate < $today) {
                    $status = 'lewat';
                    $daysLeft = 0;
                } else {
                    $status = 'belum upload';
                    $daysLeft = $today->diff($deadlineDate)->days;
                }


                $daysLeft = ($deadlineDate > $today)
                    ? $today->diff($deadlineDate)->days
                    : 0;

                $deadlineProgram[] = [
                    'jenis_program'    => $r['jenis_program'],
                    'file'             => $r['file'],
                    'status_approval'  => $r['status_approval'],
                    'status'           => $status,
                    'sisa_hari'        => $daysLeft,
                    'deadline'         => $r['tanggal_deadline'], // disarankan
                ];
            }
        }

        // ===============================
        // Logika Presensi Hari Ini (Support Kegiatan)
        // ===============================
        date_default_timezone_set('Asia/Jakarta');
        $today = date('Y-m-d');
        $jam_sekarang = date('H:i:s');
        $hari_ini = date('l');

        $presensiHariIni = ['ada_jadwal' => false, 'is_kegiatan' => false];

        if ($id_user) {
            $stmtPin = $this->db->prepare("SELECT pin FROM employe WHERE id_employe = :id_employe LIMIT 1");
            $stmtPin->execute(['id_employe' => $id_user]);
            $userPin = $stmtPin->fetchColumn();

            if ($userPin) {
                // 1. CEK APAKAH ADA KEGIATAN HARI INI
                $stmtKegiatan = $this->db->prepare("
                    SELECT k.nama_kegiatan, k.jam_mulai, k.jam_selesai 
                    FROM kegiatan k
                    JOIN kegiatan_peserta kp ON k.id_kegiatan = kp.id_kegiatan
                    WHERE k.tanggal = :today AND kp.pin_guru = :pin
                    LIMIT 1
                ");
                $stmtKegiatan->execute(['today' => $today, 'pin' => $userPin]);
                $kegiatanHariIni = $stmtKegiatan->fetch(PDO::FETCH_ASSOC);

                if ($kegiatanHariIni) {
                    // --- MODE KEGIATAN ---
                    $nama_kegiatan = $kegiatanHariIni['nama_kegiatan'];
                    $jam_mulai_kegiatan = $kegiatanHariIni['jam_mulai'];
                    $jam_pulang_jadwal = $kegiatanHariIni['jam_selesai'];

                    // Ambil scan langsung dari tabel attendance
                    $stmtScan = $this->db->prepare("
                        SELECT MIN(TIME(scan_date)) as jam_masuk, MAX(TIME(scan_date)) as jam_pulang 
                        FROM attendance 
                        WHERE pin = :pin AND DATE(scan_date) = :today
                    ");
                    $stmtScan->execute(['pin' => $userPin, 'today' => $today]);
                    $scan = $stmtScan->fetch(PDO::FETCH_ASSOC);

                    $waktu_datang_bersih = $scan['jam_masuk'] ? substr($scan['jam_masuk'], 0, 5) : null;
                    $waktu_pulang_bersih = $scan['jam_pulang'] ? substr($scan['jam_pulang'], 0, 5) : null;

                    // Jika absen baru 1 kali, jam_masuk dan jam_pulang dari query MIN MAX akan sama nilainya
                    // Maka kita set waktu_pulang jadi null agar statusnya "Belum Absen"
                    if ($waktu_datang_bersih === $waktu_pulang_bersih && $waktu_datang_bersih !== null) {
                        $waktu_pulang_bersih = null;
                    }

                    // Tentukan Status Datang
                    if (!$waktu_datang_bersih) {
                        $status_datang = ($jam_sekarang > $jam_pulang_jadwal) ? 'Alpa' : 'Belum Absen';
                        $waktu_datang_tampil = '--:--';
                    } else {
                        $waktu_datang_tampil = $waktu_datang_bersih;
                        // Jika jam masuk lebih dari jam mulai kegiatan + toleransi keterlambatan
                        $status_datang = ($scan['jam_masuk'] > $jam_mulai_kegiatan) ? 'Terlambat' : 'Tepat Waktu';
                    }

                    // Tentukan Status Pulang
                    $is_blinking = false;
                    if (!$waktu_pulang_bersih) {
                        if ($status_datang === 'Alpa') {
                            $status_pulang = 'Alpa';
                            $waktu_pulang_tampil = '--:--';
                        } else {
                            $status_pulang = 'Belum Absen';
                            $waktu_pulang_tampil = 'Belum Absen';
                            $is_blinking = true;
                        }
                    } else {
                        $waktu_pulang_tampil = $waktu_pulang_bersih;
                        $status_pulang = ($scan['jam_pulang'] < $jam_pulang_jadwal) ? 'Pulang Cepat' : 'Sesuai';
                    }

                    $presensiHariIni = [
                        'ada_jadwal'    => true,
                        'is_kegiatan'   => true,
                        'nama_kegiatan' => $nama_kegiatan,
                        'waktu_datang'  => $waktu_datang_tampil,
                        'status_datang' => $status_datang,
                        'waktu_pulang'  => $waktu_pulang_tampil,
                        'status_pulang' => $status_pulang,
                        'is_blinking'   => $is_blinking
                    ];
                } else {
                    // --- MODE REGULER (Jadwal Mengajar Biasa) ---
                    $presensiModel = new Presensi($this->db);
                    $jadwalPegawai = $presensiModel->getJadwalHariIni($userPin, $hari_ini);
                    $kehadiran = $presensiModel->getKehadiranLengkap($userPin, $today);

                    if ($jadwalPegawai && !empty($kehadiran)) {
                        $jam_pulang_jadwal = $jadwalPegawai['jam_pulang'] ?? '15:00:00'; // Sesuikan kolom database

                        $isLibur = (isset($kehadiran['keterangan']) && (stripos($kehadiran['keterangan'], 'libur') !== false || stripos($kehadiran['keterangan'], 'tidak ada jadwal') !== false));

                        if (!$isLibur) {
                            $waktu_datang_raw = $kehadiran['waktu_datang'] ?? '';
                            $waktu_pulang_raw = $kehadiran['waktu_pulang'] ?? '';

                            preg_match('/(\d{2}:\d{2})/', $waktu_datang_raw, $match_datang);
                            preg_match('/(\d{2}:\d{2})/', $waktu_pulang_raw, $match_pulang);

                            $waktu_datang_bersih = $match_datang[1] ?? null;
                            $waktu_pulang_bersih = $match_pulang[1] ?? null;

                            if (!$waktu_datang_bersih) {
                                $status_datang = ($jam_sekarang > $jam_pulang_jadwal) ? 'Alpa' : 'Belum Absen';
                                $waktu_datang_tampil = '--:--';
                            } else {
                                $waktu_datang_tampil = $waktu_datang_bersih;
                                $status_datang = (stripos($waktu_datang_raw, 'Terlambat') !== false) ? 'Terlambat' : 'Tepat Waktu';
                            }

                            $is_blinking = false;
                            if (!$waktu_pulang_bersih) {
                                if ($status_datang === 'Alpa') {
                                    $status_pulang = 'Alpa';
                                    $waktu_pulang_tampil = '--:--';
                                } else {
                                    $status_pulang = 'Belum Absen';
                                    $waktu_pulang_tampil = 'Belum Absen';
                                    $is_blinking = true;
                                }
                            } else {
                                $waktu_pulang_tampil = $waktu_pulang_bersih;
                                $status_pulang = (stripos($waktu_pulang_raw, 'Pulang cepat') !== false) ? 'Pulang Cepat' : 'Sesuai';
                            }

                            $presensiHariIni = [
                                'ada_jadwal'    => true,
                                'is_kegiatan'   => false,
                                'waktu_datang'  => $waktu_datang_tampil,
                                'status_datang' => $status_datang,
                                'waktu_pulang'  => $waktu_pulang_tampil,
                                'status_pulang' => $status_pulang,
                                'is_blinking'   => $is_blinking
                            ];
                        }
                    }
                }
            }
        }
        // 4. Kirim semua data ke view yang sudah ditentukan
        view($view, [
            'jumlahSiswa'       => $jumlahSiswa,
            'jumlahGuru'        => $jumlahGuru,
            'jumlahKelas'       => $jumlahKelas,
            'deadlineList'      => $deadlineList,
            'semuaJurnalTerisi' => $semuaJurnalTerisi,
            'deadlineProgram'   => $deadlineProgram,
            'showProgramDeadline' => $showProgramDeadline,
            'presensiHariIni'   => $presensiHariIni
        ]);
    }

    public function index2()
    {
        // ambil data izin
        $model = new Izin($this->db);
        $izin = $model->getAllWithSiswaKelas();

        view('satpam/dashboard', [
            'izin' => $izin,
        ]);
    }

    // Endpoint to mark student returned
    public function markKembali()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }

        $id = $_POST['id_perizinan'] ?? null;
        $waktu_kembali = $_POST['waktu_kembali'] ?? null;
        $keterangan_kembali = $_POST['keterangan_kembali'] ?? null;
        $tindakan = $_POST['tindakan'] ?? null;

        if (!$id) {
            setFlash('error', 'ID perizinan tidak ditemukan.');
            header('Location: index.php?controller=dashboard');
            exit;
        }

        // Simple update query - sesuaikan nama tabel & kolom bila berbeda
        $stmt = $this->db->prepare("UPDATE perizinan SET waktu_kembali = :waktu_kembali, keterangan = :keterangan_kembali, tindakan = :tindakan WHERE id_perizinan = :id");
        $ok = $stmt->execute([
            ':waktu_kembali' => $waktu_kembali,
            ':keterangan_kembali' => $keterangan_kembali,
            ':tindakan' => $tindakan,
            ':id' => $id
        ]);

        if ($ok) {
            setFlash('success', 'Data berhasil diperbarui.');
        } else {
            setFlash('error', 'Gagal memperbarui data.');
        }

        header('Location: index.php?controller=dashboard&method=index2');
        exit;
    }
}
