<?php
/**
 * Security.php — fondasi keamanan terpusat SIBER.
 *
 * Dimuat SEBELUM session_start() oleh public/index.php dan public/ajax.php.
 * Semua fungsi diberi awalan sec_ agar tidak bentrok dengan helper lama.
 */

if (defined('SIBER_SECURITY_LOADED')) {
    return;
}
define('SIBER_SECURITY_LOADED', true);

/** Akar proyek (satu tingkat di atas public/). */
if (!defined('SIBER_ROOT')) {
    define('SIBER_ROOT', dirname(__DIR__, 2));
}

/* -------------------------------------------------------------------------
 * Sesi
 * ---------------------------------------------------------------------- */

function sec_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') {
        return true;
    }
    return isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443;
}

/**
 * Mulai sesi dengan path yang konsisten (dipakai index.php DAN ajax.php)
 * serta atribut cookie yang aman.
 */
function sec_session_start(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $path = SIBER_ROOT . '/sessions';
    if (is_dir($path) && is_writable($path)) {
        ini_set('session.save_path', $path);
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if (sec_is_https()) {
        ini_set('session.cookie_secure', '1');
    }

    session_start();
}

/** Hapus sesi + cookie sesi di sisi klien. */
function sec_session_destroy(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        sec_session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'] ?: '/',
            'domain'   => $p['domain'] ?? '',
            'secure'   => (bool) ($p['secure'] ?? false),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }
    session_destroy();
}

function sec_session_regenerate(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/* -------------------------------------------------------------------------
 * Header keamanan
 * ---------------------------------------------------------------------- */

function sec_send_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header("Content-Security-Policy: frame-ancestors 'self'; object-src 'none'; base-uri 'self'");
    header_remove('X-Powered-By');
    if (sec_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

/* -------------------------------------------------------------------------
 * Escaping output
 * ---------------------------------------------------------------------- */

/** Escape untuk konteks HTML. Dipakai bertahap di view. */
function sec_e($value): string
{
    if ($value === null) {
        return '';
    }
    if (is_array($value) || is_object($value)) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/* -------------------------------------------------------------------------
 * CSRF
 * ---------------------------------------------------------------------- */

function sec_csrf_token(): string
{
    if (empty($_SESSION['_csrf']) || !is_string($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function sec_csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . sec_e(sec_csrf_token()) . '">';
}

function sec_csrf_valid(): bool
{
    $sent = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || $sent === '' || empty($_SESSION['_csrf'])) {
        return false;
    }
    return hash_equals((string) $_SESSION['_csrf'], $sent);
}

/**
 * Tolak request POST tanpa token CSRF yang sah.
 * Request AJAX (X-Requested-With) dibalas JSON agar penanganan di frontend jelas.
 */
function sec_csrf_require(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        return;
    }
    if (sec_csrf_valid()) {
        return;
    }

    http_response_code(419);
    // Catat supaya cepat terlacak bila ada form yang belum membawa token.
    error_log(sprintf(
        '[SIBER CSRF] ditolak %s %s dari %s',
        $_SERVER['REQUEST_METHOD'] ?? '-',
        $_SERVER['REQUEST_URI'] ?? '-',
        sec_client_ip()
    ));
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        || (!empty($_SERVER['HTTP_ACCEPT']) && stripos((string) $_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => 'Sesi formulir kedaluwarsa. Muat ulang halaman lalu coba lagi.']);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><div style="font-family:sans-serif;padding:32px;max-width:520px;margin:auto">'
        . '<h3 style="color:#c0392b">Sesi formulir kedaluwarsa</h3>'
        . '<p>Halaman terlalu lama dibuka atau permintaan tidak berasal dari aplikasi ini. '
        . 'Muat ulang halaman, lalu kirim ulang datanya.</p>'
        . '<p><a href="' . sec_e($_SERVER['HTTP_REFERER'] ?? 'index.php') . '">Kembali</a></p></div>';
    exit;
}

/* -------------------------------------------------------------------------
 * Otorisasi
 * ---------------------------------------------------------------------- */

/** Daftar level pengguna aktif (integer) dari sesi. */
function sec_user_levels(): array
{
    $raw = $_SESSION['user']['level'] ?? null;
    if ($raw === null || $raw === '') {
        return [];
    }
    if (!is_string($raw)) {
        return [(int) $raw];
    }
    $decoded = base64_decode($raw, true);
    $list = $decoded !== false && strpos($decoded, ',') !== false ? explode(',', $decoded) : [$decoded !== false ? $decoded : $raw];
    $out = [];
    foreach ($list as $v) {
        $v = trim((string) $v);
        if ($v !== '' && ctype_digit($v)) {
            $out[] = (int) $v;
        }
    }
    return array_values(array_unique($out));
}

function sec_has_level($levels): bool
{
    $levels = is_array($levels) ? $levels : [$levels];
    $levels = array_map('intval', $levels);
    return (bool) array_intersect(sec_user_levels(), $levels);
}

/** Hentikan eksekusi bila level tidak mencukupi. */
function sec_require_level($levels, string $message = 'Anda tidak memiliki hak akses untuk tindakan ini.'): void
{
    if (sec_has_level($levels)) {
        return;
    }
    http_response_code(403);
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => $message]);
        exit;
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><div style="font-family:sans-serif;padding:32px;max-width:520px;margin:auto">'
        . '<h3 style="color:#c0392b">Akses ditolak</h3><p>' . sec_e($message) . '</p>'
        . '<p><a href="index.php?controller=dashboard&amp;method=index">Kembali ke Beranda</a></p></div>';
    exit;
}

/**
 * Kebijakan controller => level yang boleh.
 * Hanya modul administratif yang dibatasi; modul operasional (jurnal, penilaian,
 * kehadiran, perangkat) tetap seperti sebelumnya agar alur guru tidak berubah.
 */
function sec_policy(): array
{
    return [
        'user'                      => [1],
        'setting'                   => [1],
        'backup'                    => [1],
        'tahunPelajaran'            => [1],
        'activityLog'               => [1],
        'jenisProgramStruktural'    => [1],
        'jenisPerangkat'            => [1],
        'adminRapor'                => [1],
        'kegiatan'                  => [1],
        'deadlineProgramStruktural' => [1, 5],
        'verifikasiProgramStruktural' => [1, 5],
        'deadlinePerangkat'         => [1, 5],
        'verifikasi'                => [1, 5],
        'rekat'                     => [1, 5],
        'presensi'                  => [1],
        'repres'                    => [1],
        'represDebug'               => [1],
        'konseling'                 => [1, 3, 4, 6, 7],
        'rekon'                     => [1, 3, 4, 6, 7],
        'ekstra'                    => [1, 2, 6, 7],
        'prestasi'                  => [1, 2, 6, 7],
    ];
}

/** Method yang selalu butuh level 1 walau controllernya tidak dibatasi. */
function sec_policy_methods(): array
{
    return [
        'tahunPelajaran' => ['activate' => [1], 'store' => [1], 'update' => [1], 'delete' => [1], 'hapus' => [1]],
        'user'           => ['create' => [1], 'store' => [1], 'update' => [1], 'delete' => [1], 'hapus' => [1], 'resetPassword' => [1]],
        'setting'        => ['*' => [1]],
        'backup'         => ['*' => [1]],
        'absenManual'    => ['store' => [1], 'update' => [1], 'delete' => [1], 'hapus' => [1]],
        'izinGuru'       => ['approve' => [1, 5, 6, 7, 11], 'verifikasi' => [1, 5, 6, 7, 11], 'setujui' => [1, 5, 6, 7, 11], 'tolak' => [1, 5, 6, 7, 11]],
    ];
}

/**
 * Escape nilai untuk disisipkan ke dalam blok <script> (JSON aman).
 * Mencegah "</script>" atau karakter khusus memutus konteks skrip.
 */
function sec_js($value): string
{
    $json = json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE);
    return $json === false ? 'null' : $json;
}

/** Berlakukan kebijakan untuk controller+method yang sedang dipanggil. */
function sec_enforce_policy(string $controller, string $method): void
{
    $policy = sec_policy();
    if (isset($policy[$controller])) {
        sec_require_level($policy[$controller]);
    }

    $methods = sec_policy_methods();
    if (isset($methods[$controller])) {
        $rules = $methods[$controller];
        if (isset($rules['*'])) {
            sec_require_level($rules['*']);
        } elseif (isset($rules[$method])) {
            sec_require_level($rules[$method]);
        }
    }
}

/* -------------------------------------------------------------------------
 * Proteksi aksi destruktif lewat GET (CSRF berbasis Origin/Referer)
 * ---------------------------------------------------------------------- */

function sec_same_origin(): bool
{
    $src = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    if (!is_string($src) || $src === '') {
        return false;
    }
    $host = parse_url($src, PHP_URL_HOST);
    $port = parse_url($src, PHP_URL_PORT);
    $self = $_SERVER['HTTP_HOST'] ?? '';
    $selfHost = $self;
    $selfPort = null;
    if (strpos($self, ':') !== false) {
        [$selfHost, $selfPort] = explode(':', $self, 2);
        $selfPort = ctype_digit((string) $selfPort) ? (int) $selfPort : null;
    }
    if (!is_string($host) || $selfHost === '' || strcasecmp($host, $selfHost) !== 0) {
        return false;
    }
    // Port hanya diperiksa bila Referer mencantumkannya.
    return $port === null || $selfPort === null || $port === $selfPort;
}

function sec_require_same_origin(): void
{
    if (sec_same_origin()) {
        return;
    }
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><meta charset="utf-8"><div style="font-family:sans-serif;padding:32px;max-width:520px;margin:auto">'
        . '<h3 style="color:#c0392b">Permintaan ditolak</h3>'
        . '<p>Tindakan ini harus dijalankan dari dalam aplikasi. Buka kembali halaman sebelumnya lalu ulangi.</p>'
        . '<p><a href="index.php?controller=dashboard&amp;method=index">Kembali ke Beranda</a></p></div>';
    exit;
}

/* -------------------------------------------------------------------------
 * Pembatasan percobaan login (berbasis berkas, tanpa dependensi)
 * ---------------------------------------------------------------------- */

function sec_storage_dir(): string
{
    $dir = SIBER_ROOT . '/storage';
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }
    return $dir;
}

function sec_client_ip(): string
{
    return (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

function sec_throttle_file(string $key): string
{
    return sec_storage_dir() . '/throttle_' . hash('sha256', $key) . '.json';
}

function sec_throttle_state(string $key): array
{
    $file = sec_throttle_file($key);
    if (!is_file($file)) {
        return ['count' => 0, 'first' => 0];
    }
    $data = json_decode((string) @file_get_contents($file), true);
    if (!is_array($data) || !isset($data['count'], $data['first'])) {
        return ['count' => 0, 'first' => 0];
    }
    return ['count' => (int) $data['count'], 'first' => (int) $data['first']];
}

/** @return int sisa detik tunggu; 0 bila boleh mencoba */
function sec_throttle_wait(string $key, int $max = 5, int $window = 900): int
{
    $state = sec_throttle_state($key);
    if ($state['count'] < $max) {
        return 0;
    }
    $elapsed = time() - $state['first'];
    return $elapsed >= $window ? 0 : ($window - $elapsed);
}

function sec_throttle_fail(string $key, int $window = 900): void
{
    $state = sec_throttle_state($key);
    if ($state['count'] === 0 || (time() - $state['first']) >= $window) {
        $state = ['count' => 1, 'first' => time()];
    } else {
        $state['count']++;
    }
    @file_put_contents(sec_throttle_file($key), json_encode($state), LOCK_EX);
}

function sec_throttle_reset(string $key): void
{
    @unlink(sec_throttle_file($key));
}
