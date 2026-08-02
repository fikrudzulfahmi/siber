<?php
require_once __DIR__ . '/../models/JenisPerangkat.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class JenisPerangkatController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo);
        
        // Hanya Admin (1) yang boleh mengakses menu ini berdasarkan instruksi
        $id_level = $_SESSION['user']['level'];
        if (!isLevel($id_level, 1)) {
            setFlash('error', 'Anda tidak memiliki akses ke halaman ini.');
            header('Location: index.php?controller=dashboard&method=index');
            exit;
        }
    }

    public function index()
    {
        $model = new JenisPerangkat($this->db);
        $jenis_perangkat = $model->getAllAdmin(); // Tampilkan semua, termasuk yang nonaktif
        require __DIR__ . '/../views/admin/jenis_perangkat/index.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama = trim($_POST['nama']);
            
            if (empty($nama)) {
                setFlash('error', 'Nama jenis perangkat tidak boleh kosong.');
            } else {
                $model = new JenisPerangkat($this->db);
                if ($model->create($nama)) {
                    setFlash('success', 'Jenis perangkat berhasil ditambahkan.');
                } else {
                    setFlash('error', 'Gagal menambahkan jenis perangkat.');
                }
            }
        }
        header('Location: index.php?controller=jenisPerangkat&method=index');
        exit;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $nama = trim($_POST['nama']);
            $status = isset($_POST['status']) ? 1 : 0;
            
            if (empty($nama)) {
                setFlash('error', 'Nama jenis perangkat tidak boleh kosong.');
            } else {
                $model = new JenisPerangkat($this->db);
                if ($model->update($id, $nama, $status)) {
                    setFlash('success', 'Jenis perangkat berhasil diperbarui.');
                } else {
                    setFlash('error', 'Gagal memperbarui jenis perangkat.');
                }
            }
        }
        header('Location: index.php?controller=jenisPerangkat&method=index');
        exit;
    }

    public function delete()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $model = new JenisPerangkat($this->db);
            
            if ($model->delete($id)) {
                setFlash('success', 'Jenis perangkat berhasil dinonaktifkan.');
            } else {
                setFlash('error', 'Gagal menonaktifkan jenis perangkat.');
            }
        }
        header('Location: index.php?controller=jenisPerangkat&method=index');
        exit;
    }
}
