<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Muat konfigurasi database & rahasia
require_once __DIR__ . '/../../config/database.php';
if (is_file(__DIR__ . '/../../config/secrets.php')) {
    require_once __DIR__ . '/../../config/secrets.php';
}
require_once __DIR__ . '/../../app/config.php';

// Cek akses via key (CLI dibebaskan atau mencocokkan key bila diberikan)
$isCli = (PHP_SAPI === 'cli' || php_sapi_name() === 'cli');
$providedKey = $_GET['key'] ?? null;
if ($isCli && $providedKey === null && isset($_SERVER['argv'])) {
    foreach ($_SERVER['argv'] as $arg) {
        if (str_starts_with($arg, '--key=')) {
            $providedKey = substr($arg, 6);
        } elseif (str_starts_with($arg, 'key=')) {
            $providedKey = substr($arg, 4);
        }
    }
}

if (!$isCli || $providedKey !== null) {
    if (!isset($providedKey) || !defined('CRON_KEY') || CRON_KEY === '' || !hash_equals(CRON_KEY, (string) $providedKey)) {
        if (PHP_SAPI !== 'cli') {
            http_response_code(403);
        }
        die('Unauthorized');
    }
}

// --- Kunci anti-tumpang-tindih ---------------------------------------------
// Sebelumnya dua eksekusi berbarengan (cron dobel / klik manual saat cron jalan)
// membuat pesan yang sama dimasukkan dua kali ke antrean -> terkirim dobel.
$__lockBerkas = __DIR__ . '/.lock_' . basename(__FILE__); // dipatok ke folder skrip: path pasti sama untuk cron (CLI) maupun akses web
$__lockFp = @fopen($__lockBerkas, 'c');
if ($__lockFp !== false && !flock($__lockFp, LOCK_EX | LOCK_NB)) {
    echo 'SKIP: ' . basename(__FILE__) . " masih berjalan (lock aktif).\n";
    exit;
}
register_shutdown_function(function () use ($__lockFp) {
    if (is_resource($__lockFp)) {
        flock($__lockFp, LOCK_UN);
        fclose($__lockFp);
    }
});

// Helper pengiriman WA: kirim langsung ke endpoint WA Gateway
require_once __DIR__ . '/_wa_outbox.php';

// File: rekap_walas.php
// Versi Final: Integrasi Tahun Pelajaran Aktif & Plotting Siswa

// ------------------- KONFIGURASI -------------------
$logFile = __DIR__ . '/rekap_walas_log.txt';
$debugMode = true; // Default awal
// ---------------------------------------------------

// JARING PENGAMAN
set_time_limit(0);
ini_set('memory_limit', '-1');
header('Content-Type: text/plain');

date_default_timezone_set('Asia/Jakarta');
if (file_exists($logFile)) {
    unlink($logFile);
}

function write_log($message)
{
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logContent = "[{$timestamp}] {$message}\n";
    file_put_contents($logFile, $logContent, FILE_APPEND);
    echo $logContent;
}

write_log("===== CRON JOB REKAP WALI KELAS DIMULAI =====");

try {
    // 1. KONEKSI DATABASE
    $database = new Database();
    $pdo = $database->getConnection();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    write_log("Koneksi database BERHASIL.");
    $today = date('Y-m-d');

    // --- AMBIL SETTING DEBUG MODE DARI DATABASE ---
    $stmtSetting = $pdo->prepare("SELECT status FROM settings WHERE key_setting = 'wa_notif_rekap_walas' LIMIT 1");
    $stmtSetting->execute();
    $rowSetting = $stmtSetting->fetch(PDO::FETCH_ASSOC);

    // Jika DB status 'true' -> Debug Off (Kirim WA)
    // Jika DB status 'false' -> Debug On (Simulasi)
    $dbStatus = $rowSetting ? $rowSetting['status'] : 'false';
    $debugMode = ($dbStatus === 'false');

    write_log("MODE DEBUG: " . ($debugMode ? "AKTIF (SIMULASI)" : "NON-AKTIF (KIRIM ASLI)"));
    // ----------------------------------------------


    // --- AMBIL TAHUN PELAJARAN AKTIF SECARA DINAMIS ---
    write_log("Mengambil tahun pelajaran yang aktif...");
    $stmtTahun = $pdo->prepare("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
    $stmtTahun->execute();
    $rowTahun = $stmtTahun->fetch(PDO::FETCH_ASSOC);

    if (!$rowTahun) {
        throw new Exception("Gagal memproses! Tidak ada Tahun Pelajaran yang berstatus 'Aktif' di database.");
    }

    $idTahunAktif = $rowTahun['id_tahun_pelajaran'];
    write_log("Tahun Pelajaran Aktif terdeteksi dengan ID: {$idTahunAktif}");
    // --------------------------------------------------


    // 2. AMBIL DAFTAR SEMUA WALI KELAS AKTIF
    write_log("Mengambil daftar semua wali kelas aktif...");
    $sqlWalas = "
        SELECT e.nama, e.no_wa, k.id_kelas, k.kelas AS nama_kelas
        FROM employe e
        JOIN kelas k ON e.id_employe = k.wali_kelas
        WHERE e.id_level = 3 AND e.no_wa IS NOT NULL AND e.no_wa != ''
    ";
    $stmtWalas = $pdo->prepare($sqlWalas);
    $stmtWalas->execute();
    $waliKelasList = $stmtWalas->fetchAll(PDO::FETCH_ASSOC);
    write_log("Ditemukan " . count($waliKelasList) . " wali kelas aktif.");

    // 3. AMBIL SEMUA DATA ABSENSI HARI INI (BERDASARKAN PLOTTING SISWA & TAHUN AKTIF)
    write_log("Mengambil data absensi hari ini berdasarkan tahun pelajaran aktif...");
    $sqlAbsensi = "
        SELECT ps.id_kelas, s.nama_siswa, jk.status
        FROM jurnal_kehadiran jk
        JOIN jurnal j ON jk.id_jurnal = j.id_jurnal
        JOIN siswa s ON jk.id_siswa = s.id_siswa
        JOIN ploting_siswa ps ON s.id_siswa = ps.id_siswa
        WHERE DATE(j.created_at) = :today
          AND ps.id_tahun = :id_tahun_aktif
    ";
    $stmtAbsensi = $pdo->prepare($sqlAbsensi);
    $stmtAbsensi->execute([
        ':today' => $today,
        ':id_tahun_aktif' => $idTahunAktif
    ]);
    $dataAbsensi = $stmtAbsensi->fetchAll(PDO::FETCH_ASSOC);
    write_log("Berhasil menarik data absensi.");

    // 4. KELOMPOKKAN DATA ABSENSI PER KELAS
    $rekapPerKelas = [];
    foreach ($dataAbsensi as $absen) {
        $id_kelas = $absen['id_kelas'];
        if (!isset($rekapPerKelas[$id_kelas])) {
            $rekapPerKelas[$id_kelas] = ['absen' => []];
        }
        if ($absen['status'] !== 'H') {
            $rekapPerKelas[$id_kelas]['absen'][] = "- {$absen['nama_siswa']} ({$absen['status']})";
        }
    }

    // 5. KIRIM PESAN KE SETIAP WALI KELAS
    function format_wa_number($number)
    {
        $cleaned = preg_replace('/[^0-9]/', '', $number);
        if (substr($cleaned, 0, 1) == '0') {
            return '62' . substr($cleaned, 1);
        }
        return $cleaned;
    }

    $counter = 1;
    foreach ($waliKelasList as $walas) {
        $id_kelas = $walas['id_kelas'];
        $namaClean = trim($walas['nama']); // Dipindah ke atas agar aman di semua kondisi
        $pesan = "";
        $status_laporan = "";

        // KONDISI 1: Belum diabsen
        if (!isset($rekapPerKelas[$id_kelas])) {
            $status_laporan = "BELUM PRESENSI";
            $pesan = "Pemberitahuan Presensi ⚠️\n\n" .
                "Yth. Bapak/Ibu *{$namaClean}*,\n" .
                "Wali Kelas dari {$walas['nama_kelas']},\n\n" .
                "Hingga saat ini, terpantau *belum ada data presensi yang masuk* untuk kelas Anda.\n\n" .
                "Mohon koordinasi dengan guru yang mengajar agar segera melengkapi jurnal kehadiran.\n\n" .
                "Terima kasih,\n*SIBER PPRM*";
        } else {
            $siswa_absen = $rekapPerKelas[$id_kelas]['absen'];
            // KONDISI 2: Nihil (Hadir Semua)
            if (empty($siswa_absen)) {
                $status_laporan = "NIHIL";
                $pesan = "*Laporan Absensi Harian* ✅\n\n" .
                    "Yth. Bapak/Ibu *{$namaClean}*,\n" .
                    "Wali Kelas dari {$walas['nama_kelas']},\n\n" .
                    "Seluruh siswa di kelas Anda terpantau *HADIR* lengkap hari ini.\n\n" .
                    "Terima kasih atas perhatiannya.\n\n" .
                    "*SIBER PPRM*";
            } else {
                // KONDISI 3: Ada siswa tidak hadir
                $status_laporan = count($siswa_absen) . " Siswa Tidak Hadir";
                $daftar_siswa = implode("\n", $siswa_absen);
                $pesan = "*Laporan Absensi Harian* 📋\n\n" .
                    "Yth. Bapak/Ibu *{$namaClean}*,\n" .
                    "Wali Kelas dari {$walas['nama_kelas']},\n\n" .
                    "Berikut adalah rekap siswa yang tidak masuk pada hari ini:\n" .
                    "{$daftar_siswa}\n\n" .
                    "Mohon untuk ditindaklanjuti.\n\n" .
                    "*SIBER PPRM*";
            }
        }

        $nomorTujuan = format_wa_number($walas['no_wa']);
        write_log("--- Memproses {$counter}: {$walas['nama']} ({$walas['nama_kelas']}) | Status: {$status_laporan} ---");

        if (!$debugMode) {
            // Kirim langsung ke WA Gateway endpoint (mode PUSH)
            $hasilKirim = wa_kirim($nomorTujuan, $pesan, $pdo);
            write_log("STATUS: " . wa_kirim_hasil_log($hasilKirim));
        } else {
            write_log("STATUS: [SIMULASI] Pesan tidak dikirim ke {$nomorTujuan}");
            write_log("ISI PESAN:\n{$pesan}\n");
        }

        $counter++;
    }
} catch (Exception $e) {
    write_log("FATAL ERROR: " . $e->getMessage());
}

write_log("===== SEMUA PROSES SELESAI =====");
