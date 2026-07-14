<?php
// BaseController.php
require_once __DIR__ . '/../helpers/LevelHelper.php';

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
