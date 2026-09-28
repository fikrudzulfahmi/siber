<?php
/**
 * ============================================================================
 *  Helper antrean WhatsApp SIBER  (dipakai berkas-berkas cronjob)
 * ----------------------------------------------------------------------------
 *  Pesan TIDAK dikirim langsung dari hosting. Fungsi di sini hanya MENYIMPAN
 *  pesan ke tabel `outbox_wa` dengan status 'pending'. Gateway WA lokal
 *  (D:\ApplicationWeb\wa-gateway) menariknya lewat endpoint
 *  /wa-gateway/jobs.php, mengirim via WhatsApp, lalu melaporkan status lewat
 *  /wa-gateway/ack.php.
 *
 *  Alasan: server lokal tidak punya IP publik, jadi hosting tidak bisa
 *  menghubungi lokal — hubungan selalu dimulai dari sisi lokal (mode PULL).
 *
 *  Nama tabel & kolom mengikuti tabel yang SUDAH ADA di produksi
 *  (outbox_wa: id, nomor, pesan, status enum('pending','in_queue','sent','failed'),
 *   created_at) — tidak ada perubahan skema.
 * ============================================================================
 */

if (!function_exists('wa_norm_nomor')) {
    /**
     * Normalisasi nomor Indonesia ke format WhatsApp: 08xx / +62 / 8xx -> 62xx
     */
    function wa_norm_nomor(string $raw): string
    {
        $s = preg_replace('/[^\d+]/', '', $raw) ?? '';
        $s = ltrim($s, '+');
        if ($s === '') {
            return '';
        }
        if ($s[0] === '0') {
            return '62' . substr($s, 1);
        }
        if ($s[0] === '8') {
            return '62' . $s;
        }
        if (strncmp($s, '62', 2) !== 0) {
            return '62' . ltrim($s, '0');
        }
        return $s;
    }
}

if (!function_exists('wa_enqueue')) {
    /**
     * Simpan satu pesan ke antrean outbox_wa (status 'pending').
     *
     * DIBUNGKUS try/catch: kalau tabel bermasalah, cronjob TIDAK boleh mati —
     * hasilnya dikembalikan sebagai array supaya pemanggil bisa mencatat log.
     *
     * @return array{ok: bool, id: int|null, nomor: string, error: string|null}
     */
    function wa_enqueue(PDO $pdo, string $nomor, string $pesan): array
    {
        $nomor = wa_norm_nomor($nomor);
        if ($nomor === '' || strlen($nomor) < 9) {
            return ['ok' => false, 'id' => null, 'nomor' => $nomor, 'error' => 'nomor tidak valid'];
        }
        if (trim($pesan) === '') {
            return ['ok' => false, 'id' => null, 'nomor' => $nomor, 'error' => 'isi pesan kosong'];
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO outbox_wa (nomor, pesan, status) VALUES (?, ?, 'pending')");
            $stmt->execute([$nomor, $pesan]);
            return ['ok' => true, 'id' => (int) $pdo->lastInsertId(), 'nomor' => $nomor, 'error' => null];
        } catch (Throwable $e) {
            return ['ok' => false, 'id' => null, 'nomor' => $nomor, 'error' => $e->getMessage()];
        }
    }
}

if (!function_exists('wa_enqueue_hasil_log')) {
    /**
     * Kalimat log siap pakai (dipakai cronjob agar format log seragam).
     */
    function wa_enqueue_hasil_log(array $hasil): string
    {
        return $hasil['ok']
            ? "masuk antrean WA -> {$hasil['nomor']} (outbox_wa id {$hasil['id']})"
            : "GAGAL antre -> " . ($hasil['nomor'] !== '' ? $hasil['nomor'] : '(nomor kosong)') . " : {$hasil['error']}";
    }
}
