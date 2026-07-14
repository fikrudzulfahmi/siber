<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once __DIR__ . '/../vendor/autoload.php'; // DomPDF
require_once __DIR__ . '/../vendor2/autoload.php'; // phpoffice
use Dompdf\Dompdf;
use Dompdf\Options;

class RaporController extends BaseController
{
    protected $db;
    private $raporModel;
    private $kelasModel;


    public function __construct($pdo)
    {
        $this->db = $pdo;
        // Inisialisasi Model
        require_once '../app/models/Rapor.php';
        require_once '../app/models/Kelas.php';
        $this->raporModel = new Rapor($pdo);
        $this->kelasModel = new Kelas($pdo);
    }

    public function index()
    {
        $id_employe = $_SESSION['user']['id'];
        $setting = $this->raporModel->getActiveSetting();

        if (!$setting) {
            setFlash('danger', 'Admin belum mengaktifkan periode rapor.');
            header("Location: ?controller=dashboard");
            exit;
        }

        $stmt = $this->db->prepare("SELECT * FROM kelas WHERE wali_kelas = ?");
        $stmt->execute([$id_employe]);
        $kelas = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$kelas) {
            setFlash('danger', 'Hanya Walikelas yang dapat mengakses menu ini.');
            header("Location: ?controller=dashboard");
            exit;
        }

        // AMBIL PROGRES NILAI
        $progresData = $this->raporModel->getProgresNilaiSiswa($kelas['id_kelas'], $setting['id_tahun_pelajaran'], $setting['jenis_rapor']);
        $progresMap = [];
        foreach ($progresData as $p) {
            $progresMap[$p['id_siswa']] = ['terisi' => $p['mapel_terisi'], 'total' => $p['total_mapel']];
        }

        // QUERY SISWA
        $sql = "SELECT s.id_siswa, s.nama_siswa, s.nisn, rs.id_rapor_siswa,
               rs.sakit, rs.izin, rs.alfa, rs.catatan_walikelas,
               -- SUBQUERY DIPERBARUI: Nama tabel diganti menjadi ekstra_nilai
               (SELECT COUNT(*) 
                FROM ekstra_nilai en 
                WHERE en.id_siswa = s.id_siswa 
                  AND en.id_rapor = ? 
                  AND en.nilai IS NOT NULL 
                  AND en.nilai != '') as count_ekstra,
               (SELECT COUNT(*) FROM rapor_prestasi WHERE id_rapor_siswa = rs.id_rapor_siswa) as count_prestasi
        FROM ploting_siswa p 
        JOIN siswa s ON p.id_siswa = s.id_siswa 
        LEFT JOIN rapor_siswa rs ON s.id_siswa = rs.id_siswa AND rs.id_rapor = ?
        WHERE p.id_kelas = ? AND p.id_tahun = ?
        ORDER BY s.nama_siswa ASC";

        $stmt = $this->db->prepare($sql);

        // Urutan parameter tetap dipertahankan seperti sebelumnya
        $stmt->execute([
            $setting['id_rapor'],          // 1. Untuk en.id_rapor = ? (di subquery ekstra)
            $setting['id_rapor'],          // 2. Untuk rs.id_rapor = ? (di LEFT JOIN rapor_siswa)
            $kelas['id_kelas'],            // 3. Untuk p.id_kelas = ?
            $setting['id_tahun_pelajaran'] // 4. Untuk p.id_tahun = ?
        ]);

        $siswaRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $siswaList = [];
        foreach ($siswaRaw as $s) {
            $s['has_absensi'] = (!is_null($s['sakit']) || !is_null($s['izin']) || !is_null($s['alfa']));
            $s['has_ekstra'] = ($s['count_ekstra'] > 0);
            $s['has_prestasi'] = ($s['count_prestasi'] > 0);
            $s['has_catatan'] = (!empty($s['catatan_walikelas']));
            $s['progres'] = $progresMap[$s['id_siswa']] ?? ['terisi' => 0, 'total' => 0];
            $siswaList[] = $s;
        }

        include '../app/views/admin/rapor/input.php';
    }

    public function detail_progres()
    {
        // Bersihkan buffer output agar JSON tidak rusak oleh spasi / warning PHP
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            if (!isset($_GET['id_siswa'])) {
                echo json_encode(['error' => 'ID Siswa tidak ditemukan pada request.']);
                exit;
            }

            $id_siswa = $_GET['id_siswa'];
            $setting = $this->raporModel->getActiveSetting();

            if (!$setting) {
                echo json_encode(['error' => 'Periode rapor aktif belum disetting oleh admin.']);
                exit;
            }

            // Ambil data kelas siswa
            $stmt = $this->db->prepare("SELECT id_kelas FROM ploting_siswa WHERE id_siswa = ? AND id_tahun = ?");
            $stmt->execute([$id_siswa, $setting['id_tahun_pelajaran']]);
            $kelas = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$kelas) {
                echo json_encode([]); // Kirim array kosong jika ploting kelas tidak ada
                exit;
            }

            // Ambil detail progres nilai
            $detail = $this->raporModel->getDetailProgresSiswa(
                $id_siswa,
                $kelas['id_kelas'],
                $setting['id_tahun_pelajaran'],
                $setting['jenis_rapor']
            );

            echo json_encode($detail ? $detail : []);
        } catch (Exception $e) {
            echo json_encode(['error' => 'Terjadi kesalahan internal: ' . $e->getMessage()]);
        }
        exit;
    }

    public function edit_rapor()
    {
        $id_siswa = $_GET['id_siswa'];
        $setting = $this->raporModel->getActiveSetting();

        // 1. Ambil Data Rapor Utama
        $rapor = $this->raporModel->getRaporSiswa($setting['id_rapor'], $id_siswa);
        if (!$rapor) {
            $this->raporModel->initRaporSiswa($setting['id_rapor'], $id_siswa);
            $rapor = $this->raporModel->getRaporSiswa($setting['id_rapor'], $id_siswa);
        }
        $id_rapor_siswa = $rapor['id_rapor_siswa'];

        // =========================================================================
        // 🌟 TARIK DATA REKAP DARI MODEL & SET DEFAULT FALLBACK
        // =========================================================================
        $rekapKehadiran = $this->raporModel->getRekapKehadiranSemester($id_siswa, $setting['id_tahun_pelajaran']);

        // Cek apakah rapor ini masih bawaan default (0,0,0 dan catatan kosong)
        $belum_diedit_manual = (
            empty($rapor['sakit']) &&
            empty($rapor['izin']) &&
            empty($rapor['alfa'])
        );

        if ($belum_diedit_manual) {
            // Gunakan hitungan dari jurnal otomatis
            $rapor['sakit'] = $rekapKehadiran['total_sakit'] ?? 0;
            $rapor['izin']  = $rekapKehadiran['total_izin'] ?? 0;
            $rapor['alfa']  = $rekapKehadiran['total_alfa'] ?? 0;
        }
        // =========================================================================
        $id_rapor_aktif = $setting['id_rapor'];

        // 2. LOGIKA SINKRONISASI EKSTRA
        $ekstra = $this->raporModel->getEkstraOtomatisSiswa($id_siswa, $id_rapor_aktif);

        // 3. LOGIKA SINKRONISASI PRESTASI
        $prestasi_manual = $this->raporModel->getPrestasiSiswa($id_rapor_siswa);
        $prestasi_otomatis = $this->raporModel->getPrestasiKolektifSiswa($id_siswa, $setting['id_tahun_pelajaran']);
        $prestasi = !empty($prestasi_manual) ? $prestasi_manual : $prestasi_otomatis;

        // 4. Data Siswa untuk Header
        $stmt = $this->db->prepare("SELECT nama_siswa, nisn FROM siswa WHERE id_siswa = ?");
        $stmt->execute([$id_siswa]);
        $dataSiswa = $stmt->fetch(PDO::FETCH_ASSOC);

        include '../app/views/admin/rapor/form_terpadu.php';
    }

    public function update_terpadu()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Mengambil ID Rapor Siswa dari form (Pastikan nilainya bukan 0)
            $id_rapor_siswa = $_POST['id_rapor_siswa'];

            try {
                $this->db->beginTransaction();

                // 1. Update Tabel Utama (Presensi & Catatan)
                // Catatan: Gunakan nama kolom yang sesuai di tabel rapor_siswa (misal: 'catatan_wali')
                $sqlRapor = "UPDATE rapor_siswa SET sakit = ?, izin = ?, alfa = ?, catatan_walikelas = ? WHERE id_rapor_siswa = ?";
                $this->db->prepare($sqlRapor)->execute([
                    $_POST['sakit'],
                    $_POST['izin'],
                    $_POST['alfa'],
                    $_POST['catatan_walikelas'],
                    $id_rapor_siswa
                ]);

                // 2. Sync Ekstrakurikuler (Hapus lama, Insert baru)
                // Menggunakan kolom 'id_ekstra' sebagai Foreign Key sesuai error Anda
                // $this->db->prepare("DELETE FROM rapor_ekstra WHERE id_rapor_siswa = ?")->execute([$id_rapor_siswa]);
                // foreach ($_POST['ekstra'] as $e) {
                //     if (!empty(trim($e['nama']))) {
                //         $sqlEkstra = "INSERT INTO rapor_ekstra (id_rapor_siswa, nama_kegiatan, nilai, keterangan) VALUES (?, ?, ?, ?)";
                //         $this->db->prepare($sqlEkstra)->execute([
                //             $id_rapor_siswa,
                //             trim($e['nama']),
                //             trim($e['nilai']),
                //             trim($e['ket'])
                //         ]);
                //     }
                // }

                // 3. Sync Prestasi (Hapus lama, Insert baru)
                // Menggunakan kolom 'id_prestasi' sebagai Foreign Key sesuai error Anda
                $this->db->prepare("DELETE FROM rapor_prestasi WHERE id_rapor_siswa = ?")->execute([$id_rapor_siswa]);
                foreach ($_POST['prestasi'] as $p) {
                    if (!empty(trim($p['jenis']))) {
                        $sqlPrestasi = "INSERT INTO rapor_prestasi (id_rapor_siswa, jenis_prestasi, keterangan) VALUES (?, ?, ?)";
                        $this->db->prepare($sqlPrestasi)->execute([
                            $id_rapor_siswa,
                            trim($p['jenis']),
                            trim($p['ket'])
                        ]);
                    }
                }

                $this->db->commit();
                setFlash('success', 'Data Rapor Terpadu berhasil disimpan.');
                header("Location: index.php?controller=rapor&method=index");
                exit;
            } catch (Exception $e) {
                $this->db->rollBack();
                // Menampilkan error spesifik jika gagal lagi
                die("Kesalahan Database: " . $e->getMessage());
            }
        }
    }
    public function cetak()
    {
        // Hindari output error PHP masuk ke dalam PDF
        error_reporting(0);

        if (!isset($_GET['id_siswa'])) {
            die("ID Siswa tidak ditemukan.");
        }

        $id_siswa = $_GET['id_siswa'];
        $setting = $this->raporModel->getActiveSetting();
        if (!$setting) {
            die("Periode rapor aktif tidak ditemukan.");
        }

        $id_tahun = $setting['id_tahun_pelajaran'];
        $jenis_rapor = $setting['jenis_rapor'];
        $semester = $setting['semester'];

        // 1. Data Siswa
        $siswa = $this->db->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
        $siswa->execute([$id_siswa]);
        $dataSiswa = $siswa->fetch(PDO::FETCH_ASSOC);

        // 2. Data Kelas
        $stmtKelas = $this->db->prepare("SELECT k.* FROM kelas k 
                                     JOIN ploting_siswa ps ON k.id_kelas = ps.id_kelas 
                                     WHERE ps.id_siswa = ? AND ps.id_tahun = ?");
        $stmtKelas->execute([$id_siswa, $id_tahun]);
        $dataKelas = $stmtKelas->fetch(PDO::FETCH_ASSOC);
        $id_kelas = $dataKelas['id_kelas'];

        // 3. Data Rapor & Catatan
        $dataRaporSiswa = $this->raporModel->getRaporSiswa($setting['id_rapor'], $id_siswa);
        if ($dataRaporSiswa) {
            $dataRaporSiswa['catatan'] = $dataRaporSiswa['catatan_walikelas'];
        }

        // 4. Nilai & Peringkat
        $nilaiAkhir = $this->raporModel->getNilaiAkhirSiswa($id_siswa, $id_tahun, $jenis_rapor, $id_kelas);
        $peringkatData = $this->raporModel->getPeringkatKelas($id_kelas, $id_tahun, $jenis_rapor);

        $rankSiswa = '-';
        $totalSiswa = count($peringkatData);
        foreach ($peringkatData as $pd) {
            if ($pd['id_siswa'] == $id_siswa) {
                $rankSiswa = $pd['peringkat'];
                break;
            }
        }

        // 5. Ekstra & Prestasi
        $setting = $this->raporModel->getActiveSetting();
        $id_rapor_aktif = $setting['id_rapor'];

        // 2. Inisialisasi awal array kosong
        $dataEkstra = [];
        $dataPrestasi = [];

        if ($dataRaporSiswa) {
            $id_rapor_siswa = $dataRaporSiswa['id_rapor_siswa'];

            // Tarik data ekstra persis seperti di edit_rapor()
            $dataEkstra = $this->raporModel->getEkstraOtomatisSiswa($id_siswa, $id_rapor_aktif);

            // Tarik data prestasi menggabungkan manual & otomatis persis seperti di edit_rapor()
            $prestasi_manual = $this->raporModel->getPrestasiSiswa($id_rapor_siswa);
            $prestasi_otomatis = $this->raporModel->getPrestasiKolektifSiswa($id_siswa, $setting['id_tahun_pelajaran']);

            $dataPrestasi = !empty($prestasi_manual) ? $prestasi_manual : $prestasi_otomatis;
        }

        // 6. Profil Sekolah & Judul
        $tingkat = (int)$dataKelas['tingkat'];
        $isMTS = ($tingkat >= 7 && $tingkat <= 9);
        $sekolah = $isMTS
            ? ['nama' => 'MTS BUSTANUL ULUM', 'kamad' => 'IRSADA FITRI ZULKARNAIN, S.S.']
            : ['nama' => 'MA ROUDLOTUL MUTA\'ALLIMIN', 'kamad' => 'DEWI LUTFIYAH, S.Si.'];

        $judul = ($jenis_rapor == 'tengah') ? "RAPOR TENGAH SEMESTER $semester" : "RAPOR AKHIR SEMESTER $semester";

        // 7.5 Load Logo & Convert to Base64
        $logoPath = ($tingkat >= 7 && $tingkat <= 9)
            ? $_SERVER['DOCUMENT_ROOT'] . '/public/assets/img/logos/logo_mts.png'
            : $_SERVER['DOCUMENT_ROOT'] . '/public/assets/img/logos/logo_ma.png';

        $base64Logo = "";

        if (file_exists($logoPath)) {
            $data = file_get_contents($logoPath);
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);

            // Gunakan rawurlencode untuk memastikan tidak ada karakter aneh
            // Dan pastikan base64_encode tidak memiliki line break
            $base64Data = base64_encode($data);
            $base64Logo = 'data:image/' . $type . ';base64,' . $base64Data;
        } else {
            // Log error secara internal untuk debug jika file tidak ada
            error_log("Dompdf Error: Logo tidak ditemukan di path: " . $logoPath);
        }

        if ($tingkat >= 7 && $tingkat <= 9) {
            $fase = "D";
        } elseif ($tingkat == 10) {
            $fase = "E";
        } elseif ($tingkat >= 11 && $tingkat <= 12) {
            $fase = "F";
        } else {
            $fase = "-"; // Jika tingkat di bawah 7 (SD) atau tidak terdefinisi
        }

        // 8. Render Dompdf
        ob_start();
        include '../app/views/admin/rapor/pdf_template.php';
        $html = ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // BERSIHKAN BUFFER sebelum stream untuk mencegah "Failed to load PDF"
        if (ob_get_length()) ob_end_clean();

        $filename = "Rapor_" . str_replace(' ', '_', $dataSiswa['nama_siswa']) . ".pdf";
        $dompdf->stream($filename, ["Attachment" => false]);
        exit;
    }

    public function cetak_peringkat()
    {
        // Hindari output error PHP masuk ke dalam PDF
        error_reporting(0);

        if (!isset($_GET['id_kelas'])) {
            die("ID Kelas tidak ditemukan.");
        }

        $id_kelas = $_GET['id_kelas'];
        $setting = $this->raporModel->getActiveSetting();
        if (!$setting) {
            die("Periode rapor aktif tidak ditemukan.");
        }

        $id_tahun = $setting['id_tahun_pelajaran'];
        $jenis_rapor = $setting['jenis_rapor'];
        $semester = $setting['semester'];

        // 1. Data Kelas
        $stmtKelas = $this->db->prepare("SELECT * FROM kelas WHERE id_kelas = ?");
        $stmtKelas->execute([$id_kelas]);
        $dataKelas = $stmtKelas->fetch(PDO::FETCH_ASSOC);
        if (!$dataKelas) {
            die("Data kelas tidak ditemukan.");
        }

        // 2. Ambil Semua Data Peringkat Kelas (Langsung digunakan semua tanpa filter)
        $peringkatData = $this->raporModel->getPeringkatKelas($id_kelas, $id_tahun, $jenis_rapor);

        // 3. Profil Sekolah & Judul
        $tingkat = (int)$dataKelas['tingkat'];
        $isMTS = ($tingkat >= 7 && $tingkat <= 9);
        $sekolah = $isMTS
            ? ['nama' => 'MTS BUSTANUL ULUM', 'kamad' => 'IRSADA FITRI ZULKARNAIN, S.S.']
            : ['nama' => 'MA ROUDLOTUL MUTA\'ALLIMIN', 'kamad' => 'DEWI LUTFIYAH, S.Si.'];

        $judul = ($jenis_rapor == 'tengah') ? "PERINGKAT TENGAH SEMESTER $semester" : "PERINGKAT AKHIR SEMESTER $semester";

        // 4. Load Logo & Convert to Base64
        $logoPath = $isMTS
            ? $_SERVER['DOCUMENT_ROOT'] . '/public/assets/img/logos/logo_mts.png'
            : $_SERVER['DOCUMENT_ROOT'] . '/public/assets/img/logos/logo_ma.png';

        $base64Logo = "";
        if (file_exists($logoPath)) {
            $data = file_get_contents($logoPath);
            $type = pathinfo($logoPath, PATHINFO_EXTENSION);
            $base64Data = base64_encode($data);
            $base64Logo = 'data:image/' . $type . ';base64,' . $base64Data;
        } else {
            error_log("Dompdf Error: Logo tidak ditemukan di path: " . $logoPath);
        }

        // 5. Penentuan Fase
        if ($tingkat >= 7 && $tingkat <= 9) {
            $fase = "D";
        } elseif ($tingkat == 10) {
            $fase = "E";
        } elseif ($tingkat >= 11 && $tingkat <= 12) {
            $fase = "F";
        } else {
            $fase = "-";
        }

        // 6. Render Dompdf
        ob_start();
        include '../app/views/admin/rapor/pdf_peringkat_template.php';
        $html = ob_get_clean();

        $options = new \Dompdf\Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        if (ob_get_length()) ob_end_clean();

        $filename = "Daftar_Peringkat_Kelas_" . str_replace(' ', '_', $dataKelas['nama_kelas']) . ".pdf";
        $dompdf->stream($filename, ["Attachment" => false]);
        exit;
    }
}
