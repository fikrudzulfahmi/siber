<?php
$id_kelas = $_GET['id_kelas'] ?? null;

if (!$id_kelas) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id_siswa, nama_siswa FROM siswa WHERE id_kelas = ?");
$stmt->execute([$id_kelas]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
