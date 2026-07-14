<?php
class ProgramKerjaModel
{
    private $db;

    public function __construct($db)
    {
        // $db = PDO
        $this->db = $db;
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("
        SELECT *
        FROM program_kerja
        WHERE id_program = ?
        LIMIT 1
    ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }



    public function getAllByTahun($idTahun, $userId = null)
    {
        $sql = "
        SELECT pk.*, e.nama
        FROM program_kerja pk
        JOIN employe e ON pk.created_by = e.id_employe
        WHERE pk.id_tahun_pelajaran = ?
    ";

        $params = [$idTahun];

        if ($userId) {
            $sql .= " AND pk.created_by = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY pk.created_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * User yang pernah mengisi program kerja
     */
    public function getUserPengisiProgramKerja($idTahun)
    {
        $stmt = $this->db->prepare("
        SELECT DISTINCT e.id_employe, e.nama
        FROM program_kerja pk
        JOIN employe e ON pk.created_by = e.id_employe
        WHERE pk.id_tahun_pelajaran = ?
        ORDER BY e.nama ASC
    ");
        $stmt->execute([$idTahun]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByUserAndTahun($userId, $idTahun)
    {
        $stmt = $this->db->prepare("
            SELECT pk.*, tp.semester, tp.tahun_pelajaran
            FROM program_kerja pk
            JOIN tahun_pelajaran tp ON pk.id_tahun_pelajaran = tp.id_tahun_pelajaran
            WHERE created_by = ?
              AND pk.id_tahun_pelajaran = ?
            ORDER BY pk.created_at DESC
        ");
        $stmt->execute([$userId, $idTahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($data)
    {
        $stmt = $this->db->prepare("
        INSERT INTO program_kerja
        (nama_program, deskripsi_default, id_tahun_pelajaran, created_by, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");

        return $stmt->execute([
            $data['nama_program'],
            $data['deskripsi_default'],
            $data['id_tahun_pelajaran'],
            $data['created_by']
        ]);
    }

    public function update($data)
    {
        $stmt = $this->db->prepare("
        UPDATE program_kerja
        SET 
            nama_program = ?,
            deskripsi_default = ?
        WHERE id_program = ?
    ");

        return $stmt->execute([
            $data['nama_program'],
            $data['deskripsi_default'],
            $data['id_program']
        ]);
    }


    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM program_kerja WHERE id_program = ?");
        return $stmt->execute([$id]);
    }
}
