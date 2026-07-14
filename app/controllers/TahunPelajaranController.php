<?php
require_once __DIR__ . '/../models/TahunPelajaran.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class TahunPelajaranController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo); // ⛑️ authGuard aktif di BaseController
    }

    public function index()
    {
        $model = new TahunPelajaran($this->db);
        $tahun = $model->getAll();
        require __DIR__ . '/../views/admin/tahun_pelajaran/index.php';
    }

    public function activate()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];

            // Buat instance model di sini, sama seperti method lainnya
            $model = new TahunPelajaran($this->db);

            // Langkah 1: Gunakan variabel $model (bukan $this->model)
            $model->deactivateAll();

            // Langkah 2: Gunakan variabel $model (bukan $this->model)
            $model->activateById($id);

            // Set pesan sukses dan redirect kembali ke halaman utama
            setFlash('success', 'Status tahun pelajaran berhasil diubah.');
            // Redirect ke method index (lebih konsisten dengan method lain)
            header('Location: index.php?controller=tahunPelajaran&method=index');
            exit;
        } else {
            // Handle jika tidak ada ID
            setFlash('error', 'ID tidak ditemukan.');
            header('Location: index.php?controller=tahunPelajaran&method=index');
            exit;
        }
    }

    public function create()
    {
        require __DIR__ . '/../views/admin/tahun_pelajaran/create.php';
    }

    public function store()
    {
        $model = new TahunPelajaran($this->db);
        $model->insert($_POST);
        setFlash('success', 'Tahun pelajaran berhasil ditambahkan.');
        header('Location: index.php?controller=tahunPelajaran&method=index');
    }

    public function edit()
    {
        $model = new TahunPelajaran($this->db);
        $tahun = $model->find($_GET['id']);
        require __DIR__ . '/../views/admin/tahun_pelajaran/edit.php';
    }

    public function update()
    {
        $model = new TahunPelajaran($this->db);
        $model->update($_POST);
        setFlash('success', 'Tahun pelajaran berhasil diperbarui.');
        header('Location: index.php?controller=tahunPelajaran&method=index');
    }

    public function delete()
    {
        $model = new TahunPelajaran($this->db);
        $model->delete($_GET['id']);
        setFlash('success', 'Tahun pelajaran berhasil dihapus.');
        header('Location: index.php?controller=tahunPelajaran&method=index');
    }
}
