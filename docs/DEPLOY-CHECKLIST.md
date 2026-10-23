# Checklist Deploy Keamanan SIBER ke Hosting

Branch: `security/hardening` (6 commit di atas `main`)
Produksi: `https://siber.pondokminggirsari.com` — cPanel + LiteSpeed, PHP 8.2.34, MySQL `u607305378_siber`
Estimasi waktu: 25–40 menit (di luar jam KBM). Tidak ada perubahan skema database.

Ikuti berurutan. Centang satu per satu. Jangan lompat ke langkah 4 sebelum langkah 2 selesai.

---

## 0. Cek dulu tata letak folder di server (2 menit)

Buka cPanel → Terminal (atau SSH), lalu:

```bash
ls -d ~/siber ~/public_html 2>/dev/null
ls ~/siber 2>/dev/null | head
```

- **Kasus A (paling mungkin)**: repo ada di `~/siber`, docroot domain diarahkan ke `~/siber/public`.
  Semua perintah di bawah memakai `~/siber`.
- **Kasus B**: isi `public/` sudah dipindah ke `~/public_html`, dan `app/`, `config/` ada di `~`.
  Ganti setiap `~/siber/public/...` menjadi `~/public_html/...`, dan `~/siber/app/...` menjadi `~/app/...`.

Kalau ragu, lihat docroot: cPanel → Domains → kolom Document Root.

---

## 1. Backup (wajib, 5 menit)

- [ ] cPanel → phpMyAdmin → pilih DB `u607305378_siber` → **Export** → format SQL → **Go** → simpan file `.sql` ke komputer.
- [ ] cPanel → File Manager → masuk `~/siber` (atau `~`) → pilih folder `app`, `public`, `config` → **Compress** → `Zip Archive` → unduh zip-nya.
- [ ] Catat isi `app/config.php` yang lama (token Fonnte, Google Drive) di catatan aman — dipakai di langkah 2.
- [ ] Catat nilai `CRON_KEY` lama (dipakai di URL cron) — ada di `app/config.php` baris `define('CRON_KEY', ...)`.

Jangan lanjut kalau backup belum ada di komputer.

---

## 2. Buat `config/secrets.php` di server (SEBELUM upload kode)

Ini langkah paling kritis: `config/database.php` versi baru **tidak punya** kredensial literal, jadi kalau file ini belum ada, situs langsung "Koneksi database gagal".

- [ ] File Manager → masuk folder `config` (sebelah `public`, **bukan** di dalam `public`).
- [ ] File baru: `secrets.php`, isi seperti di bawah, ganti bagian `<...>` dengan nilai asli:

```php
<?php
// Kredensial SIBER - JANGAN di-commit, jangan taruh di dalam public/.

if (!defined('FONNTE_TOKEN'))            define('FONNTE_TOKEN', '<token Fonnte>');
if (!defined('GOOGLE_DRIVE_WEBHOOK_URL')) define('GOOGLE_DRIVE_WEBHOOK_URL', '<url apps script>');
if (!defined('GOOGLE_DRIVE_SECRET_KEY'))  define('GOOGLE_DRIVE_SECRET_KEY', '<secret drive>');
if (!defined('GOOGLE_DRIVE_FOLDER_ID'))   define('GOOGLE_DRIVE_FOLDER_ID', '<folder id>');

if (!defined('CRON_KEY'))       define('CRON_KEY', '<kunci cron yang SUDAH dipakai di panel>');
if (!defined('API_DEVICE_KEY')) define('API_DEVICE_KEY', ''); // kosong = mesin presensi lama tetap jalan

if (!defined('SIBER_DB_HOST'))     define('SIBER_DB_HOST', 'localhost');
if (!defined('SIBER_DB_PORT'))     define('SIBER_DB_PORT', '');
if (!defined('SIBER_DB_NAME'))     define('SIBER_DB_NAME', 'u607305378_siber');
if (!defined('SIBER_DB_USERNAME')) define('SIBER_DB_USERNAME', 'u607305378_siber');
if (!defined('SIBER_DB_PASSWORD')) define('SIBER_DB_PASSWORD', '<password DB>');
```

- [ ] Permission file: klik kanan → Change Permissions → `600`.
- [ ] **Jangan** menambahkan `WHATSAPP_RECIPIENTS` di sini. Nomor penerima tetap memakai daftar default di `app/config.php`.
- [ ] `CRON_KEY` **harus sama** dengan yang lama, supaya URL cron di panel tidak perlu diubah.

Uji cepat lewat Terminal:

```bash
php -r 'require "/home/<USER>/siber/config/secrets.php"; echo (defined("SIBER_DB_NAME") && SIBER_DB_PASSWORD!=="") ? "secrets-ok\n" : "secrets-KURANG\n";'
```

---

## 3. Deploy kode (5 menit)

Pilih satu cara.

**Cara A — Git (disarankan)**

```bash
cd ~/siber
git status                 # pastikan bersih, tidak ada perubahan lokal
git fetch origin
git checkout security/hardening
git pull origin security/hardening
git log --oneline -6       # harus muncul bcdb625 di paling atas
```

**Cara B — Upload zip** (kalau Git di server tidak tersedia)

- [ ] Unduh branch `security/hardening` sebagai ZIP dari GitHub → uji di komputer → extract.
- [ ] Upload via File Manager lalu extract **menimpa** folder `app`, `public`, `config`, `docs`.
- [ ] **Jangan** hapus/overwrite: `public/uploads/`, `config/secrets.php`, `config/wa_gateway.php`, `backups/`, `sessions/`.

Setelah deploy, pastikan yang ini ada di server (kalau tidak ada, aplikasi error):

- [ ] `app/helpers/Security.php`
- [ ] `app/helpers/Upload.php`
- [ ] `public/assets/js/csrf.js`
- [ ] `public/uploads/.htaccess`
- [ ] `public/cron_jobs/.htaccess`
- [ ] `config/secrets.example.php`

---

## 4. Hapus file lama yang sudah tidak ada di repo (5 menit)

File-file ini dulu publik dan berisi data sensitif. Repo baru sudah menghapusnya, tapi kalau deploy pakai cara B atau folder lama tidak tertimpa, cek manual dan hapus:

```bash
cd ~/siber/public
rm -f test.php test_db.php test_db2.php test_db3.php test_db4.php testlogin.php \
      hash.php sessiontest.php teswa.php testFonnte.php \
      final_test_1.php final_test_2.php "final_test-2.php" test_keterlambatan.php \
      debug.txt debug_log.txt cron_log.txt terlambat_log.txt test_terlambat_log.txt \
      API/index.php.template

cd ~/siber/public/cron_jobs
rm -f "reminder (1).php" reminder_repo.php rekap_terlambat_copy.php rekap_terlambat_test.php \
      tes.php tes_keterlambatan.php testFonnte.php _reminder.php _rekap_terlambat.php \
      *_log.txt .lock_*
```

- [ ] Setelah itu `ls public/cron_jobs` hanya berisi: `_wa_outbox.php`, `rekap_terlambat.php`, `rekap_walas.php`, `reminder.php`, `reminder_jurnal_struktural.php`, `reminder_terlambat.php`, `.htaccess`.

---

## 5. Permission & folder (3 menit)

```bash
cd ~/siber
mkdir -p storage backups
chmod 700 sessions 2>/dev/null
chmod 750 storage backups   # storage dipakai throttle login/CSRF
chmod 600 config/secrets.php
chmod 644 public/uploads/.htaccess public/cron_jobs/.htaccess
```

- [ ] `storage/` bisa ditulis user PHP (dipakai throttle login & CSRF).
- [ ] `backups/` bisa ditulis user PHP dan `mysqldump` tersedia (`which mysqldump`).
- [ ] `sessions/` ada di luar `public/` dan tidak bisa diakses lewat web.

---

## 6. Pastikan URL cron tetap jalan (3 menit)

Cron di panel memanggil 5 skrip ini (path bisa berbeda tergantung Kasus A/B):

- [ ] `.../public/cron_jobs/reminder.php?key=<CRON_KEY>`
- [ ] `.../public/cron_jobs/rekap_walas.php?key=<CRON_KEY>`
- [ ] `.../public/cron_jobs/reminder_terlambat.php?key=<CRON_KEY>`
- [ ] `.../public/cron_jobs/rekap_terlambat.php?key=<CRON_KEY>`
- [ ] `.../public/cron_jobs/reminder_jurnal_struktural.php?key=<CRON_KEY>`

Uji satu manual dari Terminal:

```bash
curl -s "https://siber.pondokminggirsari.com/cron_jobs/reminder.php?key=<CRON_KEY>" | head -5
```

- [ ] Respons bukan `403` / bukan halaman login.
- [ ] Tanpa `?key=` hasilnya harus `403` (bukti proteksi jalan).
- [ ] Cek balasan WA untuk satu notifikasi (reminder jurnal paling cepat terlihat).

---

## 7. Uji fungsional per peran (10–15 menit)

Login ulang semua akun (sesi lama otomatis tidak berlaku).

**Administrator**

- [ ] Buka dashboard — tidak ada error, angka tampil.
- [ ] Data guru: tambah → edit → hapus satu data uji.
- [ ] Data siswa & kelas: buka daftar, cari satu siswa.
- [ ] Setting: simpan satu perubahan (mis. jam masuk), lalu buka lagi nilainya tersimpan.
- [ ] Backup: **Buat Backup** → file `.sql` muncul di daftar → **Unduh** berhasil → **Hapus** berhasil.
- [ ] Presensi manual: simpan satu kehadiran.
- [ ] Cetak satu PDF (izin atau rapor) dan satu Excel (rekap) — keduanya terbuka normal.

**Guru (level 2)**

- [ ] Dashboard guru tampil.
- [ ] Jurnal: tambah, edit, hapus **jurnal milik sendiri**.
- [ ] Presensi kelas: isi satu kelas, simpan.
- [ ] Unggah satu foto kegiatan (JPG/PNG) — berhasil dan tampil.
- [ ] Buka paksa `?controller=user&method=index` → harus halaman **403 / "tidak berhak"**, bukan daftar user.
- [ ] Buka paksa `?controller=setting&method=index` → sama, ditolak.

**Wali kelas (level 5)**

- [ ] Rapor: buka kelas sendiri, simpan satu nilai.

**BK (level 7) / Kesiswaan (level 6)**

- [ ] Konseling: tambah catatan + unggah bukti.
- [ ] Kegiatan/prestasi: tambah satu data + unggah foto.

**Perangkat presensi (fingerprint)**

- [ ] Mesin tetap bisa kirim absensi (lihat apakah ada record baru di menu presensi).
- [ ] Kalau `API_DEVICE_KEY` dibiarkan kosong, mesin lama tetap diterima.

---

## 8. Verifikasi keamanan cepat (3 menit)

```bash
# header keamanan
curl -sI https://siber.pondokminggirsari.com/ | grep -iE "x-frame|x-content-type|referrer|permissions|strict-transport"

# file lama harus hilang
for p in test_db3.php terlambat_log.txt debug.txt cron_log.txt API/index.php.template; do
  echo -n "$p -> "; curl -s -o /dev/null -w "%{http_code}\n" "https://siber.pondokminggirsari.com/$p"
done

# cron tanpa kunci harus 403
curl -s -o /dev/null -w "cron tanpa key: %{http_code}\n" "https://siber.pondokminggirsari.com/cron_jobs/reminder.php"

# ajax tanpa login harus 401
curl -s -o /dev/null -w "ajax anon: %{http_code}\n" "https://siber.pondokminggirsari.com/ajax.php?action=get_siswa&id_kelas=1"
```

- [ ] Header muncul (minimal `x-frame-options` dan `x-content-type-options`).
- [ ] Semua file lama → `404` (atau `403`).
- [ ] Cron tanpa key → `403`.
- [ ] Ajax anon → `401`.

---

## 9. Pantau 30 menit pertama

- [ ] cPanel → Metrics → **Errors**, atau `tail -50 ~/siber/public/error_log` / `~/logs/*error*`.
- [ ] Cari kata: `Fatal`, `Uncaught`, `CSRF`, `SQLSTATE`.
- [ ] `Fatal`/`Uncaught` = rollback (langkah 11). `CSRF` sesekali dari tab lama = normal, minta pengguna refresh.
- [ ] Pastikan tidak ada laporan "Sesi formulir kedaluwarsa" berulang saat pemakaian normal.

---

## 10. Setelah stabil (hari berikutnya)

- [ ] Rotasi token Fonnte (dashboard Fonnte → buat token baru → simpan ke `config/secrets.php`).
- [ ] Ganti password DB `u607305378_siber` (cPanel → MySQL Databases) → perbarui `SIBER_DB_PASSWORD` di `config/secrets.php`.
- [ ] Kalau semua mesin presensi sudah siap header: isi `API_DEVICE_KEY` dengan string acak panjang, lalu set header `X-Device-Key` di mesin.
- [ ] Kalau sudah yakin: gabungkan branch ke `main` (`git checkout main && git merge security/hardening && git push`).
- [ ] Unduh satu backup terbaru dan simpan di luar server.

---

## 11. Rollback (kalau ada masalah)

1. Hentikan sementara 5 cron di panel (supaya tidak mengirim WA ganda).
2. `cd ~/siber && git checkout main` (cara A) **atau** extract zip backup menimpa `app/`, `public/`, `config/` (cara B).
3. `config/secrets.php` **biarkan** — file lama tetap jalan karena `app/config.php` versi lama tidak membacanya. Kalau cara B menimpa `app/config.php` lama, token Fonnte kembali ke nilai literal lama (masih ada di backup).
4. Hapus `public/uploads/.htaccess` dan `public/cron_jobs/.htaccess` **hanya** kalau upload/cron bermasalah.
5. Tidak perlu restore database — rilis ini tidak mengubah skema.
6. Nyalakan cron lagi, uji satu kiriman WA.

---

## 12. Yang TIDAK boleh dilakukan

- Jangan menaruh `config/secrets.php`, `backups/`, atau `sessions/` di dalam `public/`.
- Jangan menghapus `public/uploads/` (berisi foto kegiatan/prestasi/konseling).
- Jangan mengubah `CRON_KEY` sebelum URL cron di panel ikut diubah.
- Jangan mengisi `API_DEVICE_KEY` sebelum semua mesin presensi disiapkan.
- Jangan menjalankan `git checkout`/upload saat jam KBM berlangsung.

---

## Kalau ada yang gagal (gejala → penyebab → tindakan)

- **"Koneksi database gagal"** → `config/secrets.php` belum ada / `SIBER_DB_*` salah → perbaiki file, jangan ubah `config/database.php`.
- **Semua form balas "Sesi formulir kedaluwarsa"** → `csrf.js` tidak termuat (404) atau cache browser → pastikan `public/assets/js/csrf.js` ada, hard refresh (Ctrl+F5).
- **Upload gagal "Tipe file tidak diizinkan"** → hanya JPG/JPEG/PNG/WEBP/GIF dan PDF/DOC/DOCX/XLS/XLSX/PPT/PPTX yang diterima, maksimal 8 MB (SVG sengaja ditolak) → sesuaikan jenis file; jangan matikan `Upload.php`.
- **Cron balas 403** → `CRON_KEY` di `config/secrets.php` beda dengan URL panel → samakan.
- **WA tidak terkirim** → cek `FONNTE_TOKEN` di `config/secrets.php` dan saldo Fonnte.
- **Guru bisa buka halaman admin** → `public/index.php` belum tertimpa versi baru → ulangi langkah 3, cek `git log`.
- **Backup tidak bisa dibuat** → `backups/` tidak writable atau `mysqldump` tidak ada → `chmod 750 backups`, cek `which mysqldump`.
