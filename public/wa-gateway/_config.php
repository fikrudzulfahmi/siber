<?php
/**
 * ============================================================================
 *  Konfigurasi jembatan WA Gateway untuk SIBER
 *  Dipakai oleh: public/wa-gateway/jobs.php dan public/wa-gateway/ack.php
 * ============================================================================
 *
 *  Cara kerja: gateway WA lokal (server Windows + XAMPP) menembak
 *    GET  https://siber.pondokminggirsari.com/wa-gateway/jobs.php
 *    POST https://siber.pondokminggirsari.com/wa-gateway/ack.php
 *  Keduanya WAJIB memakai header  X-Gateway-Token  yang sama dengan token
 *  aplikasi "SIBER" di dashboard gateway.
 *
 *  Nilai default di bawah bisa ditimpa lewat environment variable
 *  (dipakai saat pengujian lokal terhadap salinan database siber_prod_copy).
 */

// ------------------ 1. Koneksi database produksi hosting -------------------
$WA_DB = [
    'host' => getenv('SIBER_DB_HOST') !== false ? (string) getenv('SIBER_DB_HOST') : 'localhost',
    'name' => getenv('SIBER_DB_NAME') !== false ? (string) getenv('SIBER_DB_NAME') : 'u607305378_siber',
    'user' => getenv('SIBER_DB_USER') !== false ? (string) getenv('SIBER_DB_USER') : 'u607305378_siber',
    'pass' => getenv('SIBER_DB_PASS') !== false ? (string) getenv('SIBER_DB_PASS') : 'root@P4ssw0rd',
];

// ------------------ 2. Token gateway (GANTI bila dirotasi) -----------------
if (!defined('WA_GATEWAY_TOKEN')) {
    define('WA_GATEWAY_TOKEN', getenv('WA_GATEWAY_TOKEN') !== false
        ? (string) getenv('WA_GATEWAY_TOKEN')
        : 'wagsib_7f3a91c4e85b2d60af41c9e3b7d20568');
}

// ------------------ 3. Batas tanggal penarikan pesan -----------------------
// Hanya pesan yang dibuat SETELAH tanggal ini yang boleh ditarik gateway.
// Ini mencegah 68 pesan lama (April–Juli 2026) yang belum pernah terkirim
// tiba-tiba dikirim sekaligus saat gateway pertama kali dinyalakan.
if (!defined('WA_MULAI_DARI')) {
    define('WA_MULAI_DARI', getenv('WA_MULAI_DARI') !== false
        ? (string) getenv('WA_MULAI_DARI')
        : '2026-09-28 00:00:00');
}

// ------------------ 4. Helper koneksi -------------------------------------
if (!function_exists('wa_pdo')) {
    /**
     * Buka koneksi PDO ke database SIBER.
     * @param array{host:string,name:string,user:string,pass:string} $cfg
     */
    function wa_pdo(array $cfg): PDO
    {
        $pdo = new PDO(
            "mysql:host={$cfg['host']};dbname={$cfg['name']};charset=utf8mb4",
            $cfg['user'],
            $cfg['pass'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        return $pdo;
    }
}

if (!function_exists('wa_json_out')) {
    /**
     * Kirim respons JSON lalu berhenti.
     */
    function wa_json_out(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('wa_cek_token')) {
    /**
     * Tolak request tanpa token yang benar.
     */
    function wa_cek_token(): void
    {
        $token = $_SERVER['HTTP_X_GATEWAY_TOKEN'] ?? '';
        if ($token === '' || !hash_equals(WA_GATEWAY_TOKEN, (string) $token)) {
            wa_json_out(['ok' => false, 'error' => 'token gateway tidak valid'], 401);
        }
    }
}
