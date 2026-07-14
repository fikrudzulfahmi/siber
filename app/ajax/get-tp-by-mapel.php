<?php
$id_mapel_guru = $_GET['id_mapel_guru'] ?? null;

if (!$id_mapel_guru) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id_tp, tujuan_pembelajaran FROM tp WHERE id_mapel_guru = ?");
$stmt->execute([$id_mapel_guru]);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
