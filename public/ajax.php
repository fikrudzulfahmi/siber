<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

// Koneksi database harus ada di sini
require_once '../config/database.php';

// Periksa otentikasi
// if (!isset($_SESSION['user'])) {
//     http_response_code(403);
//     echo json_encode(['error' => 'Unauthorized']);
//     exit;
// }

// Ambil koneksi PDO
try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi database gagal.']);
    exit;
}

// Logika routing AJAX
$action = $_GET['action'] ?? null;

switch ($action) {
    case 'get_mapel':
        require '../app/ajax/get-mapel-by-kelas.php';
        break;
    case 'get_tp':
        require '../app/ajax/get-tp-by-mapel.php';
        break;
    case 'get_siswa_jurnal':
    case 'get_siswa':
        require '../app/ajax/get-siswa.php';
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
