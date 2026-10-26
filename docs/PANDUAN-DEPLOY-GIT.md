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
`status_siswa = 'nonaktif'` (tidak dihapus, masih bisa diaudit).

Kalau ada nilai untuk kategori yang sama di kedua record, skrip berhenti dan menampilkan
perbandingan isinya (`kolom: dari=... ke=...`), lalu meminta satu keputusan:

```bash
php scripts/merge_siswa.php --dari=ID --ke=ID --nilai=isi   # pilih otomatis: yang isinya lebih lengkap
php scripts/merge_siswa.php --dari=ID --ke=ID --nilai=ke    # selalu pakai nilai record yang dipertahankan
php scripts/merge_siswa.php --dari=ID --ke=ID --nilai=dari  # selalu pakai nilai record duplikat
```

`--nilai=isi` yang paling aman: untuk tiap kategori dipilih baris yang kolom angkanya
terisi (bukan 0/NULL); kalau keduanya sama lengkap, dipakai record yang dipertahankan.
Jangan pakai `--nilai=dari` atau `--nilai=ke` kalau sebagian nilai kosong di salah satu
record — nilai yang terisi bisa tertimpa angka 0.

Tambahkan `--apply` pada perintah yang sama setelah daftar rencana terbaca benar.

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

## 15. Hak akses layanan rapor (tanpa perubahan database)

Mulai commit ini, menu **Layanan Rapor** terbuka untuk tiga role:

- **Admin** (level 1) dan **Waka Kurikulum** (level 5): bisa memilih kelas mana pun
  lewat dropdown di kanan atas kartu, lalu **hanya melihat** nilai, progres mapel,
  dan detail rapor siswa, serta mencetak rapor/peringkat.
- **Walikelas** (level 3): tetap hanya kelas yang diampu, dan satu-satunya yang bisa
  **menginput/mengubah** rapor.

Yang dijaga:

- Tombol **Simpan** dan kolom isian tidak muncul untuk admin/waka; semua field tampil
  `disabled` dan ada label "Mode hanya lihat". Membuka siswa yang belum punya baris
  rapor pun tidak membuat baris baru.
- POST ke `update_terpadu` dari akun admin/waka ditolak **403** walau token CSRF valid,
  jadi tetap aman meski URL/form dipalsukan.
- Walas yang membuka `edit_rapor`, `cetak`, `cetak_peringkat`, atau `detail_progres`
  untuk siswa di kelas lain mendapat **403 Akses ditolak**.
- Daftar kelas di dropdown hanya kelas yang punya siswa aktif pada tahun pelajaran
  rapor yang sedang aktif, jadi kelas kosong/arsip tidak muncul.
- Setelah menyimpan, walikelas dikembalikan ke kelas yang tadi dibuka (`&id_kelas=`).

Deploy: cukup `git pull origin main` di folder aplikasi. Tidak ada tabel/kolom baru.
Uji cepat: login sebagai waka kurikulum -> Layanan Rapor -> ganti kelas di dropdown ->
buka Input pada satu siswa -> Simpan -> harus kembali ke kelas yang sama.

---

## 16. Siswa baru dan baris nilai kategori (tanpa perubahan database)

Dulu baris `nilai` hanya dibuat sekali, yaitu saat guru **membuat kategori**. Siswa yang
diploting ke kelas **setelah** kategori itu ada tidak punya baris nilai, sehingga tidak
muncul di halaman input nilai, rekap, leger, maupun rapor.

Sekarang ada sinkronisasi `Kategori::syncNilaiKelas($id_kelas, $id_tahun)` yang dipanggil
otomatis di dua titik:

1. saat siswa diploting ke kelas (menu **Ploting** → Simpan), dan
2. saat guru membuka halaman **input nilai** sebuah kategori.

Ada juga tombol manual kalau ingin menyapu data lama sekaligus:

- **Sinkronkan Siswa** — di halaman daftar kategori sebuah mapel
  (`Penilaian` → `Detail`). Menyinkronkan satu kelas + satu mapel.
- **Sinkronkan Semua Nilai** — di halaman `Penilaian`, hanya tampil untuk admin.
  Menyapu semua kelas/mapel yang punya kategori.

Keduanya hanya menambah baris yang belum ada, jadi aman ditekan berkali-kali.

Sifatnya idempotent dan hanya menambah:

- hanya membuat baris untuk pasangan kategori–siswa yang belum ada;
- nilai yang sudah diisi tidak diubah, tidak dikosongkan, dan tidak dihapus;
- siswa berstatus nonaktif (keluar/lulus/pindah/…) tidak dibuatkan baris;
- aman dipanggil berkali-kali, tidak menghasilkan baris ganda.

Artinya siswa lama tidak perlu mengisi ulang apa pun, dan siswa baru langsung ikut serta
di semua kategori yang sudah ada. Kategori yang dibuat setelah siswa masuk tetap terisi
lewat proses pembuatan kategori seperti biasa.

Cek di production (read-only):

```sql
-- siswa yang belum punya baris nilai pada kategori kelasnya
SELECT mg.id_kelas, mg.id_tahun_pelajaran, kn.id_kategori, s.nama_siswa
FROM kategori_nilai kn
JOIN mapel_guru mg ON mg.id_mapel_guru = kn.id_mapel_guru
JOIN ploting_siswa ps ON ps.id_kelas = mg.id_kelas AND ps.id_tahun = mg.id_tahun_pelajaran
JOIN siswa s ON s.id_siswa = ps.id_siswa
LEFT JOIN nilai n ON n.id_kategori = kn.id_kategori AND n.id_siswa = ps.id_siswa
WHERE n.id_nilai IS NULL
ORDER BY mg.id_kelas, s.nama_siswa;
```

Kalau query di atas mengembalikan baris, cukup buka halaman input nilai kategori terkait
sekali (atau ploting ulang siswanya) — baris yang kurang akan dibuat otomatis.


## 17. Navigasi desktop dan mobile (bottom menu)

Sidebar dan menu mobile sekarang dibaca dari **satu sumber**: `app/views/layouts/menu.php`.
Menambah/mengubah menu cukup di file itu, supaya tampilan desktop dan mobile tidak
pernah berbeda lagi (dulu ada menu yang muncul di desktop tetapi hilang di mobile).

- **Desktop (>= 1200px)**: sidebar kiri seperti biasa (`layouts/sidebar.php`), dirender
  dari `siber_menu_for_level()`. Di bawah 1200px sidebar Material Dashboard memang
  digeser keluar layar, jadi navigasi mobile dipakai sampai breakpoint itu.
- **Mobile/tablet (< 1200px)**: tidak ada sidebar. Yang tampil bottom bar 5 slot:
  **Beranda · Penilaian · Jurnal · Rapor · Lainnya** (`layouts/mobile_nav.php`),
  dengan CSS `public/assets/css/siber-nav.css` dan JS `public/assets/js/siber-nav.js`.
  - Slot diisi dari daftar prioritas sesuai level; menu yang tidak boleh diakses
    tidak ditampilkan. Role tanpa akses rapor mendapat slot **Rekap Kehadiran**.
  - Tombol **Lainnya** membuka bottom sheet berisi seluruh menu terkelompok
    (Master, Pembelajaran, Layanan Rapor, dst.) plus tombol Keluar.
  - Halaman yang tidak ada di bottom bar otomatis menyorot tombol **Lainnya**.
- **Topbar mobile**: header tipis berisi logo + judul halaman, menggantikan tombol
  hamburger lama. Tidak ada drawer samping lagi.
- `<main>` hanya dibuka satu kali (di `layouts/sidebar.php`), bukan lagi di
  `header.php`, sehingga tidak ada lagi elemen `<main>` bersarang.

Setelah `git pull`, lakukan hard refresh (Ctrl+F5) di HP karena CSS/JS baru
(`?v=1`) bisa tersimpan di cache browser.

## 18. Notifikasi: toast, SweetAlert, dan pengingat struktural

Tanpa perubahan skema database. Semua file di-commit bersama, cukup `git pull`.

### 18.1 Satu pintu notifikasi

- `layouts/header.php` menyedot `$_SESSION['flash']` sekali ke
  `$GLOBALS['siber_flashes']` lalu menghapusnya dari session.
- `layouts/toast.php` memetakan tipe flash:
  - `success`/`sukses`/`berhasil` -> **toast** hijau di pojok kanan atas.
  - `warning`/`peringatan` -> toast kuning.
  - `info` -> toast biru.
  - `error`/`gagal`/`danger`/`no_student` -> **SweetAlert** (modal) karena
    biasanya berisi sebab kegagalan yang perlu dibaca.
- Akibatnya **blok `getFlash()` di dalam view dihapus** (66 blok di 50 file).
  Jangan menambah lagi: cukup `setFlash('success', '...')` di controller.
- `public/assets/js/siber-notify.js` menyediakan `SiberToast.success/error/
  warning/info`, `SiberConfirm({teks, judul, bahaya})`, `SiberDialog(tipe, teks)`.
- `window.alert` dialihkan ke toast; konfirmasi memakai atribut
  `data-confirm="pesan"` (menggantikan `onclick="return confirm(...)"`), dan
  validasi HTML5 (`required`, `pattern`) memunculkan SweetAlert, bukan bubble
  bawaan browser.

### 18.2 Tombol dan tabel

- `btn-primary` -> `btn-success` di **seluruh** `app/views` (termasuk
  `satpam/`), sisa 0.
- `btn-dark` untuk aksi biasa diganti: aksi utama -> `btn-success`, cetak/ekspor
  -> `btn-outline-dark`, konfirmasi kecil -> `btn-outline-secondary`,
  hapus/bersihkan -> `btn-danger`.
- Tombol `Kembali`/`Batal`/`Tutup` yang masih `btn-secondary` solid diubah ke
  `btn-outline-secondary`.
- Ikon Font Awesome (`fa fa-*`, `fas fa-*`) diganti Material Icons Round,
  sisa 0 di seluruh view.
- `columnDefs: width: '...px'` di konfigurasi DataTables (`layouts/footer.php`)
  dihapus supaya kolom tidak saling menekan; `th style="width:..%"` di halaman
  web (bukan cetak/PDF) dihapus, termasuk tabel Rekap Presensi yang total
  lebarnya 154%.
- 8 tabel web yang belum punya wrapper dibungkus `<div class="table-responsive">`.
  Tabel pada view `*cetak*`/`*pdf*` sengaja tidak diubah (layout cetak memang
  lebar tetap).

### 18.3 Pengingat program struktural untuk tenaga kependidikan

- Logika dipusatkan di `app/models/PengingatDeadline.php`:
  - `LEVEL_STRUKTURAL = [5,6,7,10,11,12,13,14,15,16]`.
  - `bolehProgramStruktural()` mengecek level session, lalu `employe.id_level`
    (sumber jabatan resmi), lalu riwayat unggahan `program_struktural`.
- Kartu **selalu** tampil untuk yang berhak, dengan empty state
  "Belum ada deadline program struktural untuk tahun pelajaran ini."
- Halaman Program Struktural: aturan level diambil dari model (bukan daftar
  ganda di controller); admin (level 1) boleh membuka untuk memantau.
- Hasil uji lokal: level 5, 6, 7, 10-16 melihat kartu; level 1-4 tidak.

Setelah `git pull`: hard refresh (Ctrl+F5) karena ada CSS/JS baru `?v=1`.

## 19. Menu Urutkan Siswa (drag & drop) — butuh 1 kolom baru

Fitur: merapikan urutan tampil siswa per kelas. Dulu semua daftar dipaksa
abjad; sekarang sekolah bisa menentukan urutannya sendiri (mis. urut absen
atau urut sesuai keputusan rapat), dan urutan itu dipakai di semua halaman.

### 19.1 Akses
- Admin (level 1), waka kurikulum (5), waka kesiswaan (6): **semua kelas**.
- Walikelas (3): **hanya kelas yang diampu** (`kelas.wali_kelas` = id employe).
- Level lain: menu tidak tampil, POST ditolak 403 (`sec_policy()`).

### 19.2 Database
Satu kolom baru: `ploting_siswa.urutan` (INT NULL). Jalankan sekali:

```
php scripts/migrasi_urutan_siswa.php
```

atau lewat phpMyAdmin:

```sql
ALTER TABLE ploting_siswa
  ADD COLUMN urutan INT NULL DEFAULT NULL AFTER id_tahun,
  ADD INDEX idx_ploting_urut (id_kelas, id_tahun, urutan);
```

Skrip aman diulang (`--cek` hanya melaporkan status). Tidak ada data yang
diubah/dihapus: baris lama `urutan = NULL` tetap tampil dan diurutkan abjad.

### 19.3 Cara pakai
Menu **Master > Urutkan Siswa** → pilih kelas + tahun pelajaran → geser kartu
(handle `⋮⋮`, bisa ke atas/bawah) atau pakai tombol ↑/↓ di HP → **Simpan
Urutan**. Tombol **Kembali ke Abjad** mengosongkan urutan kelas itu.

Siswa baru otomatis masuk di belakang (urutan NULL) sampai diatur manual.

### 19.4 Titik yang ikut urutan manual
Semua query daftar siswa memakai satu pola:

```
ORDER BY (ps.urutan IS NULL) ASC, ps.urutan ASC, s.nama_siswa ASC
```

Dipakai di: ploting/anggota kelas, jurnal & absensi, rekap kehadiran, leger,
input & daftar siswa rapor, prestasi, ekstrakurikuler, konseling, siswa keluar,
kategori nilai/laporan, dan dropdown siswa (`ajax.php?action=get_siswa`).
Query ber-`GROUP BY` memakai `MIN(ps.urutan)`; query ber-`DISTINCT`
(konseling, dropdown ajax) memakai subquery `urutan_tampil` agar tetap aman
untuk `ONLY_FULL_GROUP_BY`.

### 19.5 Uji lokal
- Halaman: 6 siswa urut abjad → simpan urutan terbalik → urutan tersimpan dan
  terbaca ulang di halaman; reset mengembalikan abjad.
- Siswa tanpa urutan tampil paling belakang.
- Walikelas kelas lain → 403; tanpa token CSRF → 419; level 2 → 403.
- Menu tampil untuk level 1/3/5/6, tidak tampil untuk level 2.

Setelah `git pull`: jalankan migrasi, lalu hard refresh browser.

## 20. Dashboard rekap harian (jam masuk, jurnal mengajar, jurnal struktural) — tanpa perubahan database

Halaman utama (`controller=dashboard`) kini menampilkan sampai tiga kartu rekap untuk **hari ini**:

1. **Presensi Harian** — jam masuk/pulang dari finger (`attendance`), status Tepat Waktu / Terlambat N menit / Pulang Cepat N menit. Sumber urutan prioritas: kegiatan lembaga (`kegiatan` + `kegiatan_peserta.pin_guru`) → jadwal kerja (`jadwal`) → jam scan mentah. Tanpa scan sama sekali tetap tampil "Belum melakukan Presensi" / "Alpa" (kecuali hari libur nasional). Sudah scan tapi tidak punya jadwal maupun kegiatan → jam scan tetap ditampilkan dengan catatan **tidak terekap karena tidak terjadwal**.
2. **Jurnal Mengajar Hari Ini** — semua slot `jadwal_pelajaran` hari itu (bukan hanya yang kosong), badge **Sudah** (+ jam input), **Belum** (jam sudah lewat), **Belum waktunya** (jam belum mulai). Satu mapel+kelas = satu jurnal per hari. Kartu tidak muncul bila guru tidak punya jam mengajar hari itu.
3. **Jurnal Struktural Hari Ini** — hanya untuk **Tenaga Kependidikan** (`employe.id_jabatan = 2`). Kartu tetap tampil setiap hari, tetapi **hanya menagih pengisian bila hari ini ada jadwal kerja dan bukan hari libur**: kondisi itu → badge merah *Belum diisi* + tombol Isi sekarang. Tanpa jadwal kerja atau pada hari libur → badge abu *Tidak wajib diisi* + keterangan, tanpa tombol (tetap ada tautan kecil "Tetap isi jurnal (opsional)"). Sudah isi → ringkasan catatan + jumlah program kerja.

### Yang berubah

- Partial baru `app/views/partials/rekap_harian.php`, dipakai 7 dashboard (admin, guru, walikelas, bk, kurikulum, kesiswaan, kaponpes). Blok "Card Presensi" lama (91 baris per view) diganti satu `require`.
- `DashboardController`: tiga method rekap (`rekapPresensiHariIni`, `rekapJurnalMengajarHariIni`, `rekapJurnalStrukturalHariIni`) + `namaHariIndo()`. Rekap bersifat **pribadi** — admin/waka tidak melihat angka guru lain.
- `Presensi::getRingkasanJadwal($pin, $hari)` — MIN/MAX jam kerja dari `jadwal`.
- `Jurnal::getJadwalHariIniStatus($id_guru, $id_tahun, $hari)` — slot mengajar + status jurnal.
- `LevelHelper`: `siber_jabatan()`, `is_jabatan()`, dan `siber_sinkron_jabatan($db)`; sesi login kini menyimpan `id_jabatan`.
- **Penting:** `User::findByUsername()` wajib ikut `SELECT ... id_jabatan` — kalau tidak, AuthController menyimpan `id_jabatan = 0`
  dan semua tenaga kependidikan ditolak dari jurnal struktural. Sesi lama yang terlanjur bernilai 0 akan dilengkapi otomatis
  (`BaseController` + konstruktor `JurnalStrukturalController` memanggil `siber_sinkron_jabatan()` saat nilainya kosong),
  jadi tidak perlu logout paksa. Pemeriksaannya pakai `empty()`, bukan `isset()`.
- Menu `Jurnal Struktural` memakai filter `'jabatan' => [2]` untuk entri **Input Jurnal**; `Rekap Jurnal` tetap untuk admin/waka (`[1,5]`).
- `JurnalStrukturalController` menolak `index/store/history/edit/update` untuk selain Tenaga Kependidikan.

### Bug yang ikut diperbaiki

`Presensi::getKehadiranLengkap()` memakai `strtolower(date('l'))` sedangkan `jadwal.day` menyimpan nama hari **kapital** (`'Monday'`, dari `date('l')` / `DAYNAME()`). Akibatnya pencarian jadwal selalu gagal: pegawai dianggap "Libur Rutin" dan toleransi terlambat jatuh ke default 08:00. Sekarang memakai `date('l')`. Tidak ada data yang diubah.

### Perbedaan nama hari di dua tabel

- `jadwal.day` → **Inggris** kapital: `Sunday … Saturday` (`date('l')`, `DAYNAME(CURDATE())`).
- `jadwal_pelajaran.hari` → **Indonesia**: `Senin … Sabtu` (diisi dari form jadwal).

Jangan menyamakan keduanya; `DashboardController::namaHariIndo()` dipakai untuk memetakan.

### Sebelum `git pull` — cek dulu (read-only)

```sql
-- 1. Pastikan kolom id_jabatan ada dan terisi. Tendik WAJIB id_jabatan = 2,
--    kalau NULL mereka akan kehilangan akses Input Jurnal setelah deploy.
SELECT id_jabatan, COUNT(*) FROM employe GROUP BY id_jabatan;

-- 2. Pastikan nilai hari di jadwal pelajaran memang bahasa Indonesia.
SELECT DISTINCT hari FROM jadwal_pelajaran;
```

Kalau hasil (1) menunjukkan banyak `NULL` pada staf tata usaha, isi dulu `id_jabatan` sebelum deploy — atau tunda deploy dan laporkan.

### Deploy

```bash
cd ~/domains/pondokminggirsari.com/public_html/subdomain/siber
git pull origin main
```

Tidak ada migrasi, tidak ada tabel/kolom baru. Setelah pull: hard refresh (Ctrl+F5) karena ada view dan file JS yang berubah.

### Verifikasi setelah deploy

- Login **guru** → kartu Presensi Harian tampil jam masuk/pulang sesuai finger; kartu Jurnal Mengajar hanya bila ada jam hari itu; menu Jurnal Struktural **tidak** memuat "Input Jurnal".
- Login **tenaga kependidikan** (`id_jabatan = 2`) → kartu Jurnal Struktural tampil; tombol isi membuka form; menu memuat "Input Jurnal".
- Login **admin** → kartu Jurnal Struktural tidak tampil (admin bukan tendik); menu hanya "Rekap Jurnal"; membuka URL `?controller=jurnalStruktural&method=index` dialihkan ke dashboard dengan pesan error.
- Hari libur nasional → kartu presensi menampilkan keterangan libur; kartu jurnal struktural tetap tampil.

---

## 21. Zona waktu: semua tampilan memakai WIB (GMT+7)

**Masalah.** Server hosting (PHP `date.timezone = UTC`, MySQL `@@system_time_zone = UTC`) membuat
setiap `date()` di aplikasi dan setiap `CURRENT_TIMESTAMP`/`NOW()`/`CURDATE()` menghasilkan jam UTC.
Akibatnya jam isi **Jurnal Mengajar** di dashboard tampil mundur 7 jam, dan penilaian "Belum diisi" /
"Alpa" pada kartu presensi memakai jam yang salah.

**Perbaikan (kode, sudah ikut repo).**
- `public/index.php` — `date_default_timezone_set('Asia/Jakarta')` paling awal.
- `config/database.php` — timezone PHP yang sama (untuk skrip CLI/cron yang tidak lewat `index.php`)
  dan `SET time_zone = '+07:00'` pada setiap koneksi PDO, sehingga `NOW()`, `CURDATE()`, dan
  `DEFAULT CURRENT_TIMESTAMP` ikut WIB.
- Tidak ada kolom/tabel baru; tidak ada perubahan pada data finger (`attendance.scan_date` sudah WIB
  dari mesin finger).

**Data lama masih UTC.** Skrip perbaikan menggeser timestamp historis +7 jam:

```bash
php scripts/perbaikan_timezone.php            # hanya memeriksa (aman)
php scripts/perbaikan_timezone.php --apply    # geser +7 jam
```

- Default yang digeser: `jurnal.created_at` dan `jurnal_struktural.created_at` (dua kolom yang
  tampil di dashboard). Tambah kolom lain bila perlu:
  `--tabel=jurnal.created_at,aktivitas.created_at`
- Output mode periksa menampilkan semua kolom `datetime`/`timestamp` di database beserta nilai
  minimum/maksimum, jadi bisa dilihat kolom mana yang masih UTC.
- Idempoten: setiap kolom yang sudah digeser dicatat di tabel `siber_migrasi`, sehingga skrip tidak
  menggeser dua kali. **Jalankan setelah backup database.**
- `jurnal.tanggal` tidak diubah karena mayoritas bernilai `NULL`; dashboard memakai
  `COALESCE(tanggal, DATE(created_at))` yang otomatis ikut WIB setelah `created_at` digeser.

**Verifikasi.**
1. `php scripts/perbaikan_timezone.php` → baris `PHP zona aktif` = `Asia/Jakarta`,
   `MySQL sesi` = `+07:00`, `MySQL NOW()` = `UTC_TIMESTAMP()` + 7 jam.
2. Isi satu jurnal mengajar, lalu dashboard → jam pada baris "Sudah" sama dengan jam dinding WIB
   (bukan 7 jam lebih awal).
3. Kartu presensi: setelah jam pulang, status berubah "Alpa" pada hari yang sama (bukan 7 jam telat).
