<?php
// ✅ TAMBAHKAN FUNGSI INI
function log_message($message)
{
    $log_file = dirname(__DIR__, 2) . '/debug.log'; // Simpan file log di folder root proyek
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] " . $message . "\n", FILE_APPEND);
}

// Level Mapping
function getLevelName($id_level)
{
    // versi singkat → dipakai di sidebar / cek level
    $levels = [
        1 => 'admin',
        2 => 'guru',
        3 => 'walikelas',
        4 => 'bk',
        5 => 'kurikulum',
        6 => 'kesiswaan',
        7 => 'kaponpes',
        8 => 'satpam',
        9 => 'guruPiket',
        10 => 'kamad',
        11 => 'katu',
        12 => 'arsip',
        13 => 'data',
        14 => 'keuangan',
        15 => 'madin',
        16 => 'pondok',
    ];
    return $levels[$id_level] ?? 'unknown';
}

function getLevelDisplayName($id_level)
{
    $displayNames = [
        1 => 'Administrator',
        2 => 'Guru',
        3 => 'Wali Kelas',
        4 => 'Guru BK',
        5 => 'Waka Kurikulum',
        6 => 'Waka Kesiswaan',
        7 => 'Kepala Pondok',
        8 => 'Satpam',
        9 => 'Guru Piket',
        10 => 'Kepala Madrasah',
        11 => 'Kepala TU',
        12 => 'Staff Arsip',
        13 => 'Staff Data/Operator',
        14 => 'Staff Keuangan',
        15 => 'Staff Madin',
        16 => 'Staff Data Pondok',
    ];

    if (is_array($id_level)) {
        $names = [];
        foreach ($id_level as $level) {
            $names[] = $displayNames[$level] ?? 'Unknown';
        }
        return implode(', ', $names);
    }
    return $displayNames[$id_level] ?? 'Level Tidak Dikenali';
}
// alias untuk kemudahan
function levelName($id_level)
{
    return getLevelName($id_level); // untuk sidebar / role
}

function levelDisplay($user_levels)
{
    // ✅ DECODE DATA SEBELUM DITAMPILKAN
    if (is_string($user_levels) && !empty($user_levels)) {
        $decoded_levels = base64_decode($user_levels);
        $user_levels = explode(',', $decoded_levels);
    }
    return getLevelDisplayName($user_levels);
}


function isLevel($user_levels, $target)
{
    // ✅ DECODE DATA SEBELUM DICEK
    if (is_string($user_levels) && !empty($user_levels)) {
        $decoded_levels = base64_decode($user_levels);
        $user_levels = explode(',', $decoded_levels);
    }

    if (is_array($user_levels)) {
        return in_array($target, $user_levels);
    }

    return $user_levels == $target;
}


// Multiple Active Sidebar (Group Menu)
function isAnyActive(array $controllers)
{
    $currController = $_GET['controller'] ?? '';
    return in_array($currController, $controllers);
}


function isAnyLevel($user_levels, $targets = [])
{
    // ✅ DECODE DATA SEBELUM DICEK
    if (is_string($user_levels) && !empty($user_levels)) {
        $decoded_levels = base64_decode($user_levels);
        $user_levels = explode(',', $decoded_levels);
    }

    if (!is_array($user_levels)) {
        $user_levels = [$user_levels];
    }

    return !empty(array_intersect($user_levels, $targets));
}


// Flash Message
function setFlash($key, $message)
{
    $_SESSION['flash'][$key] = $message;
}

function getFlash($key)
{
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}
// Auto Title
function autoTitle($controller, $method)
{
    $map = [
        // ================= BERANDA =================
        'dashboard' => [
            'index'  => 'Beranda',
            'index2' => 'Beranda'
        ],

        // ================= MASTER =================
        'kelas' => [
            'index' => 'Data Kelas'
        ],
        'siswa' => [
            'index' => 'Data Siswa'
        ],
        'ploting' => [
            'index' => 'Ploting Kelas'
        ],
        'mapel' => [
            'index' => 'Data Mapel'
        ],
        'jadwal' => [
            'index' => 'Jadwal Pelajaran'
        ],
        'tahunPelajaran' => [
            'index' => 'Tahun Pelajaran'
        ],
        'setting' => [
            'index' => 'Setting Notif WA'
        ],
        'user' => [
            'index'  => 'Data Pegawai',
            'create' => 'Tambah Pegawai',
            'edit'   => 'Edit Pegawai'
        ],

        // ============== PEMBELAJARAN ==============
        'penilaian' => [
            'index' => 'Penilaian'
        ],
        'jurnal' => [
            'index' => 'Jurnal',
            'rekap' => 'Rekap Jurnal'
        ],
        'tp' => [
            'index'   => 'Tujuan Pembelajaran',
            'rekaptp' => 'Rekap TP'
        ],
        'laporan' => [
            'index' => 'Rekap Nilai'
        ],
        'leger' => [
            'index' => 'Legger Nilai'
        ],

        // ============= LAYANAN RAPOR =============
        'adminRapor' => [
            'index' => 'Setting Rapor'
        ],
        'rapor' => [
            'index' => 'Input Rapor Siswa'
        ],

        // =========== EKSTRA & PRESTASI ===========
        'ekstra' => [
            'index' => 'Manajemen Ekstra'
        ],
        'prestasi' => [
            'index' => 'Prestasi Siswa'
        ],

        // ============ REKAP KEHADIRAN ============
        'kehadiran' => [
            'index'        => 'Rekap Harian',
            'rekapBulanan' => 'Rekap Bulanan'
        ],

        // ========== PROGRAM STRUKTURAL ==========
        'programStruktural' => [
            'index' => 'Program Struktural'
        ],
        'deadlineProgramStruktural' => [
            'index' => 'Pengaturan Deadline'
        ],
        'verifikasiProgramStruktural' => [
            'index' => 'Verifikasi Program'
        ],

        // ============= PROGRAM KERJA =============
        'programKerja' => [
            'index'      => 'Program Kerja Saya',
            'indexAdmin' => 'Semua Program Kerja'
        ],

        // =========== JURNAL STRUKTURAL ===========
        'jurnalStruktural' => [
            'index'        => 'Input Jurnal',
            'historyAdmin' => 'Rekap Jurnal'
        ],

        // =========== KEGIATAN LEMBAGA ===========
        'kegiatan' => [
            'index'  => 'Kegiatan Lembaga',
            'tambah' => 'Tambah Kegiatan',
            'edit'   => 'Edit Kegiatan',
            'detail' => 'Detail Kegiatan'
        ],

        // ========== PERANGKAT MENGAJAR ==========
        'perangkat' => [
            'index' => 'Perangkat Saya'
        ],
        'rekat' => [
            'index' => 'Rekap Perangkat'
        ],
        'verifikasi' => [
            'index' => 'Verifikasi Perangkat'
        ],
        'deadlinePerangkat' => [
            'index' => 'Pengaturan Deadline'
        ],

        // =========== PRESENSI PEGAWAI ===========
        'presensi' => [
            'index' => 'Presensi Harian'
        ],
        'absenManual' => [
            'index' => 'Absen Manual'
        ],
        'izinGuru' => [
            'index' => 'Perizinan Pegawai'
        ],
        'repres' => [
            'index' => 'Rekap Presensi'
        ],

        // ================= LAINNYA =================
        'izin' => [
            'index' => 'Perizinan Siswa'
        ],
        'konseling' => [
            'index'        => 'Bimbingan & Konseling',
            'create'       => 'Tambah Konseling',
            'tindakLanjut' => 'Tindak Lanjut Konseling'
        ],
        'rekon' => [
            'index' => 'Rekap Konseling'
        ],
        'kalender' => [
            'index' => 'Kalender'
        ],
        'auth' => [
            'logout' => 'Logout'
        ]
    ];

    // Jika controller atau method tidak terdaftar di $map, tampilkan nama controller-nya secara dinamis
    return $map[$controller][$method] ?? 'Halaman ' . ucfirst(preg_replace('/(?<!^)([A-Z])/', ' $1', $controller));
}




// Active Sidebar
function isActive($controller, $method = 'index')
{
    $currController = $_GET['controller'] ?? '';
    $currMethod = $_GET['method'] ?? 'index';
    return $currController === $controller && $currMethod === $method;
}

// View Loader
function view($path, $data = [])
{
    $file = __DIR__ . '/../views/' . $path . '.php';
    if (!file_exists($file)) {
        die("❌ View tidak ditemukan: $file");
    }

    if (!isset($data['title'])) {
        $controller = $_GET['controller'] ?? '';
        $method = $_GET['method'] ?? 'index';
        $data['title'] = autoTitle($controller, $method);
    }

    extract($data);

    require __DIR__ . '/../views/layouts/header.php';
    require __DIR__ . '/../views/layouts/sidebar.php';
    require $file;
    require __DIR__ . '/../views/layouts/footer.php';
}

// Guard Login
function authGuard()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // // --- KODE DEBUGGING UNTUK MELIHAT SESSION DI HALAMAN DASHBOARD ---
    // echo "<h1>DEBUG: Sedang Memeriksa Hak Akses (authGuard)</h1>";
    // echo "Isi dari \$_SESSION saat tiba di halaman ini:";
    // echo "<pre>";
    // print_r($_SESSION);
    // echo "</pre>";
    // die("Script dihentikan di dalam authGuard() untuk pemeriksaan.");
    // // --- AKHIR KODE DEBUGGING ---

    if (!isset($_SESSION['user'])) {
        redirect('index.php');
    }
}

// Logout Helper
function logoutUser()
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    session_unset();
    session_destroy();

    header("Location: index.php");
    exit;
}

// Redirect Helper
function redirect($url)
{
    header("Location: $url");
    exit;
}

// Prevent Cache (agar back tidak bisa akses halaman)
function noCache()
{
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
}
function formatTanggalIndo($tanggal, $tampilJam = false, $tampilHari = false)
{
    $hari = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu'
    ];

    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($tanggal);
    $namaHari  = $hari[date('w', $timestamp)];
    $tgl       = date('j', $timestamp);
    $bulanNama = $bulan[(int)date('n', $timestamp)];
    $tahun     = date('Y', $timestamp);
    $jam       = date('H:i', $timestamp);

    $hasil = "$tgl $bulanNama $tahun";

    if ($tampilHari) {
        $hasil = "$namaHari, " . $hasil;
    }

    if ($tampilJam) {
        $hasil .= " $jam";
    }

    return $hasil;
}

function formatTanggalIndoHari($tanggal, $tampilJam = false, $tampilHari = true)
{
    $hari = [
        0 => 'Minggu',
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu'
    ];

    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];

    $timestamp = strtotime($tanggal);
    $namaHari  = $hari[date('w', $timestamp)];
    $tgl       = date('j', $timestamp);
    $bulanNama = $bulan[(int)date('n', $timestamp)];
    $tahun     = date('Y', $timestamp);
    $jam       = date('H:i', $timestamp);

    $hasil = "$tgl $bulanNama $tahun";

    if ($tampilHari) {
        $hasil = "$namaHari, " . $hasil;
    }

    if ($tampilJam) {
        $hasil .= " $jam";
    }

    return $hasil;
}
