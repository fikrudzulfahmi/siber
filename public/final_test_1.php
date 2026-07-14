<?php
// Konfigurasi session yang sama persis seperti di index.php
$session_path = dirname(__DIR__) . '/sessions';
ini_set('session.save_path', $session_path);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Buat session
$_SESSION['status'] = 'BERHASIL DIBUAT DI HALAMAN 1';

// Paksa simpan session sebelum redirect
session_write_close();

// Langsung redirect ke halaman kedua
header('Location: final_test_2.php');
exit;
