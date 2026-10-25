<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Izin.php';
require_once __DIR__ . '/../models/Presensi.php';
require_once __DIR__ . '/../models/PengingatDeadline.php';
require_once __DIR__ . '/../models/Jurnal.php';
require_once __DIR__ . '/../models/JurnalStrukturalModel.php';

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

        $pengingat = new PengingatDeadline($this->db);
        $id_guru = $_SESSION['user']['id'] ?? null;
        // Pengingat deadline perangkat pembelajaran (guru mapel)
        $deadlineList = $pengingat->perangkat($id_guru, $id_tahun_pelajaran_aktif);

        // ===============================
        // Deadline Program Struktural (jabatan struktural & tenaga kependidikan)
        // ===============================
        $showProgramDeadline = $pengingat->bolehProgramStruktural($userLevelsInt, $id_user);
        $deadlineProgram = $showProgramDeadline
            ? $pengingat->programStruktural($id_user, $id_tahun_pelajaran_aktif)
            : [];


        // ===============================
        // Rekap hari ini: presensi, jurnal mengajar, jurnal struktural
        // ===============================
        $presensiHariIni       = $this->rekapPresensiHariIni($id_user);
        $rekapJurnalMengajar   = $this->rekapJurnalMengajarHariIni($id_guru, $id_tahun_pelajaran_aktif);
        $rekapJurnalStruktural = $this->rekapJurnalStrukturalHariIni($id_user, $id_tahun_pelajaran_aktif);

        // 4. Kirim semua data ke view yang sudah ditentukan
        view($view, [
            'jumlahSiswa'       => $jumlahSiswa,
            'jumlahGuru'        => $jumlahGuru,
            'jumlahKelas'       => $jumlahKelas,
            'deadlineList'      => $deadlineList,
            'deadlineProgram'   => $deadlineProgram,
            'showProgramDeadline' => $showProgramDeadline,
            'presensiHariIni'   => $presensiHariIni,
            'rekapJurnalMengajar'   => $rekapJurnalMengajar,
            'rekapJurnalStruktural' => $rekapJurnalStruktural
        ]);
    }

    /** Nama hari bahasa Indonesia dari nama hari English (Monday -> Senin). */
    private function namaHariIndo($hari)
    {
        $map = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu',
        ];
        return $map[$hari] ?? $hari;
    }

    /**
     * Rekap presensi hari ini.
     * Sumber: kegiatan lembaga (kegiatan + kegiatan_peserta), jadwal kerja (tabel jadwal),
     * dan scan finger (tabel attendance). Scan tetap ditampilkan walau tidak terjadwal.
     */
    private function rekapPresensiHariIni($id_user)
    {
        date_default_timezone_set('Asia/Jakarta');
        $today       = date('Y-m-d');
        $hariEnglish = date('l');
        $jamSekarang = date('H:i:s');

        $hasil = [
            'mode'          => 'tanpa_jadwal',
            'ada_jadwal'    => false,
            'is_kegiatan'   => false,
            'nama_kegiatan' => null,
            'jam_kerja'     => null,
            'waktu_datang'  => '--:--',
            'status_datang' => '-',
            'waktu_pulang'  => '--:--',
            'status_pulang' => '-',
            'is_blinking'   => false,
            'scan_masuk'    => null,
            'scan_pulang'   => null,
            'jumlah_scan'   => 0,
            'keterangan'    => null,
        ];

        if (!$id_user) {
            return $hasil;
        }

        $stmt = $this->db->prepare("SELECT pin, id_jabatan FROM employe WHERE id_employe = ? LIMIT 1");
        $stmt->execute([$id_user]);
        $pegawai = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$pegawai || empty($pegawai['pin'])) {
            return $hasil;
        }

        $pin       = $pegawai['pin'];
        $idJabatan = (int) ($pegawai['id_jabatan'] ?? 0);

        // Ringkasan scan finger hari ini (dipakai semua mode)
        $stmt = $this->db->prepare("
            SELECT MIN(TIME(scan_date)) AS masuk,
                   MAX(TIME(scan_date)) AS pulang,
                   COUNT(*) AS jumlah,
                   SUBSTRING_INDEX(GROUP_CONCAT(LOWER(keterangan) ORDER BY scan_date ASC SEPARATOR ','), ',', 1) AS keterangan_awal
            FROM attendance
            WHERE pin = ? AND DATE(scan_date) = ?
        ");
        $stmt->execute([$pin, $today]);
        $scan = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $scanMasuk  = !empty($scan['masuk']) ? substr($scan['masuk'], 0, 5) : null;
        $scanPulang = !empty($scan['pulang']) ? substr($scan['pulang'], 0, 5) : null;
        if ($scanMasuk !== null && $scanMasuk === $scanPulang) {
            $scanPulang = null; // baru satu kali scan
        }

        $hasil['scan_masuk']  = $scanMasuk;
        $hasil['scan_pulang'] = $scanPulang;
        $hasil['jumlah_scan'] = (int) ($scan['jumlah'] ?? 0);

        $presensiModel = new Presensi($this->db);
        $libur = $presensiModel->cekLibur($today);

        // 1. Kegiatan lembaga hari ini (menang atas jadwal harian)
        $stmt = $this->db->prepare("
            SELECT k.nama_kegiatan, k.jam_mulai, k.jam_selesai
            FROM kegiatan k
            JOIN kegiatan_peserta kp ON k.id_kegiatan = kp.id_kegiatan
            WHERE k.tanggal = ? AND kp.pin_guru = ?
            ORDER BY k.jam_mulai ASC
            LIMIT 1
        ");
        $stmt->execute([$today, $pin]);
        $kegiatan = $stmt->fetch(PDO::FETCH_ASSOC);

        // 2. Jadwal kerja (tabel jadwal, day berbahasa Inggris)
        $jadwal = $presensiModel->getRingkasanJadwal($pin, $hariEnglish);

        $jamMulai   = null;
        $jamSelesai = null;

        if ($kegiatan) {
            $hasil['mode']          = 'kegiatan';
            $hasil['ada_jadwal']    = true;
            $hasil['is_kegiatan']   = true;
            $hasil['nama_kegiatan'] = $kegiatan['nama_kegiatan'];
            $jamMulai   = $kegiatan['jam_mulai'];
            $jamSelesai = $kegiatan['jam_selesai'];
        } elseif ($jadwal) {
            $jamMulai   = $jadwal['jam_masuk'];
            $jamSelesai = $jadwal['jam_pulang'];
            $hasil['mode']       = 'jadwal';
            $hasil['ada_jadwal'] = true;
        }

        // Tidak ada jadwal & tidak terploting kegiatan lembaga
        if (!$kegiatan && !$jadwal) {
            if ($libur && $scanMasuk === null && $idJabatan !== 3) {
                $hasil['mode']       = 'libur';
                $hasil['keterangan'] = 'Libur ' . trim((string) ($libur['keterangan'] ?? ''));
            } else {
                $hasil['mode']       = 'tanpa_jadwal';
                $hasil['keterangan'] = 'Tidak ada jadwal';
            }
            return $hasil;
        }

        $hasil['jam_kerja'] = substr($jamMulai, 0, 5) . ' - ' . substr($jamSelesai, 0, 5);

        // Libur nasional (satpam tetap wajib masuk)
        if ($libur && $scanMasuk === null && $idJabatan !== 3 && !$kegiatan) {
            $hasil['mode']       = 'libur';
            $hasil['keterangan'] = 'Libur ' . trim((string) ($libur['keterangan'] ?? ''));
            return $hasil;
        }

        // Scan dengan keterangan selain "hadir" (izin/sakit/cuti)
        $keteranganAbsen = strtolower(trim((string) ($scan['keterangan_awal'] ?? '')));
        if ($keteranganAbsen !== '' && $keteranganAbsen !== 'hadir') {
            $hasil['status_datang'] = ucwords($keteranganAbsen);
            $hasil['waktu_datang']  = '--:--';
            $hasil['status_pulang'] = '-';
            $hasil['waktu_pulang']  = '--:--';
            return $hasil;
        }

        // Datang
        if ($scanMasuk === null) {
            $hasil['status_datang'] = ($jamSekarang > $jamSelesai) ? 'Alpa' : 'Belum Absen';
            $hasil['waktu_datang']  = '--:--';
        } else {
            $hasil['waktu_datang']  = $scanMasuk;
            $hasil['status_datang'] = ($scan['masuk'] > $jamMulai) ? 'Terlambat' : 'Tepat Waktu';
        }

        // Pulang
        if ($scanPulang === null) {
            if ($hasil['status_datang'] === 'Alpa') {
                $hasil['status_pulang'] = 'Alpa';
                $hasil['waktu_pulang']  = '--:--';
            } else {
                $hasil['status_pulang'] = 'Belum Absen';
                $hasil['waktu_pulang']  = 'Belum Absen';
                $hasil['is_blinking']   = ($scanMasuk !== null);
            }
        } else {
            $hasil['waktu_pulang']  = $scanPulang;
            $hasil['status_pulang'] = ($scan['pulang'] < $jamSelesai) ? 'Pulang Cepat' : 'Sesuai';
        }

        return $hasil;
    }

    /** Daftar jam mengajar hari ini beserta status jurnalnya (kosong = tidak ada jam mengajar). */
    private function rekapJurnalMengajarHariIni($id_guru, $id_tahun)
    {
        if (!$id_guru || !$id_tahun) {
            return [];
        }

        $model = new Jurnal($this->db);
        $baris = $model->getJadwalHariIniStatus($id_guru, $id_tahun, $this->namaHariIndo(date('l')));
        $jamSekarang = date('H:i:s');

        foreach ($baris as &$b) {
            if (!empty($b['id_jurnal'])) {
                $b['status'] = 'Sudah';
            } elseif ($jamSekarang >= $b['jam_mulai']) {
                $b['status'] = 'Belum';
            } else {
                $b['status'] = 'Belum waktunya';
            }
            $b['jam'] = substr($b['jam_mulai'], 0, 5) . ' - ' . substr($b['jam_selesai'], 0, 5);
        }
        unset($b);

        return $baris;
    }

    /** Status jurnal struktural hari ini. Hanya untuk Tenaga Kependidikan (employe.id_jabatan = 2). */
    private function rekapJurnalStrukturalHariIni($id_user, $id_tahun)
    {
        if (!$id_user) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT pin, id_jabatan FROM employe WHERE id_employe = ? LIMIT 1");
        $stmt->execute([$id_user]);
        $pegawai = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pegawai || (int) ($pegawai['id_jabatan'] ?? 0) !== 2) {
            return null;
        }

        date_default_timezone_set('Asia/Jakarta');

        $presensiModel = new Presensi($this->db);
        $jadwal = $presensiModel->getRingkasanJadwal($pegawai['pin'], date('l'));

        $model  = new JurnalStrukturalModel($this->db);
        $hariIni = $model->getStatusHariIni($id_user, date('Y-m-d'), $id_tahun);

        return [
            'jadwal' => $jadwal ? [
                'jam_masuk'  => substr($jadwal['jam_masuk'], 0, 5),
                'jam_pulang' => substr($jadwal['jam_pulang'], 0, 5),
            ] : null,
            'sudah'          => (bool) $hariIni,
            'jam_isi'        => $hariIni && !empty($hariIni['jam_isi']) ? substr($hariIni['jam_isi'], 0, 5) : null,
            'catatan'        => trim((string) ($hariIni['catatan_akhir'] ?? '')),
            'jumlah_program' => (int) ($hariIni['jumlah_program'] ?? 0),
        ];
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
