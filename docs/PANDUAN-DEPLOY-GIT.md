# Panduan Deploy SIBER ke Hosting — Versi Git (Langkah demi Langkah)

Dijalankan dari atas ke bawah. Semua perintah di-copy-paste ke **cPanel → Terminal**
(kalau tidak ada: cPanel → Advanced → Terminal, atau SSH/PuTTY).

**Yang berubah di rilis ini:** pengamanan akses per peran, anti-CSRF di semua form,
upload dibatasi, backup database pindah ke luar folder publik, token/password pindah
ke `config/secrets.php`, file uji & log berisi data siswa dihapus.
**WhatsApp tetap memakai gateway lokal** (bukan Fonnte). Notifikasi izin pegawai
sekarang ikut masuk antrean `outbox_wa`, sama seperti cron.

Perkiraan waktu: 30–45 menit. Tidak ada perubahan struktur database.

---

## 0. Paham dulu (1 menit)

1. **Repo** = folder aplikasi di server, biasanya `~/siber`.
2. **Docroot** = folder yang dibuka browser, yaitu isi folder `public/`.
3. **`config/secrets.php`** = file baru berisi password & kunci. Letaknya **di luar** `public/`.

Urutan deploy: **backup → buat `secrets.php` → `git pull` → uji**.

Kenapa `secrets.php` lebih dulu? Karena kode baru mengambil password database dari
file itu. Kalau dibalik, situs menampilkan **“Koneksi database gagal”** sampai file dibuat.

**Soal WhatsApp:** hosting tidak mengirim WA sendiri. Aplikasi hanya menulis pesan ke
tabel `outbox_wa` (status `pending`), lalu gateway WA di komputer sekolah menariknya
lewat `/wa-gateway/jobs.php`. Jadi tidak ada token Fonnte yang perlu diisi.

---

## 1. Backup (10 menit) — jangan dilewati

**1a. Database**

1. cPanel → **phpMyAdmin** → klik database `u607305378_siber`.
2. Tab **Export** → **Quick** → format **SQL** → **Go**.
3. Simpan file `.sql` di komputer (nama bebas, mis. `siber-sebelum-hardening.sql`).

**1b. File aplikasi**

1. cPanel → **File Manager** → masuk folder repo (`siber`).
2. Klik kanan `app` → **Compress** → **Zip Archive**. Ulangi untuk `public` dan `config`.
3. Unduh ketiga zip ke komputer.

**1c. Catat 2 nilai lama**

```bash
cd ~/siber
grep -nE "define\('(CRON_KEY|GOOGLE_DRIVE[A-Z_]*|BACKUP_CRON_TOKEN)'" app/config.php
```

- `CRON_KEY` → harus **sama persis** dengan kunci di URL cron cPanel.
- `GOOGLE_DRIVE_SECRET_KEY` → dipakai sebagai token URL cron auto backup (`?token=...`).
- `GOOGLE_DRIVE_WEBHOOK_URL`, `GOOGLE_DRIVE_FOLDER_ID` → untuk upload backup ke Drive.

---

## 2. Buat `config/secrets.php` (10 menit)

1. File Manager → folder `config` (sejajar `app`, **bukan** di dalam `public`).
2. **+ File** → nama `secrets.php` → **Create** → klik kanan → **Edit** → tempel isi di bawah.
3. Ganti setiap `ISI_...` dengan nilai asli, lalu **Save**.
4. Klik kanan → **Change Permissions** → `600`.

```php
<?php
// Rahasia SIBER. Jangan di-commit ke Git. Jangan ditaruh di dalam public/.

// Backup otomatis: secret ini sekaligus jadi token URL cron autoBackup (?token=...)
if (!defined('GOOGLE_DRIVE_WEBHOOK_URL')) define('GOOGLE_DRIVE_WEBHOOK_URL', 'ISI_URL_APPS_SCRIPT');
if (!defined('GOOGLE_DRIVE_SECRET_KEY'))  define('GOOGLE_DRIVE_SECRET_KEY', 'ISI_SECRET_DRIVE');
if (!defined('GOOGLE_DRIVE_FOLDER_ID'))   define('GOOGLE_DRIVE_FOLDER_ID', 'ISI_FOLDER_ID');

// Kunci cron: HARUS sama dengan yang sudah dipakai URL cron di cPanel.
if (!defined('CRON_KEY')) define('CRON_KEY', 'ISI_KUNCI_CRON_LAMA');

// Kunci API mesin presensi. Kosongkan saat deploy pertama (mesin lama tetap jalan).
if (!defined('API_DEVICE_KEY')) define('API_DEVICE_KEY', '');

// Database (cPanel -> MySQL Databases)
if (!defined('SIBER_DB_HOST'))     define('SIBER_DB_HOST', 'localhost');
if (!defined('SIBER_DB_PORT'))     define('SIBER_DB_PORT', '');
if (!defined('SIBER_DB_NAME'))     define('SIBER_DB_NAME', 'u607305378_siber');
if (!defined('SIBER_DB_USERNAME')) define('SIBER_DB_USERNAME', 'u607305378_siber');
if (!defined('SIBER_DB_PASSWORD')) define('SIBER_DB_PASSWORD', 'ISI_PASSWORD_DB');
```

**Tidak perlu** mengisi token Fonnte. Kalau di file lama masih ada baris
`FONNTE_TOKEN`, hapus saja — sudah tidak dipakai kode mana pun.

**Cek tanpa menampilkan isi rahasia:**

```bash
php ~/siber/config/secrets.php
```

Harus berakhir: `Semua wajib terisi. Aman untuk lanjut ke git pull.`
Kalau ada `[!] ... BELUM DIISI`, lengkapi dulu.

---

## 3. Tarik kode dengan Git (5 menit)

```bash
cd ~/siber
git status                 # harus "nothing to commit, working tree clean"
git pull origin main       # kalau ada "local changes": git stash dulu
```

Kalau muncul `error: Your local changes ... would be overwritten`, jalankan:

```bash
git stash && git pull origin main
```

Pastikan kode baru benar-benar terpasang:

```bash
grep -q "sec_enforce_policy" public/index.php && echo "KODE BARU: terpasang" || echo "KODE BARU: belum"
ls app/helpers/Security.php app/helpers/Upload.php public/assets/js/csrf.js
```

Kalau `belum` atau file tidak ada, repo Anda bukan di `~/siber`. Cari:

```bash
find ~ -maxdepth 4 -name Security.php -path "*helpers*" 2>/dev/null
```

---

## 4. Rapikan file & folder (5 menit)

`git pull` sudah menghapus file uji yang terdaftar di Git. Sisa file log di server
tidak terkelola Git, jadi hapus manual:

```bash
cd ~/siber
rm -f public/*.txt public/test*.php public/hash.php public/sessiontest.php public/teswa.php \
      public/final_test*.php public/API/index.php.template
rm -f public/cron_jobs/*_log.txt public/cron_jobs/.lock_*
ls public/cron_jobs
```

Yang harus tersisa:

```
.htaccess  _wa_outbox.php  rekap_terlambat.php  rekap_walas.php
reminder.php  reminder_jurnal_struktural.php  reminder_terlambat.php
```

Permission & folder kerja:

```bash
cd ~/siber
mkdir -p storage backups
chmod 700 sessions
chmod 750 storage backups
chmod 600 config/secrets.php
chmod 644 public/uploads/.htaccess public/cron_jobs/.htaccess
which mysqldump        # harus ada, dipakai tombol Backup
```

---

## 5. Periksa gateway WhatsApp (3 menit)

Kredensial gateway **tidak** ada di `secrets.php`. File yang dipakai:
`config/wa_gateway.php` (di luar `public/`) — berisi token yang sama dengan
aplikasi “SIBER” di dashboard gateway.

```bash
ls -l ~/siber/config/wa_gateway.php       # harus ada
curl -s -H "X-Gateway-Token: <token-gateway>" https://siber.pondokminggirsari.com/wa-gateway/status.php | head -c 400
```

Yang diharapkan: JSON berisi jumlah baris `pending` / `sent` / `failed`.
Kalau balas `401`/`403`, token di `config/wa_gateway.php` beda dengan yang dikirim.

Tidak perlu mengubah apa pun di sini kalau gateway sudah berjalan sekarang —
rilis ini hanya menambah jalur notifikasi izin ke antrean yang sama.

---

## 6. Cron: 5 penjadwal + 1 auto backup (5 menit)

Cron yang dipakai (nama berkas tidak berubah):

1. `cron_jobs/reminder.php?key=<CRON_KEY>`
2. `cron_jobs/rekap_walas.php?key=<CRON_KEY>`
3. `cron_jobs/reminder_terlambat.php?key=<CRON_KEY>`
4. `cron_jobs/rekap_terlambat.php?key=<CRON_KEY>`
5. `cron_jobs/reminder_jurnal_struktural.php?key=<CRON_KEY>`
6. Auto backup: `?controller=backup&method=autoBackup&token=<GOOGLE_DRIVE_SECRET_KEY>`

**Perhatikan:** cron nomor 6 memakai parameter **`token`**, bukan `key`. Jangan
diseragamkan — kalau diubah jadi `key`, auto backup akan balas `403 Token tidak valid`.

Uji satu cron WA dan satu auto backup:

```bash
curl -s -o /dev/null -w "reminder: %{http_code}\n" "https://siber.pondokminggirsari.com/cron_jobs/reminder.php?key=<CRON_KEY>"
curl -s -o /dev/null -w "autoBackup: %{http_code}\n" "https://siber.pondokminggirsari.com/?controller=backup&method=autoBackup&token=<GOOGLE_DRIVE_SECRET_KEY>"
curl -s -o /dev/null -w "reminder tanpa key: %{http_code}\n" "https://siber.pondokminggirsari.com/cron_jobs/reminder.php"
```

- `reminder: 200`, `autoBackup: 200`, dan tanpa key `403`.
- Auto backup menyimpan file `.sql` di folder `backups/` (di luar webroot, dipakai
  untuk restore dari panel) dan mengunggah versi **terkompres `.sql.gz`** ke Drive
  (database ~50 MB; versi mentah ditolak Google dengan HTTP 400). Cek folder Drive
  setelah uji — nama file berakhiran `.sql.gz`.

---

## 7. Uji per peran (15 menit)

Login ulang semua akun (sesi lama otomatis berakhir — ini normal).

**Admin (level 1)**

| Buka | Yang harus terjadi |
|---|---|
| Dashboard | angka tampil, tidak ada pesan error |
| Data Guru | tambah → edit → hapus satu data uji |
| Setting | ubah satu nilai → simpan → muat ulang, nilai tetap |
| Backup | **Buat Backup** → file `.sql` muncul → **Unduh** → **Hapus** |
| Presensi manual | simpan satu kehadiran |
| Cetak 1 PDF + 1 Excel | keduanya terbuka normal |

**Guru (level 2)**

| Buka | Yang harus terjadi |
|---|---|
| Jurnal | tambah, edit, hapus jurnal sendiri |
| Presensi kelas | simpan satu kelas |
| Kegiatan | unggah satu foto JPG |
| `?controller=user&method=index` | **403 / "tidak berhak"**, bukan daftar user |
| `?controller=setting&method=index` | **403** |

**Uji notifikasi izin (jalur baru)**

1. Login sebagai guru → menu izin → ajukan satu izin.
2. Notifikasi **tidak** langsung terkirim; ia masuk antrean. Cek:

```bash
curl -s -H "X-Gateway-Token: <token-gateway>" https://siber.pondokminggirsari.com/wa-gateway/status.php | head -c 400
```

Yang diharapkan: jumlah `pending` bertambah 1 (untuk setiap nomor penerima admin).
3. Tunggu gateway menarik antrean (default ≤ 1 menit), lalu status berubah
   `sent` dan WA masuk ke HP penerima.
4. Setujui/tolak izin itu sebagai admin → satu pesan baru masuk antrean untuk
   nomor pegawai.

Kalau `pending` tidak bertambah: cek `tail -20 ~/siber/public/wa-gateway/ack_log.txt`
dan pastikan tabel `outbox_wa` ada.

**Peran lain:** wali kelas (rapor kelas sendiri), BK (konseling + unggah bukti),
kesiswaan (kegiatan + foto), mesin fingerprint (absen sekali → record masuk).

**Kalau form menolak dengan "Sesi formulir kedaluwarsa":** tab lama, Ctrl+F5.
Kalau terjadi di semua form, cek `curl -o /dev/null -w "%{http_code}" https://siber.pondokminggirsari.com/assets/js/csrf.js` → harus `200`.

---

## 8. Cek keamanan cepat (3 menit)

```bash
D=https://siber.pondokminggirsari.com

curl -sI $D/ | grep -iE "x-frame|x-content-type|referrer|permissions|strict-transport"

for p in test_db3.php terlambat_log.txt debug.txt cron_log.txt API/index.php.template "cron_jobs/reminder (1).php"; do
  printf "%-34s " "$p"; curl -s -o /dev/null -w "%{http_code}\n" "$D/$p"
done

printf "ajax tanpa login (401): "; curl -s -o /dev/null -w "%{http_code}\n" "$D/ajax.php?action=get_siswa&id_kelas=1"
printf "cron tanpa key (403):   "; curl -s -o /dev/null -w "%{http_code}\n" "$D/cron_jobs/reminder.php"
```

Harapan: header keamanan muncul; semua file lama `404`/`403`; ajax `401`; cron `403`.

---

## 9. 30 menit pertama

- cPanel → **Metrics → Errors**, cari `Fatal`, `Uncaught`, `SQLSTATE`.
- `CSRF` sesekali = tab lama, normal. Muncul di semua form = `csrf.js` belum termuat.
- Fatal berulang → rollback (bagian 11).
- Cek antrean WA tidak menumpuk: `pending` harus turun setelah gateway jalan.

```bash
tail -30 ~/siber/public/error_log 2>/dev/null
```

---

## 10. Gejala → penyebab → tindakan

| Gejala | Penyebab | Tindakan |
|---|---|---|
| "Koneksi database gagal" / halaman putih | `secrets.php` belum ada atau password salah | Perbaiki `config/secrets.php`, jangan ubah `config/database.php` |
| Semua form "Sesi formulir kedaluwarsa" | `csrf.js` tidak termuat / cache lama | Ctrl+F5; cek URL `assets/js/csrf.js` = 200 |
| Upload foto ditolak | Bukan JPG/PNG/WEBP/GIF atau > 8 MB | Kecilkan/konversi file (SVG memang ditolak) |
| Cron WA balas 403 | `CRON_KEY` beda dengan URL panel | Samakan nilainya |
| Auto backup balas 403 | Parameter diubah jadi `key`, atau `GOOGLE_DRIVE_SECRET_KEY` beda | Pakai `?token=<GOOGLE_DRIVE_SECRET_KEY>` |
| Auto backup balas 500 "webhook belum diisi" | `GOOGLE_DRIVE_WEBHOOK_URL` kosong | Isi di `config/secrets.php` |
| WA tidak sampai | Gateway lokal mati / token gateway beda / antrean menumpuk | Nyalakan gateway, cek `status.php`, cek `ack_log.txt` |
| Notifikasi izin tidak masuk antrean | Tabel `outbox_wa` tidak ada atau nomor kosong | Cek `error_log` (baris `[WA-IZIN]`) |
| Guru masih bisa buka halaman admin | `public/index.php` belum ter-update | Cek `grep -q "sec_enforce_policy" public/index.php && echo terpasang` |
| Tombol Backup gagal | `backups/` tidak writable / `mysqldump` tidak ada | `chmod 750 backups`, cek `which mysqldump` |
| Drive menolak `HTTP 400` | Kiriman base64 terlalu besar (DB > ~35 MB) | Sudah ditangani: file > 4 MB dikirim sebagai `.sql.gz`; pastikan kode terbaru terpasang |

---

## 11. Rollback

1. Matikan sementara semua cron di cPanel → **Cron Jobs**.
2. Terminal:

```bash
cd ~/siber
git reset --hard 2bc7196
git log --oneline -1        # harus 2bc7196
```

3. Biarkan `config/secrets.php` (kode lama tidak membacanya).
4. Kalau tadi `git stash`: `git stash pop`.
5. Nyalakan cron lagi, uji satu kiriman WA.

Tidak perlu restore database — tidak ada perubahan skema.

---

## 12. Setelah stabil (hari berikutnya)

1. **Hapus sisa Fonnte:** hapus baris `FONNTE_TOKEN` dari `config/secrets.php`,
   lalu **revoke** token itu di dashboard Fonnte (sudah tidak dipakai).
2. **Ganti password database:** cPanel → MySQL Databases → ubah → perbarui
   `SIBER_DB_PASSWORD`.
3. **Rotasi `GOOGLE_DRIVE_SECRET_KEY`** (opsional): ganti di Apps Script **dan** di
   `config/secrets.php`, lalu perbarui URL cron auto backup.
4. **Kunci API presensi** (opsional): isi `API_DEVICE_KEY` setelah semua mesin
   fingerprint disiapkan header `X-Device-Key`.
5. Simpan backup terbaru di luar server.

---

## 13. PD Keluar — catat siswa berhenti & kembalikan (2 menit)

Menu ini untuk siswa yang **berhenti, mengundurkan diri, mutasi, atau meninggal**.
Siswa **lulus tetap di menu Ploting** (kelulusan = penyelesaian akademik).

Cara mengeluarkan siswa:

1. Buka **Data Siswa** → klik ikon keluar (kuning) di baris siswa, **atau** menu
   sidebar **PD Keluar** → pilih kelas → pilih siswa.
2. Pilih alasan (`mengundurkan diri`, `mutasi`, `meninggal`, `keluar`), isi tanggal
   dan keterangan, lalu **Simpan**.

Yang terjadi: `siswa.status_siswa` diisi alasan tersebut, sehingga siswa hilang dari
daftar nilai, input nilai, rapor, leger, dan laporan. **Data nilainya tidak dihapus** —
hanya disembunyikan.

Cara mengembalikan (salah tandai / siswa kembali):

1. Buka **PD Keluar** → centang **tampilkan riwayat termasuk yang dibatalkan**
   (atau filter status) → cari nama siswa.
2. Klik **Kembalikan**. Status kembali aktif, nilai dan rapor tampil lagi,
   dan baris riwayat ditandai "dibatalkan" (tidak hilang).

Kelulusan dari Ploting juga tercatat di riwayat ini, jadi salah pilih siswa saat
"Luluskan" bisa dibatalkan dari sini.

Tabel `siswa_keluar` dibuat otomatis saat menu pertama kali dibuka. Bila user
database tidak punya izin `CREATE TABLE`, jalankan `siswa_keluar.sql` lewat
phpMyAdmin sekali saja.

> Jangan pakai tombol hapus (ikon tempat sampah) untuk siswa yang keluar — tombol itu
> menghapus data siswa. Pakai **PD Keluar** supaya riwayat dan nilai tetap tersimpan.

## 14. Menggabungkan siswa yang tercatat dua kali (5 menit)

Gejala: satu nama muncul dua kali di daftar absensi jurnal, daftar nilai, atau rapor,
padahal plotting-nya hanya satu. Penyebabnya record `siswa` dobel (biasanya impor ulang
atau entri manual dengan NISN placeholder seperti `1111111111`).

Cek dulu, jangan langsung hapus:

```sql
SELECT s.id_siswa, s.nama_siswa, s.nisn, s.status, s.status_siswa,
  (SELECT COUNT(*) FROM nilai n WHERE n.id_siswa = s.id_siswa) AS nilai,
  (SELECT COUNT(*) FROM jurnal_kehadiran j WHERE j.id_siswa = s.id_siswa) AS absensi,
  (SELECT COUNT(*) FROM ploting_siswa p WHERE p.id_siswa = s.id_siswa) AS plotting
FROM siswa s
WHERE s.nama_siswa LIKE '%NAMA SISWA%';
```

Record dengan NISN asli dan riwayat terbanyak = yang **dipertahankan**.

```bash
cd ~/domains/pondokminggirsari.com/public_html/subdomain/siber
php scripts/merge_siswa.php --dari=ID_DUPLIKAT --ke=ID_DIPERTAHANKAN            # pratinjau
php scripts/merge_siswa.php --dari=ID_DUPLIKAT --ke=ID_DIPERTAHANKAN --apply    # eksekusi
```

Skrip memindahkan nilai, absensi, izin, konseling, rapor, keanggotaan ekstra/prestasi,
membuang plotting ganda, lalu menandai record duplikat `status = 'Nonaktif'` dan
`status_siswa = 'nonaktif'` (tidak dihapus, masih bisa diaudit). Kalau ada nilai untuk
kategori yang sama di kedua record, skrip **berhenti** dan tidak mengubah apa pun —
kasus itu harus diputuskan manual.

Backup dulu sebelum `--apply`:

```bash
php public/index.php 2>/dev/null; echo "atau lewat tombol Backup di aplikasi"
```

## Lampiran A — Perintah harian

```bash
cd ~/siber
git log --oneline -5
git status
git pull origin main
git reset --hard 2bc7196      # rollback
php config/secrets.php        # cek rahasia lengkap/belum (tidak menampilkan isi)
```

## Lampiran B — Jangan dihapus / ditimpa

`config/secrets.php`, `config/wa_gateway.php`, `public/uploads/`, `backups/`,
`sessions/`, `storage/`.

## Lampiran C — Alur WhatsApp sekarang

```
cron (5 berkas)  ─┐
                  ├─► outbox_wa (status pending) ─► gateway lokal (mode PULL)
notifikasi izin ──┘        ▲                              │
                           └───── ack.php (sent/failed) ◄─┘
```

Artinya: kalau gateway mati, pesan **tidak hilang** — hanya menunggu di antrean.
Notifikasi izin (pengajuan, disetujui, ditolak) memakai jalur yang sama dengan cron.
