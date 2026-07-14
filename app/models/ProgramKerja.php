<?php
class ProgramKerja
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll($isAdmin, $idEmploye)
    {
        if ($isAdmin) {
            return $this->db->query("SELECT * FROM program_kerja ORDER BY created_at DESC")->fetchAll();
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM program_kerja WHERE created_by = ? ORDER BY created_at DESC"
        );
        $stmt->execute([$idEmploye]);
        return $stmt->fetchAll();
    }

    public function insert($data)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO program_kerja (nama_program, deskripsi_default, created_by)
             VALUES (?, ?, ?)"
        );
        $stmt->execute([
            $data['nama_program'],
            $data['deskripsi_default'],
            $data['created_by']
        ]);

        return $this->db->lastInsertId();
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM program_kerja WHERE id_program = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function update($id, $data)
    {
        $stmt = $this->db->prepare(
            "UPDATE program_kerja SET nama_program = ?, deskripsi_default = ? WHERE id_program = ?"
        );
        return $stmt->execute([
            $data['nama_program'],
            $data['deskripsi_default'],
            $id
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM program_kerja WHERE id_program = ?");
        return $stmt->execute([$id]);
    }
}
