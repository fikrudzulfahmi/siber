<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class AdminRaporController extends BaseController
{
    protected $db; // Mengikuti BaseController
    private $raporModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo); // ⛑️ Menjalankan authGuard dari BaseController
        $this->db = $pdo;
        require_once __DIR__ . '/../models/Rapor.php';
        $this->raporModel = new Rapor($pdo);

        // Tambahan Proteksi: Hanya level 1 (Admin) yang boleh masuk
        if (!isAnyLevel($_SESSION['user']['level'], [1])) {
            setFlash('danger', 'Anda tidak memiliki akses ke halaman ini.');
            header("Location: index.php?controller=dashboard&method=index");
            exit;
        }
    }

    // Alias untuk menangani default method router Anda
    public function login()
    {
        $this->index();
    }

    public function index()
    {
        // Ambil data tahun pelajaran untuk dropdown
        $tahun_pelajaran = $this->db->query("SELECT * FROM tahun_pelajaran ORDER BY id_tahun_pelajaran DESC")->fetchAll(PDO::FETCH_ASSOC);
        $settings = $this->raporModel->getAllSettings();

        require __DIR__ . '/../views/admin/rapor/rapor_setting.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $data = [
                'id_tahun_pelajaran' => $_POST['id_tahun'],
                'jenis_rapor' => $_POST['jenis_rapor'],
                'tgl_pembagian' => $_POST['tgl_pembagian'],
                'is_kenaikan' => isset($_POST['is_kenaikan']) ? 1 : 0
            ];

            $this->raporModel->createSetting($data);
            setFlash('success', 'Setting rapor berhasil ditambahkan.');

            // Perbaikan: Redirect konsisten dengan method=index dan huruf kecil
            header("Location: index.php?controller=adminRapor&method=index");
        }
    }

    // Menampilkan halaman yang sama tetapi melempar data edit_setting
    public function edit()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];

            // Asumsi nama method fetch data Anda di model sama dengan yang di method index()
            $tahun_pelajaran = $this->db->query("SELECT * FROM tahun_pelajaran ORDER BY id_tahun_pelajaran DESC")->fetchAll(PDO::FETCH_ASSOC);
            $settings = $this->raporModel->getAllSettings();

            // Ambil data 1 setting spesifik yang mau diedit
            $edit_setting = $this->raporModel->getSettingById($id);

            // Load file view yang sama seperti method index
            require '../app/views/admin/rapor/rapor_setting.php'; // (Sesuaikan path view Anda)
        }
    }

    // Mengeksekusi perubahan dari database
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $id = $_POST['id_rapor'];
            $data = [
                'id_tahun_pelajaran' => $_POST['id_tahun'],
                'jenis_rapor' => $_POST['jenis_rapor'],
                'tgl_pembagian' => $_POST['tgl_pembagian'],
                'is_kenaikan' => isset($_POST['is_kenaikan']) ? 1 : 0
            ];

            $this->raporModel->updateSetting($id, $data);
            setFlash('success', 'Setting rapor berhasil diperbarui.');

            header("Location: index.php?controller=adminRapor&method=index");
            exit;
        }
    }

    public function activate()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $this->raporModel->activateSetting($id);
            setFlash('success', 'Periode rapor berhasil diaktifkan.');
        }
        header("Location: index.php?controller=adminRapor&method=index");
    }

    public function lock()
    {
        if (isset($_GET['id']) && isset($_GET['status'])) {
            $id = $_GET['id'];
            $status = $_GET['status']; // 1 untuk lock, 0 untuk unlock
            $this->raporModel->toggleLock($id, $status);

            $pesan = ($status == 1) ? 'Input rapor berhasil dikunci.' : 'Input rapor berhasil dibuka.';
            setFlash('success', $pesan);
        }
        header("Location: index.php?controller=adminRapor&method=index");
    }
}
