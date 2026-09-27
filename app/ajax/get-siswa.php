<?php
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
$stmtTahun = $pdo->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
$id_tahun  = $stmtTahun ? $stmtTahun->fetchColumn() : null;

// 3. Ambil siswa pada KELAS + TAHUN AKTIF.
//    GROUP BY mencegah nama dobel: satu siswa bisa punya lebih dari satu baris
//    ploting pada id_kelas yang sama (id_kelas dipakai ulang antar tahun).
$sql = "SELECT s.id_siswa, s.nama_siswa 
        FROM ploting_siswa ps
        JOIN siswa s ON ps.id_siswa = s.id_siswa 
        WHERE ps.id_kelas = ?
        AND (s.status_siswa IS NULL OR s.status_siswa != 'lulus')";

$params = [$id_kelas];
if ($id_tahun) {
    $sql .= " AND ps.id_tahun = ?";
    $params[] = $id_tahun;
}

$sql .= " GROUP BY s.id_siswa ORDER BY s.nama_siswa ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($siswa);
