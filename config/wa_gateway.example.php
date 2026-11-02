<?php
/**
 * ============================================================================
 *  Konfigurasi WA Gateway SIBER (mode PUSH ke REST Endpoint)
 * ============================================================================
 *
 *  Salin berkas ini ke `config/wa_gateway.php` (di luar folder public/):
 *      cp config/wa_gateway.example.php config/wa_gateway.php
 *
 *  Lalu isi `token` sesuai dengan token yang terdaftar di gateway WA Anda.
 *  Berkas `config/wa_gateway.php` sudah diabaikan (.gitignore) agar token aman.
 */

return [
    // Endpoint REST WA Gateway tujuan
    'endpoint' => 'https://wa-pprm.almuhsinblitar.my.id/send',

    // Token otorisasi gateway WA (dikirim via header Authorization & X-Token)
    'token' => 'ISI_TOKEN_WA_GATEWAY_DI_SINI',
];
