<?php
require_once 'Model.php';

class JenisProgramStruktural extends Model
{
    /**
     * Ambil semua jenis program struktural
     */
    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT *
            FROM jenis_program_struktural
            ORDER BY id ASC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * (Opsional) Ambil hanya nama jenis program
     * Berguna jika hanya butuh array string
     */
    public function getAllNama()
    {
        $stmt = $this->db->prepare("
            SELECT nama
            FROM jenis_program_struktural
            ORDER BY id ASC
        ");
        $stmt->execute();
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'nama');
    }
}
