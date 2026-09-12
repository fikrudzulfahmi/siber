<?php
/**
 * Helper Activity Log
 * Menyediakan: deteksi IP, deteksi perangkat (browser/OS/tipe) tanpa library tambahan,
 * penyusun deskripsi aktivitas, dan fungsi pencatat tunggal yang aman (tidak pernah
 * membuat aplikasi error walau tabel belum dibuat).
 */

/**
 * Ambil IP klien sebenarnya (hosting umumnya di belakang proxy).
 */
function activityLogGetIp()
{
    $kandidat = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    ];

    foreach ($kandidat as $key) {
        if (!empty($_SERVER[$key])) {
            // X-Forwarded-For bisa berisi daftar: ambil yang pertama
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }

    return $_SERVER['REMOTE_ADDR'] ?? null;
}

/**
 * Deteksi browser, OS, dan tipe perangkat dari User-Agent.
 * Tanpa library eksternal (vendor/ tidak tersedia di repo).
 *
 * @return array{browser:string, os:string, device:string}
 */
function activityLogDetectDevice($userAgent)
{
    $ua = (string)$userAgent;
    $hasil = ['browser' => 'Tidak diketahui', 'os' => 'Tidak diketahui', 'device' => 'Desktop'];

    if ($ua === '') {
        return $hasil;
    }

    // ---- Sistem Operasi (urutan penting: Android sebelum Linux, iOS sebelum macOS) ----
    if (preg_match('/windows nt 10|windows nt 11/i', $ua)) {
        $hasil['os'] = 'Windows 10/11';
    } elseif (preg_match('/windows nt 6\.3/i', $ua)) {
        $hasil['os'] = 'Windows 8.1';
    } elseif (preg_match('/windows nt 6\.1/i', $ua)) {
        $hasil['os'] = 'Windows 7';
    } elseif (preg_match('/windows phone/i', $ua)) {
        $hasil['os'] = 'Windows Phone';
    } elseif (preg_match('/windows/i', $ua)) {
        $hasil['os'] = 'Windows';
    } elseif (preg_match('/android/i', $ua)) {
        $hasil['os'] = 'Android';
    } elseif (preg_match('/iphone|ipad|ipod/i', $ua)) {
        $hasil['os'] = 'iOS';
    } elseif (preg_match('/mac os x|macintosh/i', $ua)) {
        $hasil['os'] = 'macOS';
    } elseif (preg_match('/cros/i', $ua)) {
        $hasil['os'] = 'ChromeOS';
    } elseif (preg_match('/ubuntu/i', $ua)) {
        $hasil['os'] = 'Ubuntu';
    } elseif (preg_match('/linux/i', $ua)) {
        $hasil['os'] = 'Linux';
    }

    // ---- Browser (urutan penting: Edge/Opera sebelum Chrome, Chrome sebelum Safari) ----
    if (preg_match('/edg[a-z]*\//i', $ua)) {
        $hasil['browser'] = 'Microsoft Edge';
    } elseif (preg_match('/opr\/|opera/i', $ua)) {
        $hasil['browser'] = 'Opera';
    } elseif (preg_match('/samsungbrowser/i', $ua)) {
        $hasil['browser'] = 'Samsung Internet';
    } elseif (preg_match('/ucbrowser/i', $ua)) {
        $hasil['browser'] = 'UC Browser';
    } elseif (preg_match('/chrome\//i', $ua)) {
        $hasil['browser'] = 'Chrome';
    } elseif (preg_match('/firefox\//i', $ua)) {
        $hasil['browser'] = 'Firefox';
    } elseif (preg_match('/safari\//i', $ua)) {
        $hasil['browser'] = 'Safari';
    } elseif (preg_match('/msie|trident/i', $ua)) {
        $hasil['browser'] = 'Internet Explorer';
    }

    // ---- Tipe perangkat ----
    if (preg_match('/ipad|tablet|playbook|silk/i', $ua)) {
        $hasil['device'] = 'Tablet';
    } elseif (preg_match('/mobile|iphone|ipod|android.*mobile|windows phone|blackberry|opera mini/i', $ua)) {
        $hasil['device'] = 'Mobile';
    }

    return $hasil;
}

/**
 * Ubah nama controller menjadi label yang enak dibaca.
 * jurnalStruktural -> Jurnal Struktural
 */
function activityLogPrettify($teks)
{
    $teks = preg_replace('/(?<!^)([A-Z])/', ' $1', (string)$teks);
    $teks = str_replace(['_', '-'], ' ', $teks);

    return ucwords(trim($teks));
}

/**
 * Tentukan nama modul dari controller.
 * Memakai peta eksplisit supaya label modul tidak bergantung pada method
 * (autoTitle($c,'index') bisa mengembalikan label spesifik method, mis.
 * "Input Jurnal" untuk jurnalStruktural → membuat aktivitas jadi rancu).
 */
function activityLogModul($controller)
{
    $petaModul = [
        'dashboard'                  => 'Beranda',
        'kelas'                      => 'Data Kelas',
        'siswa'                      => 'Data Siswa',
        'ploting'                    => 'Ploting Kelas',
        'mapel'                      => 'Data Mapel',
        'jadwal'                     => 'Jadwal Pelajaran',
        'tahunPelajaran'             => 'Tahun Pelajaran',
        'setting'                    => 'Setting',
        'user'                       => 'Data Pegawai',
        'penilaian'                  => 'Penilaian',
        'jurnal'                     => 'Jurnal Pembelajaran',
        'tp'                         => 'Tujuan Pembelajaran',
        'laporan'                    => 'Rekap Nilai',
        'leger'                      => 'Legger Nilai',
        'adminRapor'                 => 'Setting Rapor',
        'rapor'                      => 'Rapor Siswa',
        'ekstra'                     => 'Ekstrakurikuler',
        'prestasi'                   => 'Prestasi Siswa',
        'kehadiran'                  => 'Rekap Kehadiran',
        'programStruktural'          => 'Program Struktural',
        'deadlineProgramStruktural'  => 'Deadline Program Struktural',
        'verifikasiProgramStruktural' => 'Verifikasi Program Struktural',
        'programKerja'               => 'Program Kerja',
        'jurnalStruktural'           => 'Jurnal Struktural',
        'kegiatan'                   => 'Kegiatan Lembaga',
        'perangkat'                  => 'Perangkat Mengajar',
        'rekat'                      => 'Rekap Perangkat',
        'verifikasi'                 => 'Verifikasi Perangkat',
        'deadlinePerangkat'          => 'Deadline Perangkat',
        'presensi'                   => 'Presensi Pegawai',
        'absenManual'                => 'Absen Manual',
        'izinGuru'                   => 'Perizinan Pegawai',
        'repres'                     => 'Rekap Presensi',
        'izin'                       => 'Perizinan Siswa',
        'konseling'                  => 'Bimbingan & Konseling',
        'rekon'                      => 'Rekap Konseling',
        'kalender'                   => 'Kalender',
        'activityLog'                => 'Activity Log',
        'auth'                       => 'Login',
    ];

    if (isset($petaModul[$controller])) {
        return $petaModul[$controller];
    }

    return activityLogPrettify($controller);
}

/**
 * Susun deskripsi aktivitas + kategori aksi dari controller/method.
 *
 * @return array{aktivitas:string, modul:string, aksi:string}
 */
function activityLogSusunAktivitas($controller, $method)
{
    $modul = activityLogModul($controller);
    $aksi = 'Lainnya';
    $aktivitas = activityLogPrettify($method) . ' - ' . $modul;

    // Aksi baca data via AJAX (dropdown/loading) — tetap dicatat, tapi dikelompokkan
    if (preg_match('/^(get_|get[A-Z]|ajaxGet|load)/', $method)) {
        return [
            'aktivitas' => 'Akses data: ' . $modul . ' (' . $method . ')',
            'modul'     => $modul,
            'aksi'      => 'Akses Data',
        ];
    }

    $peta = [
        'login'           => ['Login', 'Login ke sistem'],
        'logout'          => ['Logout', 'Logout dari sistem'],
        'index'           => ['Buka', 'Buka ' . $modul],
        'indexAdmin'      => ['Buka', 'Buka ' . $modul],
        'index2'          => ['Buka', 'Buka ' . $modul],
        'create'          => ['Buka', 'Buka form tambah ' . $modul],
        'tambah'          => ['Buka', 'Buka form tambah ' . $modul],
        'edit'            => ['Buka', 'Buka form ubah ' . $modul],
        'store'           => ['Tambah', 'Tambah ' . $modul],
        'simpan'          => ['Tambah', 'Simpan ' . $modul],
        'update'          => ['Ubah', 'Ubah ' . $modul],
        'delete'          => ['Hapus', 'Hapus ' . $modul],
        'hapus'           => ['Hapus', 'Hapus ' . $modul],
        'cetak'           => ['Cetak', 'Cetak ' . $modul],
        'cetakLaporan'    => ['Cetak', 'Cetak laporan ' . $modul],
        'print'           => ['Cetak', 'Cetak ' . $modul],
        'rekap'           => ['Buka', 'Buka rekap ' . $modul],
        'history'         => ['Buka', 'Buka riwayat ' . $modul],
        'historyAdmin'    => ['Buka', 'Buka rekap ' . $modul],
        'export'          => ['Export', 'Export ' . $modul],
        'export_excel'    => ['Export', 'Export Excel ' . $modul],
        'upload'          => ['Tambah', 'Upload data ' . $modul],
        'uploadExcel'     => ['Tambah', 'Import Excel ' . $modul],
        'verifikasi'      => ['Ubah', 'Verifikasi ' . $modul],
        'activate'        => ['Ubah', 'Aktifkan ' . $modul],
        'luluskan'        => ['Ubah', 'Luluskan siswa (' . $modul . ')'],
    ];

    if (isset($peta[$method])) {
        list($aksi, $aktivitas) = $peta[$method];
    }

    return [
        'aktivitas' => $aktivitas,
        'modul'     => $modul,
        'aksi'      => $aksi,
    ];
}

/**
 * Catat satu aktivitas ke database.
 * AMAN: seluruh proses dibungkus try/catch — kegagalan mencatat tidak akan
 * mengganggu jalannya aplikasi. Bila tabel belum dibuat, fungsi langsung keluar.
 *
 * @param PDO    $pdo
 * @param string $controller
 * @param string $method
 */
function activityLogCatat($pdo, $controller, $method)
{
    try {
        // Jangan banjiri log dengan aktivitas membuka menu log itu sendiri
        if ($controller === 'activityLog' && !in_array($method, ['hapus', 'bersihkan'], true)) {
            return;
        }

        require_once __DIR__ . '/../models/ActivityLog.php';
        $model = new ActivityLog($pdo);

        if (!$model->tabelAda()) {
            return; // tabel belum di-import — diamkan
        }

        $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $user       = $_SESSION['user'] ?? null;

        $namaUser  = $user['nama'] ?? null;
        $idEmploye = $user['id'] ?? null;
        $levelUser = $user['level'] ?? null;

        // Login gagal / akses tanpa sesi
        if (!$user && $controller === 'auth' && $method === 'login' && $httpMethod === 'POST') {
            $namaUser = $_POST['username'] ?? 'Tamu';
            $susunan  = ['aktivitas' => 'Percobaan login gagal', 'modul' => 'Login', 'aksi' => 'Login'];
        } else {
            $susunan = activityLogSusunAktivitas($controller, $method);
            if (!$user) {
                $namaUser = $_POST['username'] ?? 'Tamu (belum login)';
            }
        }

        $device = activityLogDetectDevice($_SERVER['HTTP_USER_AGENT'] ?? '');

        // Bangun URL lengkap yang diakses
        $url = ($_SERVER['REQUEST_URI'] ?? '');
        if (strlen($url) > 500) {
            $url = substr($url, 0, 500);
        }

        $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
        if (strlen($ua) > 500) {
            $ua = substr($ua, 0, 500);
        }

        $model->insert([
            'id_employe'  => $idEmploye,
            'nama_user'   => $namaUser,
            'level_user'  => function_exists('levelDisplay') && $levelUser ? levelDisplay($levelUser) : $levelUser,
            'aktivitas'   => $susunan['aktivitas'],
            'modul'       => $susunan['modul'],
            'aksi'        => $susunan['aksi'],
            'controller'  => $controller,
            'method'      => $method,
            'http_method' => $httpMethod,
            'url'         => $url,
            'ip_address'  => activityLogGetIp(),
            'user_agent'  => $ua,
            'browser'     => $device['browser'],
            'os'          => $device['os'],
            'device'      => $device['device'],
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    } catch (Throwable $e) {
        // Biarkan aplikasi tetap berjalan; cukup catat ke file log
        if (function_exists('log_message')) {
            log_message('ActivityLog gagal mencatat: ' . $e->getMessage());
        }
    }
}
