# Panduan Deploy SIBER ke Hosting — Versi Git (Langkah demi Langkah)

Panduan ini dibuat untuk dijalankan **dari atas ke bawah**, tanpa perlu paham Git.
Semua perintah di-copy-paste ke **cPanel → Terminal** (kalau tidak ada Terminal: cPanel → Advanced → Terminal, atau SSH dari PuTTY).

Isi panduan:
- Bagian 0: paham dulu 1 menit
- Bagian 1: backup (wajib)
- Bagian 2: buat file rahasia
- Bagian 3: tarik kode baru dengan Git
- Bagian 4: rapikan file & folder
- Bagian 5: cek cron (penjadwal WA)
- Bagian 6: uji coba per peran
- Bagian 7: cek keamanan cepat
- Bagian 8: 30 menit pertama
- Bagian 9: kalau ada masalah
- Bagian 10: rollback (kembali ke kondisi lama)
- Bagian 11: setelah seminggu

---

## 0. Paham dulu (1 menit)

Ada 3 istilah yang akan sering muncul:

1. **Repo** = folder aplikasi di server yang terhubung ke GitHub. Biasanya `~/siber`.
2. **Docroot** = folder yang dibuka publik lewat browser. Untuk aplikasi ini isinya folder `public/` (berisi `index.php`, `assets/`, `uploads/`).
3. **`config/secrets.php`** = file baru berisi password & token. Letaknya **di luar** `public/`, jadi tidak bisa dibuka dari browser.

Alur deploy: **backup → bikin `secrets.php` → `git pull` → uji**.

Kenapa `secrets.php` dulu, bukan `git pull` dulu? Karena kode baru mengambil password database dari file itu. Kalau urutannya dibalik, situs akan menampilkan **"Koneksi database gagal"** selama beberapa menit.

Total waktu: 30–45 menit. Tidak ada perubahan struktur database, jadi data siswa/nilai aman.

---

## 1. Backup (10 menit) — jangan dilewati

### 1a. Backup database

1. cPanel → **phpMyAdmin**.
2. Klik database `u607305378_siber` di kiri.
3. Tab **Export** → metode **Quick** → format **SQL** → klik **Go**.
4. File `.sql` otomatis terunduh. Simpan di komputer, beri nama `siber-db-sebelum-hardening.sql`.

**Cek berhasil:** ukuran file lebih dari 1 MB (database sekolah biasanya beberapa MB).

### 1b. Backup file aplikasi

1. cPanel → **File Manager**.
2. Masuk ke folder repo (biasanya `siber`).
3. Klik kanan folder `app` → **Compress** → **Zip Archive** → **Compress Files**.
4. Ulangi untuk folder `public` dan `config`.
5. Unduh ketiga file `.zip` itu ke komputer.

### 1c. Catat 3 nilai lama (penting, 2 menit)

Di Terminal, jalankan:

```bash
cd ~/siber
grep -nE "define\('(FONNTE_TOKEN|CRON_KEY|GOOGLE_DRIVE[A-Z_]*|API_DEVICE_KEY)'" app/config.php
```

Yang muncul adalah nilai **lama**. Salin ke Notepad:

- `FONNTE_TOKEN` → dipakai di langkah 2
- `CRON_KEY` → **harus sama persis** dengan yang dipakai URL cron sekarang
- `GOOGLE_DRIVE_WEBHOOK_URL`, `GOOGLE_DRIVE_SECRET_KEY`, `GOOGLE_DRIVE_FOLDER_ID` → dipakai di langkah 2

Password database tidak ada di file itu (dulu ada di `config/database.php`). Kalau lupa, buka cPanel → **MySQL Databases** → atau reset password di situ (kalau reset, langsung pakai password baru di langkah 2).

---

## 2. Buat `config/secrets.php` (10 menit)

Ini file kunci. Dibuat **sebelum** `git pull`.

1. cPanel → **File Manager** → masuk folder `config` (sejajar dengan `app` dan `public`, **bukan** di dalam `public`).
2. Klik **+ File**, nama: `secrets.php` → **Create**.
3. Klik kanan `secrets.php` → **Edit** → hapus semua isi → tempel kode di bawah → sesuaikan nilainya.

```php
<?php
// File rahasia SIBER. Jangan di-commit ke Git. Jangan taruh di dalam public/.

if (!defined('FONNTE_TOKEN'))             define('FONNTE_TOKEN', 'ISI_TOKEN_FONNTE');
if (!defined('GOOGLE_DRIVE_WEBHOOK_URL')) define('GOOGLE_DRIVE_WEBHOOK_URL', 'ISI_URL_APPS_SCRIPT');
if (!defined('GOOGLE_DRIVE_SECRET_KEY'))  define('GOOGLE_DRIVE_SECRET_KEY', 'ISI_SECRET_DRIVE');
if (!defined('GOOGLE_DRIVE_FOLDER_ID'))   define('GOOGLE_DRIVE_FOLDER_ID', 'ISI_FOLDER_ID');

// HARUS sama dengan kunci yang sudah dipakai URL cron di cPanel.
if (!defined('CRON_KEY')) define('CRON_KEY', 'ISI_KUNCI_CRON_LAMA');

// Kosongkan dulu supaya mesin fingerprint lama tetap bisa mengirim data.
if (!defined('API_DEVICE_KEY')) define('API_DEVICE_KEY', '');

// Database
if (!defined('SIBER_DB_HOST'))     define('SIBER_DB_HOST', 'localhost');
if (!defined('SIBER_DB_PORT'))     define('SIBER_DB_PORT', '');
if (!defined('SIBER_DB_NAME'))     define('SIBER_DB_NAME', 'u607305378_siber');
if (!defined('SIBER_DB_USERNAME')) define('SIBER_DB_USERNAME', 'u607305378_siber');
if (!defined('SIBER_DB_PASSWORD')) define('SIBER_DB_PASSWORD', 'ISI_PASSWORD_DB');
```

4. **Save**.
5. Klik kanan `secrets.php` → **Change Permissions** → centang hanya **Read** dan **Write** untuk Owner (angka `600`) → **Change**.
6. **Jangan** menambahkan `WHATSAPP_RECIPIENTS` ke file ini. Nomor WA penerima tetap dibaca dari `app/config.php`.

Uji file ini langsung (harus muncul `secrets-ok`):

```bash
php -r 'require "/home/$USER/siber/config/secrets.php"; echo SIBER_DB_PASSWORD !== "" && defined("CRON_KEY") ? "secrets-ok\n" : "secrets-KURANG\n";'
```

Kalau muncul `secrets-KURANG` → ada nilai yang masih kosong, perbaiki dulu sebelum lanjut.

> Kalau repo Anda bukan di `/home/$USER/siber`, ganti path-nya. Cek dengan `cd ~ && ls`.

---

## 3. Tarik kode baru dengan Git (5 menit)

Semua kode baru sudah ada di branch **`main`** di GitHub, jadi cukup `git pull`.

### 3a. Masuk ke folder repo

```bash
cd ~/siber
pwd
git branch --show-current
git log --oneline -1
```

Yang diharapkan: folder `.../siber`, branch `main`, dan commit terakhir **`2bc7196`**.

Kalau baris **`security(hardening)`** sudah muncul, berarti sudah pernah ter-update — lanjut ke langkah 4.

### 3b. Pastikan tidak ada perubahan lokal

```bash
git status
```

Harus muncul **`nothing to commit, working tree clean`**.

Kalau ada baris `modified: ...` (berarti ada file yang pernah diedit langsung di server), simpan dulu:

```bash
git stash
```

File yang di-stash bisa dilihat kapan saja dengan `git stash list`. (Jangan di-`drop` sebelum yakin semuanya baik.)

### 3c. Tarik kode

```bash
git pull origin main
```

Yang diharapkan di akhir output:

```
187 files changed, ...
create mode 100644 app/helpers/Security.php
create mode 100644 public/assets/js/csrf.js
...
```

### 3d. Pastikan versi sudah benar

```bash
git log --oneline -1
ls app/helpers/Security.php app/helpers/Upload.php public/assets/js/csrf.js
```

Yang diharapkan: ketiga file itu **ada** (tidak ada tulisan `No such file`), dan perintah cek ini menjawab **`KODE BARU: terpasang`**:

```bash
grep -q "sec_enforce_policy" public/index.php && echo "KODE BARU: terpasang" || echo "KODE BARU: belum"
```

> Kalau `ls` bilang file tidak ada, berarti docroot/repo Anda bukan di `~/siber`. Cari dengan:
> `find ~ -maxdepth 3 -name Security.php -path "*helpers*" 2>/dev/null`

---

## 4. Rapikan file & folder (5 menit)

`git pull` sudah menghapus file uji yang dulu terdaftar di Git. Yang tersisa biasanya hanya file **log** yang tidak pernah masuk Git. Bersihkan:

```bash
cd ~/siber
rm -f public/*.txt public/test*.php public/hash.php public/sessiontest.php public/teswa.php \
      public/final_test*.php public/API/index.php.template
rm -f public/cron_jobs/*_log.txt public/cron_jobs/.lock_*
ls public/cron_jobs
```

Yang diharapkan muncul di `public/cron_jobs` hanya:

```
.htaccess  _wa_outbox.php  rekap_terlambat.php  rekap_walas.php
reminder.php  reminder_jurnal_struktural.php  reminder_terlambat.php
```

Lalu siapkan folder kerja + permission:

```bash
cd ~/siber
mkdir -p storage backups
chmod 700 sessions
chmod 750 storage backups
chmod 600 config/secrets.php
chmod 644 public/uploads/.htaccess public/cron_jobs/.htaccess
```

Cek `mysqldump` (dipakai tombol Backup):

```bash
which mysqldump
```

Kalau kosong, tombol backup database tidak akan bekerja — hubungi hosting.

---

## 5. Cek Cron / penjadwal WA (5 menit)

Cron di cPanel tetap memanggil 5 URL ini. Yang berubah hanya **cara memeriksa kuncinya**, URL-nya tidak berubah:

- `.../cron_jobs/reminder.php?key=<CRON_KEY>`
- `.../cron_jobs/rekap_walas.php?key=<CRON_KEY>`
- `.../cron_jobs/reminder_terlambat.php?key=<CRON_KEY>`
- `.../cron_jobs/rekap_terlambat.php?key=<CRON_KEY>`
- `.../cron_jobs/reminder_jurnal_struktural.php?key=<CRON_KEY>`

Uji satu, ganti `<CRON_KEY>` dengan nilai asli:

```bash
curl -s -o /dev/null -w "%{http_code}\n" "https://siber.pondokminggirsari.com/cron_jobs/reminder.php?key=<CRON_KEY>"
```

- **200** = bagus.
- **403** = kunci di `config/secrets.php` tidak sama dengan URL. Samakan, lalu ulangi.
- **404** = path cron berbeda; buka cPanel → **Cron Jobs** dan lihat URL yang terpasang.

Uji juga bahwa tanpa kunci ditolak:

```bash
curl -s -o /dev/null -w "%{http_code}\n" "https://siber.pondokminggirsari.com/cron_jobs/reminder.php"
```

Harus **403**. Kalau 200, berarti `public/cron_jobs/.htaccess` belum ada → ulangi langkah 4.

---

## 6. Uji coba per peran (15 menit)

Buka di browser, login ulang (sesi lama memang otomatis keluar — ini normal dan disengaja).

**Admin (level 1)**

| Buka | Yang harus terjadi |
|---|---|
| Dashboard | angka & grafik tampil, tidak ada tulisan error |
| Data Guru → Tambah → Simpan | data muncul di daftar |
| Data Guru → Edit → Simpan | perubahan tersimpan |
| Data Guru → Hapus (pakai data uji) | data hilang, muncul notifikasi |
| Setting → ubah satu nilai → Simpan | nilai tetap tersimpan setelah halaman dimuat ulang |
| Backup → **Buat Backup** | file `.sql` baru muncul di daftar |
| Backup → **Unduh** | file terunduh, ukurannya wajar |
| Cetak satu PDF (mis. surat izin) | PDF terbuka, tulisan rapi |
| Cetak satu Excel (mis. rekap) | file `.xlsx` terbuka di Excel |

**Guru (level 2)**

| Buka | Yang harus terjadi |
|---|---|
| Dashboard guru | tampil normal |
| Jurnal → Tambah → Simpan | tersimpan |
| Jurnal → Edit jurnal sendiri → Simpan | tersimpan |
| Jurnal → Hapus jurnal sendiri | terhapus |
| Presensi kelas → Simpan | kehadiran tersimpan |
| Kegiatan → Unggah foto (JPG) | foto tampil |
| Ketik di URL: `?controller=user&method=index` | **halaman 403 / "tidak berhak"**, bukan daftar user |
| Ketik di URL: `?controller=setting&method=index` | **403**, bukan halaman setting |

**Wali kelas (level 5)**: buka rapor kelas sendiri → simpan satu nilai.
**BK (level 7)**: konseling → tambah catatan + unggah bukti.
**Kesiswaan (level 6)**: kegiatan/prestasi → tambah + unggah foto.
**Mesin fingerprint**: absen sekali, lalu cek di menu presensi apakah record baru masuk.

### Kalau ada form yang menolak dengan "Sesi formulir kedaluwarsa"

Penyebab paling umum: halaman lama masih terbuka dari sebelum deploy. **Refresh (Ctrl+F5)** lalu submit lagi. Kalau tetap muncul di semua form, cek:

```bash
ls -l ~/siber/public/assets/js/csrf.js
curl -s -o /dev/null -w "%{http_code}\n" https://siber.pondokminggirsari.com/assets/js/csrf.js
```

Harus **200**. Kalau 404, file belum ikut ter-pull → ulangi langkah 3.

---

## 7. Cek keamanan cepat (3 menit)

Copy-paste semuanya:

```bash
D=https://siber.pondokminggirsari.com

echo "== header keamanan =="
curl -sI $D/ | grep -iE "x-frame|x-content-type|referrer|permissions|strict-transport"

echo "== file lama harus hilang (harus 404) =="
for p in test_db3.php terlambat_log.txt debug.txt cron_log.txt API/index.php.template "cron_jobs/reminder (1).php"; do
  printf "%-35s " "$p"; curl -s -o /dev/null -w "%{http_code}\n" "$D/$p"
done

echo "== tanpa login harus ditolak =="
printf "ajax.php (harus 401): "; curl -s -o /dev/null -w "%{http_code}\n" "$D/ajax.php?action=get_siswa&id_kelas=1"
printf "cron tanpa key (403): "; curl -s -o /dev/null -w "%{http_code}\n" "$D/cron_jobs/reminder.php"
```

Yang diharapkan:

- Minimal muncul `x-frame-options` dan `x-content-type-options`.
- Semua file lama → `404` (atau `403`).
- `ajax.php` tanpa login → `401`.
- Cron tanpa key → `403`.

---

## 8. 30 menit pertama (pantau)

1. cPanel → **Metrics** → **Errors**.
2. Cari kata: `Fatal`, `Uncaught`, `SQLSTATE`.
3. Kalau ada banyak `CSRF` → biasanya karena pengguna masih memakai tab lama; minta refresh.
4. Kalau muncul `Fatal`/`Uncaught` berkali-kali → rollback (bagian 10).

Alternatif lewat Terminal:

```bash
tail -50 ~/siber/public/error_log 2>/dev/null
ls ~/logs 2>/dev/null | head
```

---

## 9. Kalau ada masalah (cari gejalanya)

| Gejala | Penyebab | Tindakan |
|---|---|---|
| Halaman putih / "Koneksi database gagal" | `config/secrets.php` belum ada atau password salah | Perbaiki `secrets.php` (langkah 2), jangan ubah `config/database.php` |
| Semua form bilang "Sesi formulir kedaluwarsa" | `csrf.js` tidak termuat / cache | Hard refresh, cek URL `assets/js/csrf.js` = 200 |
| Upload foto ditolak | Bukan JPG/PNG/WEBP/GIF, atau > 8 MB | Kecilkan/konversi file. SVG memang ditolak |
| Cron balas 403 | `CRON_KEY` beda dengan URL panel | Samakan nilainya |
| WA tidak terkirim | Token Fonnte salah / saldo habis | Cek `FONNTE_TOKEN` + dashboard Fonnte |
| Guru masih bisa buka halaman admin | `public/index.php` belum ter-update | Jalankan cek `grep -q "sec_enforce_policy" public/index.php && echo terpasang`; kalau "belum", ulangi langkah 3 |
| Tombol Backup gagal | `backups/` tidak writable atau `mysqldump` tidak ada | `chmod 750 backups`, cek `which mysqldump` |
| Halaman 500 setelah login | Cek error log; kirim 20 baris terakhir ke saya | `tail -30 ~/siber/public/error_log` |

---

## 10. Rollback (kembali ke kondisi sebelum hardening)

Dipakai hanya kalau ada masalah serius dan tidak selesai dalam 15 menit.

1. Matikan sementara 5 cron di cPanel → **Cron Jobs** (supaya WA tidak terkirim ganda).
2. Di Terminal:

```bash
cd ~/siber
git reset --hard 2bc7196
git log --oneline -1        # harus 2bc7196
```

3. **Biarkan** `config/secrets.php` (file lama tidak membacanya, jadi tidak mengganggu).
4. Kalau sebelumnya Anda menjalankan `git stash` di langkah 3b: `git stash pop`.
5. Nyalakan cron lagi, uji satu kiriman WA.

Tidak perlu restore database — rilis ini tidak mengubah struktur tabel. Backup database dari langkah 1 hanya jaring pengaman.

---

## 11. Setelah aplikasi stabil (hari berikutnya)

1. **Rotasi token Fonnte**: dashboard Fonnte → buat token baru → ganti `FONNTE_TOKEN` di `config/secrets.php`.
2. **Ganti password database**: cPanel → MySQL Databases → ubah password → perbarui `SIBER_DB_PASSWORD` di `config/secrets.php`.
3. **Kunci API presensi** (opsional): isi `API_DEVICE_KEY` dengan string acak 32 karakter, lalu set header `X-Device-Key` di semua mesin fingerprint. Kalau belum siap, biarkan kosong.
4. **Hapus branch lama**: `git branch -d security/hardening` (opsional, sudah tidak dipakai).
5. Simpan backup terbaru di luar server (Google Drive/eksternal).

---

## Lampiran A — Perintah harian yang berguna

```bash
cd ~/siber

git log --oneline -5          # lihat 5 commit terakhir
git status                    # ada perubahan lokal atau tidak
git diff                      # lihat isi perubahan lokal
git pull origin main          # ambil update berikutnya
git reset --hard 2bc7196      # rollback ke sebelum hardening
```

## Lampiran B — Daftar file yang TIDAK boleh dihapus/ditimpa

- `config/secrets.php` (dibuat sendiri di langkah 2)
- `config/wa_gateway.php` (konfigurasi gateway WhatsApp, letaknya di luar `public/`)
- `public/uploads/` (foto kegiatan, prestasi, bukti konseling)
- `backups/` (hasil backup database)
- `sessions/`, `storage/`

## Lampiran C — Apa saja yang berubah (untuk diketahui)

- Hak akses: setiap halaman admin kini diperiksa; guru/kesiswaan tidak bisa membuka halaman admin lewat URL.
- Form: semua form punya token anti-CSRF otomatis (tanpa perlu diubah tampilannya).
- Upload: hanya gambar/dokumen tertentu, maksimal 8 MB.
- Backup database: disimpan di luar folder publik, hanya admin.
- Kata sandi & token: dipindah dari kode ke `config/secrets.php`.
- File uji/debug dan log berisi data siswa/guru: dihapus dari server.
- Tampilan, menu, dan alur kerja: **tidak berubah**.
