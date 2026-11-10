<?php
/**
 * ============================================================================
 *  TEMPLATE config/wa_local.php — AKSES KE WA GATEWAY DI SERVER LOKAL
 * ============================================================================
 *
 *  Dipakai oleh StatusController (halaman "Status Layanan") untuk menampilkan
 *  status sesi, QR, antrean pesan, dan log gateway.
 *
 *  CARA PAKAI:
 *   1. Di hosting, masuk folder `config` (sejajar `app` dan `public`).
 *   2. Buat file baru: wa_local.php
 *   3. Salin isi ini, ganti nilainya dengan milikmu.
 *   4. Jangan di-commit (sudah masuk .gitignore).
 *
 *  TOKEN diambil dari `engine/.env` di server lokal (baris ENGINE_TOKEN),
 *  dan HARUS sama dengan yang tertulis di server lokal.
 */

return [
    // Hostname tunnel Cloudflare yang mengarah ke engine (port 3001 lokal).
    'base_url' => 'https://wa-pprm.almuhsinblitar.my.id',

    // ENGINE_TOKEN dari engine/.env di server lokal.
    'token'    => 'ISI_ENGINE_TOKEN',

    // Nama sesi yang ingin ditampilkan (default: utama).
    'session'  => 'utama',

    // Batas tunggu tiap panggilan (detik). Jangan besar-besar: halaman ini
    // memanggil beberapa endpoint sekaligus.
    'timeout'  => 6,
];
