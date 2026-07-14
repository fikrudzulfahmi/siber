<?php
class Guru
{
    private $db;
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Ambil guru yang terdaftar di mapel_guru pada tahun pelajaran tertentu
     */
    public function getGuruByTahun($id_tahun_pelajaran)
    {
        $sql = "SELECT DISTINCT e.id_employe, e.nama
                FROM perangkat_mengajar pm
                JOIN employe e ON e.id_employe = pm.id_employe
                WHERE pm.id_tahun_pelajaran = :id_tahun_pelajaran
                ORDER BY e.nama ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_tahun_pelajaran' => $id_tahun_pelajaran]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ambil semua guru (tanpa filter tahun pelajaran)
     */
    public function getAll()
    {
        $sql = "SELECT id_employe, nama 
                FROM employe 
                WHERE role = 'guru'
                ORDER BY nama ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
