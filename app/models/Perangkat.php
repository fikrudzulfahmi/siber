<?php
require_once 'Model.php'; // Pastikan path-nya sesuai, ini relatif terhadap BaseController

class Perangkat extends Model
{
    public function getByUserAndTahun($id_user, $id_tahun)
    {
        $stmt = $this->db->prepare("SELECT * FROM perangkat_mengajar 
        WHERE id_employe = ? AND id_tahun_pelajaran = ?
        ORDER BY jenis_perangkat ASC");
        $stmt->execute([$id_user, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getPerangkat($id_employe, $id_tahun, $jenis)
    {
        $stmt = $this->db->prepare("SELECT * FROM perangkat_mengajar WHERE id_employe = ? AND id_tahun_pelajaran = ? AND jenis_perangkat = ?");
        $stmt->execute([$id_employe, $id_tahun, $jenis]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

public function insertOrUpdate($data)
{
    // hapus data sebelumnya
    $this->db->prepare("DELETE FROM perangkat_mengajar 
        WHERE id_employe = ? AND id_tahun_pelajaran = ? AND id_mapel_guru = ? AND jenis_perangkat = ?")
        ->execute([$data['id_employe'], $data['id_tahun_pelajaran'], $data['id_mapel_guru'], $data['jenis_perangkat']]);

    // insert data baru
    $stmt = $this->db->prepare("INSERT INTO perangkat_mengajar 
        (id_employe, id_tahun_pelajaran, id_mapel_guru, tingkat, jenis_perangkat, file, status_approval, tanggal_upload) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");

    return $stmt->execute([
        $data['id_employe'],
        $data['id_tahun_pelajaran'],
        $data['id_mapel_guru'],
        $data['tingkat'],
        $data['jenis_perangkat'],
        $data['file']
    ]);
}



    public function getAllPerangkatGuru($id_tahun)
    {
        $stmt = $this->db->prepare("SELECT p.*, e.nama FROM perangkat_mengajar p JOIN employe e ON p.id_employe = e.id_employe WHERE p.id_tahun_pelajaran = ?");
        $stmt->execute([$id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

public function getWithDeadline($id_user, $id_tahun_pelajaran, $id_mapel_guru, $tingkat)
{
    $stmt = $this->db->prepare("
        SELECT 
            p.id_mapel_guru,
            m.nama_mapel,
            k.kelas,
            k.tingkat,
            p.jenis_perangkat,
            p.file,
            p.status_approval,
            p.tanggal_upload,
            d.tanggal_deadline,
            p.catatan  -- Menambahkan kolom catatan di sini
        FROM perangkat_mengajar p
        LEFT JOIN deadline_perangkat d 
            ON p.jenis_perangkat = d.jenis_perangkat 
            AND p.id_tahun_pelajaran = d.id_tahun_pelajaran
        JOIN mapel_guru mg ON p.id_mapel_guru = mg.id_mapel_guru
        JOIN mapel m ON mg.id_mapel = m.id_mapel
        JOIN kelas k ON mg.id_kelas = k.id_kelas
        WHERE p.id_employe = :id_user 
          AND p.id_tahun_pelajaran = :id_tp
          AND p.id_mapel_guru = :id_mapel_guru
          AND k.tingkat = :tingkat
    ");
    $stmt->execute([
        ':id_user' => $id_user,
        ':id_tp' => $id_tahun_pelajaran,
        ':id_mapel_guru' => $id_mapel_guru,
        ':tingkat' => $tingkat
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function getByGuruAndMapel($idGuru, $idTahun, $idMapelGuru)
{
    $stmt = $this->db->prepare("SELECT * FROM perangkat WHERE id_employe = ? AND id_tahun_pelajaran = ? AND id_mapel_guru = ?");
    $stmt->execute([$idGuru, $idTahun, $idMapelGuru]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getPerangkatByMapelKelas($id_mapel_kelas)
{
    $stmt = $this->db->prepare("SELECT p.*, jp.nama_jenis 
        FROM perangkat p 
        JOIN jenis_perangkat jp ON p.id_jenis_perangkat = jp.id_jenis_perangkat 
        WHERE p.id_mapel_kelas = ?
        ORDER BY jp.urutan ASC");
    $stmt->execute([$id_mapel_kelas]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



    public function setStatusApproval($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE perangkat_mengajar SET status_approval = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

public function getByGuru($id_guru)
{
    $stmt = $this->db->prepare("SELECT DISTINCT m.nama_mapel, k.tingkat, MIN(mg.id_mapel_guru) AS id_mapel_guru
                                FROM mapel_guru mg
                                JOIN mapel m ON mg.id_mapel = m.id_mapel
                                JOIN kelas k ON mg.id_kelas = k.id_kelas
                                WHERE mg.id_guru = ?
                                GROUP BY m.nama_mapel, k.tingkat
                                ORDER BY m.nama_mapel, k.tingkat");
    $stmt->execute([$id_guru]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getByMapelGuru($id_mapel_guru)
{
    $stmt = $this->db->prepare("SELECT * FROM perangkat_mengajar WHERE id_mapel_guru = ?");
    $stmt->execute([$id_mapel_guru]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function updateStatus($id, $status)
{
    $stmt = $this->db->prepare("UPDATE perangkat_mengajar SET status_approval = ? WHERE id_perangkat = ?");
    return $stmt->execute([$status, $id]);
}

}