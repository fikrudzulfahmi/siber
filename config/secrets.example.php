<?php
/**
 * Contoh Berkas Konfigurasi Rahasia SIBER.
 *
 * Salin berkas ini ke `config/secrets.php` lalu sesuaikan nilainya.
 * PERINGATAN: `config/secrets.php` memuat kredensial sensitif dan
 * TIDAK boleh di-commit ke Git (sudah masuk .gitignore).
 */

// Token API Fonnte untuk notifikasi WhatsApp
if (!defined('FONNTE_TOKEN')) {
    define('FONNTE_TOKEN', 'YOUR_FONNTE_TOKEN');
}

// Konfigurasi Webhook Google Apps Script untuk pencadangan otomatis
if (!defined('GOOGLE_DRIVE_WEBHOOK_URL')) {
    define('GOOGLE_DRIVE_WEBHOOK_URL', 'https://script.google.com/macros/s/YOUR_MACRO_ID/exec');
}

// Kunci rahasia Google Drive Webhook
if (!defined('GOOGLE_DRIVE_SECRET_KEY')) {
    define('GOOGLE_DRIVE_SECRET_KEY', 'YOUR_GOOGLE_DRIVE_SECRET_KEY');
}

// Folder ID Google Drive untuk menyimpan file backup
if (!defined('GOOGLE_DRIVE_FOLDER_ID')) {
    define('GOOGLE_DRIVE_FOLDER_ID', 'YOUR_GOOGLE_DRIVE_FOLDER_ID');
}

// Kunci rahasia untuk eksekusi skrip cron (public/cron_jobs/*.php)
if (!defined('CRON_KEY')) {
    define('CRON_KEY', 'YOUR_CRON_SECRET_KEY');
}

// Kunci autentikasi perangkat mesin presensi (public/API/index.php)
if (!defined('API_DEVICE_KEY')) {
    define('API_DEVICE_KEY', 'YOUR_API_DEVICE_KEY');
}

// CATATAN: WHATSAPP_RECIPIENTS sengaja TIDAK didefinisikan di sini.
// Nomor penerima memakai daftar default di app/config.php; isi di sini hanya
// bila ingin mengganti daftar tersebut (format array nomor 62...).

// Kredensial database (config/database.php memakai ini bila env SIBER_DB_* kosong)
if (!defined('SIBER_DB_HOST')) {
    define('SIBER_DB_HOST', 'localhost');
}
if (!defined('SIBER_DB_PORT')) {
    define('SIBER_DB_PORT', '');
}
if (!defined('SIBER_DB_NAME')) {
    define('SIBER_DB_NAME', 'YOUR_DB_NAME');
}
if (!defined('SIBER_DB_USERNAME')) {
    define('SIBER_DB_USERNAME', 'YOUR_DB_USER');
}
if (!defined('SIBER_DB_PASSWORD')) {
    define('SIBER_DB_PASSWORD', 'YOUR_DB_PASSWORD');
}
