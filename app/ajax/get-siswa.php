<?php
if (!isset($pdo)) {
    require_once '../../config/database.php';
}

// 1. Ambil id_kelas dan id_tahun (penting: satu siswa bisa di kelas berbeda di tahun berbeda)
$id_kelas = $_GET['id_kelas'] ?? null;
$id_tahun = $_GET['id_tahun'] ?? null;

if (!$id_kelas || !is_numeric($id_kelas)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// 2. Jika tahun tidak dikirim frontend, pakai TAHUN PELAJARAN AKTIF.
//    Tanpa ini, siswa dari tahun ajaran lama ikut muncul (kelas lama).
if (!$id_tahun) {
    $stmtTahun = $pdo->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
    $id_tahun = $stmtTahun ? $stmtTahun->fetchColumn() : null;
}

// 3. Query siswa pada KELAS + TAHUN tersebut.
//    GROUP BY mencegah nama siswa dobel (satu siswa bisa punya lebih dari satu
//    baris ploting di id_kelas yang sama pada tahun berbeda).
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
