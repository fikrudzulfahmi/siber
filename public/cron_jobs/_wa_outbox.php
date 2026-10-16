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

// Zona waktu WIB: penanda "hari ini" pada pemeriksaan anti-duplikat di bawah
// harus sama dengan tanggal yang dipakai query jadwal/jurnal cronjob.
date_default_timezone_set('Asia/Jakarta');

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
            $s = '62' . substr($s, 1);
        } elseif ($s[0] === '8') {
            $s = '62' . $s;
        } elseif (strncmp($s, '62', 2) !== 0) {
            $s = '62' . ltrim($s, '0');
        }

        // Nomor Indonesia yang sah minimal 9 digit (62 + 7); selain itu dianggap tidak valid.
        return strlen($s) >= 9 ? $s : '';
    }
}

if (!function_exists('wa_enqueue')) {
    /**
     * Simpan satu pesan ke antrean outbox_wa (status 'pending').
     *
     * DIBUNGKUS try/catch: kalau tabel bermasalah, cronjob TIDAK boleh mati —
     * hasilnya dikembalikan sebagai array supaya pemanggil bisa mencatat log.
     *
     * ANTI-DOBEL: pesan dengan teks identik ke nomor yang sama yang masih
     * menunggu/terkirim HARI INI (WIB) tidak dimasukkan lagi. Sebelumnya cron
     * yang jalan dua kali atau tumpang-tindih membuat pesan terkirim dobel.
     * Bila memang perlu mengirim teks yang sama lagi di hari yang sama,
     * panggil dengan $opsi = ['izinkan_dobel' => true].
     *
     * @param array{izinkan_dobel?:bool} $opsi
     * @return array{ok: bool, id: int|null, nomor: string, error: string|null, duplikat?: bool}
     */
    function wa_enqueue(PDO $pdo, string $nomor, string $pesan, array $opsi = []): array
    {
        $nomor = wa_norm_nomor($nomor);
        if ($nomor === '' || strlen($nomor) < 9) {
            return ['ok' => false, 'id' => null, 'nomor' => $nomor, 'error' => 'nomor tidak valid'];
        }
        // Gateway WA hanya menerima 9–15 digit. Nomor di luar itu akan ditolak
        // gateway dan (bila tetap dimasukkan) menyumbat slot antrean.
        if (strlen($nomor) > 15) {
            return ['ok' => false, 'id' => null, 'nomor' => $nomor, 'error' => 'nomor terlalu panjang (maks 15 digit)'];
        }
        if (trim($pesan) === '') {
            return ['ok' => false, 'id' => null, 'nomor' => $nomor, 'error' => 'isi pesan kosong'];
        }

        if (empty($opsi['izinkan_dobel'])) {
            try {
                $cek = $pdo->prepare(
                    "SELECT id FROM outbox_wa
                      WHERE nomor = ? AND pesan = ? AND status IN ('pending','in_queue','sent')
                        AND created_at >= ?
                      LIMIT 1"
                );
                $cek->execute([$nomor, $pesan, date('Y-m-d 00:00:00')]);
                $sudahAda = $cek->fetchColumn();
                if ($sudahAda !== false && $sudahAda !== null) {
                    return [
                        'ok'       => false,
                        'id'       => (int) $sudahAda,
                        'nomor'    => $nomor,
                        'error'    => 'sudah ada di antrean hari ini (id ' . (int) $sudahAda . ')',
                        'duplikat' => true,
                    ];
                }
            } catch (Throwable $e) {
                // pemeriksaan gagal (mis. index/jaringan) -> jangan halangi pengiriman
            }
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
        if (!empty($hasil['duplikat'])) {
            return "DILEWATI (duplikat hari ini, outbox_wa id {$hasil['id']}) -> {$hasil['nomor']}";
        }
        return $hasil['ok']
            ? "masuk antrean WA -> {$hasil['nomor']} (outbox_wa id {$hasil['id']})"
            : "GAGAL antre -> " . ($hasil['nomor'] !== '' ? $hasil['nomor'] : '(nomor kosong)') . " : {$hasil['error']}";
    }
}
