<?php
require_once __DIR__ . '/../helpers/SiswaAktif.php';

if (!isset($pdo)) {
    require_once '../../config/database.php';
}

// 1. Ambil id_kelas (kelas yang dipilih di form)
$id_kelas = $_GET['id_kelas'] ?? null;

if (!$id_kelas || !is_numeric($id_kelas)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// 2. Tentukan TAHUN PELAJARAN AKTIF dari DATABASE — sumber kebenaran.
//    Nilai dari sesi/form sengaja TIDAK dipakai: $_SESSION['user']['id_tahun']
//    hanya di-set sekali saat login dan tidak pernah di-refresh, sehingga bisa
//    BASI bila tahun pelajaran diganti tanpa login ulang. Akibatnya daftar siswa
//    justru menampilkan rombel tahun ajaran lama (kelas lama / nama campur).
$id_tahun = $pdo->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1")->fetchColumn();

// 3. Ambil siswa pada KELAS + TAHUN AKTIF.
//    DISTINCT mencegah nama dobel (satu siswa bisa punya lebih dari satu baris
//    ploting pada id_kelas yang sama) sekaligus aman untuk sql_mode
//    ONLY_FULL_GROUP_BY — berbeda dengan GROUP BY di MySQL/MariaDB ketat.
$sql = "SELECT DISTINCT s.id_siswa, s.nama_siswa, 
               (SELECT MIN(p2.urutan) FROM ploting_siswa p2
                 WHERE p2.id_siswa = s.id_siswa
                   AND p2.id_kelas = ps.id_kelas
                   AND p2.id_tahun = ps.id_tahun) AS urutan_tampil 
        FROM ploting_siswa ps
        JOIN siswa s ON ps.id_siswa = s.id_siswa 
        WHERE ps.id_kelas = ?
        AND " . siswa_aktif_sql('s') . "";

$params = [$id_kelas];
if ($id_tahun) {
    $sql .= " AND ps.id_tahun = ?";
    $params[] = $id_tahun;
}

$sql .= " ORDER BY (urutan_tampil IS NULL) ASC, urutan_tampil ASC, s.nama_siswa ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($siswa);
