<?php
require_once 'Model.php';
require_once '../app/models/DeadlineProgramStruktural.php';


class ProgramStruktural extends Model
{
    public function getByUser($id_user, $id_tahun)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM program_struktural
            WHERE id_employe = ? AND id_tahun_pelajaran = ?
            ORDER BY jenis_program ASC
        ");
        $stmt->execute([$id_user, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertOrUpdate($data)
    {
        // hapus data lama
        $this->db->prepare("
            DELETE FROM program_struktural
            WHERE id_employe = ? 
              AND id_tahun_pelajaran = ?
              AND jenis_program = ?
        ")->execute([
            $data['id_employe'],
            $data['id_tahun_pelajaran'],
            $data['jenis_program']
        ]);

        // insert baru
        $stmt = $this->db->prepare("
            INSERT INTO program_struktural
            (id_employe, id_tahun_pelajaran, user_level, jenis_program, file)
            VALUES (?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['id_employe'],
            $data['id_tahun_pelajaran'],
            $data['user_level'],
            $data['jenis_program'],
            $data['file']
        ]);
    }
}
