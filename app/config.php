<?php
// File: config.php

// // Konfigurasi Database
// define('DB_HOST', 'localhost');
// define('DB_NAME', 'nama_database_anda');
// define('DB_USER', 'user_database_anda');
// define('DB_PASS', 'password_database_anda');

// Konfigurasi API Fonnte
define('FONNTE_TOKEN', 'ZFUkS3d6pgPT1vRgfuLV'); // 🔹 Taruh token Anda di sini

// Nomor WhatsApp Tujuan Notifikasi
define('WHATSAPP_RECIPIENTS', [
    '6285790900076',
    '6285645810609',
    '6285815543137',
    '6285735119674',
    '6282139315007',
]);

// Konfigurasi Google Drive Webhook untuk Auto Backup
define('GOOGLE_DRIVE_WEBHOOK_URL', 'https://script.google.com/macros/s/AKfycbw80hxm_meKP_VjOKS2rkrVNbm6JbQlErvZgqXKZSMtP-aKhlNu91t9W96FvYcdQCN0dg/exec');
define('GOOGLE_DRIVE_SECRET_KEY', 'TUsmekisa1968'); // Harus sama dengan SECRET_KEY di Google Apps Script
define('GOOGLE_DRIVE_FOLDER_ID', ''); // Simpan Folder ID untuk upload otomatis

