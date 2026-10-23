<?php
/**
 * Front controller SIBER.
 * Menangani: sesi aman, header keamanan, CSRF, otorisasi per controller, routing.
 */

require_once __DIR__ . '/../app/helpers/Security.php';

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

// Daftar controller-method yang bebas akses (token tetap dicek di dalamnya).
$freeAccess = [
    'auth'   => ['login', 'logout'],
    'backup' => ['autoBackup'],
];

$controller = (string) ($_GET['controller'] ?? 'auth');
$method     = (string) ($_GET['method'] ?? 'login');

if (!preg_match('/^[A-Za-z][A-Za-z0-9_]{0,59}$/', $controller) || !preg_match('/^[A-Za-z][A-Za-z0-9_]{0,59}$/', $method)) {
    http_response_code(400);
    echo '<div style="font-family:sans-serif;padding:24px">Permintaan tidak valid.</div>';
    exit;
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
    error_log('[SIBER] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . ($e->getFile() ?: '?') . ':' . $e->getLine());
    http_response_code(500);
    echo '<div style="padding:20px;font-family:sans-serif;color:#c0392b;max-width:560px;margin:auto">'
        . '<h3>Terjadi kesalahan</h3>'
        . '<p>Permintaan tidak dapat diproses. Silakan coba lagi atau hubungi administrator.</p>'
        . '<p><a href="index.php?controller=dashboard&amp;method=index">Kembali ke Beranda</a></p></div>';
}
