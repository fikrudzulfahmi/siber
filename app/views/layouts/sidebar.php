<?php
$currentPage = $_GET['page'] ?? '';
$id_level = $_SESSION['user']['level'];
?>

<style>
    /* Sembunyikan scrollbar */
    #sidenav-main .navbar-collapse {
        overflow-y: auto;
        scrollbar-width: none;
    }

    #sidenav-main .navbar-collapse::-webkit-scrollbar {
        display: none;
    }

    #sidenav-main::-webkit-scrollbar {
        display: none;
    }

    /* Hover per-item */
    .sidebar-item {
        cursor: default;
        margin-bottom: 4px;
    }

    .sidebar-item a.nav-link {
        cursor: pointer;
        display: block;
        padding: 8px 16px;
        border-radius: 0.375rem;
        transition: background-color 0.2s ease;
    }

    .sidebar-item a.nav-link:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }
</style>
<?php
if (!function_exists('collapseItem')) {
    function collapseItem($id, $label, $icon, $controllers, $items, $id_level, $accordion = "accordionSidebar")
    {
        $active = isAnyActive($controllers);
?>
        <li class="sidebar-item">
            <a class="nav-link text-white d-flex justify-content-between align-items-center <?= $active ? 'active bg-gradient-success' : '' ?>"
                data-bs-toggle="collapse" href="#<?= $id ?>" role="button"
                aria-expanded="<?= $active ? 'true' : 'false' ?>" aria-controls="<?= $id ?>">
                <span><i class="material-icons opacity-10 me-2"><?= $icon ?></i> <?= $label ?></span>
            </a>
            <div class="collapse <?= $active ? 'show' : '' ?>" id="<?= $id ?>" data-bs-parent="#<?= $accordion ?>">
                <ul class="nav flex-column ms-4">
                    <?= $items ?>
                </ul>
            </div>
        </li>
<?php
    }
}
?>

<!-- DESKTOP SIDEBAR -->
<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-gradient-dark" id="sidenav-main">
    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-xl-none" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="?controller=dashboard&method=index">
            <img src="assets/img/logo-ct2.png" class="navbar-brand-img" style="height: 50px; object-fit: contain;" alt="main_logo">
            <span class="ms-1 font-weight-bold text-white">SiBer PPRM</span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">

    <div class="collapse navbar-collapse w-auto h-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav accordion" id="accordionSidebar">
            <?php if (isAnyLevel($id_level, [1, 2, 3, 4, 5, 6, 7, 9, 10, 11, 12, 13, 14, 15, 16])): ?>
                <!-- Beranda -->
                <li class="sidebar-item">
                    <a class="nav-link text-white <?= isActive('dashboard') ? 'active bg-gradient-success' : '' ?>" href="?controller=dashboard&method=index">
                        <i class="material-icons opacity-10 me-2">dashboard</i> Beranda
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isLevel($id_level, 8)): ?>
                <li class="sidebar-item">
                    <a class="nav-link text-white <?= isActive('dashboard', 'index2') ? 'active bg-gradient-success' : '' ?>" href="?controller=dashboard&method=index2">
                        <i class="material-icons opacity-10 me-2">dashboard</i> Beranda
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 5, 6, 7])): ?>
                <?php
                collapseItem(
                    "collapseMaster",
                    "Master",
                    "folder",
                    ['kelas', 'siswa', 'mapel', 'tahunPelajaran', 'jadwal', 'user', 'ploting', 'setting'], // Tambahkan 'setting' di sini
                    // Isi list item
                    '
                ' . (
                        // Data Kelas: Admin (1), Kurikulum (5), Level 7
                        (isAnyLevel($id_level, [1, 5, 7]) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("kelas") ? "active bg-gradient-success" : "") . '" href="?controller=kelas&method=index"><i class="material-icons me-2 opacity-10">class</i> Data Kelas</a></li>'
                            : '')
                    ) . '

                ' . (
                        // Data Siswa: Admin (1), Kesiswaan (6), Level 7
                        (isAnyLevel($id_level, [1, 6, 7]) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("siswa") ? "active bg-gradient-success" : "") . '" href="?controller=siswa&method=index"><i class="material-icons me-2 opacity-10">groups</i> Data Siswa</a></li>'
                            : '')
                    ) . '
                    ' . (
                        // Data Siswa: Admin (1), Kesiswaan (6), Level 7
                        (isAnyLevel($id_level, [1, 6, 7]) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("ploting") ? "active bg-gradient-success" : "") . '" href="?controller=ploting&method=index"><i class="material-icons me-2 opacity-10">groups</i> Ploting Kelas</a></li>'
                            : '')
                    ) . '

                ' . (
                        // Data Mapel: Admin (1), Kurikulum (5), Level 7
                        (isAnyLevel($id_level, [1, 5, 7]) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("mapel") ? "active bg-gradient-success" : "") . '" href="?controller=mapel&method=index"><i class="material-icons me-2 opacity-10">library_books</i> Data Mapel</a></li>'
                            : '')
                    ) . '

                ' . (
                        // Jadwal Pelajaran: Admin (1), Kurikulum (5), Level 7
                        (isAnyLevel($id_level, [1, 5, 7]) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("jadwal") ? "active bg-gradient-success" : "") . '" href="?controller=jadwal&method=index"><i class="material-icons me-2 opacity-10">event_note</i> Jadwal Pelajaran</a></li>'
                            : '')
                    ) . '

                ' . (
                        // Tahun Pelajaran: Admin (1)
                        (isLevel($id_level, 1) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("tahunPelajaran") ? "active bg-gradient-success" : "") . '" href="?controller=tahunPelajaran&method=index"><i class="material-icons me-2 opacity-10">date_range</i> Tahun Pelajaran</a></li>'
                            : '')
                    ) . '
' . (
                        // Setting WA Jurnal: Admin (1)
                        (isLevel($id_level, 1) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . ((isActive("setting")) ? "active bg-gradient-success" : "") . '" href="?controller=setting&method=index"><i class="material-icons me-2 opacity-10">notification_important</i> Setting Notif WA</a></li>'
                            : '')
                    ) . '

                ' . (
                        // Data Pegawai: Semua level yang berhak masuk Master
                        '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("user") ? "active bg-gradient-success" : "") . '" href="?controller=user&method=index"><i class="material-icons me-2 opacity-10">person</i> Data Pegawai</a></li>'
                    ),
                    $id_level
                );
                ?>
            <?php endif; ?>



            <?php if (isAnyLevel($id_level, [1, 2, 3, 4, 5, 6, 7, 9])): ?>
                <?php collapseItem(
                    "collapseLearning",
                    "Pembelajaran",
                    "school",
                    ['penilaian', 'laporan', 'jurnal', 'tp', 'rekap', 'rekaptp', 'leger'], // ✅ tambahin rekaptp
                    '
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("penilaian") ? "active bg-gradient-success" : "") . '" href="?controller=penilaian&method=index"><i class="material-icons me-2 opacity-10">grading</i> Penilaian</a></li>
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("jurnal") ? "active bg-gradient-success" : "") . '" href="?controller=jurnal&method=index"><i class="material-icons me-2 opacity-10">book</i> Jurnal</a></li>
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("tp") ? "active bg-gradient-success" : "") . '" href="?controller=tp&method=index"><i class="material-icons me-2 opacity-10">local_library</i> Tujuan Pembelajaran</a></li>
            ' . (isAnyLevel($id_level, [1, 5]) ? '
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("jurnal", "rekap") ? "active bg-gradient-success" : "") . '" href="?controller=jurnal&method=rekap"><i class="material-icons me-2 opacity-10">summarize</i> Rekap Jurnal</a></li>
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("tp", "rekaptp") ? "active bg-gradient-success" : "") . '" href="?controller=tp&method=rekaptp"><i class="material-icons me-2 opacity-10">summarize</i> Rekap TP</a></li>
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("laporan", "index") ? "active bg-gradient-success" : "") . '" href="?controller=laporan&method=index"><i class="material-icons me-2 opacity-10">summarize</i> Rekap Nilai</a></li>
            ' : '') . '
            ' . (isAnyLevel($id_level, [1, 3, 5]) ? '
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("leger", "index") ? "active bg-gradient-success" : "") . '" href="?controller=leger&method=index"><i class="material-icons me-2 opacity-10">summarize</i> Legger Nilai</a></li>

            ' : '') . '
            
            ',
                    $id_level
                ); ?>
            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 3])): ?>
                <?php collapseItem(
                    "collapseRapor",
                    "Layanan Rapor",
                    "description", // Icon material untuk rapor
                    ['adminRapor', 'rapor'],
                    '
        ' . (isAnyLevel($id_level, [1]) ? '
        <li class="sidebar-item">
            <a class="nav-link text-white ' . (isActive("adminRapor", "index") ? "active bg-gradient-success" : "") . '" href="?controller=adminRapor&method=index">
                <i class="material-icons me-2 opacity-10">settings</i> Setting Rapor
            </a>
        </li>
        ' : '') . '
        
        ' . (isAnyLevel($id_level, [3]) ? '
        <li class="sidebar-item">
            <a class="nav-link text-white ' . (isActive("rapor", "index") ? "active bg-gradient-success" : "") . '" href="?controller=rapor&method=index">
                <i class="material-icons me-2 opacity-10">edit_note</i> Input Rapor Siswa
            </a>
        </li>
        ' : '') . '
        ',
                    $id_level
                ); ?>
            <?php endif; ?>

            <?php
            // 1. Cek apakah user adalah Admin ATAU Guru yang ditunjuk sebagai pengampu di tahun aktif


            if (isAnyLevel($id_level, [1, 2])): // Tambahkan ID Level guru Anda di sini (misal 2 atau 3)
            ?>

                <li class="sidebar-item">
                    <a class="nav-link text-white <?= isActive('ekstra') ? 'active bg-gradient-success' : '' ?>" href="?controller=ekstra&method=index">
                        <i class="material-icons me-2 opacity-10">groups</i> Manajemen Ekstra
                    </a>
                </li>

            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 6, 7])): // Sesuaikan level yang diizinkan 
            ?>
                <li class="sidebar-item">
                    <a class="nav-link text-white <?= isActive('prestasi') ? 'active bg-gradient-success' : '' ?>" href="?controller=prestasi&method=index">
                        <i class="material-icons opacity-10 me-2">emoji_events</i>
                        <span class="nav-link-text ms-1">Prestasi Siswa</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 2, 3, 4, 5, 6, 7, 9])): // Gunakan level akses yang sama 
            ?>
                <?php collapseItem(
                    "collapseKehadiran",      // ID unik untuk collapse
                    "Rekap Kehadiran",        // Judul menu utama
                    "view_timeline",          // Ikon menu utama
                    ['kehadiran'],            // Controller yang membuat menu ini aktif
                    '
            <li class="sidebar-item">
                <a class="nav-link text-white ' . (isActive("kehadiran", "index") ? "active bg-gradient-success" : "") . '" href="?controller=kehadiran&method=index">
                    <i class="material-icons me-2 opacity-10">today</i> Rekap Harian
                </a>
            </li>
            ' . (isAnyLevel($id_level, [1, 2, 3, 4, 5, 6, 7, 9]) ? '
            <li class="sidebar-item">
                <a class="nav-link text-white ' . (isActive("kehadiran", "rekapBulanan") ? "active bg-gradient-success" : "") . '" href="?controller=kehadiran&method=rekapBulanan">
                    <i class="material-icons me-2 opacity-10">calendar_month</i> Rekap Bulanan
                </a>
            </li>
            ' : '') . '
        ',
                    $id_level
                ); ?>
            <?php endif; ?>
            <?php
            if (isAnyLevel($id_level, [5, 6, 10, 11, 12, 13, 14, 15, 16])) {
                collapseItem(
                    "collapseProgramStruktural",
                    "Program Struktural",
                    "source",
                    ['programStruktural', 'deadlineProgramStruktural', 'verifikasiProgramStruktural'],
                    '
            <li class="sidebar-item">
                <a class="nav-link text-white ' . (isActive("programStruktural") ? "active bg-gradient-success" : "") . '" 
                   href="?controller=programStruktural&method=index">
                   <i class="material-icons me-2 opacity-10">source</i> Program Struktural
                </a>
            </li>
            ' . (isAnyLevel($id_level, [1, 5]) ? '
            <li class="sidebar-item">
                <a class="nav-link text-white ' . (isActive("deadlineProgramStruktural") ? "active bg-gradient-success" : "") . '" 
                   href="?controller=deadlineProgramStruktural&method=index">
                   <i class="material-icons me-2 opacity-10">settings</i> Pengaturan Deadline
                </a>
            </li>
            <li class="sidebar-item">
                <a class="nav-link text-white ' . (isActive("verifikasiProgramStruktural") ? "active bg-gradient-success" : "") . '" 
                   href="?controller=verifikasiProgramStruktural&method=index">
                   <i class="material-icons me-2 opacity-10">task</i> Verifikasi Program
                </a>
            </li>
            ' : '') . '
        ',
                    $id_level
                );
            }
            ?>



            <?php
            if (isAnyLevel($id_level, [1, 5, 6, 7, 10, 11, 12, 13, 14, 15, 16])) {

                $method = $_GET['method'] ?? 'index';

                $isIndexActive      = ($method === 'index');
                $isIndexAdminActive = ($method === 'indexAdmin');

                collapseItem(
                    "collapseProgramKerja",
                    "Program Kerja",
                    "assignment",
                    ['programKerja'],
                    '

        <!-- Program Kerja Saya -->
        <li class="sidebar-item">
            <a class="nav-link text-white ' . ($isIndexActive ? "active bg-gradient-success" : "") . '"
               href="?controller=programKerja&method=index">
                <i class="material-icons me-2 opacity-10">assignment</i>
                Program Kerja Saya
            </a>
        </li>

        ' . (isAnyLevel($id_level, [1, 5]) ? '

        <!-- Semua Program Kerja (Admin) -->
        <li class="sidebar-item">
            <a class="nav-link text-white ' . ($isIndexAdminActive ? "active bg-gradient-success" : "") . '"
               href="?controller=programKerja&method=indexAdmin">
                <i class="material-icons me-2 opacity-10">supervisor_account</i>
                Semua Program Kerja
            </a>
        </li>

        ' : '') . '
        ',
                    $id_level
                );
            }
            ?>


            <?php
            if (isAnyLevel($id_level, [1, 5, 6, 7, 10, 11, 12, 13, 14, 15, 16])) {

                $controller = $_GET['controller'] ?? '';
                $method     = $_GET['method'] ?? 'index';

                $isJurnalIndexActive  = ($controller === 'jurnalStruktural' && $method === 'index');
                $isJurnalHistoryActive = ($controller === 'jurnalStruktural' && $method === 'historyAdmin');

                collapseItem(
                    "collapseJurnalStruktural",
                    "Jurnal Struktural",
                    "book",
                    ['jurnalStruktural'],
                    '

        <!-- Input Jurnal Struktural -->
        <li class="sidebar-item">
            <a class="nav-link text-white ' . ($isJurnalIndexActive ? "active bg-gradient-success" : "") . '"
               href="?controller=jurnalStruktural&method=index">
                <i class="material-icons me-2 opacity-10">edit_note</i>
                Input Jurnal
            </a>
        </li>
' . (isAnyLevel($id_level, [1, 5]) ? '
        <!-- Rekap Jurnal Struktural -->
        <li class="sidebar-item">
            <a class="nav-link text-white ' . ($isJurnalHistoryActive ? "active bg-gradient-success" : "") . '"
               href="?controller=jurnalStruktural&method=historyAdmin">
                <i class="material-icons me-2 opacity-10">history</i>
                Rekap Jurnal
            </a>
        </li>
' : '') . '
        ',
                    $id_level
                );
            }
            ?>

            <?php if (isAnyLevel($id_level, [1])): // Sesuaikan ID Level yang boleh mengakses menu ini 
            ?>

                <li class="sidebar-item">
                    <a class="nav-link text-white <?= (isActive("kegiatan") ? "active bg-gradient-success" : "") ?> " href="?controller=kegiatan&method=index">
                        <i class="material-icons me-2 opacity-10">event_available</i> Kegiatan Lembaga
                    </a>
                </li>

            <?php endif; ?>


            <?php if (isAnyLevel($id_level, [1, 2, 3, 4, 5, 6, 7, 9])): ?>
                <?php collapseItem(
                    "collapsePerangkat",
                    "Perangkat Mengajar",
                    "source",
                    ['perangkat', 'verifikasi', 'deadlinePerangkat', 'rekat'],
                    '
                    <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("perangkat") ? "active bg-gradient-success" : "") . '" href="?controller=perangkat&method=index"><i class="material-icons me-2 opacity-10">note_add</i> Perangkat Saya</a></li>
                    ' . (isAnyLevel($id_level, [1, 5]) ? '
                        <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("rekat") ? "active bg-gradient-success" : "") . '" href="?controller=rekat&method=index"><i class="material-icons me-2 opacity-10">view_timeline</i> Rekap Perangkat</a></li>
                        <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("verifikasi") ? "active bg-gradient-success" : "") . '" href="?controller=verifikasi&method=index"><i class="material-icons me-2 opacity-10">task</i> Verifikasi Perangkat</a></li>
                        <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("deadlinePerangkat") ? "active bg-gradient-success" : "") . '" href="?controller=deadlinePerangkat&method=index"><i class="material-icons me-2 opacity-10">settings</i> Pengaturan Deadline</a></li>
                    ' : '') . '
                ',
                    $id_level
                ); ?>
            <?php endif; ?>
            <?php if (isAnyLevel($id_level, [1])): ?>
                <?php collapseItem(
                    "collapsePresensi",
                    "Presensi Pegawai",
                    "punch_clock",
                    ['presensi', 'absenManual', 'izinGuru', 'repres'],
                    '
<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("presensi") ? "active bg-gradient-success" : "") . '" href="?controller=presensi&method=index">
        <i class="material-icons me-2 opacity-10">how_to_reg</i> Presensi Harian
    </a>
</li>

<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("absenManual") ? "active bg-gradient-success" : "") . '" href="?controller=absenManual&method=index">
        <i class="material-icons me-2 opacity-10">edit_calendar</i> Absen Manual
    </a>
</li>

<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("izinGuru") ? "active bg-gradient-success" : "") . '" href="?controller=izinGuru&method=index">
        <i class="material-icons me-2 opacity-10">event_busy</i> Perizinan Pegawai
    </a>
</li>

<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("repres") ? "active bg-gradient-success" : "") . '" href="?controller=repres&method=index">
        <i class="material-icons me-2 opacity-10">summarize</i> Rekap Presensi
    </a>
</li>

',
                    $id_level
                ); ?>
            <?php else: ?>
                <?php collapseItem(
                    "collapsePresensi",
                    "Presensi",
                    "punch_clock",
                    ['presensi', 'izinGuru'],
                    '
<!-- <li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("presensi") ? "active bg-gradient-success" : "") . '" href="?controller=presensi&method=index">
        <i class="material-icons me-2 opacity-10">how_to_reg</i> Presensi Harian
    </a>
</li> -->

<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("izinGuru") ? "active bg-gradient-success" : "") . '" href="?controller=izinGuru&method=index">
        <i class="material-icons me-2 opacity-10">event_busy</i> Perizinan Pegawai
    </a>
</li>
',
                    $id_level
                ); ?>
            <?php endif; ?>


            <?php if (isAnyLevel($id_level, [1, 3, 4, 6, 7, 9])): ?>
                <li class="sidebar-item">
                    <a class="nav-link text-white <?= isActive('izin') ? 'active bg-gradient-success' : '' ?>" href="?controller=izin&method=index">
                        <i class="material-icons opacity-10 me-2">assignment_late</i> Perizinan Siswa
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 3, 4, 6, 7])): ?>
                <?php collapseItem(
                    "collapseCounseling",
                    "Konseling",
                    "psychology",
                    ['konseling', 'rekon'],
                    '
                        <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("konseling") ? "active bg-gradient-success" : "") . '" href="?controller=konseling&method=index"><i class="material-icons me-2 opacity-10">psychology</i> Bimbingan & Konseling</a></li>
                        <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("rekon") ? "active bg-gradient-success" : "") . '" href="?controller=rekon&method=index"><i class="material-icons me-2 opacity-10">print</i> Rekap Konseling</a></li>
                    ',
                    $id_level
                ); ?>
            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 5, 7])): ?>
                <li class="sidebar-item"><a class="nav-link text-white <?= isActive('kalender') ? 'active bg-gradient-success' : '' ?>" href="?controller=kalender&method=index"><i class="material-icons opacity-10 me-2">calendar_month</i> Kalender</a></li>
            <?php endif; ?>
            <li class="sidebar-item"><a class="nav-link text-white" href="?controller=auth&method=logout"><i class="material-icons opacity-10 me-2">logout</i> Logout</a></li>
        </ul>
    </div>
</aside>
<!-- MOBILE SIDEBAR -->
<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 bg-gradient-dark position-fixed d-xl-none"
    id="sidenav-mobile"
    style="top: 0; left: 0; height: 100vh; width: 260px; transform: translateX(-100%); transition: transform 0.3s ease; z-index: 1050;">

    <div class="sidenav-header">
        <i class="fas fa-times p-3 cursor-pointer text-white opacity-5 position-absolute end-0 top-0 d-xl-none" id="iconSidenav"></i>
        <a class="navbar-brand m-0" href="?controller=dashboard&method=index">
            <img src="assets/img/logo-ct2.png" class="navbar-brand-img" style="height: 50px; object-fit: contain;" alt="main_logo">
            <span class="ms-1 font-weight-bold text-white">SiBer PPRM</span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">

    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav accordion" id="accordionSidebar">
            <?php if (isAnyLevel($id_level, [1, 2, 3, 4, 5, 6, 7, 9, 10, 11, 12, 13, 14, 15, 16])): ?>
                <!-- Beranda -->
                <li class="sidebar-item">
                    <a class="nav-link text-white <?= isActive('dashboard') ? 'active bg-gradient-success' : '' ?>" href="?controller=dashboard&method=index">
                        <i class="material-icons opacity-10 me-2">dashboard</i> Beranda
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isLevel($id_level, 8)): ?>
                <li class="sidebar-item">
                    <a class="nav-link text-white <?= isActive('dashboard', 'index2') ? 'active bg-gradient-success' : '' ?>" href="?controller=dashboard&method=index2">
                        <i class="material-icons opacity-10 me-2">dashboard</i> Beranda
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 5, 6, 7])): ?>
                <?php
                collapseItem(
                    "collapseMaster",
                    "Master",
                    "folder",
                    ['kelas', 'siswa', 'mapel', 'tahunPelajaran', 'jadwal', 'user', 'ploting', 'setting'], // Tambahkan 'setting' di sini
                    // Isi list item
                    '
                ' . (
                        // Data Kelas: Admin (1), Kurikulum (5), Level 7
                        (isAnyLevel($id_level, [1, 5, 7]) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("kelas") ? "active bg-gradient-success" : "") . '" href="?controller=kelas&method=index"><i class="material-icons me-2 opacity-10">class</i> Data Kelas</a></li>'
                            : '')
                    ) . '

                ' . (
                        // Data Siswa: Admin (1), Kesiswaan (6), Level 7
                        (isAnyLevel($id_level, [1, 6, 7]) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("siswa") ? "active bg-gradient-success" : "") . '" href="?controller=siswa&method=index"><i class="material-icons me-2 opacity-10">groups</i> Data Siswa</a></li>'
                            : '')
                    ) . '
                    ' . (
                        // Data Siswa: Admin (1), Kesiswaan (6), Level 7
                        (isAnyLevel($id_level, [1, 6, 7]) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("ploting") ? "active bg-gradient-success" : "") . '" href="?controller=ploting&method=index"><i class="material-icons me-2 opacity-10">groups</i> Ploting Kelas</a></li>'
                            : '')
                    ) . '

                ' . (
                        // Data Mapel: Admin (1), Kurikulum (5), Level 7
                        (isAnyLevel($id_level, [1, 5, 7]) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("mapel") ? "active bg-gradient-success" : "") . '" href="?controller=mapel&method=index"><i class="material-icons me-2 opacity-10">library_books</i> Data Mapel</a></li>'
                            : '')
                    ) . '

                ' . (
                        // Jadwal Pelajaran: Admin (1), Kurikulum (5), Level 7
                        (isAnyLevel($id_level, [1, 5, 7]) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("jadwal") ? "active bg-gradient-success" : "") . '" href="?controller=jadwal&method=index"><i class="material-icons me-2 opacity-10">event_note</i> Jadwal Pelajaran</a></li>'
                            : '')
                    ) . '

                ' . (
                        // Tahun Pelajaran: Admin (1)
                        (isLevel($id_level, 1) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("tahunPelajaran") ? "active bg-gradient-success" : "") . '" href="?controller=tahunPelajaran&method=index"><i class="material-icons me-2 opacity-10">date_range</i> Tahun Pelajaran</a></li>'
                            : '')
                    ) . '
' . (
                        // Setting WA Jurnal: Admin (1)
                        (isLevel($id_level, 1) ?
                            '<li class="sidebar-item"><a class="nav-link text-white ' . ((isActive("setting")) ? "active bg-gradient-success" : "") . '" href="?controller=setting&method=index"><i class="material-icons me-2 opacity-10">notification_important</i> Setting Notif WA</a></li>'
                            : '')
                    ) . '

                ' . (
                        // Data Pegawai: Semua level yang berhak masuk Master
                        '<li class="sidebar-item"><a class="nav-link text-white ' . (isActive("user") ? "active bg-gradient-success" : "") . '" href="?controller=user&method=index"><i class="material-icons me-2 opacity-10">person</i> Data Pegawai</a></li>'
                    ),
                    $id_level
                );
                ?>
            <?php endif; ?>



            <?php if (isAnyLevel($id_level, [1, 2, 3, 4, 5, 6, 7, 9])): ?>
                <?php collapseItem(
                    "collapseLearning",
                    "Pembelajaran",
                    "school",
                    ['penilaian', 'laporan', 'jurnal', 'tp', 'rekap', 'rekaptp', 'leger'], // ✅ tambahin rekaptp
                    '
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("penilaian") ? "active bg-gradient-success" : "") . '" href="?controller=penilaian&method=index"><i class="material-icons me-2 opacity-10">grading</i> Penilaian</a></li>
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("jurnal") ? "active bg-gradient-success" : "") . '" href="?controller=jurnal&method=index"><i class="material-icons me-2 opacity-10">book</i> Jurnal</a></li>
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("tp") ? "active bg-gradient-success" : "") . '" href="?controller=tp&method=index"><i class="material-icons me-2 opacity-10">local_library</i> Tujuan Pembelajaran</a></li>
            ' . (isAnyLevel($id_level, [1, 5]) ? '
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("jurnal", "rekap") ? "active bg-gradient-success" : "") . '" href="?controller=jurnal&method=rekap"><i class="material-icons me-2 opacity-10">summarize</i> Rekap Jurnal</a></li>
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("tp", "rekaptp") ? "active bg-gradient-success" : "") . '" href="?controller=tp&method=rekaptp"><i class="material-icons me-2 opacity-10">summarize</i> Rekap TP</a></li>
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("laporan", "index") ? "active bg-gradient-success" : "") . '" href="?controller=laporan&method=index"><i class="material-icons me-2 opacity-10">summarize</i> Rekap Nilai</a></li>
            ' : '') . '
            ' . (isAnyLevel($id_level, [1, 3, 5]) ? '
            <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("leger", "index") ? "active bg-gradient-success" : "") . '" href="?controller=leger&method=index"><i class="material-icons me-2 opacity-10">summarize</i> Legger Nilai</a></li>

            ' : '') . '
            
            ',
                    $id_level
                ); ?>
            <?php endif; ?>


            <?php if (isAnyLevel($id_level, [1, 2, 3, 4, 5, 6, 7, 9])): // Gunakan level akses yang sama 
            ?>
                <?php collapseItem(
                    "collapseKehadiran",      // ID unik untuk collapse
                    "Rekap Kehadiran",        // Judul menu utama
                    "view_timeline",          // Ikon menu utama
                    ['kehadiran'],            // Controller yang membuat menu ini aktif
                    '
            <li class="sidebar-item">
                <a class="nav-link text-white ' . (isActive("kehadiran", "index") ? "active bg-gradient-success" : "") . '" href="?controller=kehadiran&method=index">
                    <i class="material-icons me-2 opacity-10">today</i> Rekap Harian
                </a>
            </li>
            ' . (isAnyLevel($id_level, [1, 2, 3, 4, 5, 6, 7, 9]) ? '
            <li class="sidebar-item">
                <a class="nav-link text-white ' . (isActive("kehadiran", "rekapBulanan") ? "active bg-gradient-success" : "") . '" href="?controller=kehadiran&method=rekapBulanan">
                    <i class="material-icons me-2 opacity-10">calendar_month</i> Rekap Bulanan
                </a>
            </li>
            ' : '') . '
        ',
                    $id_level
                ); ?>
            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 3])): ?>
                <?php collapseItem(
                    "collapseRapor",
                    "Layanan Rapor",
                    "description", // Icon material untuk rapor
                    ['adminRapor', 'rapor'],
                    '
        ' . (isAnyLevel($id_level, [1]) ? '
        <li class="sidebar-item">
            <a class="nav-link text-white ' . (isActive("adminRapor", "index") ? "active bg-gradient-success" : "") . '" href="?controller=adminRapor&method=index">
                <i class="material-icons me-2 opacity-10">settings</i> Setting Rapor
            </a>
        </li>
        ' : '') . '
        
        ' . (isAnyLevel($id_level, [3]) ? '
        <li class="sidebar-item">
            <a class="nav-link text-white ' . (isActive("rapor", "index") ? "active bg-gradient-success" : "") . '" href="?controller=rapor&method=index">
                <i class="material-icons me-2 opacity-10">edit_note</i> Input Rapor Siswa
            </a>
        </li>
        ' : '') . '
        ',
                    $id_level
                ); ?>
            <?php endif; ?>

            <?php
            if (isAnyLevel($id_level, [1, 2])): // Tambahkan ID Level guru Anda di sini (misal 2 atau 3)
            ?>

                <li class="sidebar-item">
                    <a class="nav-link text-white <?= isActive('ekstra') ? 'active bg-gradient-success' : '' ?>" href="?controller=ekstra&method=index">
                        <i class="material-icons me-2 opacity-10">groups</i> Manajemen Ekstra
                    </a>
                </li>

            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 6, 7])): // Sesuaikan level yang diizinkan 
            ?>
                <li class="sidebar-item">
                    <a class="nav-link text-white <?= isActive('prestasi') ? 'active bg-gradient-success' : '' ?>" href="?controller=prestasi&method=index">
                        <i class="material-icons opacity-10 me-2">emoji_events</i>
                        <span class="nav-link-text ms-1">Prestasi Siswa</span>
                    </a>
                </li>
            <?php endif; ?>

            <?php
            if (isAnyLevel($id_level, [5, 6, 10, 11, 12, 13, 14, 15, 16])) {
                collapseItem(
                    "collapseProgramStruktural",
                    "Program Struktural",
                    "source",
                    ['programStruktural', 'deadlineProgramStruktural', 'verifikasiProgramStruktural'],
                    '
            <li class="sidebar-item">
                <a class="nav-link text-white ' . (isActive("programStruktural") ? "active bg-gradient-success" : "") . '" 
                   href="?controller=programStruktural&method=index">
                   <i class="material-icons me-2 opacity-10">source</i> Program Struktural
                </a>
            </li>
            ' . (isAnyLevel($id_level, [1, 5]) ? '
            <li class="sidebar-item">
                <a class="nav-link text-white ' . (isActive("deadlineProgramStruktural") ? "active bg-gradient-success" : "") . '" 
                   href="?controller=deadlineProgramStruktural&method=index">
                   <i class="material-icons me-2 opacity-10">settings</i> Pengaturan Deadline
                </a>
            </li>
            <li class="sidebar-item">
                <a class="nav-link text-white ' . (isActive("verifikasiProgramStruktural") ? "active bg-gradient-success" : "") . '" 
                   href="?controller=verifikasiProgramStruktural&method=index">
                   <i class="material-icons me-2 opacity-10">task</i> Verifikasi Program
                </a>
            </li>
            ' : '') . '
        ',
                    $id_level
                );
            }

            ?>


            <?php
            if (isAnyLevel($id_level, [1, 5, 6, 7, 10, 11, 12, 13, 14, 15, 16])) {

                $method = $_GET['method'] ?? 'index';

                $isIndexActive      = ($method === 'index');
                $isIndexAdminActive = ($method === 'indexAdmin');

                collapseItem(
                    "collapseProgramKerja",
                    "Program Kerja",
                    "assignment",
                    ['programKerja'],
                    '

        <!-- Program Kerja Saya -->
        <li class="sidebar-item">
            <a class="nav-link text-white ' . ($isIndexActive ? "active bg-gradient-success" : "") . '"
               href="?controller=programKerja&method=index">
                <i class="material-icons me-2 opacity-10">assignment</i>
                Program Kerja Saya
            </a>
        </li>

        ' . (isAnyLevel($id_level, [1, 5]) ? '

        <!-- Semua Program Kerja (Admin) -->
        <li class="sidebar-item">
            <a class="nav-link text-white ' . ($isIndexAdminActive ? "active bg-gradient-success" : "") . '"
               href="?controller=programKerja&method=indexAdmin">
                <i class="material-icons me-2 opacity-10">supervisor_account</i>
                Semua Program Kerja
            </a>
        </li>

        ' : '') . '
        ',
                    $id_level
                );
            }
            ?>


            <?php
            if (isAnyLevel($id_level, [1, 5, 6, 10, 11, 12, 13, 14, 15, 16])) {

                $controller = $_GET['controller'] ?? '';
                $method     = $_GET['method'] ?? 'index';

                $isJurnalIndexActive  = ($controller === 'jurnalStruktural' && $method === 'index');
                $isJurnalHistoryActive = ($controller === 'jurnalStruktural' && $method === 'historyAdmin');

                collapseItem(
                    "collapseJurnalStruktural",
                    "Jurnal Struktural",
                    "book",
                    ['jurnalStruktural'],
                    '

        <!-- Input Jurnal Struktural -->
        <li class="sidebar-item">
            <a class="nav-link text-white ' . ($isJurnalIndexActive ? "active bg-gradient-success" : "") . '"
               href="?controller=jurnalStruktural&method=index">
                <i class="material-icons me-2 opacity-10">edit_note</i>
                Input Jurnal
            </a>
        </li>
' . (isAnyLevel($id_level, [1, 5]) ? '
        <!-- Rekap Jurnal Struktural -->
        <li class="sidebar-item">
            <a class="nav-link text-white ' . ($isJurnalHistoryActive ? "active bg-gradient-success" : "") . '"
               href="?controller=jurnalStruktural&method=historyAdmin">
                <i class="material-icons me-2 opacity-10">history</i>
                Rekap Jurnal
            </a>
        </li>
' : '') . '
        ',
                    $id_level
                );
            }
            ?>


            <?php if (isAnyLevel($id_level, [1])): // Sesuaikan ID Level yang boleh mengakses menu ini 
            ?>

                <li class="sidebar-item">
                    <a class="nav-link text-white <?= (isActive("kegiatan") ? "active bg-gradient-success" : "") ?> " href="?controller=kegiatan&method=index">
                        <i class="material-icons me-2 opacity-10">event_available</i> Kegiatan Lembaga
                    </a>
                </li>

            <?php endif; ?>


            <?php if (isAnyLevel($id_level, [1, 2, 3, 4, 5, 6, 7, 9])): ?>
                <?php collapseItem(
                    "collapsePerangkat",
                    "Perangkat Mengajar",
                    "source",
                    ['perangkat', 'verifikasi', 'deadlinePerangkat', 'rekat'],
                    '
                    <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("perangkat") ? "active bg-gradient-success" : "") . '" href="?controller=perangkat&method=index"><i class="material-icons me-2 opacity-10">note_add</i> Perangkat Saya</a></li>
                    ' . (isAnyLevel($id_level, [1, 5]) ? '
                        <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("rekat") ? "active bg-gradient-success" : "") . '" href="?controller=rekat&method=index"><i class="material-icons me-2 opacity-10">view_timeline</i> Rekap Perangkat</a></li>
                        <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("verifikasi") ? "active bg-gradient-success" : "") . '" href="?controller=verifikasi&method=index"><i class="material-icons me-2 opacity-10">task</i> Verifikasi Perangkat</a></li>
                        <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("deadlinePerangkat") ? "active bg-gradient-success" : "") . '" href="?controller=deadlinePerangkat&method=index"><i class="material-icons me-2 opacity-10">settings</i> Pengaturan Deadline</a></li>
                    ' : '') . '
                ',
                    $id_level
                ); ?>
            <?php endif; ?>
            <?php if (isAnyLevel($id_level, [1])): ?>
                <?php collapseItem(
                    "collapsePresensi",
                    "Presensi Pegawai",
                    "punch_clock",
                    ['presensi', 'absenManual', 'izinGuru', 'repres'],
                    '
<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("presensi") ? "active bg-gradient-success" : "") . '" href="?controller=presensi&method=index">
        <i class="material-icons me-2 opacity-10">how_to_reg</i> Presensi Harian
    </a>
</li>

<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("absenManual") ? "active bg-gradient-success" : "") . '" href="?controller=absenManual&method=index">
        <i class="material-icons me-2 opacity-10">edit_calendar</i> Absen Manual
    </a>
</li>

<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("izinGuru") ? "active bg-gradient-success" : "") . '" href="?controller=izinGuru&method=index">
        <i class="material-icons me-2 opacity-10">event_busy</i> Perizinan Pegawai
    </a>
</li>

<!-- Hidden sementara
<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("repres") ? "active bg-gradient-success" : "") . '" href="?controller=repres&method=index">
        <i class="material-icons me-2 opacity-10">summarize</i> Rekap Presensi
    </a>
</li>
-->
',
                    $id_level
                ); ?>
            <?php else: ?>
                <?php collapseItem(
                    "collapsePresensi",
                    "Presensi",
                    "punch_clock",
                    ['presensi', 'izinGuru'],
                    '
                    <!-- Hidden sementara
<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("presensi") ? "active bg-gradient-success" : "") . '" href="?controller=presensi&method=index">
        <i class="material-icons me-2 opacity-10">how_to_reg</i> Presensi Harian
    </a>
</li>
-->

<li class="sidebar-item">
    <a class="nav-link text-white ' . (isActive("izinGuru") ? "active bg-gradient-success" : "") . '" href="?controller=izinGuru&method=index">
        <i class="material-icons me-2 opacity-10">event_busy</i> Perizinan Pegawai
    </a>
</li>
',
                    $id_level
                ); ?>
            <?php endif; ?>


            <?php if (isAnyLevel($id_level, [1, 3, 4, 6, 7, 9])): ?>
                <li class="sidebar-item">
                    <a class="nav-link text-white <?= isActive('izin') ? 'active bg-gradient-success' : '' ?>" href="?controller=izin&method=index">
                        <i class="material-icons opacity-10 me-2">assignment_late</i> Perizinan Siswa
                    </a>
                </li>
            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 3, 4, 6, 7])): ?>
                <?php collapseItem(
                    "collapseCounseling",
                    "Konseling",
                    "psychology",
                    ['konseling', 'rekon'],
                    '
                        <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("konseling") ? "active bg-gradient-success" : "") . '" href="?controller=konseling&method=index"><i class="material-icons me-2 opacity-10">psychology</i> Bimbingan & Konseling</a></li>
                        <li class="sidebar-item"><a class="nav-link text-white ' . (isActive("rekon") ? "active bg-gradient-success" : "") . '" href="?controller=rekon&method=index"><i class="material-icons me-2 opacity-10">print</i> Rekap Konseling</a></li>
                    ',
                    $id_level
                ); ?>
            <?php endif; ?>

            <?php if (isAnyLevel($id_level, [1, 5, 7])): ?>
                <li class="sidebar-item"><a class="nav-link text-white <?= isActive('kalender') ? 'active bg-gradient-success' : '' ?>" href="?controller=kalender&method=index"><i class="material-icons opacity-10 me-2">calendar_month</i> Kalender</a></li>
            <?php endif; ?>
            <li class="sidebar-item"><a class="nav-link text-white" href="?controller=auth&method=logout"><i class="material-icons opacity-10 me-2">logout</i> Logout</a></li>
        </ul>
    </div>
</aside>

<!-- Main Content Start -->
<main class="main-content position-relative h-100 border-radius-lg" id="main-content">