<?php
/**
 * ============================================================================
 *  Helper WhatsApp SIBER (mode PUSH ke REST Endpoint Gateway)
 * ----------------------------------------------------------------------------
 *  Aplikasi SIBER mengirim langsung (PUSH) notifikasi WhatsApp ke REST endpoint:
 *  https://wa-pprm.almuhsinblitar.my.id/send
 *
 *  Kredensial token dibaca dari:
 *    config/wa_gateway.php (di luar folder public/) atau environment variable.
 * ============================================================================
 */

// Zona waktu WIB
date_default_timezone_set('Asia/Jakarta');

if (!function_exists('wa_baca_gateway_config')) {
    /**
     * Baca konfigurasi endpoint & token WA gateway dari config/wa_gateway.php
     *
     * @return array{endpoint: string, token: string}
     */
    function wa_baca_gateway_config(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }

        $dariBerkas = [];
        $kandidat = [
            dirname(__DIR__, 2) . '/config/wa_gateway.php',
            dirname(__DIR__, 3) . '/config/wa_gateway.php',
            __DIR__ . '/../../config/wa_gateway.php',
        ];

        foreach ($kandidat as $path) {
            if (is_file($path)) {
                $isi = require $path;
                if (is_array($isi)) {
                    $dariBerkas = $isi;
                    break;
                }
            }
        }

        $endpoint = (string) (
            getenv('WA_GATEWAY_ENDPOINT') ?:
            ($dariBerkas['endpoint'] ?? (defined('WA_GATEWAY_ENDPOINT') ? WA_GATEWAY_ENDPOINT : 'https://wa-pprm.almuhsinblitar.my.id/send'))
        );

        $token = (string) (
            (getenv('WA_GATEWAY_TOKEN') !== false && getenv('WA_GATEWAY_TOKEN') !== '')
                ? getenv('WA_GATEWAY_TOKEN')
                : ($dariBerkas['token'] ?? $dariBerkas['api_key'] ?? (defined('WA_GATEWAY_TOKEN') ? WA_GATEWAY_TOKEN : ''))
        );

        $cached = [
            'endpoint' => rtrim($endpoint, '/'),
            'token'    => trim($token),
        ];

        return $cached;
    }
}

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

if (!function_exists('wa_kirim')) {
    /**
     * Kirim satu pesan WhatsApp langsung ke endpoint WA Gateway via cURL POST.
     *
     * @param string   $nomor  Nomor tujuan (08xx / 62xx)
     * @param string   $pesan  Isi pesan teks
     * @param PDO|null $pdo    Instance PDO opsional (untuk anti-dobel & audit history)
     * @param array{izinkan_dobel?:bool, delay?:int|string} $opsi Opsi tambahan
     * @return array{ok: bool, id: int|null, nomor: string, http_code: int, response: string|null, error: string|null, duplikat: bool}
     */
    function wa_kirim(string $nomor, string $pesan, ?PDO $pdo = null, array $opsi = []): array
    {
        $nomorBersih = wa_norm_nomor($nomor);
        if ($nomorBersih === '' || strlen($nomorBersih) < 9) {
            return [
                'ok'        => false,
                'id'        => null,
                'nomor'     => $nomor,
                'http_code' => 0,
                'response'  => null,
                'error'     => 'nomor tidak valid',
                'duplikat'  => false,
            ];
        }

        if (strlen($nomorBersih) > 15) {
            return [
                'ok'        => false,
                'id'        => null,
                'nomor'     => $nomorBersih,
                'http_code' => 0,
                'response'  => null,
                'error'     => 'nomor terlalu panjang (maks 15 digit)',
                'duplikat'  => false,
            ];
        }

        if (trim($pesan) === '') {
            return [
                'ok'        => false,
                'id'        => null,
                'nomor'     => $nomorBersih,
                'http_code' => 0,
                'response'  => null,
                'error'     => 'isi pesan kosong',
                'duplikat'  => false,
            ];
        }

        $cfg = wa_baca_gateway_config();
        $endpoint = $cfg['endpoint'];
        $token = $cfg['token'];

        if ($token === '') {
            return [
                'ok'        => false,
                'id'        => null,
                'nomor'     => $nomorBersih,
                'http_code' => 0,
                'response'  => null,
                'error'     => 'token WA gateway belum diisi di config/wa_gateway.php',
                'duplikat'  => false,
            ];
        }

        // Cek anti-duplikat jika PDO diberikan dan opsi izinkan_dobel tidak aktif
        if ($pdo !== null && empty($opsi['izinkan_dobel'])) {
            try {
                $cutoff = date('Y-m-d 00:00:00');
                try {
                    $jamDb  = (string) $pdo->query('SELECT NOW()')->fetchColumn();
                    $selisih = strtotime($jamDb . ' UTC') - time();
                    $cutoff = gmdate('Y-m-d H:i:s', strtotime($cutoff) + $selisih);
                } catch (Throwable $e) {
                    // gagal baca jam DB, gunakan jam PHP
                }

                $cek = $pdo->prepare(
                    "SELECT id FROM outbox_wa
                      WHERE nomor = ? AND pesan = ? AND status = 'sent'
                        AND created_at >= ?
                      LIMIT 1"
                );
                $cek->execute([$nomorBersih, $pesan, $cutoff]);
                $sudahAda = $cek->fetchColumn();
                if ($sudahAda !== false && $sudahAda !== null) {
                    return [
                        'ok'        => false,
                        'id'        => (int) $sudahAda,
                        'nomor'     => $nomorBersih,
                        'http_code' => 200,
                        'response'  => null,
                        'error'     => 'sudah terkirim hari ini (outbox_wa id ' . (int) $sudahAda . ')',
                        'duplikat'  => true,
                    ];
                }
            } catch (Throwable $e) {
                // jika tabel outbox_wa tidak ada atau skema beda, jangan gagalkan pengiriman
            }
        }

        // Siapkan payload POST (kompatibel Fonnte & Node.js WA Gateway)
        $payload = [
            'target'  => $nomorBersih,
            'message' => $pesan,
        ];
        if (!empty($opsi['delay'])) {
            $payload['delay'] = (string) $opsi['delay'];
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($payload),
            CURLOPT_HTTPHEADER     => [
                'Authorization: ' . $token,
                'X-Token: ' . $token,
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        $isSuccess = false;
        $errorMessage = null;

        if ($curlErr !== '') {
            $errorMessage = 'cURL error: ' . $curlErr;
        } else {
            $respData = json_decode((string) $response, true);
            if ($httpCode >= 200 && $httpCode < 300) {
                if (is_array($respData) && array_key_exists('status', $respData)) {
                    $isSuccess = ($respData['status'] === true);
                    if (!$isSuccess) {
                        $errorMessage = $respData['reason'] ?? $respData['message'] ?? 'Gateway menolak pesan';
                    }
                } else {
                    $isSuccess = true;
                }
            } else {
                $errorMessage = (is_array($respData) && (!empty($respData['reason']) || !empty($respData['message'])))
                    ? ($respData['reason'] ?? $respData['message'])
                    : "HTTP {$httpCode}";
            }
        }

        // Catat ke outbox_wa sebagai audit trail bila koneksi DB tersedia
        $insertId = null;
        if ($pdo !== null) {
            try {
                $status = $isSuccess ? 'sent' : 'failed';
                $stmt = $pdo->prepare("INSERT INTO outbox_wa (nomor, pesan, status, created_at) VALUES (?, ?, ?, NOW())");
                $stmt->execute([$nomorBersih, $pesan, $status]);
                $insertId = (int) $pdo->lastInsertId();
            } catch (Throwable $e) {
                // tabel outbox_wa opsional untuk audit
            }
        }

        return [
            'ok'        => $isSuccess,
            'id'        => $insertId,
            'nomor'     => $nomorBersih,
            'http_code' => $httpCode,
            'response'  => $response,
            'error'     => $errorMessage,
            'duplikat'  => false,
        ];
    }
}

if (!function_exists('wa_send')) {
    /**
     * Alias fungsi wa_kirim()
     */
    function wa_send(string $nomor, string $pesan, ?PDO $pdo = null, array $opsi = []): array
    {
        return wa_kirim($nomor, $pesan, $pdo, $opsi);
    }
}

if (!function_exists('wa_enqueue')) {
    /**
     * Kompatibilitas mundur: fungsi wa_enqueue sekarang langsung memanggil wa_kirim().
     *
     * @param PDO|null $pdo
     * @param string   $nomor
     * @param string   $pesan
     * @param array    $opsi
     * @return array{ok: bool, id: int|null, nomor: string, http_code: int, response: string|null, error: string|null, duplikat: bool}
     */
    function wa_enqueue($pdo, string $nomor, string $pesan, array $opsi = []): array
    {
        $db = ($pdo instanceof PDO) ? $pdo : null;
        return wa_kirim($nomor, $pesan, $db, $opsi);
    }
}

if (!function_exists('wa_kirim_hasil_log')) {
    /**
     * Format string log hasil pengiriman pesan WA.
     */
    function wa_kirim_hasil_log(array $hasil): string
    {
        if (!empty($hasil['duplikat'])) {
            return "DILEWATI (duplikat hari ini) -> {$hasil['nomor']}";
        }
        if (!empty($hasil['ok'])) {
            $code = !empty($hasil['http_code']) ? " (HTTP {$hasil['http_code']})" : '';
            return "TERKIRIM -> {$hasil['nomor']}{$code}";
        }
        $nomor = !empty($hasil['nomor']) ? $hasil['nomor'] : '(nomor kosong)';
        $err   = !empty($hasil['error']) ? $hasil['error'] : 'error tidak diketahui';
        return "GAGAL kirim -> {$nomor} : {$err}";
    }
}

if (!function_exists('wa_enqueue_hasil_log')) {
    /**
     * Kompatibilitas mundur: fungsi wa_enqueue_hasil_log memanggil wa_kirim_hasil_log().
     */
    function wa_enqueue_hasil_log(array $hasil): string
    {
        return wa_kirim_hasil_log($hasil);
    }
}
