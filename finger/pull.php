<?php
/**
 * pull.php — satu pintu untuk siklus presensi:
 *   tarik dari EasyLink -> simpan MySQL lokal -> dorong yang belum terkirim ke hosting.
 *
 * Menggantikan pola lama (fetch_fingerprint_data.php + sync_to_host.php dipanggil
 * terpisah tiap siklus). Satu request = satu koneksi DB = satu putaran kerja.
 *
 * Keamanan: hanya menerima koneksi dari localhost (service Node), kecuali
 * pemanggil menyertakan header X-Token yang cocok dengan PULL_TOKEN di config.
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('Asia/Jakarta');

$config_file = __DIR__ . '/config.local.php';
$cfg = is_file($config_file) ? require $config_file : [];

$HOST_URL   = $cfg['host_url']   ?? 'https://siber.pondokminggirsari.com/API/index.php';
$PULL_TOKEN = $cfg['pull_token'] ?? '';
// Opsional: kalau di hosting diisi API_DEVICE_KEY, header ini wajib ikut.
$DEVICE_KEY = trim((string) ($cfg['device_key'] ?? ''));

$machines = [
    ['sn' => 'Fio616202022322018', 'host' => '127.0.0.1', 'port' => 8090],
    ['sn' => 'Fio616202022412641', 'host' => '127.0.0.1', 'port' => 8090],
];

$db_host = $cfg['db_host'] ?? 'localhost';
$db_user = $cfg['db_user'] ?? 'root';
$db_pass = $cfg['db_pass'] ?? '';
$db_name = $cfg['db_name'] ?? 'db_fingerprint_local';

// ---------- otorisasi ----------
// Catatan: kalau Cloudflare Tunnel menembak ke Apache lokal, koneksi masuk ke
// PHP datang dari 127.0.0.1 — jadi "localhost" TIDAK boleh dipercaya begitu saja
// ketika permintaan sebenarnya datang dari internet. Kehadiran header Cloudflare
// membatalkan pengecualian localhost.
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$lewatCloudflare = isset($_SERVER['HTTP_CF_CONNECTING_IP']) || isset($_SERVER['HTTP_CF_RAY']);
$lokal = !$lewatCloudflare && in_array($ip, ['127.0.0.1', '::1'], true);
if (!$lokal) {
    $token = $_SERVER['HTTP_X_TOKEN'] ?? '';
    if ($PULL_TOKEN === '' || !hash_equals($PULL_TOKEN, $token)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'forbidden']);
        exit;
    }
}

// ---------- kunci: satu tarikan dalam satu waktu ----------
$lock = @fopen(__DIR__ . '/pull.lock', 'c');
if (!$lock) {
    echo json_encode(['ok' => false, 'error' => 'tidak bisa membuat lock file']);
    exit;
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    echo json_encode(['ok' => true, 'busy' => true, 'note' => 'tarikan lain sedang berjalan']);
    exit;
}

// ---------- koneksi DB ----------
mysqli_report(MYSQLI_REPORT_OFF);
$conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    flock($lock, LOCK_UN);
    fclose($lock);
    echo json_encode(['ok' => false, 'error' => 'DB: ' . $conn->connect_error]);
    exit;
}
$conn->set_charset('utf8mb4');

/**
 * Ambil /scanlog/new dari EasyLink. null = gagal.
 */
function pullScanlog(string $host, int $port, string $sn, ?string &$err): ?array
{
    $err = null;
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => "http://{$host}:{$port}/scanlog/new",
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query(['sn' => $sn]),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 25,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
    ]);
    $raw  = curl_exec($ch);
    $cerr = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($raw === false) {
        $err = 'cURL: ' . $cerr;
        return null;
    }
    if (trim((string) $raw) === '') {
        $err = "EasyLink balas kosong (HTTP {$code})";
        return null;
    }
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        $err = 'Balasan bukan JSON: ' . substr(trim((string) $raw), 0, 120);
        return null;
    }
    return $data;
}

$hasil = [
    'ok'        => true,
    'time'      => date('c'),
    'inserted'  => 0,
    'machines'  => [],
    'hosting'   => ['status' => 'tidak ada data baru'],
];

// ---------- 1. tarik & simpan ----------
foreach ($machines as $m) {
    $entry = ['sn' => $m['sn'], 'result' => false, 'fetched' => 0, 'inserted' => 0];

    $err  = null;
    $resp = pullScanlog($m['host'], $m['port'], $m['sn'], $err);

    if ($resp === null) {
        $entry['error'] = $err;
        $hasil['ok'] = false;
        $hasil['machines'][] = $entry;
        continue;
    }

    if (empty($resp['Result'])) {
        $entry['message'] = $resp['message'] ?? 'No data';
        $hasil['machines'][] = $entry;
        continue;
    }

    $rows = isset($resp['Data']) && is_array($resp['Data']) ? $resp['Data'] : [];
    $entry['result']  = true;
    $entry['fetched'] = count($rows);

    foreach ($rows as $row) {
        $scan_sn   = $row['SN'] ?? $m['sn'];
        $scan_date = $row['ScanDate'] ?? null;
        if (!$scan_date) {
            continue;
        }

        $pin            = (int) ($row['PIN'] ?? 0);
        $verify_mode    = (int) ($row['VerifyMode'] ?? 0);
        $io_mode        = (int) ($row['IOMode'] ?? 0);
        $scan_date_only = date('Y-m-d', strtotime((string) $scan_date));

        // sudah pernah tersimpan?
        $chk = $conn->prepare('SELECT COUNT(*) FROM attendance WHERE sn = ? AND scan_date = ? AND pin = ?');
        $chk->bind_param('ssi', $scan_sn, $scan_date, $pin);
        $chk->execute();
        $chk->bind_result($sudah);
        $chk->fetch();
        $chk->close();
        if ($sudah > 0) {
            continue;
        }

        // scan pertama hari itu = datang, berikutnya = pulang
        $q = $conn->prepare('SELECT COUNT(*) FROM attendance WHERE pin = ? AND DATE(scan_date) = ?');
        $q->bind_param('is', $pin, $scan_date_only);
        $q->execute();
        $q->bind_result($scan_count);
        $q->fetch();
        $q->close();

        $status = ((int) $scan_count === 0) ? 'datang' : 'pulang';

        $ins = $conn->prepare(
            'INSERT INTO attendance (sn, scan_date, pin, verify_mode, io_mode, status_sent, status) VALUES (?, ?, ?, ?, ?, 0, ?)'
        );
        $ins->bind_param('ssiiis', $scan_sn, $scan_date, $pin, $verify_mode, $io_mode, $status);
        if ($ins->execute()) {
            $entry['inserted']++;
        }
        $ins->close();
    }

    $hasil['inserted'] += $entry['inserted'];
    $hasil['machines'][] = $entry;
}

// ---------- 2. dorong yang belum terkirim (hanya kalau ada) ----------
$pending = [];
$res = $conn->query('SELECT * FROM attendance WHERE status_sent = 0 ORDER BY id ASC LIMIT 500');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $pending[] = $row;
    }
}

if ($pending) {
    $headers = ['Content-Type: application/json'];
    if ($DEVICE_KEY !== '') {
        $headers[] = 'X-Device-Key: ' . $DEVICE_KEY;
    }

    $ch = curl_init($HOST_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($pending),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => $headers,
    ]);
    $balas = curl_exec($ch);
    $cerr  = curl_error($ch);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($balas === false) {
        $hasil['hosting'] = ['status' => 'gagal', 'error' => $cerr, 'pending' => count($pending)];
        $hasil['ok'] = false;
    } else {
        $j = json_decode((string) $balas, true);
        if (is_array($j) && ($j['status'] ?? '') === 'success') {
            $ids = array_map(static fn ($r) => (int) $r['id'], $pending);
            if ($ids) {
                $conn->query('UPDATE attendance SET status_sent = 1 WHERE id IN (' . implode(',', $ids) . ')');
            }
            $hasil['hosting'] = ['status' => 'ok', 'terkirim' => count($pending)];
        } else {
            $hasil['hosting'] = [
                'status' => 'ditolak',
                'http'   => $code,
                'balasan' => substr((string) $balas, 0, 200),
                'pending' => count($pending),
            ];
        }
    }
}

$conn->close();
flock($lock, LOCK_UN);
fclose($lock);

echo json_encode($hasil);
