<?php
/**
 * Endpoint mesin presensi (fingerprint) & heartbeat server monitoring.
 *
 * Keamanan:
 *  - Kredensial diambil dari config/database.php (tidak lagi literal di sini).
 *  - Wajib kunci perangkat bila API_DEVICE_KEY diisi (config/secrets.php).
 *  - Semua input divalidasi; query memakai prepared statement.
 */

ini_set('display_errors', '0');
ini_set('log_errors', '1');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../app/helpers/Security.php';

foreach ([__DIR__ . '/../../config/secrets.php', SIBER_ROOT . '/config/secrets.php'] as $secretsFile) {
    if (is_file($secretsFile)) {
        require_once $secretsFile;
        break;
    }
}

function api_respond(int $code, array $body): void
{
    http_response_code($code);
    echo json_encode($body);
    exit;
}

// --- Autentikasi perangkat ------------------------------------------------
$deviceKey = defined('API_DEVICE_KEY') ? (string) API_DEVICE_KEY : '';
if ($deviceKey !== '') {
    $sent = $_SERVER['HTTP_X_DEVICE_KEY'] ?? ($_GET['key'] ?? '');
    if (!is_string($sent) || !hash_equals($deviceKey, $sent)) {
        error_log('[SIBER API] permintaan ditolak: kunci perangkat tidak cocok dari ' . ($_SERVER['REMOTE_ADDR'] ?? '?'));
        api_respond(401, ['status' => 'error', 'message' => 'Unauthorized']);
    }
} else {
    error_log('[SIBER API] peringatan: API_DEVICE_KEY belum diisi di config/secrets.php');
}

try {
    $database = new Database();
    $pdo = $database->getConnection();
} catch (Throwable $e) {
    error_log('[SIBER API] koneksi gagal: ' . $e->getMessage());
    api_respond(500, ['status' => 'error', 'message' => 'Database connection failed']);
}

$raw = file_get_contents('php://input');
$data = json_decode((string) $raw, true);

if (!is_array($data)) {
    api_respond(400, ['status' => 'error', 'message' => 'Payload tidak valid']);
}

// 1. Update last ping (server lokal) — opsional, jangan gagalkan permintaan.
try {
    $pdo->exec("UPDATE server_monitoring SET last_ping = NOW() WHERE id = 1");
} catch (Throwable $e) {
    error_log('[SIBER API] gagal update server_monitoring: ' . $e->getMessage());
}

// 2. Heartbeat status mesin
if (isset($data['tipe']) && $data['tipe'] === 'heartbeat') {
    $statusM1 = isset($data['mesin_1']) ? substr((string) $data['mesin_1'], 0, 32) : '';
    $statusM2 = isset($data['mesin_2']) ? substr((string) $data['mesin_2'], 0, 32) : '';

    $stmt = $pdo->prepare("UPDATE server_monitoring SET mesin1_status = ?, mesin2_status = ? WHERE id = 1");
    try {
        $stmt->execute([$statusM1, $statusM2]);
    } catch (Throwable $e) {
        error_log('[SIBER API] gagal simpan heartbeat: ' . $e->getMessage());
        api_respond(500, ['status' => 'error', 'message' => 'Heartbeat gagal disimpan']);
    }

    api_respond(200, ['status' => 'success', 'message' => 'Heartbeat and machine status received']);
}

// 3. Data presensi
if (empty($data)) {
    api_respond(200, ['status' => 'error', 'message' => 'No data received']);
}

$checkStmt = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE pin = ? AND DATE(scan_date) = DATE(?)");
$insertStmt = $pdo->prepare(
    "INSERT INTO attendance (sn, scan_date, pin, verify_mode, io_mode, status)
     VALUES (?, ?, ?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE scan_date = VALUES(scan_date), verify_mode = VALUES(verify_mode), io_mode = VALUES(io_mode), status = VALUES(status)"
);

$diproses = 0;
foreach ($data as $entry) {
    if (!is_array($entry)) {
        continue;
    }

    $pin = isset($entry['pin']) ? trim((string) $entry['pin']) : '';
    $scanDate = isset($entry['scan_date']) ? trim((string) $entry['scan_date']) : '';
    $sn = isset($entry['sn']) ? substr((string) $entry['sn'], 0, 50) : '';
    $verifyMode = isset($entry['verify_mode']) ? (int) $entry['verify_mode'] : 0;
    $ioMode = isset($entry['io_mode']) ? (int) $entry['io_mode'] : 0;

    if ($pin === '' || !preg_match('/^[0-9]{1,20}$/', $pin) || $scanDate === '' || strtotime($scanDate) === false) {
        error_log('[SIBER API] entri presensi dilewati (pin/tanggal tidak valid)');
        continue;
    }

    $checkStmt->execute([$pin, $scanDate]);
    $status = ((int) $checkStmt->fetchColumn() === 0) ? 'datang' : 'pulang';

    try {
        $insertStmt->execute([$sn, $scanDate, $pin, $verifyMode, $ioMode, $status]);
    } catch (Throwable $e) {
        error_log('[SIBER API] gagal simpan presensi: ' . $e->getMessage());
        api_respond(500, ['status' => 'error', 'message' => 'Failed to insert data']);
    }
    $diproses++;
}

api_respond(200, ['status' => 'success', 'message' => 'Data successfully saved', 'jumlah' => $diproses]);
