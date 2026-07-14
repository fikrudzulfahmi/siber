<?php
require_once '../config/database.php';
require_once '../app/models/Perangkat.php';

$perangkat = new Perangkat($pdo);

if (isset($_POST['id_mapel_kelas'])) {
    $id_mapel_kelas = $_POST['id_mapel_kelas'];
    $data = $perangkat->getPerangkatByMapelKelas($id_mapel_kelas);

    if (empty($data)) {
        echo "<p class='text-muted'>Belum ada data perangkat.</p>";
    } else {
        echo "<table class='table table-bordered'>
                <thead>
                    <tr>
                        <th>Jenis Perangkat</th>
                        <th>Status</th>
                        <th>File</th>
                    </tr>
                </thead>
                <tbody>";
        foreach ($data as $row) {
            echo "<tr>
                    <td>{$row['nama_jenis']}</td>
                    <td>{$row['status_approval']}</td>
                    <td><a href='../uploads/{$row['file']}' target='_blank'>Lihat</a></td>
                </tr>";
        }
        echo "</tbody></table>";
    }
}
