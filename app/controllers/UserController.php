<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class UserController extends BaseController
{
    private $model;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->model = new User($this->db);
    }

    // =================== USER ===================
    public function index()
    {
        $users = $this->model->getAll();
        require __DIR__ . '/../views/admin/user/index.php';
    }

    public function create()
    {
        require __DIR__ . '/../views/admin/user/create.php';
    }

    public function store()
    {
        $this->model->insert($_POST);
        setFlash('success', 'User berhasil ditambahkan.');
        header('Location: index.php?controller=user&method=index');
    }

    public function edit() // <-- Hapus parameter $id dari sini
    {
        // Ambil ID dari URL menggunakan $_GET
        $id = $_GET['id'] ?? null;
        if (!$id) {
            die('Error: ID User tidak ditemukan di URL.');
        }

        // 1. Ambil data user, jadwal, dan jabatan (seperti kode Anda sebelumnya)
        $user = $this->model->find($id);
        $jadwal = $this->model->getJadwal($user['pin']);
        $jabatans = $this->model->getAllJabatan();

        // 2. Ambil SEMUA level yang tersedia di sistem
        $levelStmt = $this->db->query("SELECT * FROM level ORDER BY nama_level ASC");
        $allLevels = $levelStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Ambil level yang saat ini DIMILIKI oleh user tersebut
        $userLevelStmt = $this->db->prepare("SELECT id_level FROM user_level WHERE id_employe = ?");
        $userLevelStmt->execute([$id]);
        $userOwnedLevels = $userLevelStmt->fetchAll(PDO::FETCH_COLUMN);

        // 4. Load view (menggunakan 'require' seperti cara Anda)
        require __DIR__ . '/../views/admin/user/edit.php';
    }

    public function update()
    {
        $id_employe = $_POST['id'];
        $selectedLevels = $_POST['levels'] ?? []; // Ambil level dari form, default array kosong

        // 1. Update data dasar di tabel 'employe'
        $this->model->update($_POST);

        // 2. Hapus semua level LAMA milik user ini
        $deleteStmt = $this->db->prepare("DELETE FROM user_level WHERE id_employe = ?");
        $deleteStmt->execute([$id_employe]);

        // 3. Masukkan semua level BARU yang dipilih
        if (!empty($selectedLevels)) {
            $insertStmt = $this->db->prepare("INSERT INTO user_level (id_employe, id_level) VALUES (?, ?)");
            foreach ($selectedLevels as $id_level) {
                $insertStmt->execute([$id_employe, $id_level]);
            }
        }

        setFlash('success', 'User berhasil diperbarui.');
        header('Location: index.php?controller=user&method=edit&id=' . $id_employe);
        exit;
    }

    public function delete()
    {
        $this->model->delete($_GET['id']);
        setFlash('success', 'User berhasil dihapus.');
        header('Location: index.php?controller=user&method=index');
    }

    // =================== JADWAL ===================
    public function addJadwal()
    {
        if ($this->model->addJadwal($_POST)) {
            setFlash('success', 'Jadwal berhasil ditambahkan.');
        } else {
            setFlash('danger', 'Gagal menambahkan jadwal.');
        }
        header('Location: index.php?controller=user&method=edit&id=' . $_POST['id_employe']);
        exit; // Selalu gunakan exit setelah header location
    }

    public function updateJadwal()
    {
        $this->model->updateJadwal($_POST);
        setFlash('success', 'Jadwal berhasil diperbarui.');
        header('Location: index.php?controller=user&method=edit&id=' . $_POST['id_employe']);
    }

    public function deleteJadwal()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->deleteJadwal($_POST['id']);
            setFlash('success', 'Jadwal berhasil dihapus.');
            header('Location: index.php?controller=user&method=edit&id=' . $_POST['id_user']);
        } else {
            // Jika bukan POST, redirect atau tampilkan error
            header('Location: index.php?controller=user&method=index');
        }
    }
}
