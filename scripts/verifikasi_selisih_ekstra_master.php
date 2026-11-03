<?php
/**
 * scripts/verifikasi_selisih_ekstra_master.php
 *
 * Membandingkan jumlah siswa per kelas antara:
 * - Logika MASTER SISWA (ploting_siswa + filter siswa_aktif_sql)
 * - Logika LAMA EKSTRA (ploting_siswa TANPA filter status, sebelum fix)
 *
 * Tujuan: membuktikan selisih jumlah siswa (contoh: 23 vs 24 di kelas 7A,
 * total 13 anak) memang berasal dari siswa non-aktif (lulus/keluar/dll)
 * yang masih punya baris ploting_siswa tapi sudah difilter di Master.
 *
 * Jalankan di server produksi setelah `git pull`:
 *   php scripts/verifikasi_selisih_ekstra_master.php
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers/SiswaAktif.php';

$db = (new Database())->getConnection();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Ambil tahun pelajaran aktif (sesuai Master Siswa)
$stmt = $db->query("SELECT id_tahun_pelajaran, tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
$tahunAktif = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$tahunAktif) {
    fwrite(STDERR, "Tidak ada tahun pelajaran aktif.\n");
    exit(1);
}
$id_tahun = (int) $tahunAktif['id_tahun_pelajaran'];

echo "Tahun Pelajaran Aktif: " . $tahunAktif['tahun_pelajaran'] . " (ID: $id_tahun)\n";
echo str_repeat('=', 90) . "\n";
printf("%-12s | %-8s | %-8s | %-8s | %s\n", "Kelas", "Master", "Ekstra*", "Selisih", "Nama Siswa Penyebab Selisih");
echo str_repeat('=', 90) . "\n";

// Semua kelas
$kelasList = $db->query("SELECT id_kelas, kelas, tingkat FROM kelas ORDER BY tingkat ASC, kelas ASC")->fetchAll(PDO::FETCH_ASSOC);

$totalMaster = 0;
$totalEkstraLama = 0;
$totalSelisih = 0;
$semuaPenyebab = [];

foreach ($kelasList as $kls) {
    $id_kelas = (int) $kls['id_kelas'];

    // Versi MASTER: ploting_siswa + JOIN siswa + filter aktif
    $stmtMaster = $db->prepare("
        SELECT s.id_siswa, s.nama_siswa, ps.id_ploting
        FROM ploting_siswa ps
        JOIN siswa s ON ps.id_siswa = s.id_siswa
        WHERE ps.id_kelas = ? AND ps.id_tahun = ?
          AND " . siswa_aktif_sql('s') . "
        ORDER BY s.nama_siswa ASC
    ");
    $stmtMaster->execute([$id_kelas, $id_tahun]);
    $master = $stmtMaster->fetchAll(PDO::FETCH_ASSOC);
    $idMasterSet = array_column($master, 'id_ploting');

    // Versi LAMA EKSTRA: ploting_siswa + JOIN siswa TANPA filter status
    $stmtLama = $db->prepare("
        SELECT s.id_siswa, s.nama_siswa, s.status_siswa, ps.id_ploting
        FROM ploting_siswa ps
        JOIN siswa s ON ps.id_siswa = s.id_siswa
        WHERE ps.id_kelas = ? AND ps.id_tahun = ?
        ORDER BY s.nama_siswa ASC
    ");
    $stmtLama->execute([$id_kelas, $id_tahun]);
    $lama = $stmtLama->fetchAll(PDO::FETCH_ASSOC);

    $jmlMaster = count($master);
    $jmlLama = count($lama);
    $selisih = $jmlLama - $jmlMaster;

    $totalMaster += $jmlMaster;
    $totalEkstraLama += $jmlLama;
    $totalSelisih += $selisih;

    $penyebab = [];
    if ($selisih > 0) {
        foreach ($lama as $l) {
            if (!in_array($l['id_ploting'], $idMasterSet)) {
                $penyebab[] = $l['nama_siswa'] . " [status: " . ($l['status_siswa'] ?? 'NULL') . "]";
                $semuaPenyebab[] = [
                    'kelas' => $kls['kelas'],
                    'nama' => $l['nama_siswa'],
                    'status' => $l['status_siswa'] ?? 'NULL',
                    'id_siswa' => $l['id_siswa'],
                ];
            }
        }
    }

    // Hanya tampilkan baris yang ada perbedaan atau ada siswa sama sekali
    if ($jmlMaster > 0 || $jmlLama > 0) {
        printf("%-12s | %-8d | %-8d | %-8d | %s\n",
            $kls['kelas'],
            $jmlMaster,
            $jmlLama,
            $selisih,
            empty($penyebab) ? '-' : implode('; ', $penyebab)
        );
    }
}

echo str_repeat('=', 90) . "\n";
printf("%-12s | %-8d | %-8d | %-8d\n", "TOTAL", $totalMaster, $totalEkstraLama, $totalSelisih);
echo str_repeat('=', 90) . "\n";

echo "\nRINGKASAN SISWA PENYEBAB SELISIH (ada di Ekstra versi lama, tidak ada di Master):\n";
if (empty($semuaPenyebab)) {
    echo "  Tidak ada siswa penyebab selisih.\n";
} else {
    foreach ($semuaPenyebab as $p) {
        printf("  - [%-8s] ID:%-5d %-30s | status_siswa: %s\n", $p['kelas'], $p['id_siswa'], $p['nama'], $p['status']);
    }
    echo "\nTotal siswa penyebab selisih: " . count($semuaPenyebab) . " anak\n";
}

// Cek tambahan: siswa dengan id_siswa DUPLIKAT di ploting_siswa untuk tahun ini
// (siswa yang punya lebih dari 1 baris ploting di tahun yang sama -> bisa bikin hitungan ganda)
echo "\n" . str_repeat('=', 90) . "\n";
echo "CEK TAMBAHAN: SISWA DENGAN PLOTING GANDA DI TAHUN INI (id_tahun=$id_tahun)\n";
echo str_repeat('=', 90) . "\n";
$stmtDup = $db->prepare("
    SELECT s.id_siswa, s.nama_siswa, s.status_siswa, COUNT(*) as jml_ploting, GROUP_CONCAT(ps.id_ploting) as id_plotings, GROUP_CONCAT(k.kelas) as kelas_list
    FROM ploting_siswa ps
    JOIN siswa s ON ps.id_siswa = s.id_siswa
    JOIN kelas k ON ps.id_kelas = k.id_kelas
    WHERE ps.id_tahun = ?
    GROUP BY s.id_siswa
    HAVING COUNT(*) > 1
");
$stmtDup->execute([$id_tahun]);
$dupPloting = $stmtDup->fetchAll(PDO::FETCH_ASSOC);
if (empty($dupPloting)) {
    echo "  Tidak ada siswa dengan ploting ganda di tahun ini.\n";
} else {
    foreach ($dupPloting as $d) {
        printf("  - ID:%-5d %-30s | status: %-10s | Jml Ploting: %d | ID Ploting: %s | Kelas: %s\n",
            $d['id_siswa'], $d['nama_siswa'], $d['status_siswa'] ?? 'NULL', $d['jml_ploting'], $d['id_plotings'], $d['kelas_list']);
    }
}

echo "\nSELESAI VERIFIKASI.\n";
