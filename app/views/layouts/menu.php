<?php
/**
 * Sumber tunggal struktur menu SIBER.
 *
 * Dipakai oleh:
 *  - app/views/layouts/sidebar.php     (navigasi desktop)
 *  - app/views/layouts/mobile_nav.php  (bottom navigation mobile)
 *
 * Menu baru cukup ditambahkan di sini satu kali, supaya desktop dan mobile
 * tidak pernah berbeda lagi (dulu ada dua sidebar terpisah yang isinya
 * menyimpang: role 7 kehilangan Jurnal Struktural, label "Setting" vs
 * "Pengaturan", dsb).
 */

if (!function_exists('siber_all_levels')) {
    /** Semua level pegawai yang dikenal (1=admin ... 16=staff pondok). */
    function siber_all_levels(): array
    {
        return [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16];
    }
}

if (!function_exists('siber_level')) {
    function siber_level()
    {
        return $_SESSION['user']['level'] ?? 0;
    }
}

if (!function_exists('siber_can')) {
    function siber_can($levels): bool
    {
        if (empty($levels)) {
            return false;
        }
        return isAnyLevel(siber_level(), array_values((array) $levels));
    }
}

if (!function_exists('siber_nav_active')) {
    /**
     * @param array       $controllers daftar controller yang dianggap aktif
     * @param string|null $method      null = semua method controller tersebut
     */
    function siber_nav_active(array $controllers, $method = 'index'): bool
    {
        $current = $_GET['controller'] ?? '';
        if (!in_array($current, $controllers, true)) {
            return false;
        }
        if ($method === null) {
            return true;
        }
        return ($_GET['method'] ?? 'index') === $method;
    }
}

if (!function_exists('siber_menu')) {
    /**
     * Struktur menu lengkap. Urutan array = urutan tampil di sidebar desktop.
     *
     * Tiap entri:
     *   type        'item' | 'group'
     *   label       teks menu
     *   icon        nama ikon Material Icons
     *   href        (item) URL relatif
     *   controllers (item) controller untuk deteksi menu aktif
     *   method      (item) method untuk deteksi aktif, null = semua method
     *   levels      level yang boleh melihat
     *   id/items    (group) id collapse + daftar anak
     *   label_by_level (opsional) label khusus per level
     */
    function siber_menu(): array
    {
        static $menu = null;
        if ($menu !== null) {
            return $menu;
        }

        $semua = siber_all_levels();
        $staf  = [1, 2, 3, 4, 5, 6, 7, 9, 10, 11, 12, 13, 14, 15, 16];

        $menu = [
            [
                'type' => 'item',
                'label' => 'Beranda',
                'icon' => 'dashboard',
                'href' => '?controller=dashboard&method=index',
                'href_by_level' => [8 => '?controller=dashboard&method=index2'],
                'controllers' => ['dashboard'],
                'method' => null,
                'levels' => $semua,
            ],
            [
                'type' => 'group',
                'id' => 'collapseMaster',
                'label' => 'Master',
                'icon' => 'folder',
                'levels' => [1, 5, 6, 7],
                'controllers' => ['kelas', 'siswa', 'siswaKeluar', 'mapel', 'tahunPelajaran', 'jadwal', 'user', 'ploting', 'setting', 'activityLog'],
                'items' => [
                    ['label' => 'Data Kelas', 'icon' => 'class', 'href' => '?controller=kelas&method=index', 'controllers' => ['kelas'], 'levels' => [1, 5, 7]],
                    ['label' => 'Data Siswa', 'icon' => 'groups', 'href' => '?controller=siswa&method=index', 'controllers' => ['siswa'], 'levels' => [1, 6, 7]],
                    ['label' => 'Ploting Kelas', 'icon' => 'groups', 'href' => '?controller=ploting&method=index', 'controllers' => ['ploting'], 'levels' => [1, 6, 7]],
                    ['label' => 'Anggota Kelas', 'icon' => 'people', 'href' => '?controller=ploting&method=anggota', 'controllers' => ['ploting'], 'method' => 'anggota', 'levels' => [1, 6, 7]],
                    ['label' => 'PD Keluar', 'icon' => 'person_off', 'href' => '?controller=siswaKeluar&method=index', 'controllers' => ['siswaKeluar'], 'levels' => [1, 6, 7]],
                    ['label' => 'Data Mapel', 'icon' => 'library_books', 'href' => '?controller=mapel&method=index', 'controllers' => ['mapel'], 'levels' => [1, 5, 7]],
                    ['label' => 'Jadwal Pelajaran', 'icon' => 'event_note', 'href' => '?controller=jadwal&method=index', 'controllers' => ['jadwal'], 'levels' => [1, 5, 7]],
                    ['label' => 'Tahun Pelajaran', 'icon' => 'date_range', 'href' => '?controller=tahunPelajaran&method=index', 'controllers' => ['tahunPelajaran'], 'levels' => [1]],
                    ['label' => 'Setting', 'icon' => 'settings', 'href' => '?controller=setting&method=index', 'controllers' => ['setting'], 'levels' => [1]],
                    ['label' => 'Activity Log', 'icon' => 'history_edu', 'href' => '?controller=activityLog&method=index', 'controllers' => ['activityLog'], 'levels' => [1]],
                    ['label' => 'Data Pegawai', 'icon' => 'person', 'href' => '?controller=user&method=index', 'controllers' => ['user'], 'levels' => [1, 5, 6, 7]],
                ],
            ],
            [
                'type' => 'group',
                'id' => 'collapseLearning',
                'label' => 'Pembelajaran',
                'icon' => 'school',
                'levels' => [1, 2, 3, 4, 5, 6, 7, 9],
                'controllers' => ['penilaian', 'jurnal', 'tp', 'laporan', 'leger'],
                'items' => [
                    ['label' => 'Penilaian', 'icon' => 'grading', 'href' => '?controller=penilaian&method=index', 'controllers' => ['penilaian'], 'levels' => [1, 2, 3, 4, 5, 6, 7, 9]],
                    ['label' => 'Jurnal', 'icon' => 'book', 'href' => '?controller=jurnal&method=index', 'controllers' => ['jurnal'], 'levels' => [1, 2, 3, 4, 5, 6, 7, 9]],
                    ['label' => 'Tujuan Pembelajaran', 'icon' => 'local_library', 'href' => '?controller=tp&method=index', 'controllers' => ['tp'], 'levels' => [1, 2, 3, 4, 5, 6, 7, 9]],
                    ['label' => 'Rekap Jurnal', 'icon' => 'summarize', 'href' => '?controller=jurnal&method=rekap', 'controllers' => ['jurnal'], 'method' => 'rekap', 'levels' => [1, 5]],
                    ['label' => 'Rekap TP', 'icon' => 'summarize', 'href' => '?controller=tp&method=rekaptp', 'controllers' => ['tp'], 'method' => 'rekaptp', 'levels' => [1, 5]],
                    ['label' => 'Rekap Nilai', 'icon' => 'summarize', 'href' => '?controller=laporan&method=index', 'controllers' => ['laporan'], 'levels' => [1, 5]],
                    ['label' => 'Legger Nilai', 'icon' => 'summarize', 'href' => '?controller=leger&method=index', 'controllers' => ['leger'], 'levels' => [1, 3, 5]],
                ],
            ],
            [
                'type' => 'group',
                'id' => 'collapseRapor',
                'label' => 'Layanan Rapor',
                'icon' => 'description',
                'levels' => [1, 3, 5],
                'controllers' => ['adminRapor', 'rapor'],
                'items' => [
                    ['label' => 'Setting Rapor', 'icon' => 'settings', 'href' => '?controller=adminRapor&method=index', 'controllers' => ['adminRapor'], 'levels' => [1]],
                    ['label' => 'Input Rapor Siswa', 'icon' => 'edit_note', 'href' => '?controller=rapor&method=index', 'controllers' => ['rapor'], 'levels' => [1, 3, 5]],
                ],
            ],
            [
                'type' => 'item',
                'label' => 'Manajemen Ekstra',
                'icon' => 'groups',
                'href' => '?controller=ekstra&method=index',
                'controllers' => ['ekstra'],
                'levels' => [1, 2],
            ],
            [
                'type' => 'item',
                'label' => 'Urutkan Siswa',
                'icon' => 'low_priority',
                'href' => '?controller=urutkanSiswa&method=index',
                'controllers' => ['urutkanSiswa'],
                'levels' => [1, 3, 5, 6],
            ],
            [
                'type' => 'item',
                'label' => 'Prestasi Siswa',
                'icon' => 'emoji_events',
                'href' => '?controller=prestasi&method=index',
                'controllers' => ['prestasi'],
                'levels' => [1, 6, 7],
            ],
            [
                'type' => 'group',
                'id' => 'collapseKehadiran',
                'label' => 'Rekap Kehadiran',
                'icon' => 'view_timeline',
                'levels' => [1, 2, 3, 4, 5, 6, 7, 9],
                'controllers' => ['kehadiran'],
                'items' => [
                    ['label' => 'Rekap Harian', 'icon' => 'today', 'href' => '?controller=kehadiran&method=index', 'controllers' => ['kehadiran'], 'levels' => [1, 2, 3, 4, 5, 6, 7, 9]],
                    ['label' => 'Rekap Bulanan', 'icon' => 'calendar_month', 'href' => '?controller=kehadiran&method=rekapBulanan', 'controllers' => ['kehadiran'], 'method' => 'rekapBulanan', 'levels' => [1, 2, 3, 4, 5, 6, 7, 9]],
                ],
            ],
            [
                'type' => 'group',
                'id' => 'collapseProgramStruktural',
                'label' => 'Program Struktural',
                'icon' => 'source',
                'levels' => [1, 5, 6, 10, 11, 12, 13, 14, 15, 16],
                'controllers' => ['programStruktural', 'deadlineProgramStruktural', 'verifikasiProgramStruktural', 'jenisProgramStruktural'],
                'items' => [
                    ['label' => 'Program Struktural', 'icon' => 'source', 'href' => '?controller=programStruktural&method=index', 'controllers' => ['programStruktural'], 'levels' => [1, 5, 6, 7, 10, 11, 12, 13, 14, 15, 16]],
                    ['label' => 'Pengaturan Deadline', 'icon' => 'settings', 'href' => '?controller=deadlineProgramStruktural&method=index', 'controllers' => ['deadlineProgramStruktural'], 'levels' => [1, 5]],
                    ['label' => 'Verifikasi Program', 'icon' => 'task', 'href' => '?controller=verifikasiProgramStruktural&method=index', 'controllers' => ['verifikasiProgramStruktural'], 'levels' => [1, 5]],
                    ['label' => 'Jenis Program Struktural', 'icon' => 'category', 'href' => '?controller=jenisProgramStruktural&method=index', 'controllers' => ['jenisProgramStruktural'], 'levels' => [1]],
                ],
            ],
            [
                'type' => 'group',
                'id' => 'collapseProgramKerja',
                'label' => 'Program Kerja',
                'icon' => 'assignment',
                'levels' => [1, 5, 6, 7, 10, 11, 12, 13, 14, 15, 16],
                'controllers' => ['programKerja'],
                'items' => [
                    ['label' => 'Program Kerja Saya', 'icon' => 'assignment', 'href' => '?controller=programKerja&method=index', 'controllers' => ['programKerja'], 'levels' => [1, 5, 6, 7, 10, 11, 12, 13, 14, 15, 16]],
                    ['label' => 'Semua Program Kerja', 'icon' => 'supervisor_account', 'href' => '?controller=programKerja&method=indexAdmin', 'controllers' => ['programKerja'], 'method' => 'indexAdmin', 'levels' => [1, 5]],
                ],
            ],
            [
                'type' => 'group',
                'id' => 'collapseJurnalStruktural',
                'label' => 'Jurnal Struktural',
                'icon' => 'book',
                'levels' => $semua,
                'controllers' => ['jurnalStruktural'],
                'items' => [
                    // Input jurnal hanya untuk Tenaga Kependidikan (id_jabatan = 2).
                    ['label' => 'Input Jurnal', 'icon' => 'edit_note', 'href' => '?controller=jurnalStruktural&method=index', 'controllers' => ['jurnalStruktural'], 'levels' => $semua, 'jabatan' => [2]],
                    ['label' => 'Rekap Jurnal', 'icon' => 'history', 'href' => '?controller=jurnalStruktural&method=historyAdmin', 'controllers' => ['jurnalStruktural'], 'method' => 'historyAdmin', 'levels' => [1, 5]],
                ],
            ],
            [
                'type' => 'item',
                'label' => 'Kegiatan Lembaga',
                'icon' => 'event_available',
                'href' => '?controller=kegiatan&method=index',
                'controllers' => ['kegiatan'],
                'levels' => [1],
            ],
            [
                'type' => 'group',
                'id' => 'collapsePerangkat',
                'label' => 'Perangkat Mengajar',
                'icon' => 'source',
                'levels' => [1, 2, 3, 4, 5, 6, 7, 9],
                'controllers' => ['perangkat', 'rekat', 'verifikasi', 'deadlinePerangkat', 'jenisPerangkat'],
                'items' => [
                    ['label' => 'Perangkat Saya', 'icon' => 'note_add', 'href' => '?controller=perangkat&method=index', 'controllers' => ['perangkat'], 'levels' => [1, 2, 3, 4, 5, 6, 7, 9]],
                    ['label' => 'Rekap Perangkat', 'icon' => 'view_timeline', 'href' => '?controller=rekat&method=index', 'controllers' => ['rekat'], 'levels' => [1, 5]],
                    ['label' => 'Verifikasi Perangkat', 'icon' => 'task', 'href' => '?controller=verifikasi&method=index', 'controllers' => ['verifikasi'], 'levels' => [1, 5]],
                    ['label' => 'Pengaturan Deadline', 'icon' => 'settings', 'href' => '?controller=deadlinePerangkat&method=index', 'controllers' => ['deadlinePerangkat'], 'levels' => [1, 5]],
                    ['label' => 'Jenis Perangkat', 'icon' => 'category', 'href' => '?controller=jenisPerangkat&method=index', 'controllers' => ['jenisPerangkat'], 'levels' => [1]],
                ],
            ],
            [
                'type' => 'group',
                'id' => 'collapsePresensi',
                'label' => 'Presensi',
                'label_by_level' => [1 => 'Presensi Pegawai'],
                'icon' => 'punch_clock',
                'levels' => $semua,
                'controllers' => ['presensi', 'absenManual', 'izinGuru', 'repres'],
                'items' => [
                    ['label' => 'Presensi Harian', 'icon' => 'how_to_reg', 'href' => '?controller=presensi&method=index', 'controllers' => ['presensi'], 'levels' => $semua],
                    ['label' => 'Absen Manual', 'icon' => 'edit_calendar', 'href' => '?controller=absenManual&method=index', 'controllers' => ['absenManual'], 'levels' => [1]],
                    ['label' => 'Perizinan Pegawai', 'icon' => 'event_busy', 'href' => '?controller=izinGuru&method=index', 'controllers' => ['izinGuru'], 'levels' => $semua],
                    ['label' => 'Rekap Presensi', 'icon' => 'summarize', 'href' => '?controller=repres&method=index', 'controllers' => ['repres'], 'levels' => [1]],
                ],
            ],
            [
                'type' => 'item',
                'label' => 'Perizinan Siswa',
                'icon' => 'assignment_late',
                'href' => '?controller=izin&method=index',
                'controllers' => ['izin'],
                'levels' => [1, 3, 4, 6, 7, 9],
            ],
            [
                'type' => 'group',
                'id' => 'collapseCounseling',
                'label' => 'Konseling',
                'icon' => 'psychology',
                'levels' => [1, 3, 4, 6, 7],
                'controllers' => ['konseling', 'rekon'],
                'items' => [
                    ['label' => 'Bimbingan & Konseling', 'icon' => 'psychology', 'href' => '?controller=konseling&method=index', 'controllers' => ['konseling'], 'levels' => [1, 3, 4, 6, 7]],
                    ['label' => 'Rekap Konseling', 'icon' => 'print', 'href' => '?controller=rekon&method=index', 'controllers' => ['rekon'], 'levels' => [1, 3, 4, 6, 7]],
                ],
            ],
            [
                'type' => 'item',
                'label' => 'Kalender',
                'icon' => 'calendar_month',
                'href' => '?controller=kalender&method=index',
                'controllers' => ['kalender'],
                'levels' => [1, 5, 7],
            ],
            [
                'type' => 'item',
                'label' => 'Logout',
                'icon' => 'logout',
                'href' => '?controller=auth&method=logout',
                'controllers' => ['auth'],
                'method' => null,
                'levels' => $semua,
            ],
        ];

        return $menu;
    }
}

if (!function_exists('siber_menu_for_level')) {
    /** Menu yang sudah disaring sesuai level user (grup kosong dibuang). */
    function siber_menu_for_level($level = null): array
    {
        $level = $level ?? siber_level();
        $hasil = [];
        foreach (siber_menu() as $entry) {
            if (!isAnyLevel($level, array_values((array) $entry['levels']))) {
                continue;
            }
            if (isset($entry['jabatan']) && !is_jabatan($entry['jabatan'])) {
                continue;
            }
            if ($entry['type'] === 'group') {
                $anak = [];
                foreach ($entry['items'] as $anakItem) {
                    if (isset($anakItem['jabatan']) && !is_jabatan($anakItem['jabatan'])) {
                        continue;
                    }
                    if (isAnyLevel($level, array_values((array) $anakItem['levels']))) {
                        $anak[] = $anakItem;
                    }
                }
                if (empty($anak)) {
                    continue;
                }
                $entry['items'] = $anak;
            }
            $hasil[] = $entry;
        }
        return $hasil;
    }
}

if (!function_exists('siber_item_label')) {
    function siber_item_label(array $item): string
    {
        $level = siber_level();
        if (!empty($item['label_by_level'])) {
            foreach ($item['label_by_level'] as $lvl => $label) {
                if (isAnyLevel($level, [(int) $lvl])) {
                    return $label;
                }
            }
        }
        return $item['label'];
    }
}

if (!function_exists('siber_item_href')) {
    function siber_item_href(array $item): string
    {
        $level = siber_level();
        if (!empty($item['href_by_level'])) {
            foreach ($item['href_by_level'] as $lvl => $href) {
                if (isAnyLevel($level, [(int) $lvl])) {
                    return $href;
                }
            }
        }
        return $item['href'] ?? '#';
    }
}

if (!function_exists('siber_menu_index')) {
    /** Peta href => item, dipakai untuk mencari level sebuah tautan. */
    function siber_menu_index(): array
    {
        static $index = null;
        if ($index !== null) {
            return $index;
        }
        $index = [];
        foreach (siber_menu() as $entry) {
            if ($entry['type'] === 'group') {
                foreach ($entry['items'] as $anak) {
                    $index[$anak['href']] = $anak;
                }
            } else {
                $index[$entry['href']] = $entry;
            }
        }
        return $index;
    }
}

if (!function_exists('siber_bottom_nav')) {
    /**
     * Item bottom navigation mobile: Beranda + 3 menu utama sesuai role + Lainnya.
     * Kalau role tidak punya akses rapor, slotnya diisi Rekap Kehadiran, lalu
     * Program Kerja / Perangkat / Presensi sebagai cadangan.
     */
    function siber_bottom_nav(): array
    {
        $level = siber_level();
        $index = siber_menu_index();

        $prioritas = [
            '?controller=penilaian&method=index',
            '?controller=jurnal&method=index',
            '?controller=rapor&method=index',
            '?controller=kehadiran&method=index',
            '?controller=programKerja&method=index',
            '?controller=perangkat&method=index',
            '?controller=jurnalStruktural&method=index',
            '?controller=presensi&method=index',
        ];

        $beranda = $index['?controller=dashboard&method=index'];
        $nav = [[
            'label' => 'Beranda',
            'icon' => 'dashboard',
            'href' => siber_item_href($beranda),
            'controllers' => $beranda['controllers'],
            'method' => null,
        ]];

        // Label pendek supaya muat di bottom bar 5 slot (maks ±11 karakter).
        $labelPendek = [
            '?controller=rapor&method=index' => 'Rapor',
            '?controller=kehadiran&method=index' => 'Kehadiran',
            '?controller=programKerja&method=index' => 'Proker',
            '?controller=jurnalStruktural&method=index' => 'Jurnal',
            '?controller=presensi&method=index' => 'Presensi',
            '?controller=perangkat&method=index' => 'Perangkat',
        ];

        $dipakai = ['?controller=dashboard&method=index'];
        foreach ($prioritas as $href) {
            if (count($nav) >= 4) {
                break;
            }
            if (!isset($index[$href]) || in_array($href, $dipakai, true)) {
                continue;
            }
            if (!empty($index[$href]['jabatan']) && !is_jabatan($index[$href]['jabatan'])) {
                continue;
            }
            $item = $index[$href];
            if (!isAnyLevel($level, array_values((array) $item['levels']))) {
                continue;
            }
            if ($href === '?controller=rapor&method=index' && !isAnyLevel($level, [1, 3, 5])) {
                continue;
            }
            $method = in_array($href, ['?controller=kehadiran&method=index', '?controller=jurnal&method=index'], true)
                ? null
                : ($item['method'] ?? 'index');
            $nav[] = [
                'label' => $labelPendek[$href] ?? $item['label'],
                'icon' => $item['icon'],
                'href' => $item['href'],
                'controllers' => $item['controllers'],
                'method' => $method,
            ];
            $dipakai[] = $href;
        }

        return $nav;
    }
}
