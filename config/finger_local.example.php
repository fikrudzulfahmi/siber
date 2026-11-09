<?php
/**
 * ============================================================================
 *  TEMPLATE config/finger_local.php — PENGATURAN PING SERVER LOKAL
 * ============================================================================
 *
 *  Berkas ini dipakai oleh `public/cron_jobs/ping_server_lokal.php`
 *  (cron di hosting, tiap 30 menit) untuk menghubungi server lokal lewat
 *  Cloudflare Tunnel.
 *
 *  CARA PAKAI:
 *   1. Di hosting, masuk folder `config` (sejajar `app` dan `public`).
 *   2. Buat file baru bernama: finger_local.php
 *   3. Tempel isi file ini, ganti nilainya.
 *   4. Jangan di-commit (sudah masuk .gitignore).
 *
 *  CATATAN KEAMANAN:
 *   - Token dikirim lewat header `X-Token`, BUKAN query string, supaya tidak
 *     tercatat di access log Apache maupun log Cloudflare.
 *   - Endpoint `ping.php` di server lokal tidak menyentuh EasyLink, jadi aman
 *     dipanggil sesering apa pun tanpa mengganggu antrean presensi.
 */

return [
    // URL lengkap endpoint ping di server lokal (lewat tunnel Cloudflare).
    // HARUS https dan HARUS menunjuk langsung ke berkas ping.php.
    'ping_url' => 'https://finger.contoh-domain.com/finger/ping.php',

    // Harus SAMA PERSIS dengan 'ping_token' di finger/config.local.php (server lokal).
    // Buat acak: php -r "echo bin2hex(random_bytes(24));"
    'ping_token' => 'ISI_TOKEN_YANG_SAMA_DENGAN_SERVER_LOKAL',

    // Batas tunggu (detik). Jangan lebih dari 20 — cron berikutnya tiap 30 menit.
    'timeout' => 10,
];
