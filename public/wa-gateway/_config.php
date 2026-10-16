<?php
/**
 * ============================================================================
 *  Konfigurasi jembatan WA Gateway untuk SIBER
 *  Dipakai oleh: public/wa-gateway/{jobs,ack,requeue,status}.php + public/api_wa.php
 * ============================================================================
 *
 *  Cara kerja: gateway WA lokal (server Windows + XAMPP) menembak
 *    GET  https://siber.pondokminggirsari.com/wa-gateway/jobs.php
 *    POST https://siber.pondokminggirsari.com/wa-gateway/ack.php
 *  Keduanya WAJIB memakai header  X-Gateway-Token  yang sama dengan token
 *  aplikasi "SIBER" di dashboard gateway.
 *
 *  PENTING — DI MANA KREDENSIAL DISIMPAN
 *  --------------------------------------
 *  Berkas ini berada DI DALAM webroot (public/), jadi di sini TIDAK ada
 *  password/token mentah. Nilainya dibaca dari config/wa_gateway.php yang
 *  letaknya DI LUAR webroot (satu tingkat di atas public/), atau dari
 *  environment variable hosting.
 *
 *  Urutan pembacaan:
 *    1. environment variable  (paling menang, dipakai untuk pengujian lokal)
 *    2. config/wa_gateway.php (di luar webroot)
 *  Bila keduanya tidak ada, endpoint menolak jalan dan memberi tahu apa yang
 *  harus dibuat — lebih baik daripada diam-diam memakai kredensial salah.
 */

// ---------------------------------------------------------------------------
// 0. ZONA WAKTU — semua perbandingan tanggal di berkas ini memakai WIB, sama
//    dengan cronjob SIBER. Tanpa ini, hosting yang servernya UTC menghitung
//    batas tanggal 7 jam terlalu awal dan perbandingan `created_at` meleset.
// ---------------------------------------------------------------------------
if (!defined('WA_TZ')) {
    define('WA_TZ', getenv('WA_TZ') !== false ? (string) getenv('WA_TZ') : 'Asia/Jakarta');
}
date_default_timezone_set(WA_TZ);

// ---------------------------------------------------------------------------
// 1. Baca sumber konfigurasi (env > berkas di luar webroot)
// ---------------------------------------------------------------------------
if (!function_exists('wa_baca_konfig')) {
    /**
     * @return array<string,mixed> ['db'=>[host,port,name,user,pass], 'token'=>string,
     *                              'mulai_dari'=>string, 'lease_menit'=>int, 'max_attempt'=>int]
     */
    function wa_baca_konfig(): array
    {
        static $cfg = null;
        if ($cfg !== null) {
            return $cfg;
        }

        $dariBerkas = [];
        $kandidat   = [
            dirname(__DIR__, 2) . '/config/wa_gateway.php',   // <app>/config/wa_gateway.php (di luar public/)
            dirname(__DIR__, 3) . '/config/wa_gateway.php',   // bila public/ adalah docroot hosting
        ];
        foreach ($kandidat as $berkas) {
            if (is_file($berkas)) {
                $isi = require $berkas;
                if (is_array($isi)) {
                    $dariBerkas = $isi;
                    break;
                }
            }
        }

        $db = array_merge(
            [
                'host' => 'localhost',
                'port' => 3306,
                'name' => '',
                'user' => '',
                'pass' => '',
            ],
            isset($dariBerkas['db']) && is_array($dariBerkas['db']) ? $dariBerkas['db'] : []
        );

        // Environment variable menimpa berkas (berguna untuk uji lokal).
        // Khusus 'pass': nilai KOSONG tetap dianggap sah (mis. root tanpa
        // password di server uji) — kalau kosong dianggap tidak diset, password
        // produksi dari berkas akan terpakai dan uji lokal gagal menyambung.
        foreach (['host' => 'SIBER_DB_HOST', 'name' => 'SIBER_DB_NAME', 'user' => 'SIBER_DB_USER', 'pass' => 'SIBER_DB_PASS', 'port' => 'SIBER_DB_PORT'] as $kunci => $env) {
            $nilai = getenv($env);
            if ($nilai === false) {
                continue;
            }
            if ($kunci !== 'pass' && $nilai === '') {
                continue;
            }
            $db[$kunci] = $kunci === 'port' ? (int) $nilai : (string) $nilai;
        }

        $token = getenv('WA_GATEWAY_TOKEN') !== false && getenv('WA_GATEWAY_TOKEN') !== ''
            ? (string) getenv('WA_GATEWAY_TOKEN')
            : (string) ($dariBerkas['token'] ?? '');
        $mulai = getenv('WA_MULAI_DARI') !== false && getenv('WA_MULAI_DARI') !== ''
            ? (string) getenv('WA_MULAI_DARI')
            : (string) ($dariBerkas['mulai_dari'] ?? '2026-09-28 00:00:00');

        $cfg = [
            'db'          => $db,
            'token'       => $token,
            'mulai_dari'  => $mulai,
            'lease_menit' => (int) (getenv('WA_LEASE_MENIT') !== false ? getenv('WA_LEASE_MENIT') : ($dariBerkas['lease_menit'] ?? 45)),
            'max_attempt' => (int) (getenv('WA_MAX_ATTEMPT') !== false ? getenv('WA_MAX_ATTEMPT') : ($dariBerkas['max_attempt'] ?? 3)),
        ];
        return $cfg;
    }
}

$WA_KONFIG = wa_baca_konfig();

if ($WA_KONFIG['db']['name'] === '' && $WA_KONFIG['db']['user'] === '') {
    // Belum dikonfigurasi: beri petunjuk, jangan pakai kredensial tebakan.
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'ok'    => false,
        'error' => 'konfigurasi WA gateway belum ada',
        'solusi' => 'buat berkas config/wa_gateway.php (di luar folder public/) berisi '
            . "['db'=>['host'=>'localhost','port'=>3306,'name'=>'<db>','user'=>'<user>','pass'=>'<pass>'], "
            . "'token'=>'<token aplikasi SIBER di dashboard gateway>'] "
            . 'atau set environment variable SIBER_DB_HOST/SIBER_DB_NAME/SIBER_DB_USER/SIBER_DB_PASS/WA_GATEWAY_TOKEN',
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------ Token gateway -----------------------------------------
if (!defined('WA_GATEWAY_TOKEN')) {
    define('WA_GATEWAY_TOKEN', (string) $WA_KONFIG['token']);
}

// ------------------ Batas tanggal penarikan pesan -------------------------
// Hanya pesan yang dibuat SETELAH tanggal ini yang boleh ditarik gateway.
// Ini mencegah pesan lama yang belum pernah terkirim tiba-tiba dikirim
// sekaligus saat gateway pertama kali dinyalakan.
// Bila pesan lama itu MEMANG mau dikirim, jalankan sekali di phpMyAdmin:
//   UPDATE outbox_wa SET created_at = NOW()
//    WHERE status = 'pending' AND created_at < '2026-09-28 00:00:00';
if (!defined('WA_MULAI_DARI')) {
    define('WA_MULAI_DARI', (string) $WA_KONFIG['mulai_dari']);
}

// ------------------ Sewa klaim & batas percobaan --------------------------
// Baris yang sudah diklaim ('in_queue') tetapi tidak pernah dilaporkan balik
// gateway (crash / putus / mati lampu) dikembalikan ke 'pending' setelah
// WA_LEASE_MENIT menit oleh requeue.php. Bila sesudah WA_MAX_ATTEMPT kali masih
// mandek, baris ditutup sebagai 'failed' supaya tidak berputar selamanya.
if (!defined('WA_LEASE_MENIT')) {
    define('WA_LEASE_MENIT', (int) $WA_KONFIG['lease_menit']);
}
if (!defined('WA_MAX_ATTEMPT')) {
    define('WA_MAX_ATTEMPT', (int) $WA_KONFIG['max_attempt']);
}

// ------------------ Helper koneksi ----------------------------------------
if (!function_exists('wa_pdo')) {
    /**
     * Buka koneksi PDO ke database SIBER.
     */
    function wa_pdo(?array $cfg = null): PDO
    {
        $cfg = $cfg ?? wa_baca_konfig()['db'];
        $port = (int) ($cfg['port'] ?? 3306);
        $dsn  = "mysql:host={$cfg['host']};port={$port};dbname={$cfg['name']};charset=utf8mb4";
        $pdo  = new PDO(
            $dsn,
            (string) $cfg['user'],
            (string) ($cfg['pass'] ?? ''),
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
        // Selaraskan zona waktu sesi MySQL dengan WIB. Tanpa ini, kolom
        // `created_at` (TIMESTAMP) dicocokkan memakai zona waktu server
        // hosting dan perbandingannya dengan WA_MULAI_DARI jadi meleset.
        try {
            $pdo->exec("SET time_zone = '+07:00'");
        } catch (Throwable $e) {
            /* sebagian hosting melarang SET — abaikan, bukan masalah fatal */
        }
        return $pdo;
    }
}

if (!function_exists('wa_json_out')) {
    /**
     * Kirim respons JSON lalu berhenti.
     */
    function wa_json_out(array $payload, int $httpCode = 200): void
    {
        http_response_code($httpCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('wa_cek_token')) {
    /**
     * Tolak request tanpa token yang benar.
     */
    function wa_cek_token(): void
    {
        $token = $_SERVER['HTTP_X_GATEWAY_TOKEN'] ?? '';
        if ($token === '' || !hash_equals(WA_GATEWAY_TOKEN, (string) $token)) {
            wa_json_out(['ok' => false, 'error' => 'token gateway tidak valid'], 401);
        }
    }
}

if (!function_exists('wa_kolom_ada')) {
    /**
     * Cek keberadaan kolom — dipakai supaya berkas ini tetap jalan walaupun
     * wa_gateway_lease_migrasi.sql belum dijalankan di hosting.
     */
    function wa_kolom_ada(PDO $pdo, string $tabel, string $kolom): bool
    {
        static $cache = [];
        $kunci = $tabel . '.' . $kolom;
        if (array_key_exists($kunci, $cache)) {
            return $cache[$kunci];
        }
        try {
            $stmt = $pdo->prepare(
                'SELECT COUNT(*) FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $stmt->execute([$tabel, $kolom]);
            $cache[$kunci] = ((int) $stmt->fetchColumn()) > 0;
        } catch (Throwable $e) {
            $cache[$kunci] = false;
        }
        return $cache[$kunci];
    }
}

if (!function_exists('wa_ref_baris')) {
    /**
     * ID job yang dikirim ke gateway ("ref").
     *
     * Sengaja TIDAK hanya "OUTBOX-<id>": bila tabel outbox_wa pernah dikosongkan
     * (TRUNCATE / hapus semua baris), id mulai dari 1 lagi sedangkan gateway
     * masih menyimpan `ref` lama — pesan baru itu lalu dianggap duplikat dan
     * tidak pernah dikirim. Sidik jari di belakang id membuat ref tiap baris unik.
     */
    function wa_ref_baris(array $row): string
    {
        $sidik = substr(md5($row['id'] . '|' . $row['nomor'] . '|' . $row['created_at']), 0, 8);
        return 'OUTBOX-' . $row['id'] . '-' . $sidik;
    }
}

if (!function_exists('wa_rekap_antrean')) {
    /**
     * Ringkasan jumlah baris per status.
     * @return array<string,int>
     */
    function wa_rekap_antrean(PDO $pdo): array
    {
        $out = [];
        foreach ($pdo->query('SELECT status, COUNT(*) AS jumlah FROM outbox_wa GROUP BY status') as $r) {
            $out[(string) $r['status']] = (int) $r['jumlah'];
        }
        return $out;
    }
}

if (!function_exists('wa_baris_pending')) {
    /**
     * Ambil daftar baris yang layak ditarik TANPA mengubah status (mode intip).
     *
     * @return array<int,array<string,mixed>>
     */
    function wa_baris_pending(PDO $pdo, int $limit): array
    {
        $limit = max(1, min(200, $limit));
        $stmt  = $pdo->prepare(
            "SELECT id, nomor, pesan, status, created_at
               FROM outbox_wa
              WHERE status = 'pending'
                AND created_at >= :mulai
              ORDER BY id ASC
              LIMIT {$limit}"
        );
        $stmt->execute([':mulai' => WA_MULAI_DARI]);
        return $stmt->fetchAll();
    }
}

if (!function_exists('wa_klaim_antrean')) {
    /**
     * KLAIM ATOMIK: ambil pesan pending lalu langsung ubah statusnya menjadi
     * 'in_queue' di dalam satu transaksi (SELECT ... FOR UPDATE + UPDATE).
     *
     * Ini pengganti pola lama "jobs.php hanya membaca, status baru berubah saat
     * gateway melapor balik". Tanpa klaim di sisi ini:
     *   - baris yang sama disajikan ulang tiap siklus -> berisiko dobel kirim;
     *   - baris yang ack-nya gagal/ditolak gateway akan menempati 20 slot
     *     pertama antrean selamanya -> pesan baru TIDAK PERNAH tertarik.
     *
     * @return array<int,array<string,mixed>> baris terklaim
     */
    function wa_klaim_antrean(PDO $pdo, int $limit): array
    {
        $limit      = max(1, min(50, $limit));
        $adaClaim   = wa_kolom_ada($pdo, 'outbox_wa', 'claimed_at');
        $adaAttempt = wa_kolom_ada($pdo, 'outbox_wa', 'attempt');

        $kolom = $adaAttempt ? 'id, nomor, pesan, status, created_at, attempt' : 'id, nomor, pesan, status, created_at';

        $pdo->beginTransaction();
        try {
            $sel = $pdo->prepare(
                "SELECT {$kolom}
                   FROM outbox_wa
                  WHERE status = 'pending'
                    AND created_at >= :mulai
                  ORDER BY id ASC
                  LIMIT {$limit}
                  FOR UPDATE"
            );
            $sel->execute([':mulai' => WA_MULAI_DARI]);
            $rows = $sel->fetchAll();

            if ($rows) {
                $ids = array_map('intval', array_column($rows, 'id'));
                $ph  = implode(',', array_fill(0, count($ids), '?'));

                $set = "status = 'in_queue'";
                if ($adaClaim) {
                    $set .= ', claimed_at = NOW()';
                }
                if ($adaAttempt) {
                    $set .= ', attempt = attempt + 1';
                }

                $upd = $pdo->prepare("UPDATE outbox_wa SET {$set} WHERE id IN ({$ph})");
                $upd->execute($ids);
            }

            $pdo->commit();
            return $rows;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}

if (!function_exists('wa_lepas_klaim_mandek')) {
    /**
     * LEASE WATCHDOG: baris 'in_queue' yang tidak pernah dilaporkan balik
     * gateway dikembalikan ke 'pending' (bisa ditarik lagi); yang sudah
     * kehabisan percobaan ditutup sebagai 'failed' supaya tidak berputar
     * selamanya. Dijalankan oleh requeue.php.
     *
     * @return array{dilepas:int,gagal:int,pesan:string}
     */
    function wa_lepas_klaim_mandek(PDO $pdo): array
    {
        if (!wa_kolom_ada($pdo, 'outbox_wa', 'claimed_at')) {
            return [
                'dilepas' => 0,
                'gagal'   => 0,
                'pesan'   => 'kolom claimed_at belum ada — jalankan wa_gateway_lease_migrasi.sql di hosting',
            ];
        }

        $batas = date('Y-m-d H:i:s', time() - (WA_LEASE_MENIT * 60));

        // Yang sudah kehabisan percobaan ditutup dulu sebagai 'failed' (tidak
        // dikembalikan ke 'pending'), supaya tidak berputar tanpa akhir.
        $sqlGagal = "UPDATE outbox_wa
                        SET status = 'failed'
                      WHERE status = 'in_queue'
                        AND claimed_at IS NOT NULL
                        AND claimed_at < :batas";
        if (wa_kolom_ada($pdo, 'outbox_wa', 'attempt')) {
            $sqlGagal .= ' AND attempt >= ' . WA_MAX_ATTEMPT;
        }
        $stmt = $pdo->prepare($sqlGagal);
        $stmt->execute([':batas' => $batas]);
        $jmlGagal = $stmt->rowCount();

        $stmt = $pdo->prepare(
            "UPDATE outbox_wa
                SET status = 'pending', claimed_at = NULL
              WHERE status = 'in_queue'
                AND claimed_at IS NOT NULL
                AND claimed_at < :batas"
        );
        $stmt->execute([':batas' => $batas]);

        return ['dilepas' => $stmt->rowCount(), 'gagal' => $jmlGagal, 'pesan' => 'ok'];
    }
}
