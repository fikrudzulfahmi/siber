<?php
/**
 * ping.php — endpoint untuk cron di hosting (tiap 30 menit).
 *
 * PENTING: endpoint ini TIDAK boleh menyentuh EasyLink. Kalau ia memanggil
 * /scanlog/new, cron 30 menit akan ikut "menghabiskan" antrean presensi dan
 * bertabrakan dengan siklus pull. Di sini hanya: baca DB + cek port TCP mesin.
 *
 * Otorisasi: header `X-Token` (disarankan) atau `Authorization: Bearer <token>`.
 * Token query (?token=) hanya dipakai bila allow_query_token = true, karena
 * query string mudah bocor ke access log / log Cloudflare / riwayat browser.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Jakarta');

$config_file = __DIR__ . '/config.local.php';
$cfg = is_file($config_file) ? require $config_file : [];

$PING_TOKEN        = $cfg['ping_token'] ?? '';
$ALLOW_QUERY_TOKEN = (bool) ($cfg['allow_query_token'] ?? false);

// ---------- otorisasi ----------
$diberikan = '';
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
if ($auth !== '' && stripos($auth, 'Bearer ') === 0) {
    $diberikan = trim(substr($auth, 7));
}
if ($diberikan === '') {
    $diberikan = trim((string) ($_SERVER['HTTP_X_TOKEN'] ?? ''));
}
if ($diberikan === '' && $ALLOW_QUERY_TOKEN) {
    $diberikan = trim((string) ($_GET['token'] ?? ''));
}

if ($PING_TOKEN === '' || $diberikan === '' || !hash_equals($PING_TOKEN, $diberikan)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'token tidak valid']);
    exit;
}

// ---------- data ----------
$db_host = $cfg['db_host'] ?? 'localhost';
$db_user = $cfg['db_user'] ?? 'root';
$db_pass = $cfg['db_pass'] ?? '';
$db_name = $cfg['db_name'] ?? 'db_fingerprint_local';

$hasil = [
    'ok'    => true,
    'host'  => gethostname(),
    'waktu' => date('c'),
    'db'    => false,
    'node'  => ['hidup' => false, 'terakhir_tarik' => null, 'umur_detik' => null],
    'presensi' => ['hari_ini' => 0, 'terakhir' => null, 'belum_terkirim' => 0],
    'mesin' => [],
];

mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if (!$conn->connect_error) {
    $conn->set_charset('utf8mb4');
    $hasil['db'] = true;

    if ($r = $conn->query('SELECT COUNT(*) FROM attendance WHERE DATE(scan_date) = CURDATE()')) {
        $hasil['presensi']['hari_ini'] = (int) $r->fetch_row()[0];
    }
    if ($r = $conn->query('SELECT MAX(scan_date) FROM attendance')) {
        $hasil['presensi']['terakhir'] = $r->fetch_row()[0];
    }
    if ($r = $conn->query('SELECT COUNT(*) FROM attendance WHERE status_sent = 0')) {
        $hasil['presensi']['belum_terkirim'] = (int) $r->fetch_row()[0];
    }
    $conn->close();
} else {
    $hasil['ok'] = false;
    $hasil['error_db'] = $conn->connect_error;
}

// Node dianggap hidup kalau sync.log bertambah dalam 3x interval terjadwal.
$log = __DIR__ . '/sync.log';
if (is_file($log)) {
    $umur = time() - (int) filemtime($log);
    $jam = (int) date('G');
    $interval = ($jam >= 6 && $jam < 18) ? 60 : 300;
    $batas = $interval * 3;

    $hasil['node'] = [
        'hidup'           => $umur <= $batas,
        'terakhir_tarik'  => date('c', (int) filemtime($log)),
        'umur_detik'      => $umur,
        'batas_detik'     => $batas,
    ];
    if (!$hasil['node']['hidup']) {
        $hasil['ok'] = false;
    }
} else {
    $hasil['ok'] = false;
    $hasil['node']['catatan'] = 'sync.log belum ada';
}

// Cek port mesin (TCP saja, murah).
$daftar = $cfg['machines'] ?? [
    ['nama' => 'Mesin 1', 'ip' => '192.168.2.89', 'port' => 4370],
    ['nama' => 'Mesin 2', 'ip' => '192.168.2.91', 'port' => 4380],
];
foreach ($daftar as $m) {
    $fp = @fsockopen($m['ip'], (int) $m['port'], $errno, $errstr, 2);
    if ($fp) {
        fclose($fp);
    }
    $hasil['mesin'][] = [
        'nama'   => $m['nama'],
        'ip'     => $m['ip'],
        'port'   => (int) $m['port'],
        'online' => (bool) $fp,
    ];
}

echo json_encode($hasil, JSON_UNESCAPED_SLASHES);
