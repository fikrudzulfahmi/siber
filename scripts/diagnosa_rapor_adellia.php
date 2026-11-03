<?php
/**
 * scripts/diagnosa_rapor_adellia.php
 * Skrip diagnosa mendalam untuk memeriksa:
 * 1. Semua setting di tabel rapor_setting
 * 2. Raw nilai tabel 'nilai' & 'kategori_nilai' untuk Adellia (ID 967)
 * 3. Semua siswa di kelas X-1 (apakah ada siswa dengan mapel double / nilai 0)
 * 4. Pengecekan getNilaiAkhirSiswa untuk jenis_rapor 'tengah' vs 'akhir'
 * 5. Data tabel rapor_siswa, rapor_ekstra, rapor_prestasi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/SiswaAktif.php';
require_once __DIR__ . '/../app/models/Rapor.php';

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Gagal koneksi ke database.\n");
    exit(1);
}
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

function line(string $char = '=', int $len = 65): void {
    echo str_repeat($char, $len) . "\n";
}

function section(string $title): void {
    echo "\n";
    line('=');
    echo "  $title\n";
    line('=');
}

// 1. Semua Setting Rapor
section("1. SEMUA SETTING RAPOR (tabel rapor_setting)");
$stmt = $db->query("SELECT rs.*, tp.tahun_pelajaran, tp.semester 
                    FROM rapor_setting rs 
                    JOIN tahun_pelajaran tp ON rs.id_tahun_pelajaran = tp.id_tahun_pelajaran 
                    ORDER BY rs.id_rapor DESC");
$allSettings = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($allSettings as $st) {
    echo sprintf("  - ID: %-3d | TP: %-10s (ID: %d) | Sem: %-7s | Jenis: %-7s | Aktif: %s | Lock: %s\n",
        $st['id_rapor'],
        $st['tahun_pelajaran'],
        $st['id_tahun_pelajaran'],
        $st['semester'],
        $st['jenis_rapor'],
        $st['is_active'] ? 'YA' : 'TIDAK',
        $st['is_locked'] ? 'YA' : 'TIDAK'
    );
}

// 2. Setting Aktif
$raporModel = new Rapor($db);
$settingAktif = $raporModel->getActiveSetting();
echo "\nSetting Aktif Saat Ini:\n";
echo "  ID Rapor       : " . ($settingAktif['id_rapor'] ?? 'TIDAK ADA') . "\n";
echo "  Tahun Pelajaran: " . ($settingAktif['tahun_pelajaran'] ?? '-') . " (ID: " . ($settingAktif['id_tahun_pelajaran'] ?? '-') . ")\n";
echo "  Semester       : " . ($settingAktif['semester'] ?? '-') . "\n";
echo "  Jenis Rapor    : " . ($settingAktif['jenis_rapor'] ?? '-') . "\n";

$id_tahun = (int) ($settingAktif['id_tahun_pelajaran'] ?? 3);
$jenis_rapor = $settingAktif['jenis_rapor'] ?? 'tengah';
$id_rapor = (int) ($settingAktif['id_rapor'] ?? 14);

// 3. Siswa Adellia
section("2. DATA SISWA ADELLIA AFRIYANI (ID 967)");
$stmt = $db->prepare("SELECT * FROM siswa WHERE id_siswa = 967");
$stmt->execute();
$adellia = $stmt->fetch(PDO::FETCH_ASSOC);
if ($adellia) {
    echo "  ID Siswa : " . $adellia['id_siswa'] . "\n";
    echo "  Nama     : " . $adellia['nama_siswa'] . "\n";
    echo "  NISN     : " . $adellia['nisn'] . "\n";
    echo "  Status   : " . ($adellia['status'] ?? '-') . " | status_siswa: " . ($adellia['status_siswa'] ?? '-') . "\n";
} else {
    echo "  [ERROR] Siswa ID 967 tidak ditemukan!\n";
}

// 4. Ploting Siswa Adellia
section("3. PLOTING KELAS ADELLIA");
$stmt = $db->prepare("SELECT ps.*, k.kelas, tp.tahun_pelajaran 
                      FROM ploting_siswa ps 
                      JOIN kelas k ON ps.id_kelas = k.id_kelas 
                      LEFT JOIN tahun_pelajaran tp ON ps.id_tahun = tp.id_tahun_pelajaran 
                      WHERE ps.id_siswa = 967");
$stmt->execute();
$plots = $stmt->fetchAll(PDO::FETCH_ASSOC);
$id_kelas_x1 = 1;
foreach ($plots as $p) {
    echo sprintf("  - Ploting ID: %-5d | Kelas: %-10s (ID: %d) | TP: %-10s (ID: %d) | Urutan: %s\n",
        $p['id_ploting'] ?? $p['id_ploting_siswa'] ?? 0,
        $p['kelas'],
        $p['id_kelas'],
        $p['tahun_pelajaran'] ?? '-',
        $p['id_tahun'],
        $p['urutan'] ?? 'NULL'
    );
    if ((int)$p['id_tahun'] === $id_tahun) {
        $id_kelas_x1 = (int)$p['id_kelas'];
    }
}

// 5. Data Rapor Siswa (tabel rapor_siswa)
section("4. DATA TABEL rapor_siswa UNTUK ADELLIA");
$stmt = $db->prepare("SELECT rs.*, rset.jenis_rapor, tp.tahun_pelajaran 
                      FROM rapor_siswa rs 
                      JOIN rapor_setting rset ON rs.id_rapor = rset.id_rapor 
                      JOIN tahun_pelajaran tp ON rset.id_tahun_pelajaran = tp.id_tahun_pelajaran 
                      WHERE rs.id_siswa = 967");
$stmt->execute();
$raporSiswaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (empty($raporSiswaList)) {
    echo "  [BELUM ADA DATA DI rapor_siswa untuk Adellia]\n";
} else {
    foreach ($raporSiswaList as $rs) {
        echo sprintf("  - ID Rapor Siswa: %-5d | ID Rapor: %-3d (%s %s) | Sakit: %d | Izin: %d | Alfa: %d | Catatan: %s\n",
            $rs['id_rapor_siswa'],
            $rs['id_rapor'],
            $rs['tahun_pelajaran'],
            $rs['jenis_rapor'],
            $rs['sakit'] ?? 0,
            $rs['izin'] ?? 0,
            $rs['alfa'] ?? 0,
            substr($rs['catatan_walikelas'] ?? $rs['catatan'] ?? '-', 0, 40)
        );
    }
}

// 6. Uji Nilai Akhir untuk 'tengah' dan 'akhir'
section("5. REKAP NILAI getNilaiAkhirSiswa (Adellia - Kelas ID $id_kelas_x1)");
foreach (['tengah', 'akhir'] as $jr) {
    echo "\n>>> JENIS RAPOR: $jr <<<\n";
    $nilaiList = $raporModel->getNilaiAkhirSiswa(967, $id_tahun, $jr, $id_kelas_x1);
    echo "Jumlah baris: " . count($nilaiList) . "\n";
    $seen = [];
    foreach ($nilaiList as $n) {
        $dup = isset($seen[$n['nama_mapel']]) ? ' *** DUPLIKAT! ***' : '';
        $seen[$n['nama_mapel']] = true;
        $nol = ((float)$n['nilai_final'] == 0) ? ' [NILAI 0]' : '';
        echo sprintf("  - %-35s : %6.2f%s%s\n", $n['nama_mapel'], (float)$n['nilai_final'], $dup, $nol);
    }
}

// 7. Raw Data Nilai Siswa Adellia di Kelas X-1
section("6. RAW NILAI TABEL nilai DI KELAS X-1 (Tahun ID $id_tahun)");
$stmt = $db->prepare("
    SELECT 
        m.id_mapel,
        m.nama_mapel,
        mg.id_mapel_guru,
        mg.urutan as mg_urutan,
        e.nama as nama_guru,
        kn.id_kategori,
        kn.kategori,
        n.id_nilai,
        n.sts,
        n.nilai_raport
    FROM mapel_guru mg
    JOIN mapel m ON mg.id_mapel = m.id_mapel
    LEFT JOIN employe e ON mg.id_guru = e.id_employe
    LEFT JOIN kategori_nilai kn ON kn.id_mapel_guru = mg.id_mapel_guru
    LEFT JOIN nilai n ON n.id_kategori = kn.id_kategori AND n.id_siswa = 967
    WHERE mg.id_kelas = ? AND mg.id_tahun_pelajaran = ?
      AND m.nama_mapel != 'BIMBINGAN KONSELING'
    ORDER BY (mg.urutan IS NULL OR mg.urutan = 0) ASC, mg.urutan ASC, m.nama_mapel ASC, kn.id_kategori ASC
");
$stmt->execute([$id_kelas_x1, $id_tahun]);
$rawList = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total baris (termasuk kategori ganda jika ada): " . count($rawList) . "\n";
$hitungMapel = [];
foreach ($rawList as $r) {
    $hitungMapel[$r['nama_mapel']][] = $r;
}

foreach ($hitungMapel as $namaM => $barisList) {
    $count = count($barisList);
    $statusKategori = ($count > 1) ? " [KATEGORI GANDA: $count baris]" : "";
    echo "\nMapel: $namaM$statusKategori\n";
    foreach ($barisList as $b) {
        echo sprintf("   * MG: %-4d | Kat ID: %-4s (%-10s) | Guru: %-22s | STS: %-5s | NilaiRaport: %-5s | Nilai ID: %s\n",
            $b['id_mapel_guru'],
            $b['id_kategori'] ?? 'NULL',
            $b['kategori'] ?? '-',
            substr($b['nama_guru'] ?? '-', 0, 22),
            $b['sts'] ?? 'NULL',
            $b['nilai_raport'] ?? 'NULL',
            $b['id_nilai'] ?? 'BELUM ADA'
        );
    }
}

// 8. Cek Seluruh Siswa di Kelas X-1
section("7. SCAN SEMUA SISWA DI KELAS X-1 (Cari Nilai 0 atau Duplikat)");
$stmt = $db->prepare("
    SELECT s.id_siswa, s.nama_siswa 
    FROM ploting_siswa ps 
    JOIN siswa s ON ps.id_siswa = s.id_siswa 
    WHERE ps.id_kelas = ? AND ps.id_tahun = ? 
      AND " . siswa_aktif_sql('s') . "
    ORDER BY (ps.urutan IS NULL) ASC, ps.urutan ASC, s.nama_siswa ASC
");
$stmt->execute([$id_kelas_x1, $id_tahun]);
$semuaSiswaX1 = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Total siswa aktif di kelas X-1: " . count($semuaSiswaX1) . " siswa\n";

$siswaBermasalah = [];
foreach ($semuaSiswaX1 as $sw) {
    $nilaiSw = $raporModel->getNilaiAkhirSiswa($sw['id_siswa'], $id_tahun, $jenis_rapor, $id_kelas_x1);
    $namaMapelSeen = [];
    $hasDup = false;
    $hasZero = false;
    $zeroMapel = [];
    $dupMapel = [];
    foreach ($nilaiSw as $ns) {
        if (isset($namaMapelSeen[$ns['nama_mapel']])) {
            $hasDup = true;
            $dupMapel[] = $ns['nama_mapel'];
        }
        $namaMapelSeen[$ns['nama_mapel']] = true;
        if ((float)$ns['nilai_final'] == 0) {
            $hasZero = true;
            $zeroMapel[] = $ns['nama_mapel'];
        }
    }

    if ($hasDup || $hasZero) {
        $siswaBermasalah[] = [
            'id_siswa' => $sw['id_siswa'],
            'nama_siswa' => $sw['nama_siswa'],
            'dup' => $dupMapel,
            'zero' => $zeroMapel
        ];
    }
}

if (empty($siswaBermasalah)) {
    echo "HASIL: TIDAK ADA siswa di kelas X-1 yang memiliki mapel duplikat atau nilai 0 pada getNilaiAkhirSiswa (jenis: $jenis_rapor)!\n";
} else {
    echo "[DITEMUKAN SISWA DENGAN NILAI 0 ATAU DUPLIKAT]: " . count($siswaBermasalah) . " siswa\n";
    foreach ($siswaBermasalah as $sb) {
        echo sprintf("  - ID: %-5d | Nama: %-25s | Dup: %s | Zero: %s\n",
            $sb['id_siswa'],
            $sb['nama_siswa'],
            empty($sb['dup']) ? '-' : implode(', ', $sb['dup']),
            empty($sb['zero']) ? '-' : implode(', ', $sb['zero'])
        );
    }
}

// 9. Cek Semua Mapel Guru di Tahun ID $id_tahun yang Berelasi dengan Adellia
section("8. CEK SELURUH NILAI ADELLIA DI SELURUH KELAS / TAHUN");
$stmt = $db->prepare("
    SELECT n.id_nilai, n.sts, n.nilai_raport, kn.kategori, m.nama_mapel, k.kelas, mg.id_tahun_pelajaran
    FROM nilai n
    JOIN kategori_nilai kn ON n.id_kategori = kn.id_kategori
    JOIN mapel_guru mg ON kn.id_mapel_guru = mg.id_mapel_guru
    JOIN mapel m ON mg.id_mapel = m.id_mapel
    JOIN kelas k ON mg.id_kelas = k.id_kelas
    WHERE n.id_siswa = 967
    ORDER BY mg.id_tahun_pelajaran DESC, m.nama_mapel ASC
");
$stmt->execute();
$riwayatNilai = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Total entri nilai historis Adellia: " . count($riwayatNilai) . " entri\n";
$hitungPerTahun = [];
foreach ($riwayatNilai as $rn) {
    $hitungPerTahun[$rn['id_tahun_pelajaran'] . ' (' . $rn['kelas'] . ')'][] = $rn;
}
foreach ($hitungPerTahun as $thnKelas => $rows) {
    echo "  - Tahun/Kelas $thnKelas: " . count($rows) . " entri nilai\n";
}

section("SELESAI DIAGNOSA LENGKAP");
