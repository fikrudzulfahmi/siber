<?php
/**
 * ping_server_lokal.php — cron di HOSTING (disarankan tiap 30 menit).
 *
 * Tugasnya: memanggil endpoint `ping.php` di server lokal lewat Cloudflare
 * Tunnel, lalu menyimpan hasilnya ke tabel `server_monitoring` supaya dashboard
 * (Setting → Monitoring Jaringan) menampilkan status yang benar.
 *
 * Kenapa arahnya dibalik (hosting yang memanggil lokal)?
 *  - Server lokal tidak lagi mengirim heartbeat tiap 30 detik ke hosting.
 *  - Server lokal yang mati tidak bisa melapor; hosting yang harus menyadarinya.
 *
 * Pemakaian:
 *   CLI : php public/cron_jobs/ping_server_lokal.php
 *   Web : https://domain/public/cron_jobs/ping_server_lokal.php?key=CRON_KEY
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
if (is_file(__DIR__ . '/../../config/secrets.php')) {
    require_once __DIR__ . '/../../config/secrets.php';
}
require_once __DIR__ . '/../../app/config.php';

// ---------- kunci akses (pola sama dengan cron_jobs lain) ----------
$isCli = (PHP_SAPI === 'cli' || php_sapi_name() === 'cli');
$providedKey = $_GET['key'] ?? null;
if ($isCli && $providedKey === null && isset($_SERVER['argv'])) {
    foreach ($_SERVER['argv'] as $arg) {
        if (str_starts_with($arg, '--key=')) {
            $providedKey = substr($arg, 6);
        } elseif (str_starts_with($arg, 'key=')) {
            $providedKey = substr($arg, 4);
        }
    }
}
if (!$isCli || $providedKey !== null) {
    if (!isset($providedKey) || !defined('CRON_KEY') || CRON_KEY === '' || !hash_equals(CRON_KEY, (string) $providedKey)) {
        if (PHP_SAPI !== 'cli') {
            http_response_code(403);
        }
        die(json_encode(['ok' => false, 'error' => 'Unauthorized']));
    }
}

// ---------- konfigurasi target ----------
$cfgFile = __DIR__ . '/../../config/finger_local.php';
$cfg = is_file($cfgFile) ? require $cfgFile : [];

$pingUrl   = trim((string) ($cfg['ping_url'] ?? ''));
$pingToken = trim((string) ($cfg['ping_token'] ?? ''));
$timeout   = max(3, min(20, (int) ($cfg['timeout'] ?? 10)));

if ($pingUrl === '' || $pingToken === '') {
    http_response_code(500);
    die(json_encode([
        'ok'    => false,
        'error' => 'config/finger_local.php belum ada / ping_url atau ping_token kosong',
    ]));
}

// ---------- kunci anti-tumpang-tindih ----------
$lock = @fopen(__DIR__ . '/.lock_' . basename(__FILE__), 'c');
if (!$lock || !flock($lock, LOCK_EX | LOCK_NB)) {
    die(json_encode(['ok' => false, 'busy' => true, 'message' => 'proses sebelumnya masih berjalan']));
}

$hasil = [
    'ok'      => false,
    'waktu'   => date('c'),
    'target'  => $pingUrl,
    'lokal'   => null,
    'error'   => null,
    'tersimpan' => false,
];

// ---------- panggil server lokal ----------
$ch = curl_init($pingUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => min(5, $timeout),
    CURLOPT_TIMEOUT        => $timeout,
    CURLOPT_HTTPHEADER     => [
        'X-Token: ' . $pingToken,
        'Accept: application/json',
    ],
    CURLOPT_FOLLOWLOCATION => false,
]);
$raw  = curl_exec($ch);
$cerr = curl_error($ch);
$code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$lokal = null;
if ($raw !== false && trim((string) $raw) !== '') {
    $lokal = json_decode((string) $raw, true);
}

// ---------- tulis ke server_monitoring ----------
try {
    $database = new Database();
    $pdo = $database->getConnection();

    if (is_array($lokal) && $code === 200 && ($lokal['ok'] ?? false)) {
        $m1 = !empty($lokal['mesin'][0]['online']) ? 'Online' : 'Offline';
        $m2 = !empty($lokal['mesin'][1]['online']) ? 'Online' : 'Offline';

        $pdo->prepare('UPDATE server_monitoring SET last_ping = NOW(), mesin1_status = ?, mesin2_status = ? WHERE id = 1')
            ->execute([$m1, $m2]);

        $hasil['ok'] = true;
        $hasil['tersimpan'] = true;
        $hasil['mesin'] = [$m1, $m2];
        $hasil['presensi'] = $lokal['presensi'] ?? null;
        $hasil['node'] = $lokal['node'] ?? null;
    } else {
        // Gagal hubungi server lokal: tandai mesin offline, last_ping DIBIARKAN
        // supaya umurnya terus bertambah dan dashboard menampilkan "Offline".
        $pdo->exec("UPDATE server_monitoring SET mesin1_status = 'Offline', mesin2_status = 'Offline' WHERE id = 1");

        $hasil['error'] = $cerr !== '' ? ('cURL: ' . $cerr) : ('HTTP ' . $code . ' / balasan tidak valid');
        if (is_array($lokal) && isset($lokal['error'])) {
            $hasil['error'] .= ' — ' . $lokal['error'];
        }
        error_log('[PING LOKAL] gagal: ' . $hasil['error']);
    }
} catch (Throwable $e) {
    $hasil['error'] = 'DB: ' . $e->getMessage();
    error_log('[PING LOKAL] gagal simpan status: ' . $e->getMessage());
}

$hasil['lokal'] = $lokal;

flock($lock, LOCK_UN);
fclose($lock);

echo json_encode($hasil, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
