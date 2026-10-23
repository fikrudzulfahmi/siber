# Pengerasan Keamanan SIBER

Dokumen ini merangkum perubahan keamanan pada branch `security/hardening`, cara
men-deploy dengan aman ke produksi, dan cara mengujinya per peran.

Semua perubahan bersifat **backward-compatible**: nama controller, method, URL,
dan alur bisnis tidak berubah. Yang berubah hanya cara sistem memeriksa hak
akses, memvalidasi input, dan menyimpan berkas.

## 1. Ringkasan perubahan

| Area | Sebelum | Sesudah |
| --- | --- | --- |
| Autentikasi AJAX | `public/ajax.php` membiarkan blok cek sesi dikomentari | wajib login (`401`), aksi dibatasi daftar putih: `get_mapel`, `get_tp`, `get_siswa_jurnal`, `get_siswa` |
| Otorisasi halaman | hanya 9 dari 47 controller memeriksa level | `sec_enforce_policy()` di front controller, peta level per controller, `403` bila tidak berhak |
| CSRF | 68 form POST tanpa token | token otomatis disuntik `assets/js/csrf.js`; AJAX mengirim `X-CSRF-Token`; tanpa token → `419` |
| Aksi destruktif via GET | bisa dipicu tautan lintas situs | wajib same-origin (`Origin`/`Referer`), jika tidak → `403` |
| Sesi | `session_start()` polos, cookie tanpa proteksi | `HttpOnly`, `SameSite=Lax`, `Secure` saat HTTPS, `use_strict_mode`, `session_regenerate_id()` saat login |
| Login | tanpa batas percobaan | throttle 5 percobaan / 900 detik per IP+username |
| Upload | nama & ekstensi dari user, folder bisa mengeksekusi PHP | `Upload::store()`: daftar putih ekstensi + MIME, nama acak, `.htaccess` penolak eksekusi per folder |
| Backup | dump `.sql` di `public/backups/`, bisa diunduh user login biasa | dump di `backups/` (di luar webroot), `.htaccess` deny, hanya level 1, `realpath` containment, token `hash_equals` |
| API presensi | tanpa autentikasi, kredensial DB literal, SQLi | kredensial dari `config/database.php`, prepared statement, opsional `X-Device-Key`, error tidak ditampilkan |
| Kredensial | literal di `app/config.php`, `cron_jobs/*.php` | `config/secrets.php` (di luar webroot) + `config/secrets.example.php` |
| Cron | kunci di URL GET dibandingkan `!==` | `hash_equals()` terhadap konstanta dari `config/secrets.php` |
| Eksposur berkas | `.sql`, `.log`, `.template`, `.ini`, sesi, dump | diblokir `.htaccess`; 20 berkas uji/debug dihapus; `sessions/sess_*` keluar dari Git |
| Header | `X-Powered-By` bocor | HSTS, `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy` |
| Error | `display_errors=1` dipaksa | `display_errors=0`, `log_errors=1` |
| Ekspor | rumus Excel bisa disuntik dari nama siswa | sel teks memakai `setCellValueExplicit(TYPE_STRING)` |
| PDF | Dompdf `isRemoteEnabled=true` | dimatikan (cegah SSRF) |

## 2. Berkas baru

- `app/helpers/Security.php` — sesi, header, CSRF, throttle, escaping, kebijakan level.
- `app/helpers/Upload.php` — validasi & penyimpanan unggahan.
- `public/assets/js/csrf.js` — penyuntik token untuk form dan AJAX.
- `config/secrets.example.php` — contoh berkas rahasia (tanpa nilai asli).
- `backups/` — tujuan dump database (dibuat otomatis, `0750`, di luar webroot).

## 3. Sebelum deploy

1. Backup: dump database + salin seluruh `public/` dan `app/` ke folder bertanggal.
2. Pastikan `mysqldump` tersedia bagi user PHP (`which mysqldump`).
3. Siapkan `config/secrets.php` di server (di luar `public/`), isi:
   `FONNTE_TOKEN`, `GOOGLE_DRIVE_*`, `CRON_KEY`, `API_DEVICE_KEY`.
4. Catat nilai `CRON_KEY` lama untuk membandingkan dengan URL cron yang terpasang.
5. Deploy di luar jam KBM. Perkiraan waktu uji: 10–15 menit.

## 4. Urutan deploy yang disarankan

**Tahap 1 — perubahan aman**

- Unggah: `app/helpers/Security.php`, `app/helpers/Upload.php`, `public/.htaccess`,
  `public/uploads/.htaccess`, `config/database.php`, `public/API/index.php`,
  `app/controllers/BackupController.php`, `app/controllers/SettingController.php`,
  `app/controllers/KategoriController.php`, `app/controllers/EkstraController.php`.
- Hapus berkas uji/debug yang sudah tidak ada di repo.
- Uji: login, dashboard, buka satu halaman per peran, unggah satu foto kegiatan.

**Tahap 2 — tampilan & CSRF**

- Unggah seluruh `app/views/`, `public/assets/js/csrf.js`, `public/index.php`,
  `public/ajax.php`, `app/controllers/AuthController.php`, `app/helpers/LevelHelper.php`.
- Uji: semua form utama (tambah/edit/hapus) per peran; pastikan tidak muncul
  "Sesi formulir kedaluwarsa" pada pemakaian normal.
- Catatan: pengguna yang sedang login akan diminta login ulang sekali
  (`use_strict_mode`).

**Tahap 3 — rahasia & cron**

- Unggah `config/secrets.php`, `app/config.php`, `public/cron_jobs/*.php`.
- Pastikan URL cron yang terpasang di panel hosting memakai `?key=` dengan nilai
  yang sama seperti `CRON_KEY`, atau jalankan lewat CLI.
- Uji: panggil satu cron manual, cek log, pastikan tidak ada `Unauthorized`.

## 5. Rollback

1. Hentikan sementara cron.
2. Timpa `app/`, `public/`, `config/` dari salinan bertanggal.
3. Kembalikan `public/.htaccess` dan hapus `public/uploads/.htaccess` bila perlu.
4. Pulihkan database hanya bila terjadi perubahan skema (tidak ada di rilis ini).

## 6. Checklist uji per peran

- **Administrator (level 1)**: dashboard, data guru/siswa/kelas, setting, backup
  (buat, unduh, hapus), presensi manual, jurnal, rapor.
- **Guru (level 2)**: dashboard, jurnal (tambah/edit/hapus milik sendiri), izin,
  presensi kelas, unggah foto kegiatan. Pastikan menu admin tidak bisa dibuka
  lewat URL langsung (`403`).
- **Wali kelas (level 5)**: rapor kelas sendiri, rekap, izin.
- **BK (level 7)**: konseling (tambah, tindak lanjut, unggah bukti).
- **Kesiswaan (level 6)**: kegiatan, prestasi, pelanggaran.
- **Perangkat presensi**: kirim satu record ke `/API/index.php`; bila
  `API_DEVICE_KEY` diisi, mesin harus mengirim header `X-Device-Key`.

## 7. Catatan operasional

- `stock`/`storage` throttle login ditulis di `storage/` (di luar webroot).
- Folder `sessions/` sebaiknya `0700` dan tidak pernah masuk Git.
- Bila ada tautan "hapus" yang dibuka dari luar situs (mis. dari WhatsApp),
  sistem menolak dengan halaman "Permintaan ditolak" — ini disengaja.
- `API_DEVICE_KEY` kosong = mode kompatibel (mesin lama tetap jalan). Isi hanya
  setelah semua mesin presensi siap mengirim header.
