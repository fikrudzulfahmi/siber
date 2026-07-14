<?php
if (!isset($pdo)) {
    require_once '../../config/database.php';
}

// 1. Ambil id_kelas dan id_tahun (Penting karena satu siswa bisa di kelas berbeda di tahun berbeda)
$id_kelas = $_GET['id_kelas'] ?? null;
$id_tahun = $_GET['id_tahun'] ?? null; // Disarankan mengirimkan id_tahun juga dari frontend

if (!$id_kelas || !is_numeric($id_kelas)) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// 2. Query menggunakan JOIN ke ploting_siswa
$sql = "SELECT s.id_siswa, s.nama_siswa 
        FROM ploting_siswa ps
        JOIN siswa s ON ps.id_siswa = s.id_siswa 
        WHERE ps.id_kelas = ?";

// Jika Anda memiliki sistem tahun ajaran, tambahkan filter tahun agar data akurat
$params = [$id_kelas];
if ($id_tahun) {
    $sql .= " AND ps.id_tahun = ?";
    $params[] = $id_tahun;
}

$sql .= " ORDER BY s.nama_siswa ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$siswa = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($siswa);
