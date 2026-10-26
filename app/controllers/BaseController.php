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

        // Sesi lama bisa belum punya id_jabatan, atau punya tapi bernilai 0
        // (login lama tidak mengambil kolomnya). Selama belum terisi, ambil
        // ulang dari tabel employe — jangan pakai isset(), karena kunci 0
        // tetap dianggap "ada" dan tendik jadi tidak dikenali.
        if (empty($_SESSION['user']['id_jabatan']) && !empty($_SESSION['user']['id'])) {
            siber_sinkron_jabatan($this->db);
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
