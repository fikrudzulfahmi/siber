<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';
require_once __DIR__ . '/../models/Jurnal.php';
require_once __DIR__ . '/../models/TahunPelajaran.php';

class JurnalController
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }

    // HELPER: Ambil ID Tahun Pelajaran Aktif
    private function getActiveYearId()
    {
        $stmt = $this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
        $tahun = $stmt->fetch();
        return $tahun ? $tahun['id_tahun_pelajaran'] : null;
    }

    public function index()
    {
        $id_user = $_SESSION['user']['id'];
        $model = new Jurnal($this->db);

        // Ambil Tahun Aktif
        $id_tahun = $this->getActiveYearId();

        // Ambil data untuk form awal
        $kelas = $model->getKelasByGuru($id_user, $id_tahun);

        require __DIR__ . '/../views/admin/jurnal/index.php';
    }

    // --- AJAX HANDLERS (Semua menggunakan filter Tahun) ---

    public function ajaxGetMapel()
    {
        header('Content-Type: application/json');
        $id_user = $_SESSION['user']['id'];
        $id_kelas = $_GET['id_kelas'] ?? 0;
        $id_tahun = $this->getActiveYearId();

        $model = new Jurnal($this->db);
        $data = $model->getMapelByGuruKelasTahun($id_user, $id_kelas, $id_tahun);
        echo json_encode($data);
        exit;
    }

    public function ajaxGetSiswa()
    {
        // Pastikan tidak ada output HTML lain yang mengganggu JSON
        ob_clean();
        header('Content-Type: application/json');

        try {
            $id_kelas = $_GET['id_kelas'] ?? 0;

            // Cek 1: Apakah ID Kelas terkirim?
            if (empty($id_kelas)) {
                throw new Exception("ID Kelas tidak diterima.");
            }

            // Cek 2: Ambil Tahun Aktif
            $id_tahun = $this->getActiveYearId();
            if (empty($id_tahun)) {
                throw new Exception("Tahun Pelajaran Aktif belum diset di menu Pengaturan/Tahun.");
            }

            $model = new Jurnal($this->db);

            // Cek 3: Eksekusi Query
            $data = $model->getSiswaByKelas($id_kelas, $id_tahun);

            echo json_encode($data);
        } catch (Exception $e) {
            // Jika ada error, kirim sebagai JSON agar bisa dibaca Javascript
            echo json_encode(['error' => true, 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function ajaxGetTp()
    {
        header('Content-Type: application/json');
        $id_mapel_guru = $_GET['id_mapel_guru'] ?? 0;
        $id_tahun = $this->getActiveYearId(); // Ambil tahun aktif

        $model = new Jurnal($this->db);
        // [UPDATE] Kirim id_tahun ke model TP
        $data = $model->getTPByMapelGuru($id_mapel_guru, $id_tahun);
        echo json_encode($data);
        exit;
    }
    // --- END AJAX ---

    public function simpan()
    {
        $model = new Jurnal($this->db);
        $id_kelas = $_POST['id_kelas'];
        $id_mapel_guru = $_POST['id_mapel_guru'];

        if ($model->cekJurnalHariIni($id_kelas, $id_mapel_guru)) {
            setFlash('warning', 'Jurnal untuk mapel ini di kelas ini sudah diisi hari ini.');
            header('Location: ?controller=jurnal&method=index');
            return;
        }

        $tpArray = $_POST['tujuan_pembelajaran'] ?? [];

        foreach ($tpArray as $id_tp) {
            if (empty($id_tp)) continue;
            $data = [
                'id_kelas' => $id_kelas,
                'id_mapel_guru' => $id_mapel_guru,
                'id_tp' => $id_tp,
                'materi' => $_POST['materi'],
                'jam_mulai' => $_POST['jam_mulai'],
                'jam_akhir' => $_POST['jam_akhir'],
                'catatan_kehadiran' => $_POST['catatan_kehadiran'],
                'catatan_pembelajaran' => $_POST['catatan_pembelajaran']
            ];
            $id_jurnal = $model->simpanJurnal($data);

            if (isset($_POST['kehadiran'])) {
                $model->simpanKehadiran($id_jurnal, $_POST['kehadiran']);
            }
        }
        setFlash('success', 'Jurnal berhasil disimpan');
        header('Location: ?controller=jurnal&method=index');
    }

    public function history()
    {
        // 1. Cek Login (PENTING: Tambahkan validasi session)
        if (!isset($_SESSION['user'])) {
            // Redirect ke login jika session tidak ada
            header("Location: ?controller=auth&method=login");
            exit;
        }

        $id_guru = $_SESSION['user']['id'] ?? 0;

        // 2. Ambil ID Tahun Pelajaran Aktif
        $id_tahun = $this->getActiveYearId();

        // 3. Load Model Tahun Pelajaran (Manual Require agar aman)
        $tahunModel = new TahunPelajaran($this->db);

        $tahunInfo = $tahunModel->getTahunById($id_tahun);
        $nama_tahun = $tahunInfo ? $tahunInfo['tahun_pelajaran'] . ' (' . $tahunInfo['semester'] . ')' : '-';

        // 4. Panggil Model Jurnal
        $jurnalModel = new Jurnal($this->db);

        // Simpan ke array $data
        $data['jurnalList'] = $jurnalModel->getRiwayatJurnal($id_guru, $id_tahun);
        $data['tahun_aktif'] = $nama_tahun;




        // --- PERBAIKAN DISINI ---
        // Pecah array $data menjadi variabel individu ($jurnalList, $tahun_aktif)
        extract($data);

        // 5. Load View
        require __DIR__ . '/../views/admin/jurnal/history.php';
    }

    public function edit()
    {
        // 1. Cek Login
        if (!isset($_SESSION['user'])) {
            header("Location: ?controller=auth&method=login");
            exit;
        }

        $id = $_GET['id'] ?? null;
        if (!$id) {
            header('Location: ?controller=jurnal&method=history');
            exit;
        }

        $model = new Jurnal($this->db);
        $jurnal = $model->findById($id);

        if (!$jurnal) {
            header('Location: ?controller=jurnal&method=history');
            exit;
        }

        // 2. Ambil Tahun Aktif
        $id_tahun = $this->getActiveYearId();

        // 3. Ambil Data Master untuk Dropdown
        // A. List Kelas (Semua kelas milik guru ini)
        $kelas = $model->getKelasByGuru($_SESSION['user']['id'], $id_tahun);

        // B. List Mapel (Spesifik untuk Kelas yang sedang diedit)
        $mapelList = $model->getMapelByGuruKelasTahun($_SESSION['user']['id'], $jurnal['id_kelas'], $id_tahun);

        // C. List TP (Spesifik untuk Mapel yang sedang diedit)
        $tpList = $model->getTPByMapelGuru($jurnal['id_mapel_guru'], $id_tahun);

        // D. List Siswa (Spesifik untuk Kelas yang sedang diedit)
        $siswaList = $model->getSiswaByKelas($jurnal['id_kelas'], $id_tahun);

        // 4. Load View
        require __DIR__ . '/../views/admin/jurnal/edit.php';
    }

    public function update()
    {
        $data = $_POST;
        $model = new Jurnal($this->db);
        $model->update($data);
        setFlash('success', 'Jurnal berhasil diperbarui.');
        header('Location: ?controller=jurnal&method=history');
    }

    /**
     * Menampilkan halaman Rekap Jurnal Harian
     */
    public function rekap()
    {
        // 1. Cek Login
        if (!isset($_SESSION['user'])) {
            header("Location: ?controller=auth&method=login");
            exit;
        }

        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');

        // 2. Instansiasi Model Jurnal
        $jurnalModel = new Jurnal($this->db);

        // 3. Ambil Tahun Pelajaran Aktif (Sesuai kode lama Anda)
        $stmt = $this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
        $tahun = $stmt->fetch();
        $id_tahun = $tahun ? $tahun['id_tahun_pelajaran'] : 0;

        // 4. Ambil Data Rekap (Panggil method lama yang sudah terbukti benar)
        $rekapData = $jurnalModel->getRekapSemuaGuruHariIni($tanggal, $id_tahun);

        // 5. Kirim ke View
        $data = [
            'title'   => 'Rekap Jurnal Pembelajaran',
            'tanggal' => $tanggal,
            'rekap'   => $rekapData
        ];

        extract($data);
        require __DIR__ . '/../views/admin/jurnal/rekap.php';
    }

    /**
     * Method AJAX untuk Popup Detail
     */
    public function getDetail()
    {
        // Bersihkan output buffer untuk mencegah karakter liar merusak JSON
        if (ob_get_length()) ob_clean();

        if (!isset($_GET['id']) || empty($_GET['id'])) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'ID Jurnal tidak valid']);
            exit;
        }

        $id_jurnal = $_GET['id'];
        $jurnalModel = new Jurnal($this->db);

        try {
            $jurnal = $jurnalModel->getJurnalById($id_jurnal);
            $kehadiran = $jurnalModel->getAbsensiByJurnal($id_jurnal);

            if (!$jurnal) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Data jurnal tidak ditemukan di database']);
                exit;
            }

            header('Content-Type: application/json');
            echo json_encode([
                'status'    => 'success',
                'jurnal'    => $jurnal,
                'kehadiran' => $kehadiran
            ]);
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Server Error: ' . $e->getMessage()]);
        }
        exit;
    }

    public function hapus()
    {
        $model = new Jurnal($this->db);
        $model->delete($_GET['id']);
        setFlash('success', 'Jurnal berhasil dihapus.');
        header('Location: index.php?controller=jurnal&method=history');
    }

    public function setting()
    {
        // 1. Cek Login (Opsional tapi disarankan)
        if (!isset($_SESSION['user'])) {
            header("Location: ?controller=auth&method=login");
            exit;
        }

        // 2. Inisialisasi Model
        $model = new Jurnal($this->db);

        // 3. Ambil status string dari DB ('true'/'false')
        $wa_status = $model->getSetting('wa_notif_jurnal');

        require __DIR__ . '/../views/admin/jurnal/setting.php';
    }

    public function updateWASetting()
    {
        // Bersihkan buffer agar tidak ada output lain yang mengganggu JSON
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            // 1. Inisialisasi Model
            $model = new Jurnal($this->db);

            // 2. Menerima data JSON dari Fetch API
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            // 3. Konversi boolean true/false menjadi string 'true'/'false'
            $status_string = (isset($data['status']) && $data['status'] === true) ? 'true' : 'false';

            // 4. Update ke Database
            $result = $model->updateSetting('wa_notif_jurnal', $status_string);

            echo json_encode([
                'success' => $result,
                'db_value' => $status_string
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}
