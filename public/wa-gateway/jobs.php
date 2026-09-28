<?php
/**
 * ============================================================================
 *  GET /wa-gateway/jobs.php  —  daftar pesan menunggu (ditarik gateway lokal)
 * ----------------------------------------------------------------------------
 *  Dipanggil oleh engine WA Gateway lokal (mode PULL) setiap beberapa detik.
 *  Header wajib : X-Gateway-Token
 *  Query opsional: limit (1..50, default 20)
 *
 *  Respons: { "ok": true, "data": [ {id, to, type, body}, ... ] }
 *     id   -> "OUTBOX-<id outbox_wa>", dipakai gateway saat melapor balik (ack)
 *     to   -> nomor tujuan format 62xx
 *     body -> isi pesan
 *
 *  Setelah ditarik, gateway langsung mem-POST status "queued" ke ack.php,
 *  yang mengubah status baris menjadi 'in_queue' sehingga TIDAK ditarik lagi
 *  pada siklus berikutnya (anti dobel-kirim).
 *
 *  Catatan: endpoint ini hanya membaca. Amankan dengan token di _config.php.
 * ============================================================================
 */
declare(strict_types=1);

require __DIR__ . '/_config.php';

wa_cek_token();

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
$limit = max(1, min(50, $limit));   // dibatasi supaya hosting tidak berat

try {
    $pdo = wa_pdo($WA_DB);

    $stmt = $pdo->prepare(
        "SELECT id, nomor, pesan, created_at
           FROM outbox_wa
          WHERE status = 'pending'
            AND created_at >= :mulai
          ORDER BY id ASC
          LIMIT {$limit}"
    );
    $stmt->execute([':mulai' => WA_MULAI_DARI]);

    $data = [];
    foreach ($stmt->fetchAll() as $row) {
        $data[] = [
            'id'    => 'OUTBOX-' . $row['id'],
            'to'    => $row['nomor'],
            'type'  => 'text',
            'body'  => $row['pesan'],
        ];
    }

    // Ringkasan kondisi antrean (membantu saat memeriksa di browser)
    $rekap = $pdo->query(
        "SELECT status, COUNT(*) AS jumlah FROM outbox_wa GROUP BY status"
    )->fetchAll();

    wa_json_out([
        'ok'      => true,
        'data'    => $data,
        'total'   => count($data),
        'rekap'   => $rekap,
        'mulai_dari' => WA_MULAI_DARI,
    ]);
} catch (Throwable $e) {
    wa_json_out(['ok' => false, 'error' => 'gagal membaca antrean: ' . $e->getMessage()], 500);
}
