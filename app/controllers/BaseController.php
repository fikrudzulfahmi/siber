<?php
// BaseController.php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once __DIR__ . '/../helpers/SiswaAktif.php';

class BaseController
{
    protected $db;

    public function __construct($pdo)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Cek login
        if (!isset($_SESSION['user'])) {
            header('Location: index.php');
            exit;
        }

        $this->db = $pdo;

        // Sesi yang dibuat sebelum fitur kategori pegawai ada belum punya
        // id_jabatan; lengkapi sekali di sini supaya menu & kartu dashboard
        // tidak salah mengenali Tenaga Kependidikan.
        if (!isset($_SESSION['user']['id_jabatan']) && !empty($_SESSION['user']['id'])) {
            $stmt = $this->db->prepare("SELECT id_jabatan FROM employe WHERE id_employe = ? LIMIT 1");
            $stmt->execute([$_SESSION['user']['id']]);
            $_SESSION['user']['id_jabatan'] = (int) $stmt->fetchColumn();
        }
    }

    public function model($model)
    {
        require_once __DIR__ . '/../models/' . $model . '.php';
        return new $model($this->db);
    }

    public function view($view, $data = [])
    {
        extract($data);
        require_once __DIR__ . '/../views/' . $view . '.php';
    }
    
}
