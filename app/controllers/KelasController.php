<?php
require_once __DIR__ . '/../models/Kelas.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class KelasController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo); // ⛑️ authGuard aktif di BaseController
    }

    public function index()
    {
        $model = new Kelas($this->db);
        $kelas = $model->getAll();
        require __DIR__ . '/../views/admin/kelas/index.php';
    }

    public function create()
    {
        $levels = [1, 3];
        $placeholders = implode(',', array_fill(0, count($levels), '?'));

        $stmt = $this->db->prepare("
            SELECT DISTINCT e.id_employe, e.nama 
        FROM employe e 
        JOIN user_level ul ON e.id_employe = ul.id_employe 
        WHERE ul.id_level IN ($placeholders)
                    ");
        $stmt->execute($levels);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/admin/kelas/create.php';
    }


    public function store()
    {
        $model = new Kelas($this->db);
        $model->insert($_POST);
        setFlash('success', 'Kelas berhasil ditambahkan.');
        header('Location: index.php?controller=kelas&method=index');
    }

    public function edit()
    {
        $model = new Kelas($this->db);
        $kelas = $model->find($_GET['id']);
        $users = $this->db->query("
        SELECT DISTINCT e.id_employe, e.nama 
        FROM employe e 
        JOIN user_level ul ON e.id_employe = ul.id_employe 
        WHERE ul.id_level = 3")->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/admin/kelas/edit.php';
    }

    public function update()
    {
        $model = new Kelas($this->db);
        $model->update($_POST);
        setFlash('success', 'Kelas berhasil diperbarui.');
        header('Location: index.php?controller=kelas&method=index');
    }

    public function delete()
    {
        $model = new Kelas($this->db); // ✅ fix: sebelumnya salah pakai User
        $model->delete($_GET['id']);
        setFlash('success', 'Kelas berhasil dihapus.');
        header('Location: index.php?controller=kelas&method=index');
    }
}
