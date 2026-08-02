<?php
require_once __DIR__ . '/../models/JenisProgramStruktural.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class JenisProgramStrukturalController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo);
        
        // Hanya Admin (1) yang boleh mengakses menu ini
        $id_level = $_SESSION['user']['level'];
        if (!isLevel($id_level, 1)) {
            setFlash('error', 'Anda tidak memiliki akses ke halaman ini.');
            header('Location: index.php?controller=dashboard&method=index');
            exit;
        }
    }

    public function index()
    {
        $model = new JenisProgramStruktural($this->db);
        $jenis_program = $model->getAllAdmin(); 
        require __DIR__ . '/../views/admin/jenis_program_struktural/index.php';
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nama = trim($_POST['nama']);
            
            if (empty($nama)) {
                setFlash('error', 'Nama jenis program struktural tidak boleh kosong.');
            } else {
                $model = new JenisProgramStruktural($this->db);
                if ($model->create($nama)) {
                    setFlash('success', 'Jenis program struktural berhasil ditambahkan.');
                } else {
                    setFlash('error', 'Gagal menambahkan jenis program struktural.');
                }
            }
        }
        header('Location: index.php?controller=jenisProgramStruktural&method=index');
        exit;
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $nama = trim($_POST['nama']);
            $status = isset($_POST['status']) ? 1 : 0;
            
            if (empty($nama)) {
                setFlash('error', 'Nama jenis program struktural tidak boleh kosong.');
            } else {
                $model = new JenisProgramStruktural($this->db);
                if ($model->update($id, $nama, $status)) {
                    setFlash('success', 'Jenis program struktural berhasil diperbarui.');
                } else {
                    setFlash('error', 'Gagal memperbarui jenis program struktural.');
                }
            }
        }
        header('Location: index.php?controller=jenisProgramStruktural&method=index');
        exit;
    }

    public function delete()
    {
        if (isset($_GET['id'])) {
            $id = $_GET['id'];
            $model = new JenisProgramStruktural($this->db);
            
            if ($model->delete($id)) {
                setFlash('success', 'Jenis program struktural berhasil dinonaktifkan.');
            } else {
                setFlash('error', 'Gagal menonaktifkan jenis program struktural.');
            }
        }
        header('Location: index.php?controller=jenisProgramStruktural&method=index');
        exit;
    }
}
