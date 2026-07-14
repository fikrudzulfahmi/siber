<?php
require_once __DIR__ . '/../models/Penilaian.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class PenilaianController extends BaseController
{
    private $penilaianModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo); // sudah menyimpan ke $this->db
        $this->penilaianModel = new Penilaian($this->db);
    }

    public function index()
    {

        $id_employe = $_SESSION['user']['id'];
        $model = new Penilaian($this->db); // ini harus ada
        $kelasList = $model->getAllKelasByGuru($id_employe);
        require __DIR__ . '/../views/admin/penilaian/index.php';
    }


    public function create()
    {
        $users = $this->db->query("SELECT id_employe, nama FROM employe")->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/admin/mapel/create.php';
    }


    public function store()
    {
        $model = new Mapel($this->db);
        $model->insert($_POST);
        setFlash('success', 'Mata pelajaran berhasil ditambahkan.');
        header('Location: index.php?controller=mapel&method=index');
    }

    public function edit()
    {
        $model = new Mapel($this->db);
        $mapel = $model->find($_GET['id']);
        $users = $this->db->query("SELECT id_employe, nama FROM employe ")->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/admin/mapel/edit.php';
    }

    public function update()
    {
        $model = new Mapel($this->db);
        $model->update($_POST);
        setFlash('success', 'Mata pelajaran berhasil diperbarui.');
        header('Location: index.php?controller=mapel&method=index');
    }

    public function delete()
    {
        $model = new Mapel($this->db); // ✅ fix: sebelumnya salah pakai User
        $model->delete($_GET['id']);
        setFlash('success', 'Mata pelajaran berhasil dihapus.');
        header('Location: index.php?controller=mapel&method=index');
    }
}
