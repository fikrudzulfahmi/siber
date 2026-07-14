<?php
require_once 'Model.php';

class VerifikasiProgramStruktural extends Model
{
    public function getTahunPelajaran()
    {
        return $this->db->query(
            "SELECT * FROM tahun_pelajaran ORDER BY tahun_pelajaran DESC, semester DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

public function getPegawaiStruktural()
{
    $allowedLevels = [5,6,10,11,12,13,14,15,16];
    $placeholders = implode(',', array_fill(0, count($allowedLevels), '?'));

    $sql = "
        SELECT DISTINCT e.id_employe, e.nama, ul.id_level
        FROM employe e
        JOIN program_struktural ps ON ps.id_employe = e.id_employe
        JOIN user_level ul ON ul.id_employe = e.id_employe
        WHERE ul.id_level IN ($placeholders)
        ORDER BY e.nama
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($allowedLevels);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



public function getProgramByUserAndTahun($id_user, $id_tahun)
{
    $sql = "
        SELECT jp.nama AS jenis_program,
               ps.id_program AS id,
               ps.file,
               COALESCE(ps.status_approval, 'belum upload') AS status_approval,
               ps.catatan
        FROM jenis_program_struktural jp
        LEFT JOIN program_struktural ps
            ON ps.jenis_program = jp.nama
           AND ps.id_employe = ?
           AND ps.id_tahun_pelajaran = ?
        ORDER BY jp.nama
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$id_user, $id_tahun]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    public function updateStatus($id, $status)
    {
        return $this->db->prepare(
            "UPDATE program_struktural SET status_approval=? WHERE id_program=?"
        )->execute([$status, $id]);
    }

    public function updateCatatan($id, $catatan)
    {
        return $this->db->prepare(
            "UPDATE program_struktural SET catatan=? WHERE id_program=?"
        )->execute([$catatan, $id]);
    }
}
