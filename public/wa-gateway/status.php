<?php
/**
 * ============================================================================
 *  GET /wa-gateway/status.php  —  pemeriksaan kesehatan antrean WhatsApp
 * ----------------------------------------------------------------------------
 *  Header wajib : X-Gateway-Token   (buka lewat curl / browser extension)
 *  Respons: JSON ringkas + `diagnosa` berisi kalimat penyebab yang paling mungkin.
 *
 *  Isi pemeriksaan:
 *    - jumlah baris per status, baris tertua & terbaru
 *    - 20 baris pending terdepan (penanda "kepala antrean macet")
 *    - jumlah baris pending yang di luar batas WA_MULAI_DARI (tidak akan pernah
 *      ditarik selama batas itu dipakai)
 *    - kolom claimed_at / attempt sudah ada atau belum (migrasi)
 *    - 25 baris terakhir ack_log.txt
 * ============================================================================
 */
declare(strict_types=1);

require __DIR__ . '/_config.php';

wa_cek_token();

try {
    $pdo = wa_pdo();

    $rekap = wa_rekap_antrean($pdo);

    $rentang = $pdo->query(
        "SELECT status,
                COUNT(*) AS jumlah,
                MIN(created_at) AS tertua,
                MAX(created_at) AS terbaru
           FROM outbox_wa
          GROUP BY status"
    )->fetchAll();

    $depan = $pdo->query(
        "SELECT id, nomor, status, created_at
           FROM outbox_wa
          WHERE status = 'pending'
          ORDER BY id ASC
          LIMIT 20"
    )->fetchAll();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM outbox_wa WHERE status = 'pending' AND created_at < :mulai");
    $stmt->execute([':mulai' => WA_MULAI_DARI]);
    $diLuarBatas = (int) $stmt->fetchColumn();

    $lewatLease = 0;
    if (wa_kolom_ada($pdo, 'outbox_wa', 'claimed_at')) {
        $stmt = $pdo->prepare(
            "SELECT COUNT(*) FROM outbox_wa
              WHERE status = 'in_queue'
                AND ((claimed_at IS NOT NULL AND claimed_at < :batasA)
                     OR (claimed_at IS NULL AND created_at < :batasB))"
        );
        $batasLease = date('Y-m-d H:i:s', time() - (WA_LEASE_MENIT * 60));
        $batasLama  = date('Y-m-d H:i:s', time() - (WA_LEGACY_GRACE_JAM * 3600));
        $stmt->execute([':batasA' => $batasLease, ':batasB' => $batasLama]);
        $lewatLease = (int) $stmt->fetchColumn();
    }

    $logAck = [];
    $berkas = __DIR__ . '/ack_log.txt';
    if (is_readable($berkas)) {
        $baris = file($berkas, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
        $logAck = array_slice($baris, -25);
    }

    // Saklar notifikasi tiap cronjob (tabel `settings`).
    // Di skrip cron: status 'true' = KIRIM ASLI, 'false'/tidak ada baris = MODE
    // SIMULASI (pesan tidak pernah masuk outbox_wa) — penyebab paling sering
    // "antrean kosong padahal cronjob jalan".
    $saklar = [];
    $petaSaklar = [
        'wa_notif_jurnal'            => 'reminder.php',
        'wa_notif_rekap_walas'       => 'rekap_walas.php',
        'wa_notif_terlambat'         => 'reminder_terlambat.php',
        'wa_notif_rekap_terlambat'   => 'rekap_terlambat.php',
        'wa_notif_jurnal_struktural' => 'reminder_jurnal_struktural.php',
    ];
    foreach ($petaSaklar as $kunci => $skrip) {
        $baris = null;
        try {
            $stmt = $pdo->prepare('SELECT status FROM settings WHERE key_setting = ? LIMIT 1');
            $stmt->execute([$kunci]);
            $nilai = $stmt->fetchColumn();
            $baris = $nilai === false ? null : (string) $nilai;
        } catch (Throwable $e) {
            $baris = null;
        }
        $saklar[] = [
            'skrip'   => $skrip,
            'setting' => $kunci,
            'nilai'   => $baris,
            'arti'    => $baris === 'true'
                ? 'KIRIM ASLI'
                : ($baris === null ? 'BARIS SETTING TIDAK ADA -> mode SIMULASI' : 'MODE SIMULASI (tidak mengirim)'),
        ];
    }

    $diagnosa = [];
    if ($diLuarBatas > 0) {
        $diagnosa[] = "Ada {$diLuarBatas} baris pending yang lebih tua dari batas WA_MULAI_DARI (" . WA_MULAI_DARI
            . ") — baris ini TIDAK akan pernah ditarik selama batas tanggal dipakai. "
            . "Kalau memang mau dikirim, jalankan: UPDATE outbox_wa SET created_at = NOW() "
            . "WHERE status='pending' AND created_at < '" . WA_MULAI_DARI . "';";
    }
    if (!wa_kolom_ada($pdo, 'outbox_wa', 'claimed_at')) {
        $diagnosa[] = 'Kolom claimed_at belum ada: jalankan wa_gateway_lease_migrasi.sql di hosting '
            . '(tanpa ini, klaim tetap jalan tetapi penyelamat antrean mandek nonaktif).';
    }
    if ($lewatLease > 0) {
        $diagnosa[] = "Ada {$lewatLease} baris 'in_queue' yang lewat masa sewa " . WA_LEASE_MENIT
            . " menit — panggil requeue.php (atau pasang cron 10 menitan) agar kembali ke antrean.";
    }
    if (($rekap['pending'] ?? 0) > 0 && count($depan) >= 20) {
        $diagnosa[] = 'Ada ≥20 baris pending: kalau id 20 baris terdepan itu TIDAK berubah antar pemeriksaan, '
            . 'kepala antrean macet (biasanya gateway belum/tidak menarik).';
    }
    $mati = array_values(array_filter($saklar, fn($s) => $s['arti'] !== 'KIRIM ASLI'));
    if ($mati) {
        $diagnosa[] = count($mati) . ' cronjob masih MODE SIMULASI / baris setting-nya tidak ada ('
            . implode(', ', array_map(fn($s) => $s['skrip'], $mati))
            . ') — cronjob begini TIDAK memasukkan pesan apa pun ke outbox_wa. '
            . "Set di phpMyAdmin: INSERT INTO settings (key_setting,status) VALUES ('<kunci>','true') "
            . 'ON DUPLICATE KEY UPDATE status = VALUES(status).';
    }
    if (!$diagnosa) {
        $diagnosa[] = 'Tidak ada kejanggalan pada antrean.';
    }

    wa_json_out([
        'ok'             => true,
        'waktu'          => date('Y-m-d H:i:s'),
        'rekap'          => $rekap,
        'rentang'        => $rentang,
        'depan_pending'  => $depan,
        'pending_di_luar_batas' => $diLuarBatas,
        'in_queue_lewat_lease'  => $lewatLease,
        'mulai_dari'     => WA_MULAI_DARI,
        'lease_menit'    => WA_LEASE_MENIT,
        'tenggang_lama_jam' => WA_LEGACY_GRACE_JAM,
        'max_attempt'    => WA_MAX_ATTEMPT,
        'kolom_claimed_at' => wa_kolom_ada($pdo, 'outbox_wa', 'claimed_at'),
        'kolom_attempt'    => wa_kolom_ada($pdo, 'outbox_wa', 'attempt'),
        'token_dari_env'   => getenv('WA_GATEWAY_TOKEN') !== false,
        'mode_notifikasi'  => $saklar,
        'diagnosa'       => $diagnosa,
        'ack_log_terakhir' => $logAck,
    ]);
} catch (Throwable $e) {
    wa_json_out(['ok' => false, 'error' => 'gagal memeriksa antrean: ' . $e->getMessage()], 500);
}
