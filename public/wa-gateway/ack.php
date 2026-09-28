<?php
/**
 * ============================================================================
 *  POST /wa-gateway/ack.php  —  laporan balik status dari gateway lokal
 * ----------------------------------------------------------------------------
 *  Header wajib : X-Gateway-Token
 *  Body JSON    : { "ref": "OUTBOX-12", "status": "sent",
 *                   "wa_message_id": "3EB0...", "error": null }
 *
 *  Pemetaan status gateway -> kolom outbox_wa.status
 *  (enum produksi hanya punya: pending, in_queue, sent, failed):
 *
 *     queued              -> in_queue   (sudah ditarik gateway, sedang diantrekan)
 *     sent                -> sent       (diterima server WhatsApp)
 *     delivered           -> sent       (centang 2; tidak ada kolom khusus)
 *     read                -> sent       (centang biru; tidak ada kolom khusus)
 *     failed              -> failed     (gagal setelah percobaan ulang)
 *
 *  Endpoint ini IDEMPOTEN: gateway memanggilnya beberapa kali untuk satu pesan
 *  (queued -> sent -> delivered -> read), dan UPDATE yang sama diulang aman.
 * ============================================================================
 */
declare(strict_types=1);

require __DIR__ . '/_config.php';

wa_cek_token();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    wa_json_out(['ok' => false, 'error' => 'gunakan metode POST'], 405);
}

$input = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST; // toleransi bila dikirim sebagai form-urlencoded
}

$ref    = (int) preg_replace('/\D/', '', (string) ($input['ref'] ?? $input['id'] ?? '0'));
$status = (string) ($input['status'] ?? '');

$peta = [
    'queued'    => 'in_queue',
    'sent'      => 'sent',
    'delivered' => 'sent',
    'read'      => 'sent',
    'failed'    => 'failed',
];

if ($ref <= 0 || !isset($peta[$status])) {
    wa_json_out(['ok' => false, 'error' => 'ref atau status tidak dikenal'], 422);
}

$statusDb = $peta[$status];

try {
    $pdo = wa_pdo($WA_DB);

    $stmt = $pdo->prepare('UPDATE outbox_wa SET status = ? WHERE id = ?');
    $stmt->execute([$statusDb, $ref]);

    if ($stmt->rowCount() === 0) {
        // Bisa jadi baris tidak ada, atau statusnya memang sudah sama (idempoten)
        $ada = $pdo->prepare('SELECT status FROM outbox_wa WHERE id = ?');
        $ada->execute([$ref]);
        $baris = $ada->fetch();
        if (!$baris) {
            wa_json_out(['ok' => false, 'error' => "pesan id {$ref} tidak ditemukan"], 404);
        }
    }

    // Jejak audit sederhana (membantu saat memeriksa masalah pengiriman)
    $log = __DIR__ . '/ack_log.txt';
    @file_put_contents(
        $log,
        sprintf("[%s] ref=%d status=%s -> %s wa_id=%s error=%s\n",
            date('Y-m-d H:i:s'), $ref, $status, $statusDb,
            (string) ($input['wa_message_id'] ?? '-'), (string) ($input['error'] ?? '-')),
        FILE_APPEND
    );

    wa_json_out(['ok' => true, 'ref' => $ref, 'status' => $statusDb]);
} catch (Throwable $e) {
    wa_json_out(['ok' => false, 'error' => 'gagal memperbarui status: ' . $e->getMessage()], 500);
}
