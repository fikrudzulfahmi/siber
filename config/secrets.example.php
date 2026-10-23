<?php
/**
 * ============================================================================
 *  TEMPLATE config/secrets.php — APLIKASI SIBER
 * ============================================================================
 *
 *  CARA PAKAI (di cPanel → File Manager):
 *   1. Masuk folder `config` (sejajar folder `app` dan `public`,
 *      BUKAN di dalam `public`).
 *   2. Buat file baru bernama: secrets.php
 *   3. Tempel SELURUH isi file ini, lalu ganti setiap tulisan
 *      ISI_... dengan nilai aslinya.
 *   4. Simpan, lalu klik kanan → Change Permissions → 600 (Read+Write owner).
 *   5. Cek dari Terminal:
 *        php ~/siber/config/secrets.php
 *      Harus muncul "Semua wajib terisi. Aman untuk lanjut."
 *
 *  PENTING:
 *   - File ini TIDAK boleh di-commit ke Git (sudah masuk .gitignore).
 *   - File ini TIDAK boleh diletakkan di dalam folder public/.
 *   - CRON_KEY harus SAMA PERSIS dengan kunci di URL cron cPanel.
 *     Kalau diganti, semua cron akan balas 403 dan WA berhenti terkirim.
 *   - API_DEVICE_KEY sengaja dikosongkan: selama kosong, mesin presensi
 *     lama tetap bisa mengirim data. Isi nanti setelah semua mesin siap.
 * ============================================================================
 */

// ---------------------------------------------------------------------------
// 1. WhatsApp (Fonnte) — ambil dari dashboard fonnte.com → Device → Token
// ---------------------------------------------------------------------------
if (!defined('FONNTE_TOKEN')) {
    define('FONNTE_TOKEN', 'ISI_TOKEN_FONNTE');
}

// ---------------------------------------------------------------------------
// 2. Google Drive / Apps Script (dipakai fitur backup otomatis)
//    Kalau fitur ini tidak dipakai, biarkan apa adanya — tidak menggagalkan app.
// ---------------------------------------------------------------------------
if (!defined('GOOGLE_DRIVE_WEBHOOK_URL')) {
    define('GOOGLE_DRIVE_WEBHOOK_URL', 'ISI_URL_APPS_SCRIPT');
}
if (!defined('GOOGLE_DRIVE_SECRET_KEY')) {
    define('GOOGLE_DRIVE_SECRET_KEY', 'ISI_SECRET_DRIVE');
}
if (!defined('GOOGLE_DRIVE_FOLDER_ID')) {
    define('GOOGLE_DRIVE_FOLDER_ID', 'ISI_FOLDER_ID');
}

// ---------------------------------------------------------------------------
// 3. Kunci cron — HARUS sama dengan yang sudah terpasang di cPanel → Cron Jobs
//    (lihat URL: .../cron_jobs/reminder.php?key=XXXXXXXX)
//    Salin nilai lama dari app/config.php sebelum git pull.
// ---------------------------------------------------------------------------
if (!defined('CRON_KEY')) {
    define('CRON_KEY', 'ISI_KUNCI_CRON_LAMA');
}

// ---------------------------------------------------------------------------
// 4. Kunci API mesin presensi (OPSIONAL — kosongkan saat deploy pertama)
// ---------------------------------------------------------------------------
if (!defined('API_DEVICE_KEY')) {
    define('API_DEVICE_KEY', '');
}

// ---------------------------------------------------------------------------
// 5. Database MySQL (cPanel → MySQL Databases)
//    Host hampir selalu 'localhost' di shared hosting. Port kosongkan.
// ---------------------------------------------------------------------------
if (!defined('SIBER_DB_HOST')) {
    define('SIBER_DB_HOST', 'localhost');
}
if (!defined('SIBER_DB_PORT')) {
    define('SIBER_DB_PORT', '');
}
if (!defined('SIBER_DB_NAME')) {
    define('SIBER_DB_NAME', 'u607305378_siber');
}
if (!defined('SIBER_DB_USERNAME')) {
    define('SIBER_DB_USERNAME', 'u607305378_siber');
}
if (!defined('SIBER_DB_PASSWORD')) {
    define('SIBER_DB_PASSWORD', 'ISI_PASSWORD_DB');
}

// ---------------------------------------------------------------------------
// CATATAN: WHATSAPP_RECIPIENTS sengaja TIDAK ditulis di sini.
// Nomor penerima memakai daftar default di app/config.php. Isi di sini hanya
// bila ingin MENGGANTI daftar tersebut (format array nomor 62...).
// ---------------------------------------------------------------------------


/* ===========================================================================
 * PEMERIKSA MANDIRI — hanya berjalan bila file ini dijalankan dari terminal:
 *     php config/secrets.php
 * Tidak mengeluarkan apa pun saat file ini dipanggil oleh aplikasi web,
 * karena blok ini hanya aktif di CLI dan hanya untuk file ini sendiri.
 * =========================================================================== */
if (PHP_SAPI === 'cli'
    && isset($_SERVER['SCRIPT_FILENAME'])
    && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {

    $daftar = [
        'FONNTE_TOKEN'            => 'Token WhatsApp Fonnte',
        'CRON_KEY'                => 'Kunci cron (sama dengan URL panel)',
        'SIBER_DB_HOST'           => 'Host database',
        'SIBER_DB_NAME'           => 'Nama database',
        'SIBER_DB_USERNAME'       => 'User database',
        'SIBER_DB_PASSWORD'       => 'Password database',
        'GOOGLE_DRIVE_WEBHOOK_URL' => 'URL Apps Script (opsional)',
        'GOOGLE_DRIVE_SECRET_KEY'  => 'Secret Drive (opsional)',
        'GOOGLE_DRIVE_FOLDER_ID'   => 'Folder ID Drive (opsional)',
        'API_DEVICE_KEY'          => 'Kunci API presensi (opsional)',
    ];
    $opsional = ['GOOGLE_DRIVE_WEBHOOK_URL', 'GOOGLE_DRIVE_SECRET_KEY',
                 'GOOGLE_DRIVE_FOLDER_ID', 'API_DEVICE_KEY'];

    echo "\nPemeriksaan config/secrets.php\n";
    echo str_repeat('=', 62), "\n";

    $wajibKurang = 0;
    foreach ($daftar as $kunci => $label) {
        $nilai  = defined($kunci) ? (string) constant($kunci) : '';
        $kosong = ($nilai === '' || preg_match('/^(ISI_|YOUR_)/', $nilai) === 1);

        if ($kosong && !in_array($kunci, $opsional, true)) {
            $tanda = '[!]'; $status = 'BELUM DIISI'; $wajibKurang++;
        } elseif ($kosong) {
            $tanda = '[ ]'; $status = 'kosong (boleh)';
        } else {
            $tanda = '[v]'; $status = 'OK — ' . strlen($nilai) . ' karakter';
        }

        printf("%-4s %-28s %-36s %s\n", $tanda, $kunci, $label, $status);
    }

    echo str_repeat('=', 62), "\n";
    if ($wajibKurang === 0) {
        echo "Semua wajib terisi. Aman untuk lanjut ke git pull.\n\n";
        exit(0);
    }
    echo "Masih ada {$wajibKurang} nilai WAJIB yang kosong. Lengkapi dulu.\n\n";
    exit(1);
}
