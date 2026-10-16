<?php
/**
 * ============================================================================
 *  GET/POST /wa-gateway/requeue.php  —  penyelamat antrean mandek (LEASE)
 * ----------------------------------------------------------------------------
 *  Header wajib : X-Gateway-Token
 *  Query opsional: menit (batas usia klaim, default WA_LEASE_MENIT = 45)
 *  Respons: { "ok": true, "dilepas": n, "gagal": n, "pemulih": [...] }
 *
 *  Kenapa perlu: setelah jobs.php mengklaim baris (status 'in_queue'), baris itu
 *  menunggu laporan balik gateway. Bila gateway mati/listrik putus/PC restart
 *  di antara "sudah ditarik" dan "lapor balik", baris itu akan mandek 'in_queue'
 *  selamanya dan pesannya tidak pernah terkirim. Endpoint ini mengembalikannya
 *  ke 'pending' agar ditarik ulang; bila sudah WA_MAX_ATTEMPT kali tetap mandek,
 *  baris ditutup 'failed' supaya tidak berputar selamanya.
 *
 *  Panggil dari cron hosting, mis. tiap 10 menit:
 *    curl -s -H "X-Gateway-Token: <token>" \
 *      https://siber.pondokminggirsari.com/wa-gateway/requeue.php
 * ============================================================================
 */
declare(strict_types=1);

require __DIR__ . '/_config.php';

wa_cek_token();

try {
    $pdo = wa_pdo();

    $hasil = wa_lepas_klaim_mandek($pdo);

    wa_json_out([
        'ok'         => true,
        'dilepas'    => $hasil['dilepas'],
        'gagal'      => $hasil['gagal'],
        'pesan'      => $hasil['pesan'],
        'lease_menit' => WA_LEASE_MENIT,
        'max_attempt' => WA_MAX_ATTEMPT,
        'rekap'      => wa_rekap_antrean($pdo),
        'waktu'      => date('Y-m-d H:i:s'),
    ]);
} catch (Throwable $e) {
    wa_json_out(['ok' => false, 'error' => 'gagal melepas klaim mandek: ' . $e->getMessage()], 500);
}
