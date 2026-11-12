<?php
/**
 * Halaman error bertema SiBer.
 *
 * Dipakai oleh router (public/index.php) dan pemeriksaan di Security.php supaya
 * semua kegagalan tampil dengan wajah aplikasi, bukan div polos. Tidak memakai
 * database maupun session, jadi aman dipanggil saat bootstrap gagal.
 */

if (!function_exists('render_error_page')) {
    /**
     * @param int         $kode     HTTP status (400/403/404/419/500 ...)
     * @param string      $judul    judul singkat
     * @param string      $pesan    penjelasan untuk pengguna
     * @param string|null $ref      kode rujukan untuk dicocokkan dengan error_log
     * @param bool        $adaSesi  true kalau pengguna sudah login (tampilkan tombol beranda)
     */
    function render_error_page(int $kode, string $judul, string $pesan, ?string $ref = null, bool $adaSesi = false): void
    {
        if (!headers_sent()) {
            http_response_code($kode);
            header('Content-Type: text/html; charset=utf-8');
        }

        $e = static fn ($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        $judul = $e($judul);
        $pesan = $e($pesan);
        $ref   = $ref !== null && $ref !== '' ? $e($ref) : null;

        // Ikon & warna mengikuti jenis kode.
        if ($kode === 404) {
            $ikon = 'map-search-outline';
            $warna = 'linear-gradient(135deg,#7c3aed,#4f46e5)';
        } elseif ($kode === 403) {
            $ikon = 'shield-lock-outline';
            $warna = 'linear-gradient(135deg,#f59e0b,#d97706)';
        } elseif ($kode === 419) {
            $ikon = 'clock-alert-outline';
            $warna = 'linear-gradient(135deg,#f59e0b,#d97706)';
        } elseif ($kode === 400) {
            $ikon = 'alert-circle-outline';
            $warna = 'linear-gradient(135deg,#64748b,#475569)';
        } else {
            $ikon = 'alert-octagon-outline';
            $warna = 'linear-gradient(135deg,#ef4444,#b91c1c)';
        }

        $beranda = $adaSesi
            ? '<a href="index.php?controller=dashboard&method=index" class="btn btn-success mb-0">Kembali ke Beranda</a>'
            : '<a href="index.php?controller=auth&method=login" class="btn btn-success mb-0">Masuk ke Aplikasi</a>';

        $barisRef = $ref !== null
            ? '<p class="ref">Kode rujukan: <strong>' . $ref . '</strong><br><span>Berikan kode ini kepada administrator bila masalah berulang.</span></p>'
            : '';

        echo <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{$judul} | SiBer PPRM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@mdi/font@7.4.47/css/materialdesignicons.min.css" rel="stylesheet">
<link rel="shortcut icon" href="assets/img/favicon2.png" type="image/x-icon">
<style>
  body,html{height:100%;margin:0;background:#f6f7fb;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;}
  .wrap{min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;}
  .card-err{background:#fff;border-radius:16px;box-shadow:0 12px 32px rgba(15,23,42,.10);
            max-width:520px;width:100%;padding:36px 32px;text-align:center;}
  .ikon{width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;
        margin:0 auto 18px;background:{$warna};color:#fff;font-size:30px;box-shadow:0 8px 20px rgba(15,23,42,.18);}
  .kode{display:inline-block;font-size:12px;letter-spacing:.08em;text-transform:uppercase;
        color:#64748b;background:#eef2f7;border-radius:999px;padding:4px 12px;margin-bottom:10px;}
  h1{font-size:20px;font-weight:600;color:#1e293b;margin:0 0 8px;}
  p.pesan{color:#64748b;font-size:14px;line-height:1.65;margin:0 0 22px;}
  p.ref{font-size:12px;color:#94a3b8;margin:22px 0 0;line-height:1.6;}
  .brand{font-size:12px;color:#94a3b8;margin-top:26px;}
</style>
</head>
<body>
  <div class="wrap">
    <div class="card-err">
      <div class="ikon"><i class="mdi {$ikon}"></i></div>
      <div class="kode">Error {$kode}</div>
      <h1>{$judul}</h1>
      <p class="pesan">{$pesan}</p>
      {$beranda}
      {$barisRef}
      <div class="brand">SiBer PPRM &middot; Pondok Minggirsari</div>
    </div>
  </div>
</body>
</html>
HTML;
        exit;
    }
}
