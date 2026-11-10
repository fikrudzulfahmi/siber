<?php
/**
 * StatusController — halaman "Status Layanan".
 *
 * Menampilkan kondisi dua layanan yang berjalan di server lokal sekolah,
 * diambil lewat Cloudflare Tunnel oleh SERVER (bukan browser), memakai token
 * yang tersimpan di config/ (di luar webroot). Token tidak pernah dikirim ke
 * browser, dan hanya endpoint baca yang dipanggil.
 *
 *   - Presensi : finger/ping.php            (node, tarikan terakhir, mesin)
 *   - WhatsApp : <engine>/api/sessions, /api/messages, /api/logs, qr
 */

class StatusController
{
    /** @var PDO */
    private $db;

    private const CACHE_DETIK = 30;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function index()
    {
        $data = [
            'finger'  => $this->ambilFinger(),
            'wa'      => $this->ambilWa(),
            'diambil' => date('d/m/Y H:i:s'),
        ];

        require __DIR__ . '/../views/admin/status/index.php';
    }

    /* ------------------------------------------------------------------ */

    private function cfg(string $berkas): array
    {
        $path = SIBER_ROOT . '/config/' . $berkas;
        if (is_file($path)) {
            $isi = require $path;
            if (is_array($isi)) {
                return $isi;
            }
        }
        return [];
    }

    /**
     * GET JSON dengan token, timeout pendek, dan cache singkat.
     * @return array{ok:bool,data?:array,error?:string,cached?:bool}
     */
    private function ambilJson(string $url, string $token, int $timeout = 5): array
    {
        $cache = sys_get_temp_dir() . '/siber_status_' . md5($url) . '.json';
        if (is_file($cache) && (time() - filemtime($cache)) < self::CACHE_DETIK) {
            $isi = @file_get_contents($cache);
            $j = $isi !== false ? json_decode($isi, true) : null;
            if (is_array($j)) {
                return ['ok' => true, 'data' => $j, 'cached' => true];
            }
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTPHEADER     => [
                'X-Token: ' . $token,
                'Accept: application/json',
            ],
        ]);
        $raw  = curl_exec($ch);
        $err  = curl_error($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'error' => 'Tidak bisa menghubungi layanan (cURL: ' . $err . ')'];
        }
        if ($code === 401) {
            return ['ok' => false, 'error' => 'Token ditolak (401). Periksa config/*.php di hosting dan token di server lokal.'];
        }
        if ($code >= 400) {
            return ['ok' => false, 'error' => 'Layanan membalas HTTP ' . $code];
        }

        $j = json_decode((string) $raw, true);
        if (!is_array($j)) {
            return ['ok' => false, 'error' => 'Balasan bukan JSON (HTTP ' . $code . ')'];
        }

        @file_put_contents($cache, (string) $raw);
        return ['ok' => true, 'data' => $j, 'cached' => false];
    }

    /* ------------------------------------------------------------------ */

    private function ambilFinger(): array
    {
        $cfg = $this->cfg('finger_local.php');
        $url = trim((string) ($cfg['ping_url'] ?? ''));
        $tok = trim((string) ($cfg['ping_token'] ?? ''));

        if ($url === '' || $tok === '') {
            return [
                'ok'    => false,
                'error' => 'config/finger_local.php belum diisi (ping_url / ping_token).',
            ];
        }

        $r = $this->ambilJson($url, $tok, (int) ($cfg['timeout'] ?? 8));
        if (!$r['ok']) {
            return $r;
        }

        $j = $r['data'];
        return [
            'ok'        => (bool) ($j['ok'] ?? false),
            'cached'    => $r['cached'] ?? false,
            'host'      => $j['host'] ?? '-',
            'waktu'     => $j['waktu'] ?? null,
            'node'      => $j['node'] ?? [],
            'presensi'  => $j['presensi'] ?? [],
            'mesin'     => $j['mesin'] ?? [],
            'error'     => $j['ok'] ?? false ? null : 'Layanan melaporkan status tidak sehat.',
        ];
    }

    private function ambilWa(): array
    {
        $cfg     = $this->cfg('wa_local.php');
        $base    = rtrim(trim((string) ($cfg['base_url'] ?? '')), '/');
        $tok     = trim((string) ($cfg['token'] ?? ''));
        $namaSesi = trim((string) ($cfg['session'] ?? 'utama'));
        $timeout = (int) ($cfg['timeout'] ?? 6);

        if ($base === '' || $tok === '') {
            return [
                'ok'    => false,
                'error' => 'config/wa_local.php belum diisi (base_url / token).',
            ];
        }

        $hasil = ['ok' => true, 'sesi' => null, 'qr' => null, 'pesan' => [], 'log' => [], 'error' => null];

        $sesi = $this->ambilJson($base . '/api/sessions', $tok, $timeout);
        if (!$sesi['ok']) {
            $hasil['ok'] = false;
            $hasil['error'] = $sesi['error'];
            return $hasil;
        }

        $daftar = $sesi['data']['data'] ?? [];
        foreach ((array) $daftar as $s) {
            if (($s['name'] ?? '') === $namaSesi) {
                $hasil['sesi'] = $s;
                break;
            }
        }
        if ($hasil['sesi'] === null && !empty($daftar[0])) {
            $hasil['sesi'] = $daftar[0];
        }

        // QR hanya diambil kalau sesi memang sedang menunggu pindai.
        $statusSesi = $hasil['sesi']['live_status'] ?? ($hasil['sesi']['status'] ?? '');
        if ($statusSesi === 'qr') {
            $qr = $this->ambilJson($base . '/api/sessions/' . rawurlencode($namaSesi) . '/qr', $tok, $timeout);
            if ($qr['ok']) {
                $hasil['qr'] = $qr['data']['data']['qr'] ?? null;
            }
        }

        $p = $this->ambilJson($base . '/api/messages?limit=15', $tok, $timeout);
        if ($p['ok']) {
            $hasil['pesan'] = $p['data']['data'] ?? [];
        }

        $l = $this->ambilJson($base . '/api/logs?limit=15', $tok, $timeout);
        if ($l['ok']) {
            $hasil['log'] = $l['data']['data'] ?? [];
        }

        return $hasil;
    }
}
