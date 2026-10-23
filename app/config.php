<?php
// File: config.php

// Muat rahasia dari config/secrets.php bila ada
if (is_file(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
} elseif (is_file(dirname(__DIR__) . '/config/secrets.php')) {
    require_once dirname(__DIR__) . '/config/secrets.php';
}

// Catatan: notifikasi WhatsApp TIDAK lagi memakai Fonnte. Semua pesan masuk ke
// tabel outbox_wa lalu ditarik oleh gateway WA lokal (public/wa-gateway/).

// Nomor WhatsApp Tujuan Notifikasi
if (!defined('WHATSAPP_RECIPIENTS')) {
    define('WHATSAPP_RECIPIENTS', [
        '6285790900076',
        '6285645810609',
        '6285815543137',
        '6285735119674',
        '6282139315007',
    ]);
}

// Konfigurasi Google Drive Webhook untuk Auto Backup
if (!defined('GOOGLE_DRIVE_WEBHOOK_URL')) {
    define('GOOGLE_DRIVE_WEBHOOK_URL', '');
}
if (!defined('GOOGLE_DRIVE_SECRET_KEY')) {
    define('GOOGLE_DRIVE_SECRET_KEY', '');
}
if (!defined('GOOGLE_DRIVE_FOLDER_ID')) {
    define('GOOGLE_DRIVE_FOLDER_ID', '');
}

// Kunci akses cron job
if (!defined('CRON_KEY')) {
    define('CRON_KEY', '');
}

// Kunci autentikasi perangkat API presensi
if (!defined('API_DEVICE_KEY')) {
    define('API_DEVICE_KEY', '');
}
