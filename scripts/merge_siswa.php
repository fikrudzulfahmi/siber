<?php
/**
 * merge_siswa.php — gabungkan satu record siswa duplikat ke record yang benar.
 *
 * Kasus: siswa yang sama punya dua baris di tabel `siswa` (biasanya karena
 * entri/impor ulang), lalu keduanya terplot di kelas & tahun yang sama
 * sehingga namanya muncul dua kali di daftar absensi jurnal, nilai, dan rapor.
 *
 * Yang dilakukan skrip ini:
 *   1. Menampilkan isi kedua record + jumlah baris anak (nilai, absensi, izin,
 *      konseling, rapor, plotting, ekstra, prestasi).
 *   2. Mendeteksi konflik: nilai untuk kategori yang sama, atau absensi untuk
 *      jurnal yang sama. Kalau ada konflik, skrip BERHENTI — tidak ada yang
 *      dipindah, karena nilainya harus diputuskan manusia.
 *   3. Memindahkan seluruh baris anak dari `--dari` ke `--ke`.
 *   4. Baris plotting yang bentrok (kelas + tahun sama) dibuang setelah anak-anak
 *      plotting-nya (ekstra/pestasi) dipindah ke plotting milik `--ke`.
 *   5. Menandai record `--dari` nonaktif: status = 'Nonaktif' dan
 *      status_siswa = 'nonaktif'. Datanya tidak dihapus, jadi masih bisa diaudit.
 *
 * Pemakaian (dari root repo, di server):
 *   php scripts/merge_siswa.php --dari=1231 --ke=478            # pratinjau
 *   php scripts/merge_siswa.php --dari=1231 --ke=478 --apply    # eksekusi
 *
 * Selalu jalankan tanpa --apply lebih dulu dan baca daftar rencananya.
 * Backup database sebelum --apply.
 */

if (PHP_SAPI !== 'cli') {
    die("Skrip ini hanya untuk CLI.\n");
}

require_once __DIR__ . '/../config/database.php';

$opsi = getopt('', ['dari:', 'ke:', 'apply']);
$idDari = isset($opsi['dari']) ? (int) $opsi['dari'] : 0;
$idKe   = isset($opsi['ke']) ? (int) $opsi['ke'] : 0;
$apply  = array_key_exists('apply', $opsi);

if ($idDari <= 0 || $idKe <= 0 || $idDari === $idKe) {
    fwrite(STDERR, "Pemakaian: php scripts/merge_siswa.php --dari=ID_DUPLIKAT --ke=ID_DIPERTAHANKAN [--apply]\n");
    exit(1);
}

$db = (new Database())->getConnection();
if (!$db) {
    fwrite(STDERR, "Gagal koneksi database.\n");
    exit(1);
}
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/** Tabel yang punya kolom id_siswa dan cara memperlakukannya. */
$tabelAnak = [
    // tabel => kolom kunci konflik yang harus dicek (null = tanpa konflik)
    'nilai'            => 'id_kategori',
    'jurnal_kehadiran' => 'id_jurnal',
    'perizinan'        => null,
    'konseling'        => null,
    'rapor_siswa'      => null,
    'siswa_keluar'     => null,
];

/** Tabel yang merujuk ke ploting_siswa, bukan langsung ke siswa. */
$tabelPlotting = [
    'ekstra_anggota'   => 'id_ploting_siswa',
    'prestasi_peserta' => 'id_plotting_siswa',
];

function judul(string $teks): void
{
    echo "\n" . $teks . "\n" . str_repeat('-', strlen($teks)) . "\n";
}

function ambilSiswa(PDO $db, int $id): ?array
{
    $stmt = $db->prepare("SELECT * FROM siswa WHERE id_siswa = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function adaKolom(PDO $db, string $tabel, string $kolom): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
    $stmt->execute([$tabel, $kolom]);
    return (int) $stmt->fetchColumn() > 0;
}

function kolomPrimary(PDO $db, string $tabel): ?string
{
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                            AND CONSTRAINT_NAME = 'PRIMARY'
                          ORDER BY ORDINAL_POSITION LIMIT 1");
    $stmt->execute([$tabel]);
    $kolom = $stmt->fetchColumn();
    return $kolom ?: null;
}

function tabelAda(PDO $db, string $tabel): bool
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.TABLES
                          WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->execute([$tabel]);
    return (int) $stmt->fetchColumn() > 0;
}

function hitung(PDO $db, string $tabel, string $kolom, int $id): int
{
    $stmt = $db->prepare("SELECT COUNT(*) FROM `$tabel` WHERE `$kolom` = ?");
    $stmt->execute([$id]);
    return (int) $stmt->fetchColumn();
}

$dari = ambilSiswa($db, $idDari);
$ke   = ambilSiswa($db, $idKe);

if (!$dari || !$ke) {
    fwrite(STDERR, "Salah satu ID siswa tidak ditemukan.\n");
    exit(1);
}

judul('RECORD YANG DIPERTAHANKAN (--ke)');
printf("ID %-6d %s | NISN: %s\n", $idKe, $ke['nama_siswa'], $ke['nisn'] ?? '-');

judul('RECORD DUPLIKAT (--dari)');
printf("ID %-6d %s | NISN: %s\n", $idDari, $dari['nama_siswa'], $dari['nisn'] ?? '-');

if (strcasecmp(trim($dari['nama_siswa']), trim($ke['nama_siswa'])) !== 0) {
    echo "\n!! Nama berbeda. Pastikan ini memang orang yang sama sebelum melanjutkan.\n";
}

// ---------------------------------------------------------------- inventaris
judul('ISI KEDUA RECORD');
$ringkasan = [];
foreach ($tabelAnak as $tabel => $kunci) {
    if (!tabelAda($db, $tabel) || !adaKolom($db, $tabel, 'id_siswa')) {
        continue;
    }
    $ringkasan[$tabel] = [
        'dari' => hitung($db, $tabel, 'id_siswa', $idDari),
        'ke'   => hitung($db, $tabel, 'id_siswa', $idKe),
    ];
}
if (tabelAda($db, 'ploting_siswa')) {
    $ringkasan['ploting_siswa'] = [
        'dari' => hitung($db, 'ploting_siswa', 'id_siswa', $idDari),
        'ke'   => hitung($db, 'ploting_siswa', 'id_siswa', $idKe),
    ];
}

printf("%-20s %8s %8s\n", 'tabel', 'dari', 'ke');
foreach ($ringkasan as $tabel => $n) {
    printf("%-20s %8d %8d\n", $tabel, $n['dari'], $n['ke']);
}

// ---------------------------------------------------------------- konflik
judul('PEMERIKSAAN KONFLIK');
$konflik = [];      // perlu keputusan manusia -> skrip berhenti
$absensiGanda = []; // bisa diputuskan otomatis: satu siswa hanya punya satu status per jurnal

foreach ($tabelAnak as $tabel => $kunci) {
    if ($kunci === null || !isset($ringkasan[$tabel])) {
        continue;
    }
    $pk = kolomPrimary($db, $tabel);
    if ($pk === null) {
        continue;
    }
    $kolomStatus = ($tabel === 'jurnal_kehadiran')
        ? 'a.status AS status_dari, b.status AS status_ke'
        : 'NULL AS status_dari, NULL AS status_ke';
    $stmt = $db->prepare("
        SELECT a.`$kunci`, a.`$pk` AS id_dari, b.`$pk` AS id_ke, $kolomStatus
        FROM `$tabel` a
        JOIN `$tabel` b ON b.`$kunci` = a.`$kunci` AND b.id_siswa = :ke
        WHERE a.id_siswa = :dari
    ");
    $stmt->execute([':ke' => $idKe, ':dari' => $idDari]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        if ($tabel === 'jurnal_kehadiran') {
            $absensiGanda[] = $row;
            continue;
        }
        $konflik[] = sprintf(
            '%s: %s=%s sudah ada di kedua record (dari id %s, ke id %s)',
            $tabel,
            $kunci,
            $row[$kunci],
            $row['id_dari'],
            $row['id_ke']
        );
    }
}

if ($konflik) {
    foreach ($konflik as $baris) {
        echo '  ! ' . $baris . "\n";
    }
    echo "\nSkrip berhenti: selesaikan konflik di atas secara manual dulu.\n";
    exit(2);
}
echo "  Tidak ada konflik nilai.\n";
if ($absensiGanda) {
    printf("  %d absensi ganda akan dirapikan otomatis (satu status per jurnal).\n", count($absensiGanda));
}

// ---------------------------------------------------------------- rencana
judul('RENCANA');
$rencana = [];

foreach ($tabelAnak as $tabel => $kunci) {
    if (!isset($ringkasan[$tabel]) || empty($ringkasan[$tabel]['dari'])) {
        continue;
    }
    $jumlah = $ringkasan[$tabel]['dari'];
    $catatan = '';
    if ($tabel === 'jurnal_kehadiran' && $absensiGanda) {
        $jumlah -= count($absensiGanda);
        $catatan = sprintf(' (setelah %d baris ganda dihapus)', count($absensiGanda));
    }
    if ($jumlah > 0) {
        $rencana[] = sprintf('pindahkan %d baris %s ke ID %d%s', $jumlah, $tabel, $idKe, $catatan);
    }
}

$bentrokPlotting = [];
$plottingPindah = [];
if (tabelAda($db, 'ploting_siswa')) {
    $stmt = $db->prepare("
        SELECT a.id_ploting AS id_dari, b.id_ploting AS id_ke, a.id_kelas, a.id_tahun
        FROM ploting_siswa a
        JOIN ploting_siswa b
          ON b.id_kelas = a.id_kelas AND b.id_tahun = a.id_tahun AND b.id_siswa = :ke
        WHERE a.id_siswa = :dari
    ");
    $stmt->execute([':ke' => $idKe, ':dari' => $idDari]);
    $bentrokPlotting = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("
        SELECT a.id_ploting, a.id_kelas, a.id_tahun
        FROM ploting_siswa a
        WHERE a.id_siswa = :dari
          AND NOT EXISTS (
              SELECT 1 FROM ploting_siswa b
              WHERE b.id_siswa = :ke AND b.id_kelas = a.id_kelas AND b.id_tahun = a.id_tahun
          )
    ");
    $stmt->execute([':ke' => $idKe, ':dari' => $idDari]);
    $plottingPindah = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($plottingPindah as $row) {
        $rencana[] = sprintf('pindahkan plotting id %d (kelas %d, tahun %d) ke ID %d',
            $row['id_ploting'], $row['id_kelas'], $row['id_tahun'], $idKe);
    }
    foreach ($bentrokPlotting as $row) {
        $rencana[] = sprintf(
            'plotting id %d (kelas %d, tahun %d) dibuang karena ID %d sudah punya plotting id %d di kelas & tahun yang sama; keanggotaan ekstra/prestasi dipindah ke plotting id %d (baris kembar dilewati)',
            $row['id_dari'], $row['id_kelas'], $row['id_tahun'], $idKe, $row['id_ke'], $row['id_ke']
        );
    }
}

foreach ($absensiGanda as $row) {
    $rencana[] = sprintf(
        'absensi jurnal %s: baris ganda dihapus, status "%s" dipertahankan',
        $row['id_jurnal'],
        ($row['status_dari'] !== 'H' && $row['status_dari'] !== $row['status_ke']) ? $row['status_dari'] : $row['status_ke']
    );
}

$rencana[] = sprintf('record %d ditandai nonaktif (status = Nonaktif, status_siswa = nonaktif)', $idDari);

foreach ($rencana as $i => $baris) {
    printf("  %d. %s\n", $i + 1, $baris);
}

if (!$apply) {
    echo "\nMode PRATINJAU. Tidak ada yang diubah.\n";
    echo "Jalankan ulang dengan --apply setelah yakin.\n";
    exit(0);
}

// ---------------------------------------------------------------- eksekusi
judul('EKSEKUSI');
$db->beginTransaction();
try {
    // 1. Anak-anak plotting dipindah ke plotting milik --ke lebih dulu.
    if ($bentrokPlotting) {
        foreach ($bentrokPlotting as $row) {
            foreach ($tabelPlotting as $tabel => $kolom) {
                if (!tabelAda($db, $tabel) || !adaKolom($db, $tabel, $kolom)) {
                    continue;
                }
                $pk = kolomPrimary($db, $tabel);
                if ($pk === null) {
                    continue;
                }

                // Kolom pembanding: semua kolom selain primary key dan kolom plotting.
                $stmt = $db->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS
                                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                                        AND COLUMN_NAME NOT IN (?, ?)");
                $stmt->execute([$tabel, $pk, $kolom]);
                $pembanding = array_map(static fn($c) => "b.`$c` <=> a.`$c`", $stmt->fetchAll(PDO::FETCH_COLUMN));
                $syaratSama = $pembanding ? ' AND ' . implode(' AND ', $pembanding) : '';

                // Baris yang isinya sama persis dengan baris plotting tujuan dibuang,
                // supaya siswa tidak terdaftar dua kali di ekstra/prestasi yang sama.
                $db->prepare("
                    DELETE a FROM `$tabel` a
                    JOIN `$tabel` b
                      ON b.`$kolom` = :ke AND a.`$kolom` = :dari$syaratSama
                ")->execute([':dari' => $row['id_dari'], ':ke' => $row['id_ke']]);

                $db->prepare("UPDATE `$tabel` SET `$kolom` = ? WHERE `$kolom` = ?")
                   ->execute([$row['id_ke'], $row['id_dari']]);
            }
            $db->prepare("DELETE FROM ploting_siswa WHERE id_ploting = ?")->execute([$row['id_dari']]);
            printf("  plotting %d dibuang (anak-anak dipindah ke plotting %d)\n", $row['id_dari'], $row['id_ke']);
        }
    }

    // 2. Absensi ganda: satu status per siswa per jurnal.
    foreach ($absensiGanda as $row) {
        $statusDari = (string) $row['status_dari'];
        $statusKe   = (string) $row['status_ke'];
        if ($statusDari !== $statusKe && $statusDari !== 'H') {
            $db->prepare("UPDATE jurnal_kehadiran SET status = ? WHERE id_jurnal_kehadiran = ?")
               ->execute([$statusDari, $row['id_ke']]);
        }
        $db->prepare("DELETE FROM jurnal_kehadiran WHERE id_jurnal_kehadiran = ?")
           ->execute([$row['id_dari']]);
        printf("  absensi jurnal %s dirapikan\n", $row['id_jurnal']);
    }

    // 3. Baris anak yang tidak bentrok: pindahkan.
    foreach ($tabelAnak as $tabel => $kunci) {
        if (!isset($ringkasan[$tabel]) || empty($ringkasan[$tabel]['dari'])) {
            continue;
        }
        $stmt = $db->prepare("UPDATE `$tabel` SET id_siswa = ? WHERE id_siswa = ?");
        $stmt->execute([$idKe, $idDari]);
        printf("  %s: %d baris dipindah\n", $tabel, $stmt->rowCount());
    }

    // 4. Plotting yang tidak bentrok: pindahkan.
    if (!empty($plottingPindah)) {
        $db->prepare("UPDATE ploting_siswa SET id_siswa = ? WHERE id_siswa = ?")->execute([$idKe, $idDari]);
        printf("  ploting_siswa: %d baris dipindah\n", count($plottingPindah));
    }

    // 5. Tandai nonaktif; jangan hapus supaya masih bisa diaudit.
    $db->prepare("UPDATE siswa SET status = 'Nonaktif', status_siswa = 'nonaktif' WHERE id_siswa = ?")
       ->execute([$idDari]);
    printf("  siswa %d ditandai nonaktif\n", $idDari);

    $db->commit();
} catch (Throwable $e) {
    $db->rollBack();
    fwrite(STDERR, "\nGAGAL: " . $e->getMessage() . "\nSemua perubahan dibatalkan.\n");
    exit(3);
}

judul('SELESAI');
printf("Record %d sekarang kosong dari data anak dan nonaktif. Record %d memegang seluruh riwayat.\n", $idDari, $idKe);
echo "Periksa daftar absensi/nilai kelas terkait, lalu backup ulang database.\n";
