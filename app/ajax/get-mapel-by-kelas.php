<?php

if ($_GET['action'] == 'get_mapel') {
    $id_kelas = $_GET['id_kelas'] ?? null;
    $id_guru = $_GET['id_guru'] ?? null;

    if (!$id_kelas || !$id_guru) {
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT mg.id_mapel_guru, CONCAT(m.nama_mapel, ' - ', k.kelas) AS nama_mapel
                          FROM mapel_guru mg
                          JOIN mapel m ON m.id_mapel = mg.id_mapel
                          JOIN kelas k ON k.id_kelas = mg.id_kelas
                          WHERE mg.id_kelas = ? AND mg.id_guru = ?");
    $stmt->execute([$id_kelas, $id_guru]);
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($result);
    exit;
}
