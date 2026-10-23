<?php
/**
 * Endpoint AJAX. Semua aksi WAJIB login dan hanya aksi dalam daftar putih
 * yang dilayani. Path sesi disamakan dengan index.php agar sesi terbaca.
 */

require_once __DIR__ . '/../app/helpers/Security.php';

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

sec_session_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized', 'message' => 'Sesi berakhir, silakan login ulang.']);
    exit;
}

$action = isset($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : '';
$allowed = ['get_mapel', 'get_tp', 'get_siswa_jurnal', 'get_siswa'];

if (!in_array($action, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

try {
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Throwable $e) {
    error_log('[SIBER ajax] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Koneksi database gagal.']);
    exit;
}

try {
    switch ($action) {
        case 'get_mapel':
            require __DIR__ . '/../app/ajax/get-mapel-by-kelas.php';
            break;
        case 'get_tp':
            require __DIR__ . '/../app/ajax/get-tp-by-mapel.php';
            break;
        case 'get_siswa_jurnal':
        case 'get_siswa':
            require __DIR__ . '/../app/ajax/get-siswa.php';
            break;
    }
} catch (Throwable $e) {
    error_log('[SIBER ajax] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Permintaan gagal diproses.']);
}
