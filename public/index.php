<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$session_path = dirname(__DIR__) . '/sessions';
ini_set('session.save_path', $session_path);

// Mulai session SETELAH path diatur
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// ✅ TAMBAHKAN BARIS INI
// die("REQUEST MASUK DENGAN ID SESSION: " . session_id());
// echo "<pre style='color:blue;padding:20px;'>DEBUG SESSION INDEX:\n";
// print_r($_SESSION);
// // echo "</pre>";
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// ✅ PERBAIKAN: Panggil file database.php di sini agar class "Database" dikenali
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/LevelHelper.php';
log_message("--- REQUEST BARU MASUK ---"); // ✅ JEJAK 1
// Daftar controller-method yang bebas akses
$freeAccess = [
    'auth' => ['login', 'logout'],
];

// Tangkap controller dan method
$controller = $_GET['controller'] ?? 'auth';
$method     = $_GET['method'] ?? 'login';

// Middleware: Cek apakah halaman butuh login
$isProtectedPage = !isset($freeAccess[$controller]) || !in_array($method, $freeAccess[$controller]);


// Jika halaman butuh login DAN user belum login, redirect
if ($isProtectedPage && !isset($_SESSION['user'])) {
    log_message("Middleware: Akses ditolak. Redirect ke login."); // ✅ JEJAK 2
    header('Location: index.php?controller=auth&method=login');
    exit;
}

spl_autoload_register(function ($class) {
    $file = __DIR__ . "/../app/controllers/{$class}.php";
    if (file_exists($file)) {
        require_once $file;
    }
});

$controllerName = ucfirst($controller) . 'Controller';

try {
    // Buat koneksi database
    $database = new Database();
    $pdo = $database->getConnection();

    if (class_exists($controllerName)) {
        // Sekarang $pdo sudah ada isinya saat dikirim ke controller
        $object = new $controllerName($pdo);

        if (method_exists($object, $method)) {
            $object->$method();
        } else {
            throw new Exception("Method <b>{$method}</b> tidak ditemukan di controller <b>{$controllerName}</b>.");
        }
    } else {
        throw new Exception("Controller <b>{$controllerName}</b> tidak ditemukan.");
    }
} catch (Exception $e) {
    echo "<div style='padding:20px; font-family:sans-serif; color:red;'>" . $e->getMessage() . "</div>";
}
