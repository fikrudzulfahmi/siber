<?php
require_once 'Model.php';

class JenisProgramStruktural extends Model
{
    /**
     * Ambil semua jenis program struktural yang aktif (status = 1)
     */
    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM jenis_program_struktural
            WHERE status = 1
            ORDER BY id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil semua jenis program struktural untuk Admin (semua status)
     */
    public function getAllAdmin()
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM jenis_program_struktural
            ORDER BY id DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * (Opsional) Ambil hanya nama jenis program yang aktif
     */
    public function getAllNama()
    {
        $stmt = $this->db->prepare("
            SELECT nama
            FROM jenis_program_struktural
            WHERE status = 1
            ORDER BY id DESC
        ");
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nama');
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM jenis_program_struktural WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($nama)
    {
        $stmt = $this->db->prepare("INSERT INTO jenis_program_struktural (nama, status) VALUES (?, 1)");
        return $stmt->execute([$nama]);
    }

    public function update($id, $nama, $status)
    {
        $stmt = $this->db->prepare("UPDATE jenis_program_struktural SET nama = ?, status = ? WHERE id = ?");
        return $stmt->execute([$nama, $status, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("UPDATE jenis_program_struktural SET status = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
