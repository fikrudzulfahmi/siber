# Rekap Perbaikan Aplikasi SIBER

Dokumen ini merangkum seluruh perubahan kode pada aplikasi **SIBER** sejak repositori di-clone ke server ini sampai commit terakhir.

| Keterangan | Nilai |
|---|---|
| Repositori | `~/siber` — GitHub `fikrudzulfahmi/siber` (branch `main`) |
| Aplikasi produksi | `https://siber.pondokminggirsari.com` (cPanel/Hostinger, docroot `public/`) |
| Clone ke server | 16 Oktober 2026 12:34 UTC (commit dasar `0242a2f`) |
| Commit terakhir saat rekap | 28 Oktober 2026 23:04 — `c043ad2` |
| Jumlah commit setelah clone | 45 commit |
| Total perubahan | 256 file, +12.673 / −9.368 baris |

Semua perubahan bersifat *backward-compatible*: tidak ada tabel atau kolom yang dihapus, data historis tetap disimpan, dan perubahan tampilan tidak mengubah struktur data lama.

---

## 1. Ringkasan per area

| Area | Jumlah commit | Inti perubahan |
|---|---|---|
| Keamanan aplikasi (hardening) | 6 | RBAC terpusat, CSRF, XSS escape, kredensial keluar dari kode, IDOR, upload/backup/API aman |
| Integrasi WhatsApp (gateway) | 3 | Antrean outbox atomik, anti-dobel, requeue, status, kredensial di luar webroot, lepas Fonnte |
| Backup & Drive | 1 | Backup > 4 MB dikirim sebagai `.sql.gz`, helper upload tunggal |
| Data siswa & nilai | 6 | Filter siswa non-aktif, PD Keluar, merge siswa duplikat, backfill nilai siswa baru, sinkronkan siswa |
| Rapor & laporan | 6 | Akses lihat semua kelas untuk admin/waka, mode read-only, peringkat per tahun, sel kosong `-` |
| UI, notifikasi, responsif | 5 | Toast + SweetAlert, ikon Material, tabel/tombol responsif, konfirmasi submit |
| Navigasi | 2 | Satu sumber menu (sidebar + bottom nav), perbaikan `$id_level` di view |
| Urutkan Siswa | 3 | Urutan manual per kelas (drag & drop) dipakai di semua daftar siswa |
| Dashboard rekap harian | 2 | Kartu presensi, jurnal mengajar, jurnal struktural di 7 dashboard |
| Jurnal struktural & tendik | 1 | Tendik `id_jabatan = 2` kembali bisa mengisi jurnal |
| Zona waktu | 2 | Seluruh aplikasi memakai WIB (GMT+7) + skrip data historis |
| Menu Mapel | 1 | Simpan kelas & guru tidak lagi berhenti di `var_dump` |
| Domain notifikasi | 1 | Tautan WA memakai domain produksi |
| Dokumentasi | 6 | Panduan deploy, ringkasan hardening, template secrets |

---

## 2. Keamanan aplikasi

Fokus: menutup celah yang membahayakan karena aplikasi sudah live dan dapat diakses publik.

**a. Fondasi keamanan + RBAC + CSRF** (`1cec2f8`)

- `app/helpers/Security.php`: sesi aman (regenerate, cookie httponly/samesite), header keamanan, CSRF token, throttle login, policy level terpusat, guard same-origin.
- `public/index.php`: validasi nama controller/method, otorisasi dijalankan **sebelum** instansiasi controller, aksi destruktif via GET diblokir, `display_errors=0` di produksi.
- `public/ajax.php`: wajib login + whitelist aksi. `public/API/index.php`: auth device key + prepared statement.
- CSRF otomatis lewat `assets/js/csrf.js` + meta token di layout + field di form login.
- Upload terpusat (`Upload::store`) dengan `.htaccess` yang menolak eksekusi berkas.
- Backup dipindah keluar webroot, guard level 1, token dibandingkan dengan `hash_equals()`.
- Kredensial DB bisa lewat environment (`SIBER_DB_*`), kunci/rahasia dipusatkan.
- Berkas uji/debug publik dihapus, log & sessions keluar dari repo, akses web ke cron → 403.
- IDOR: kepemilikan jurnal (edit/update/hapus), izin guru non-admin hanya melihat data pending.

**b. Escape output (XSS)** (`f84d583`, `bcdb625`)

- 90 view admin dan dashboard peran: seluruh `echo` variabel dari DB/input dibungkus `sec_e()` (teks dan atribut).
- Flash message di dalam `<script>` memakai `sec_js()` (`JSON_HEX_*`), termasuk pesan SweetAlert.
- Escape pada `implode` nama, `innerHTML` halaman verifikasi, dan cetak izin.

**c. Kredensial keluar dari kode** (`3e9ba20`, `de28e02`)

- `config/secrets.php` (di luar Git, permission 600) menjadi satu-satunya sumber token/kunci/DB; `app/config.php` dan `config/database.php` tidak lagi menyimpan nilai literal.
- `secrets.example.php` disediakan sebagai template + pemeriksa mandiri.
- Cron wajib kunci (`hash_equals`), akses web tanpa kunci → 403, CLI tetap jalan.

**d. IDOR rapor terpadu** (`e4d4dc1`)

- Update rapor terpadu dibatasi ke kelas milik wali kelas / guru pengampu.

---

## 3. Integrasi WhatsApp

**a. Antrean outbox yang andal** (`0a6adaf`)

Masalah: sebagian pesan `outbox_wa` tidak pernah tertarik gateway (status `pending` selamanya), pesan bisa terkirim dobel, cron bisa bertumpuk.

- `jobs.php`: klaim `pending → in_queue` dalam transaksi (`SELECT … FOR UPDATE`) agar baris tidak disajikan ulang dan kepala antrean tidak macet.
- `ack.php`: parsing ref `OUTBOX-<id>-<sidik>`, status tidak boleh turun dari `sent`.
- Baru `requeue.php` (lease 45 menit, maks 3 percobaan) dan `status.php` (kesehatan antrean + diagnosa).
- `_config.php`: kredensial/token dibaca dari `config/wa_gateway.php` (di luar `public/`) atau environment.
- `_wa_outbox.php`: anti-dobel per hari, tolak nomor > 15 digit, `try/catch`.
- 5 skrip cron memakai `flock` + penegasan zona waktu WIB.
- `wa_gateway_lease_migrasi.sql`: kolom `claimed_at`, `attempt` + index.

**b. Baris `in_queue` legacy** (`2bc7196`)

- Baris `in_queue` dengan `claimed_at` NULL (dibuat sebelum kolom itu ada) tadinya macet permanen; sekarang dilepas memakai `created_at` + tenggang 24 jam (`WA_LEGACY_GRACE_JAM`).
- Perbaikan `HY093` karena satu nama parameter PDO dipakai dua kali saat `ATTR_EMULATE_PREPARES=false`.

**c. Notifikasi izin guru lepas dari Fonnte** (`4d94661`)

- `IzinGuruController::_sendWhatsAppNotification()` tidak lagi memanggil `api.fonnte.com`; pesan masuk `outbox_wa` lewat `wa_enqueue()` dan dikirim gateway lokal.
- `FONTE_TOKEN` dihapus dari konfigurasi dan teks setting admin.
- Anti-dobel memakai jam database sehingga tetap benar saat MySQL UTC dan PHP WIB.
- `BackupController::autoBackup()`: folder ID Drive dibaca dari konstanta, guard webhook kosong, timeout 60 detik, log `curl` errno.

---

## 4. Backup

- **`5523f5a`** — database produksi ±50 MB menjadi base64 ±66 MB dan ditolak Apps Script (HTTP 400). Sekarang isi backup > 4 MB digzip lebih dulu, nama di Drive berakhiran `.sql.gz`; berkas lokal tetap `.sql` agar restore dari panel tidak berubah. `createBackup()` dan `autoBackup()` memakai satu helper `uploadToDrive()` (connect timeout 20 s, total 300 s, laporan HTTP/errno).

---

## 5. Data siswa & nilai

**a. Siswa keluar hilang dari nilai/rapor** (`393e7e7`)

Tombol "Luluskan" hanya mengubah `siswa.status_siswa`, sementara daftar nilai, leger, peringkat, progres rapor, dan rekap laporan masih membaca `ploting_siswa` apa adanya.

- Helper baru `app/helpers/SiswaAktif.php` → `siswa_aktif_sql($alias)` sebagai **satu-satunya** definisi status non-aktif.
- Filter dipakai di Kategori (daftar & populate kategori baru), Rapor (peringkat, progres), `LaporanModel`, `LegerModel`, `RaporController`, dan dropdown siswa (`AjaxController`).
- Hapus kategori juga menghapus baris nilainya agar tidak muncul sebagai data yatim.
- Baris nilai siswa keluar **tetap disimpan**; hanya disembunyikan dan muncul lagi bila status dikembalikan.

**b. Status non-aktif yang diakui**

`lulus`, `keluar`, `pindah`, `mutasi`, `berhenti`, `drop out`, lalu ditambah `mengundurkan diri` dan `meninggal` (`f5807f3`).

**c. Menu PD Keluar** (`f5807f3`)

- Menu baru untuk admin, kesiswaan, kepala pondok: mencatat siswa mengundurkan diri / mutasi / meninggal / keluar, dan status bisa dikembalikan tanpa kehilangan nilai.
- Riwayat disimpan di tabel `siswa_keluar` (dibuat otomatis; SQL manual tersedia), status utama tetap di `siswa.status_siswa`.
- "Luluskan" di Ploting kini mencatat riwayat sehingga salah lulus bisa dibatalkan.
- Filter status yang sama diterapkan pada daftar siswa Izin, Konseling, Ploting, dan dropdown `get-siswa`.

**d. Nama siswa dobel di jurnal + merge siswa** (`e44c4bf`, `2cd589d`, `cf38915`)

- Daftar siswa jurnal tidak lagi menampilkan nama dobel.
- Skrip baru `scripts/merge_siswa.php`: **default pratinjau**, eksekusi hanya dengan `--apply`; duplikat tidak dihapus hanya dinonaktifkan; menampilkan perbandingan nilai bentrok; opsi `--nilai=ke|dari|isi`; PK tabel dinamis.

**e. Siswa baru otomatis punya baris nilai** (`9585b7b`)

- Menambah siswa (ploting/kategori baru) langsung membuat baris nilai pada kategori yang sudah ada, sehingga tidak perlu backfill manual.

**f. Sinkronkan Siswa** (`20b9e17`)

- Tombol **Sinkronkan Siswa** per mapel dan **Sinkronkan Semua** (khusus admin) untuk memperbaiki data yang terlanjur tidak sinkron.

---

## 6. Rapor & laporan

- **`b9d1104`** — admin dan waka kurikulum dapat melihat nilai **semua kelas** seperti wali kelas.
- **`386a10a`** + **`12c8a85`** — admin dan waka kurikulum **hanya melihat** (read-only); input/ubah tetap urusan wali kelas.
- **`e993fc3`** — cetak peringkat hanya memuat siswa kelas pada tahun rapor aktif (kelas lama tidak ikut).
- **`11a397a`** — tidak ada warning saat form prestasi kosong.
- **`57fc30f`** — sel nilai kosong tampil `-` (bukan `0`), rata-rata dan nilai rapor juga `-` selama belum ada nilai terisi.
- **`a40182e`** — Rekap Nilai tidak lagi menampilkan kelas tahun lalu sebagai akordeon kosong: `mapel_guru` ikut difilter tahun, default tahun aktif, key penggabung menyertakan tahun, mode arsip menampilkan badge tahun.

---

## 7. UI, notifikasi, dan responsif

**a. Notifikasi terpusat** (`c077796`, `f4df5df`, `0113b65`)

- `public/assets/js/siber-notify.js`: `SiberToast`, `SiberConfirm`, `SiberDialog`, plus override `window.alert`.
- Aturan yang dipakai: **sukses = toast pojok kanan atas**, **error/validasi = SweetAlert/modal**. Alert bawaan browser tidak dipakai lagi.
- 66 blok `getFlash()` di 50 view dihapus; flash dipusatkan di `layouts/header.php` → `layouts/toast.php` (kunci `__SIBER_FLASH`).
- 85 ikon Font Awesome diganti Material Icons Round.
- Konfirmasi tombol submit memakai `requestSubmit()` agar validasi HTML5 dan nilai tombol tetap terkirim.
- Setting/Backup: `alert()` diganti `setFlash` + redirect. Login: `alert()` diganti pesan inline (`?error=1`, `?error=level`, `?error=limit`).
- `app/models/PengingatDeadline.php` + `tests/pengingat_deadline_test.php` (21 cek) untuk pengingat deadline struktural.

**b. Responsif & markup** (`0113b65`, `34218e1`)

- Atribut `width` tetap pada `<th>` dihapus di 11 view.
- `jadwal`: tombol "Tambah Jadwal" dipindah keluar `<table>` (markup tidak valid).
- `repres`: tabel presensi tidak lagi dipaksa selebar layar di HP (`min-width: 900px` + scroll).
- `rapor/input`: header `flex-wrap`. `jurnal/history`: `btn-xs` (tidak ada di Bootstrap 5) → `btn-sm`.
- SweetAlert icon `danger` → `error`, `getFlash` dobel di halaman siswa, `?status=` untuk prestasi/ekstra, HTML verifikasi rusak, hapus view `jadwal/tambah.php` yang terpotong, Bootstrap Icons → Material Icons, tombol utama seragam `btn-success`.

---

## 8. Navigasi

- **`34218e1`** — `layouts/menu.php` menjadi satu-satunya sumber struktur menu + filter level; `sidebar.php` dan `mobile_nav.php` (bottom bar 5 slot + bottom sheet "Lainnya") merender dari sumber yang sama; CSS/JS baru `siber-nav.css`, `siber-nav.js`; bottom nav untuk layar < 1200px.
- **`c6fcddd`** — `$id_level` diisi dari session di `header.php`; sebelumnya tidak pernah diisi sehingga tombol edit/hapus tetap tampil untuk level 7 (siswa). `izin/create.php` memakai `isLevel()` (bukan `== 4`) agar wali kelas mendapat kelas tetap dan BK bisa memilih kelas.

---

## 9. Urutkan Siswa (drag & drop)

**a. Fitur utama** (`a623de0`)

- Kolom baru `ploting_siswa.urutan` (INT NULL) + `scripts/migrasi_urutan_siswa.php` (idempoten, punya mode `--cek`).
- Halaman **Master → Urutkan Siswa**: drag & drop (SortableJS lokal) + tombol naik/turun, simpan sekali via AJAX, reset kembali ke abjad.
- Hak akses: admin, wali kelas (kelasnya sendiri), waka kurikulum, waka kesiswaan (`[1,3,5,6]`).
- Pola urutan dipakai di semua daftar siswa per kelas: `(urutan IS NULL) ASC, urutan ASC, nama_siswa ASC` — siswa tanpa urutan tetap tampil di belakang secara abjad.
- Titik yang disesuaikan: ploting, jurnal, kehadiran, leger, rapor, prestasi, ekstra, konseling, siswa keluar, kategori/laporan, dropdown ajax (15+ titik).

**b. Perbaikan lanjutan** (`b8da290`, `029fbb3`)

- Di layar < 576px: item jadi dua baris, nama siswa mendapat lebar penuh (tidak tertutup tombol panah), tombol naik/turun diperbesar untuk sentuhan.
- `AjaxController::getSiswaByKelas` dan `Kehadiran::getRekapHarian` ikut memakai kolom urutan (sebelumnya masih abjad).

---

## 10. Dashboard rekap harian

**a. Tiga kartu rekap** (`121fa08`)

- Partial `app/views/partials/rekap_harian.php` dipakai 7 dashboard (admin, BK, guru, kepala pondok, kesiswaan, kurikulum, wali kelas).
- `DashboardController`: `rekapPresensiHariIni()`, `rekapJurnalMengajarHariIni()`, `rekapJurnalStrukturalHariIni()`.
- **Jurnal mengajar** menampilkan **semua jam hari ini** (bukan hanya yang belum), status Sudah / Belum / Belum waktunya, jam isi dari `MIN(TIME(created_at))`.
- Perbaikan `Presensi`: `strtolower(date('l'))` membuat jadwal tidak pernah cocok karena data `jadwal.day` berhuruf kapital.
- **Jurnal struktural** memakai `employe.id_jabatan = 2` (bukan level / `user_level`): helper `siber_jabatan()` / `is_jabatan()`, `id_jabatan` disimpan di sesi, filter menu kunci `'jabatan'`, guard `wajibTenagaKependidikan()` di controller.
- Admin/waka cukup melihat rekap pribadi.
- Kondisi presensi: tanpa scan → "Belum melakukan Presensi"; ada scan tapi tanpa jadwal → "Tidak ada jadwal" + jam scan + catatan tidak terekap.

**b. Tidak menagih saat libur / tanpa jadwal** (`982405d`)

- Kartu tetap tampil, tetapi status "Belum diisi" + tombol "Isi sekarang" hanya muncul bila ada baris jadwal hari ini **dan** hari itu bukan libur; selain itu badge netral "Tidak wajib diisi".
- Data uji: `employe` 513 (tendik), `jadwal` 5130 07:30–15:30, `hari_libur`; tiga kondisi diuji (ada jadwal, tanpa jadwal, libur) → semua HTTP 200.

---

## 11. Jurnal struktural & tenaga kependidikan

**`3be5ca3`** — laporan: tendik dengan `id_jabatan = 2` tidak bisa mengisi jurnal struktural.

Akar masalah:

1. `User::findByUsername()` hanya `SELECT id_employe, nama, username, password, pin` — kolom `id_jabatan` tidak ikut, sehingga sesi berisi `id_jabatan = 0` dan guard menolak semua tendik.
2. `BaseController` memakai `isset()` sehingga nilai `0` dianggap sudah ada dan tidak di-backfill.
3. `JurnalStrukturalController` tidak mewarisi `BaseController`, jadi tidak kena backfill.

Perbaikan:

- `User::findByUsername()` mengambil `id_jabatan` (+ fallback query lama bila kolom belum ada).
- `BaseController` memakai `empty()` agar sesi lama bernilai 0 ikut diperbaiki.
- Helper baru `siber_sinkron_jabatan($db)` — satu-satunya jalur pengambilan kategori langsung dari `employe.id_jabatan`.
- `JurnalStrukturalController` memanggil helper itu di konstruktor sebelum guard.

Verifikasi: login nyata lewat form (CSRF dari meta token) → sesi `id_jabatan = 2`, dashboard + `jurnalStruktural` 200, POST `store` 302 dan tersimpan; guru tetap ditolak (302 ke dashboard).

---

## 12. Zona waktu WIB (GMT+7)

**`9716acf`, `8ca962f`** — jam jurnal di dashboard masih UTC (mundur 7 jam), termasuk penilaian "Belum/Alpa".

- Tidak ada `date_default_timezone_set()` di seluruh repo → PHP dan MySQL memakai UTC.
- `public/index.php` + `config/database.php`: `date_default_timezone_set('Asia/Jakarta')` dan `SET time_zone = '+07:00'` per koneksi PDO, sehingga `NOW()`, `CURDATE()`, `DEFAULT CURRENT_TIMESTAMP` ikut WIB.
- `scripts/perbaikan_timezone.php`: menggeser timestamp historis `jurnal.created_at` dan `jurnal_struktural.created_at` +7 jam; **default mode periksa**, eksekusi `--apply`, idempoten lewat tabel penanda `siber_migrasi`, bisa `--tabel=nama.kolom`.
- `attendance.scan_date` **tidak** digeser karena berasal dari mesin finger dan sudah WIB.
- Fallback bila user DB tidak punya hak `CREATE TABLE` untuk tabel penanda (`8ca962f`).
- Cron dan gateway WA punya penanganan zona waktu sendiri (`WA_TZ`).

Verifikasi: insert jurnal lewat koneksi aplikasi menghasilkan `created_at = 17:06` saat `UTC_TIMESTAMP() = 10:06`.

---

## 13. Menu Mapel — kelas & guru

**`c043ad2`** — laporan: menambah kelas & guru pada mapel muncul tulisan `null`.

Akar masalah: `MapelController::update_guru()` menyisakan kode debug `var_dump($data); die();` dengan `$data` yang tidak pernah diisi, sehingga menyimpan form hanya mencetak `NULL` dan berhenti. Form edit juga berjudul "Tambah Kelas & Guru" sehingga dikira form tambah.

- `var_dump`/`die()` dihapus; `update_guru()` memakai data POST tervalidasi + `findGuru()`.
- Tahun pelajaran mengikuti baris yang diedit, tidak lagi dipaksa tahun aktif (mencegah baris tahun lama pindah tahun diam-diam).
- Judul halaman edit menjadi "Edit Kelas & Guru".
- `store_guru()`: validasi wajib isi + cek duplikat (`mapel` + `kelas` + `tahun` + `guru`) lewat `Mapel::isDuplicateGuru()`, error lewat `setFlash('danger')` + redirect.
- Verifikasi: `php -l` bersih, uji unit model dengan PDO palsu (9 cek lulus), `grep var_dump(` di `app/` bersih.

---

## 14. Domain & dokumentasi

- **`be3c6ee`** — tautan notifikasi WhatsApp di `IzinGuruController.php` diganti dari `https://siber.ingintau.my.id` menjadi `https://siber.pondokminggirsari.com`; `grep ingintau` di repo bersih.
- **Dokumentasi** — `docs/PANDUAN-DEPLOY-GIT.md` menjadi panduan utama (deploy git, 5 cron + auto backup, gateway WA, uji notifikasi, bab 20 dashboard rekap, bab 21 zona waktu), `docs/SECURITY-HARDENING.md` untuk ringkasan pengerasan, `secrets.example.php` sebagai template + pemeriksa mandiri.

---

## 15. Daftar commit lengkap (setelah clone)

| # | Hash | Tanggal | Jenis | Judul |
|---|---|---|---|---|
| 1 | `0a6adaf` | 2026-10-16 13:50 | fix | WA gateway: klaim atomik, lease antrean, anti-dobel, kredensial keluar webroot |
| 2 | `2bc7196` | 2026-10-16 13:53 | fix | WA gateway: lepas baris `in_queue` lama yang `claimed_at` NULL |
| 3 | `1cec2f8` | 2026-10-23 14:56 | security | Fondasi keamanan + RBAC + CSRF + upload/backup/API aman |
| 4 | `f84d583` | 2026-10-23 15:27 | security | Escape output (XSS) di 90 view admin & dashboard peran |
| 5 | `3e9ba20` | 2026-10-23 15:27 | security | Kredensial keluar dari kode + cron web 403 |
| 6 | `e4d4dc1` | 2026-10-23 15:27 | security | IDOR: batasi update rapor terpadu ke kelas milik wali/guru |
| 7 | `88d9a51` | 2026-10-23 15:29 | docs | Perbarui ringkasan pengerasan + urutan deploy rahasia |
| 8 | `bcdb625` | 2026-10-23 15:30 | security | Tutup sisa titik escape (flash SweetAlert, implode, innerHTML, cetak izin) |
| 9 | `aecb2ea` | 2026-10-23 21:40 | docs | Checklist deploy ke hosting + folder id ke `secrets.php` |
| 10 | `5625c06` | 2026-10-23 21:44 | docs | Panduan deploy git langkah demi langkah |
| 11 | `d916bfd` | 2026-10-23 21:45 | docs | Samakan nomor commit rujukan dengan HEAD |
| 12 | `26237f2` | 2026-10-23 21:45 | docs | Pakai penanda commit, bukan hash yang berubah |
| 13 | `5da43ff` | 2026-10-23 21:46 | docs | Cek versi pakai penanda kode |
| 14 | `de28e02` | 2026-10-23 21:52 | docs | Template `secrets.php` + pemeriksa mandiri |
| 15 | `4d94661` | 2026-10-23 22:19 | fix | Notifikasi izin lewat outbox, buang Fonnte, anti-dobel jam DB |
| 16 | `5523f5a` | 2026-10-24 10:16 | fix | Backup besar dikirim ke Drive sebagai `.sql.gz` |
| 17 | `393e7e7` | 2026-10-24 10:27 | fix | Siswa yang dikeluarkan lewat ploting hilang dari nilai & rapor |
| 18 | `57fc30f` | 2026-10-24 10:29 | fix | Sel nilai kosong tampil `-` di lembar nilai read-only |
| 19 | `f5807f3` | 2026-10-24 10:59 | feat | Menu PD Keluar + kembalikan status siswa |
| 20 | `a40182e` | 2026-10-24 11:28 | fix | Kelas tahun lalu tidak muncul di Rekap Nilai per guru |
| 21 | `e44c4bf` | 2026-10-24 12:25 | fix | Daftar siswa jurnal tanpa nama dobel + skrip merge siswa |
| 22 | `2cd589d` | 2026-10-24 12:33 | feat | Merge siswa: perbandingan nilai bentrok + `--nilai=ke\|dari` |
| 23 | `cf38915` | 2026-10-24 12:42 | fix | Merge siswa: PK tabel dinamis + `--nilai=isi` |
| 24 | `b9d1104` | 2026-10-24 13:02 | feat | Rapor: admin & waka kurikulum melihat nilai semua kelas |
| 25 | `386a10a` | 2026-10-24 13:08 | feat | Rapor: admin & waka kurikulum read-only |
| 26 | `12c8a85` | 2026-10-24 13:08 | docs | Catat mode hanya lihat rapor |
| 27 | `11a397a` | 2026-10-24 13:08 | fix | Rapor: tidak warning saat form prestasi kosong |
| 28 | `e993fc3` | 2026-10-24 13:15 | fix | Cetak peringkat hanya siswa kelas di tahun rapor aktif |
| 29 | `9585b7b` | 2026-10-25 01:53 | feat | Siswa baru otomatis dapat baris nilai pada kategori yang ada |
| 30 | `20b9e17` | 2026-10-25 02:07 | feat | Tombol Sinkronkan Siswa (per mapel) & Sinkronkan Semua |
| 31 | `34218e1` | 2026-10-25 06:31 | feat | Sidebar & menu mobile satu sumber + bottom nav < 1200px |
| 32 | `c6fcddd` | 2026-10-25 06:32 | fix | `$id_level` diisi dari session di header + cek level di izin |
| 33 | `c077796` | 2026-10-25 07:20 | feat | Notifikasi toast + SweetAlert, pengingat struktural, tombol/tabel seragam |
| 34 | `f4df5df` | 2026-10-25 07:20 | fix | Konfirmasi submit pakai `requestSubmit` |
| 35 | `a623de0` | 2026-10-25 07:40 | feat | Menu Urutkan Siswa drag & drop per kelas |
| 36 | `b8da290` | 2026-10-25 08:02 | fix | Nama siswa tidak tertutup tombol panah di HP |
| 37 | `029fbb3` | 2026-10-25 08:54 | fix | Sisa titik daftar siswa ikut urutan manual |
| 38 | `0113b65` | 2026-10-25 09:50 | fix | Toolbar/tabel responsif + hilangkan alert bawaan browser |
| 39 | `121fa08` | 2026-10-25 12:12 | feat | Dashboard: rekap harian presensi, jurnal mengajar & struktural |
| 40 | `982405d` | 2026-10-25 12:34 | fix | Jurnal struktural tidak ditagih di hari libur / tanpa jadwal |
| 41 | `3be5ca3` | 2026-10-26 00:20 | fix | Tendik `id_jabatan = 2` bisa mengisi jurnal struktural |
| 42 | `9716acf` | 2026-10-26 10:08 | fix | Seluruh aplikasi memakai WIB (GMT+7) |
| 43 | `8ca962f` | 2026-10-26 10:09 | fix | Skrip timezone tetap jalan tanpa hak `CREATE TABLE` |
| 44 | `be3c6ee` | 2026-10-28 01:23 | fix | Tautan notifikasi WA memakai domain produksi |
| 45 | `c043ad2` | 2026-10-28 23:04 | fix | Menu Mapel: simpan kelas & guru tidak lagi berhenti di `var_dump` |

---

## 16. Berkas/perubahan yang butuh tindakan manual di produksi

Tidak semua perubahan otomatis jalan setelah `git pull`; berikut yang butuh langkah manual.

| No | Hal | Berkas | Sifat |
|---|---|---|---|
| 1 | Kolom `ploting_siswa.urutan` | `scripts/migrasi_urutan_siswa.php` | Wajib; halaman Urutkan Siswa error bila belum dijalankan. Idempoten, ada `--cek` |
| 2 | Geser data historis UTC → WIB | `scripts/perbaikan_timezone.php` | Sekali saja: jalankan mode periksa, backup, lalu `--apply` |
| 3 | Tabel `siswa_keluar` | dibuat otomatis oleh aplikasi (SQL manual tersedia) | Tanpa langkah khusus bila user DB boleh `CREATE TABLE` |
| 4 | Kolom lease gateway (`claimed_at`, `attempt`) | `wa_gateway_lease_migrasi.sql` | Wajib di DB yang dipakai gateway |
| 5 | `config/secrets.php` + `config/wa_gateway.php` | `secrets.example.php` | Harus ada di server (di luar `public/`), permission 600 |
| 6 | 5 cron + auto backup | `docs/PANDUAN-DEPLOY-GIT.md` | Diatur di cPanel, wajib kunci cron |
| 7 | Merge siswa duplikat | `scripts/merge_siswa.php` | Opsional; jalankan pratinjau dulu, `--apply` bila setuju |

---

## 17. Catatan & sisa pekerjaan

- **Data historis UTC** baru digeser untuk `jurnal.created_at` dan `jurnal_struktural.created_at`; tabel log lain belum diputuskan.
- **Pemetaan level tendik** perlu dicek di produksi: `SELECT id_level, COUNT(*) FROM employe GROUP BY id_level;` — staf TU dengan `id_jabatan` NULL tetap tidak dikenali sebagai tendik.
- **Format `jadwal_pelajaran.hari`** di produksi perlu dipastikan konsisten (mis. `Senin` vs `Sunday`); kode kini memakai nama hari Indonesia berhuruf kapital.
- **Sisa temuan responsif** yang belum dipastikan: `leger/index.php`, `jurnal_struktural/index.php`, `ekstra/index.php`.
- **Uji Mapel** baru sebatas uji unit model; simpan/tambah ke database sungguhan perlu dicoba di produksi setelah `git pull`.
- Aplikasi produksi di-deploy manual (`git pull` di cPanel), jadi commit yang belum di-pull **belum** aktif di `siber.pondokminggirsari.com`.
