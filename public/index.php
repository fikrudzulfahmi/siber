<?php
/**
 * Front controller SIBER.
 * Menangani: sesi aman, header keamanan, CSRF, otorisasi per controller, routing.
 */

// Server hosting berjalan di UTC; aplikasi memakai WIB (GMT+7) di semua
// halaman. Ditaruh paling awal supaya helper yang memakai date() ikut benar.
date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/../app/helpers/Security.php';
require_once __DIR__ . '/../app/helpers/ErrorPage.php';

ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

sec_session_start();
sec_send_headers();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/LevelHelper.php';
require_once __DIR__ . '/../app/helpers/ActivityLogHelper.php';
require_once __DIR__ . '/../app/helpers/Upload.php';
require_once __DIR__ . '/../app/helpers/SiswaAktif.php';

// Daftar controller-method yang bebas akses (token tetap dicek di dalamnya).
$freeAccess = [
    'auth'   => ['login', 'logout'],
    'backup' => ['autoBackup'],
];

$controller = (string) ($_GET['controller'] ?? 'auth');
$method     = (string) ($_GET['method'] ?? 'login');

if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,59}$/', $controller) || !preg_match('/^[A-Za-z][A-Za-z0-9_]{0,59}$/', $method)) {
    render_error_page(400, 'Permintaan Tidak Valid', 'Alamat yang dibuka tidak dikenali aplikasi. Periksa kembali tautannya atau mulai dari beranda.', null, isset($_SESSION['user']));
}

$isProtectedPage = !isset($freeAccess[$controller]) || !in_array($method, $freeAccess[$controller], true);
if ($isProtectedPage && !isset($_SESSION['user'])) {
    header('Location: index.php?controller=auth&method=login');
    exit;
}

// CSRF: berlaku untuk semua POST yang masuk lewat router.
if ($isProtectedPage || $controller === 'auth') {
    sec_csrf_require();
}

spl_autoload_register(function ($class) {
    $file = __DIR__ . "/../app/controllers/{$class}.php";
    if (is_file($file)) {
        require_once $file;
    }
});

$controllerName = ucfirst($controller) . 'Controller';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    register_shutdown_function(function () use ($pdo, $controller, $method) {
        activityLogCatat($pdo, $controller, $method);
    });

    if (!class_exists($controllerName)) {
        throw new RuntimeException('Halaman tidak ditemukan.');
    }

    if (str_starts_with($method, '_')
        || in_array(strtolower($method), ['__construct', '__destruct', '__call', '__invoke'], true)
        || !method_exists($controllerName, $method)) {
        throw new RuntimeException('Halaman tidak ditemukan.');
    }

    // Otorisasi terpusat SEBELUM controller dibuat, agar konstruktor yang
    // melakukan redirect/query tidak sempat berjalan untuk peran yang salah.
    if ($isProtectedPage) {
        sec_enforce_policy($controller, $method);

        // Aksi destruktif via GET wajib berasal dari aplikasi ini (anti-CSRF tautan).
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET'
            && preg_match('/^(hapus|delete|restore|activate|aktifkan|reset)/i', $method)) {
            sec_require_same_origin();
        }
    }

    $object = new $controllerName($pdo);
    $object->$method();
} catch (Throwable $e) {
    $ref = strtoupper(bin2hex(random_bytes(3)));
    error_log('[SIBER ' . $ref . '] ' . get_class($e) . ': ' . $e->getMessage()
        . ' @ ' . ($e->getFile() ?: '?') . ':' . $e->getLine());

    $pesanAsli = $e->getMessage();
    $adaSesi   = isset($_SESSION['user']);

    if (stripos($pesanAsli, 'Halaman tidak ditemukan') !== false) {
        render_error_page(
            404,
            'Halaman Tidak Ditemukan',
            'Menu atau tautan ini tidak tersedia. Kemungkinan fiturnya belum dipasang pada versi aplikasi yang sedang dipakai.',
            $ref,
            $adaSesi
        );
    }

    render_error_page(
        500,
        'Terjadi Kesalahan',
        'Permintaan tidak dapat diproses. Coba muat ulang halaman; bila tetap sama, sampaikan kode rujukan di bawah kepada administrator.',
        $ref,
        $adaSesi
    );
}
