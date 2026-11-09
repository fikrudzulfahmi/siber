# Agen Lokal Presensi (server sekolah)

Kode ini **berjalan di server lokal sekolah** (Windows + XAMPP), *bukan* di hosting.
Hosting hanya menerima data dan menanyakan status.

## Arsitektur setelah perubahan

```
        (1) tarik, hanya di jam sibuk
┌──────────────┐  30s/60s/5m  ┌───────────────┐
│ sync_attend. │ ───────────► │   pull.php    │──► EasyLink FService (:8090) ──► mesin FP
│ (Node)       │              │               │
└──────────────┘              │  insert DB    │
                              │  push hosting │──► https://<hosting>/API/index.php
                              └───────────────┘
        (2) status, tiap 30 menit, arah dibalik
┌──────────────┐  cron 30m    ┌────────────────────────┐
│  HOSTING     │ ───────────► │ ping.php (server lokal)│
│  cron_jobs/  │  X-Token     │ baca DB + cek port     │
│  ping_server │ ◄─────────── │ TIDAK menyentuh        │
│  _lokal.php  │   JSON       │ EasyLink               │
└──────────────┘              └────────────────────────┘
```

## Berkas

| Berkas | Jalan di | Fungsi |
|---|---|---|
| `sync_attendance.js` | server lokal | penjadwal: memanggil `pull.php` sesuai jam sibuk |
| `pull.php` | server lokal | tarik EasyLink → simpan DB → dorong ke hosting (satu request) |
| `ping.php` | server lokal | jawab cron hosting (baca DB + cek port mesin) |
| `config.local.php` | server lokal | token & URL (tidak di-commit) |
| `public/cron_jobs/ping_server_lokal.php` | hosting | cron 30 menit, tulis `server_monitoring` |

## Interval

| Jam | Interval | Alasan |
|---|---|---|
| 06:00–09:00 | 30 detik | jam datang |
| 12:00–15:00 | 30 detik | jam pulang (data menunjukkan scan mulai ~12:30) |
| 09:00–12:00, 15:00–18:00 | 60 detik | aktivitas rendah |
| 18:00–06:00 | 5 menit | nyaris tidak ada scan |

Beban: ~1.224 siklus/hari (sebelumnya 2.880 siklus × 2 request = 5.760 request/hari).

Ubah di `sync_attendance.js` pada array `JADWAL` bila jam sekolah berubah.

## Pemasangan di server lokal

1. Salin `sync_attendance.js`, `pull.php`, `ping.php` ke `C:\xampp\htdocs\finger\`.
2. Salin `config.local.php.example` → `config.local.php`, isi:
   - `ping_token` → hasil `php -r "echo bin2hex(random_bytes(24));"`
   - `host_url` → endpoint API hosting
3. Hentikan service lama, jalankan yang baru (task `Hermes Finger Sync`).
4. Uji manual:
   ```
   curl http://localhost/finger/pull.php
   curl -H "X-Token: <token>" http://localhost/finger/ping.php
   ```

Berkas lama (`fetch_fingerprint_data.php`, `sync_to_host.php`) boleh dibiarkan
sebagai cadangan, tapi **jangan dijalankan bersamaan** — `/scanlog/new` itu antrean
yang habis dikonsumsi siapa pun yang bertanya lebih dulu.

## Pemasangan di hosting

1. Salin `config/finger_local.example.php` → `config/finger_local.php`, isi
   `ping_url` (URL tunnel ke `ping.php`) dan `ping_token` (harus sama).
2. Tambahkan cron:
   ```
   */30 * * * * /usr/local/bin/php ~/siber/public/cron_jobs/ping_server_lokal.php >> ~/siber/public/cron_jobs/ping_log.txt 2>&1
   ```
   atau lewat URL: `https://<hosting>/public/cron_jobs/ping_server_lokal.php?key=<CRON_KEY>`
3. Dashboard `Setting → Monitoring Jaringan` membaca `server_monitoring.last_ping`.
   Ambang "online" sudah diubah dari 90 detik menjadi **35 menit** (30 menit cron +
   toleransi 5 menit) — tanpa ini dashboard akan selalu menampilkan Offline.

## Keamanan

- `ping.php` **wajib** pakai header `X-Token`; query `?token=` dimatikan secara
  default (`allow_query_token => false`) karena bocor ke access log dan log Cloudflare.
- `pull.php` hanya menerima koneksi dari localhost. Kalau terpaksa dibuka lewat
  tunnel, isi `pull_token` dan kirim header `X-Token` — tapi sebaiknya jangan:
  endpoint itu yang menghabiskan antrean presensi.
- Jangan ekspos seluruh `htdocs` lewat tunnel; cukup path `finger/ping.php`.
  Idealnya tambah Cloudflare Access / WAF rule yang hanya mengizinkan IP hosting.
- Token di server lokal dan di hosting harus identik, dan **jangan** di-commit.

## Pemecahan masalah

- `pull.php` balas `{"busy":true}` → ada siklus lain yang belum selesai; normal.
- `ping.php` balas 401 → token tidak sama antara `config.local.php` dan `config/finger_local.php`.
- Dashboard Offline padahal server hidup → cek cron hosting benar-benar jalan
  (lihat `public/cron_jobs/ping_log.txt`), dan pastikan jam server hosting benar.
- Presensi tidak masuk padahal ada scan → cek `pull.php` langsung; kalau
  `{"message":"No data"}` berarti EasyLink memang belum punya antrean baru.
