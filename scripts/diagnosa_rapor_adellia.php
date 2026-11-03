<?php
/**
 * scripts/diagnosa_rapor_adellia.php
 * Skrip diagnosa mandiri untuk memeriksa data rapor, mapel duplikat, dan nilai 0
 * untuk siswi Adellia Afriyani di kelas X-1 pada periode rapor aktif.
 *
 * Jalankan di server:
 *   php scripts/diagnosa_rapor_adellia.php
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

function line(string $char = '=', int $len = 60): void {
    echo str_repeat($char, $len) . "\n";
}

function section(string $title): void {
    echo "\n";
    line('=');
    echo "  $title\n";
    line('=');
}

// 1. Cek Setting Rapor Aktif
section("1. SETTING RAPOR AKTIF");
$raporModel = new Rapor($db);
$setting = $raporModel->getActiveSetting();

if (!$setting) {
    echo "[PERINGATAN] Tidak ada periode rapor yang sedang aktif (is_active = 1)!\n";
    // Cari setting terakhir
    $stmt = $db->query("SELECT rs.*, tp.tahun_pelajaran, tp.semester 
                        FROM rapor_setting rs 
                        JOIN tahun_pelajaran tp ON rs.id_tahun_pelajaran = tp.id_tahun_pelajaran 
                        ORDER BY rs.id_rapor DESC LIMIT 1");
    $setting = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($setting) {
        echo "Menggunakan setting terakhir (non-aktif) untuk simulasi:\n";
    } else {
        echo "[ERROR] Tabel rapor_setting kosong!\n";
        exit(1);
    }
}

echo "ID Rapor       : " . $setting['id_rapor'] . "\n";
echo "Tahun Pelajaran: " . $setting['tahun_pelajaran'] . " (ID: " . $setting['id_tahun_pelajaran'] . ")\n";
echo "Semester       : " . $setting['semester'] . "\n";
echo "Jenis Rapor    : " . $setting['jenis_rapor'] . "\n";
echo "Status Aktif   : " . ($setting['is_active'] ? 'AKTIF' : 'NON-AKTIF') . "\n";

$id_tahun = (int) $setting['id_tahun_pelajaran'];
$jenis_rapor = $setting['jenis_rapor'];

// 2. Cari Siswa Bernama Adellia Afriyani
section("2. PENCARIAN SISWA (Adellia Afriyani)");
$stmt = $db->prepare("SELECT id_siswa, nama_siswa, nisn, status, status_siswa 
                      FROM siswa 
                      WHERE nama_siswa LIKE '%adellia%' OR nama_siswa LIKE '%afriyani%'");
$stmt->execute();
$siswaList = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($siswaList)) {
    echo "[PERINGATAN] Tidak ditemukan siswa dengan nama Adellia / Afriyani!\n";
    echo "Mencoba mencari dengan keyword 'Adel':\n";
    $stmt = $db->prepare("SELECT id_siswa, nama_siswa, nisn, status, status_siswa 
                          FROM siswa 
                          WHERE nama_siswa LIKE '%adel%'");
    $stmt->execute();
    $siswaList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

if (empty($siswaList)) {
    echo "[ERROR] Siswa tidak ditemukan di tabel siswa.\n";
} else {
    echo "Ditemukan " . count($siswaList) . " record siswa:\n";
    foreach ($siswaList as $s) {
        echo sprintf("  - ID: %-5d | NISN: %-12s | Status: %-8s | StatusSiswa: %-10s | Nama: %s\n",
            $s['id_siswa'],
            $s['nisn'] ?: '-',
            $s['status'] ?: '-',
            $s['status_siswa'] ?: 'aktif',
            $s['nama_siswa']
        );
    }
}

// 3. Cek Kelas X-1
section("3. KELAS X-1");
$stmt = $db->prepare("SELECT k.*, e.nama AS nama_walikelas 
                      FROM kelas k 
                      LEFT JOIN employe e ON k.wali_kelas = e.id_employe 
                      WHERE k.kelas LIKE '%X-1%' OR k.kelas LIKE '%X 1%' OR k.kelas = 'X-1'");
$stmt->execute();
$kelasList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$id_kelas_x1 = null;
if (empty($kelasList)) {
    echo "[PERINGATAN] Kelas X-1 tidak ditemukan dengan filter spesifik. Menampilkan semua kelas tingkat 10:\n";
    $stmt = $db->query("SELECT id_kelas, kelas, tingkat FROM kelas WHERE tingkat = 10 OR kelas LIKE '%X%'");
    $kelasList = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

foreach ($kelasList as $k) {
    echo sprintf("  - ID: %-5d | Kelas: %-12s | Tingkat: %-3s | Walas: %s\n",
        $k['id_kelas'],
        $k['kelas'],
        $k['tingkat'] ?? '-',
        $k['nama_walikelas'] ?? '-'
    );
    if (strpos(strtoupper($k['kelas']), 'X-1') !== false || strtoupper($k['kelas']) === 'X 1') {
        $id_kelas_x1 = (int) $k['id_kelas'];
    }
}

if (!$id_kelas_x1 && !empty($kelasList)) {
    $id_kelas_x1 = (int) $kelasList[0]['id_kelas'];
    echo "Menggunakan ID Kelas default: $id_kelas_x1 (" . $kelasList[0]['kelas'] . ")\n";
}

// 4. Cek Ploting Siswa Adellia
section("4. PLOTING SISWA (Adellia)");
$targetSiswa = null;
foreach ($siswaList as $s) {
    $stmt = $db->prepare("SELECT ps.*, k.kelas 
                          FROM ploting_siswa ps 
                          JOIN kelas k ON ps.id_kelas = k.id_kelas 
                          WHERE ps.id_siswa = ?");
    $stmt->execute([$s['id_siswa']]);
    $plots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Siswa ID {$s['id_siswa']} ({$s['nama_siswa']}):\n";
    if (empty($plots)) {
        echo "  [TIDAK ADA PLOTING]\n";
    } else {
        foreach ($plots as $p) {
            $isTahunIni = ((int)$p['id_tahun'] === $id_tahun) ? ' [TAHUN INI]' : '';
            echo sprintf("  - ID Ploting: %-5d | Kelas: %-10s (ID: %d) | Tahun: %d | Urutan: %s%s\n",
                $p['id_ploting'] ?? $p['id_ploting_siswa'] ?? 0,
                $p['kelas'],
                $p['id_kelas'],
                $p['id_tahun'],
                $p['urutan'] ?? 'NULL',
                $isTahunIni
            );
            if ((int)$p['id_tahun'] === $id_tahun) {
                $targetSiswa = $s;
                if (!$id_kelas_x1) $id_kelas_x1 = (int)$p['id_kelas'];
            }
        }
    }
}

if (!$targetSiswa && !empty($siswaList)) {
    $targetSiswa = $siswaList[0];
}

// 5. Cek Mapel & Guru di Kelas X-1
if ($id_kelas_x1) {
    section("5. DAFTAR MAPEL DI KELAS X-1 (Tahun ID: $id_tahun)");
    $stmt = $db->prepare("SELECT mg.id_mapel_guru, mg.id_mapel, mg.id_guru, mg.urutan, 
                                 m.nama_mapel, m.kode_mapel, e.nama as nama_guru
                          FROM mapel_guru mg
                          JOIN mapel m ON mg.id_mapel = m.id_mapel
                          LEFT JOIN employe e ON mg.id_guru = e.id_employe
                          WHERE mg.id_kelas = ? AND mg.id_tahun_pelajaran = ?
                          ORDER BY m.nama_mapel ASC, mg.id_mapel_guru ASC");
    $stmt->execute([$id_kelas_x1, $id_tahun]);
    $mapelKelas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total mapel_guru di kelas X-1: " . count($mapelKelas) . " baris\n";
    
    // Deteksi duplikasi nama_mapel atau id_mapel
    $hitungNama = [];
    $hitungId = [];
    foreach ($mapelKelas as $mk) {
        $hitungNama[$mk['nama_mapel']][] = $mk;
        $hitungId[$mk['id_mapel']][] = $mk;
    }

    $adaDuplikatMapel = false;
    foreach ($hitungNama as $namaM => $baris) {
        if (count($baris) > 1) {
            $adaDuplikatMapel = true;
            echo "\n[DUPLIKAT TERDETEKSI] Mapel '$namaM' muncul " . count($baris) . " kali di mapel_guru:\n";
            foreach ($baris as $b) {
                echo sprintf("   * id_mapel_guru: %-5d | id_mapel: %-4d | Guru: %s (ID: %d) | Urutan: %s\n",
                    $b['id_mapel_guru'],
                    $b['id_mapel'],
                    $b['nama_guru'] ?: 'Belum diset',
                    $b['id_guru'] ?: 0,
                    $b['urutan'] ?? 'NULL'
                );
            }
        }
    }

    if (!$adaDuplikatMapel) {
        echo "Tidak ada nama mapel yang duplikat di mapel_guru kelas X-1.\n";
    }

    echo "\nDaftar lengkap mapel kelas X-1:\n";
    foreach ($mapelKelas as $mk) {
        echo sprintf("  - ID MG: %-5d | ID Mapel: %-4d | Urutan: %-3s | %-30s | Guru: %s\n",
            $mk['id_mapel_guru'],
            $mk['id_mapel'],
            $mk['urutan'] ?? '-',
            $mk['nama_mapel'],
            $mk['nama_guru'] ?: '-'
        );
    }
}

// 6. Uji Hasil getNilaiAkhirSiswa untuk Adellia
if ($targetSiswa && $id_kelas_x1) {
    section("6. REKAP NILAI AKHIR (getNilaiAkhirSiswa) UNTUK {$targetSiswa['nama_siswa']}");
    $nilaiAkhir = $raporModel->getNilaiAkhirSiswa($targetSiswa['id_siswa'], $id_tahun, $jenis_rapor, $id_kelas_x1);

    echo "Jumlah baris hasil getNilaiAkhirSiswa: " . count($nilaiAkhir) . "\n";
    $namaMapelSeen = [];
    foreach ($nilaiAkhir as $na) {
        $isDup = isset($namaMapelSeen[$na['nama_mapel']]) ? ' [DUPLIKAT!]' : '';
        $namaMapelSeen[$na['nama_mapel']] = true;
        $isZero = ((float)$na['nilai_final'] == 0) ? ' [NILAI 0]' : '';
        echo sprintf("  - %-35s : %6.2f%s%s\n",
            $na['nama_mapel'],
            (float) $na['nilai_final'],
            $isDup,
            $isZero
        );
    }

    // Bandingkan dengan siswa lain di kelas yang sama
    section("7. PERBANDINGAN DENGAN SISWA LAIN DI KELAS X-1");
    $stmt = $db->prepare("SELECT s.id_siswa, s.nama_siswa 
                          FROM ploting_siswa ps 
                          JOIN siswa s ON ps.id_siswa = s.id_siswa 
                          WHERE ps.id_kelas = ? AND ps.id_tahun = ? AND s.id_siswa != ? 
                          LIMIT 1");
    $stmt->execute([$id_kelas_x1, $id_tahun, $targetSiswa['id_siswa']]);
    $siswaLain = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($siswaLain) {
        echo "Membandingkan dengan siswa: {$siswaLain['nama_siswa']} (ID: {$siswaLain['id_siswa']})\n";
        $nilaiLain = $raporModel->getNilaiAkhirSiswa($siswaLain['id_siswa'], $id_tahun, $jenis_rapor, $id_kelas_x1);
        echo "Jumlah baris siswa pembanding: " . count($nilaiLain) . "\n";
        $namaMapelSeenLain = [];
        foreach ($nilaiLain as $nl) {
            $isDup = isset($namaMapelSeenLain[$nl['nama_mapel']]) ? ' [DUPLIKAT!]' : '';
            $namaMapelSeenLain[$nl['nama_mapel']] = true;
            $isZero = ((float)$nl['nilai_final'] == 0) ? ' [NILAI 0]' : '';
            echo sprintf("  - %-35s : %6.2f%s%s\n",
                $nl['nama_mapel'],
                (float) $nl['nilai_final'],
                $isDup,
                $isZero
            );
        }
    } else {
        echo "Tidak ditemukan siswa lain di kelas X-1 tahun ini.\n";
    }

    // 8. Cek Detail Progres Siswa (Modal Progres)
    section("8. DETAIL PROGRES (getDetailProgresSiswa) UNTUK {$targetSiswa['nama_siswa']}");
    $detailProgres = $raporModel->getDetailProgresSiswa($targetSiswa['id_siswa'], $id_kelas_x1, $id_tahun, $jenis_rapor);
    echo "Jumlah baris hasil getDetailProgresSiswa: " . count($detailProgres) . "\n";
    $namaMapelSeenProg = [];
    foreach ($detailProgres as $dp) {
        $isDup = isset($namaMapelSeenProg[$dp['nama_mapel']]) ? ' [DUPLIKAT!]' : '';
        $namaMapelSeenProg[$dp['nama_mapel']] = true;
        $isZero = ((float)$dp['nilai'] == 0) ? ' [NILAI 0]' : '';
        echo sprintf("  - %-30s | Guru: %-25s | Nilai: %6.2f%s%s\n",
            $dp['nama_mapel'],
            $dp['nama_guru'] ?: '-',
            (float) $dp['nilai'],
            $isDup,
            $isZero
        );
    }

    // 9. Raw Nilai untuk Siswa Adellia
    section("9. RAW DATA NILAI (Tabel 'nilai' & 'kategori_nilai')");
    $stmt = $db->prepare("SELECT n.*, kn.nama_kategori, kn.keterangan, m.nama_mapel, mg.id_mapel_guru, mg.id_guru, e.nama as nama_guru
                          FROM nilai n
                          JOIN kategori_nilai kn ON n.id_kategori = kn.id_kategori
                          JOIN mapel_guru mg ON kn.id_mapel_guru = mg.id_mapel_guru
                          JOIN mapel m ON mg.id_mapel = m.id_mapel
                          LEFT JOIN employe e ON mg.id_guru = e.id_employe
                          WHERE n.id_siswa = ? AND mg.id_kelas = ?
                          ORDER BY m.nama_mapel ASC, kn.id_kategori ASC");
    $stmt->execute([$targetSiswa['id_siswa'], $id_kelas_x1]);
    $rawNilai = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Total entri tabel nilai untuk {$targetSiswa['nama_siswa']}: " . count($rawNilai) . " baris\n";
    foreach ($rawNilai as $rn) {
        echo sprintf("  - Mapel: %-25s | Guru: %-15s | Kategori: %-12s | STS: %-5s | NilaiRaport: %-5s\n",
            $rn['nama_mapel'],
            $rn['nama_guru'] ?: '-',
            $rn['nama_kategori'],
            $rn['sts'] ?? '-',
            $rn['nilai_raport'] ?? '-'
        );
    }
}

section("SELESAI DIAGNOSA");
