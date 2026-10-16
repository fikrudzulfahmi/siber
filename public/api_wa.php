<?php
/**
 * File: api_wa.php  —  JALUR LAMA (dipertahankan agar klien lama tetap jalan)
 * ============================================================================
 *  Endpoint yang dipakai sekarang oleh engine WA Gateway lokal:
 *     GET  /wa-gateway/jobs.php
 *     POST /wa-gateway/ack.php
 *     GET  /wa-gateway/requeue.php   (penyelamat antrean mandek, cron 10 menit)
 *     GET  /wa-gateway/status.php    (pemeriksaan kesehatan antrean)
 *
 *  PERUBAHAN PENTING pada berkas ini:
 *   1. Kredensial database TIDAK lagi ditulis mentah di dalam berkas (dulu
 *      user/password hosting terpampang di dalam webroot). Semua memakai
 *      public/wa-gateway/_config.php (bisa lewat environment variable).
 *   2. Wajib memakai header X-Gateway-Token (dulu endpoint ini terbuka).
 *   3. GET kini MENGKLAIM baris (pending -> in_queue) memakai logika yang sama
 *      dengan jobs.php, supaya jalur lama pun tidak menyumbat antrean.
 *
 *  Respons GET : { "status": true, "data": [ {id, ref, nomor, pesan, type} ] }
 *  Body POST  : { "action": "update_status", "id": "<id|ref>", "status": "..." }
 *               status yang dikenal: in_queue | sent | failed (dipetakan sama
 *               dengan ack.php: queued->in_queue, sent/delivered/read/played->sent)
 * ============================================================================
 */
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

require __DIR__ . '/wa-gateway/_config.php';

wa_cek_token();

/** Terima "12", "OUTBOX-12", atau "OUTBOX-12-abcd1234" -> 12 */
function api_wa_id(string $raw): int
{
    if (preg_match('/^OUTBOX-(\d+)/i', trim($raw), $m)) {
        return (int) $m[1];
    }
    return (int) preg_replace('/\D/', '', $raw);
}

try {
    $pdo = wa_pdo();

    // ---------------- 1. POST: update status (konfirmasi dari klien lama) ----
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        $input = json_decode((string) file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }

        if (($input['action'] ?? '') !== 'update_status') {
            wa_json_out(['status' => false, 'message' => 'action tidak dikenal'], 422);
        }

        $id     = api_wa_id((string) ($input['id'] ?? $input['ref'] ?? '0'));
        $status = (string) ($input['status'] ?? '');
        $peta   = [
            'queued'    => 'in_queue',
            'in_queue'  => 'in_queue',
            'sent'      => 'sent',
            'delivered' => 'sent',
            'read'      => 'sent',
            'played'    => 'sent',
            'failed'    => 'failed',
        ];

        if ($id <= 0 || !isset($peta[$status])) {
            wa_json_out(['status' => false, 'message' => 'id atau status tidak dikenal'], 422);
        }

        $statusDb = $peta[$status];
        if ($statusDb === 'in_queue') {
            $stmt = $pdo->prepare("UPDATE outbox_wa SET status = 'in_queue' WHERE id = ? AND status IN ('pending','in_queue')");
            $stmt->execute([$id]);
        } else {
            $stmt = $pdo->prepare('UPDATE outbox_wa SET status = ? WHERE id = ?');
            $stmt->execute([$statusDb, $id]);
        }

        wa_json_out(['status' => true, 'message' => 'Status berhasil diupdate', 'id' => $id, 'status_db' => $statusDb]);
    }

    // ---------------- 2. GET: ambil pesan pending (klaim atomik) -------------
    $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
    $rows  = wa_klaim_antrean($pdo, $limit);

    $data = [];
    foreach ($rows as $row) {
        $data[] = [
            'id'    => (int) $row['id'],
            'ref'   => wa_ref_baris($row),
            'nomor' => $row['nomor'],
            'pesan' => $row['pesan'],
            'type'  => 'text',
        ];
    }

    wa_json_out([
        'status'     => true,
        'data'       => $data,
        'total'      => count($data),
        'rekap'      => wa_rekap_antrean($pdo),
        'mulai_dari' => WA_MULAI_DARI,
    ]);
} catch (Throwable $e) {
    wa_json_out(['status' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500);
}
