<?php
// Konfigurasi session yang sama persis seperti di index.php
$session_path = dirname(__DIR__) . '/sessions';
ini_set('session.save_path', $session_path);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

echo '<h1>Halaman 2: Hasil Pengecekan Session Setelah Redirect</h1>';
echo '<pre>';
print_r($_SESSION);
echo '</pre>';

if (isset($_SESSION['status']) && $_SESSION['status'] === 'BERHASIL DIBUAT DI HALAMAN 1') {
    echo "<h2 style='color:green;'>✅ KESIMPULAN: Server caching AMAN.</h2>";
} else {
    echo "<h2 style='color:red;'>❌ KESIMPULAN: Server caching MERUSAK session. Ini adalah masalahnya.</h2>";
}
