<?php
/**
 * ============================================================================
 *  GET /wa-gateway/jobs.php  —  daftar pesan menunggu (ditarik gateway lokal)
 * ----------------------------------------------------------------------------
 *  Dipanggil oleh engine WA Gateway lokal (mode PULL) setiap beberapa detik.
 *  Header wajib : X-Gateway-Token
 *  Query opsional: limit (1..50, default 20), peek=1 (lihat saja, tanpa klaim)
 *
 *  Respons: { "ok": true, "data": [ {id, to, type, body}, ... ] }
 *     id   -> "OUTBOX-<id outbox_wa>-<sidik>", dipakai gateway saat melapor balik
 *     to   -> nomor tujuan format 62xx
 *     body -> isi pesan
 *
 *  PERUBAHAN PENTING (perbaikan antrean macet / dobel):
 *  Sejak versi ini endpoint MELAKUKAN KLAIM: baris yang diserahkan langsung
 *  diubah statusnya menjadi 'in_queue' + claimed_at diisi, di dalam satu
 *  transaksi (SELECT ... FOR UPDATE lalu UPDATE).
 *    1. Baris yang sama tidak lagi disajikan berulang tiap siklus -> tidak
 *       dobel-kirim walaupun ack gateway telat/gagal.
 *    2. Baris yang tidak pernah dilaporkan balik TIDAK lagi menyumbat 20 slot
 *       pertama antrean (dulu itu membuat pesan baru tidak pernah tertarik).
 *    3. Baris yang mandek dikembalikan ke 'pending' oleh requeue.php setelah
 *       WA_LEASE_MENIT menit (lihat _config.php).
 *
 *  Catatan: endpoint ini hanya membaca + mengklaim; aman dengan token.
 * ============================================================================
 */
declare(strict_types=1);

require __DIR__ . '/_config.php';

wa_cek_token();

$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
$limit = max(1, min(50, $limit));   // dibatasi supaya hosting tidak berat

try {
    $pdo = wa_pdo();

    if (!empty($_GET['peek'])) {
        // Mode intip untuk pemeriksaan manual di browser: TIDAK mengklaim apa pun.
        $rows = wa_baris_pending($pdo, $limit);
        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id'   => wa_ref_baris($row),
                'to'   => $row['nomor'],
                'type' => 'text',
                'body' => $row['pesan'],
            ];
        }
        wa_json_out([
            'ok'         => true,
            'peek'       => true,
            'data'       => $data,
            'total'      => count($data),
            'rekap'      => wa_rekap_antrean($pdo),
            'mulai_dari' => WA_MULAI_DARI,
        ]);
    }

    $rows = wa_klaim_antrean($pdo, $limit);

    $data = [];
    foreach ($rows as $row) {
        $data[] = [
            'id'   => wa_ref_baris($row),
            'to'   => $row['nomor'],
            'type' => 'text',
            'body' => $row['pesan'],
        ];
    }

    wa_json_out([
        'ok'         => true,
        'data'       => $data,
        'total'      => count($data),
        'rekap'      => wa_rekap_antrean($pdo),
        'mulai_dari' => WA_MULAI_DARI,
    ]);
} catch (Throwable $e) {
    wa_json_out(['ok' => false, 'error' => 'gagal membaca antrean: ' . $e->getMessage()], 500);
}
