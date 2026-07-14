<?php
class TahunPelajaran
{
    private $db;
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function getAll()
    {
        return $this->db->query("SELECT * FROM tahun_pelajaran")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAlltp()
    {
        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tambahkan fungsi ini untuk memperbaiki error
    public function getTahunById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran WHERE id_tahun_pelajaran = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAktif()
    {
        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($data)
    {
        $status = 'Tidak Aktif'; // Default saat insert
        $stmt = $this->db->prepare("INSERT INTO tahun_pelajaran (tahun_pelajaran, semester, status) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['tahun_pelajaran'],
            $data['semester'],
            $status
        ]);
    }

    public function update($data)
    {
        $stmt = $this->db->prepare("UPDATE tahun_pelajaran SET tahun_pelajaran = ?, semester = ?, status = ? WHERE id_tahun_pelajaran = ?");
        $stmt->execute([
            $data['tahun_pelajaran'],
            $data['semester'],
            $data['status'],
            $data['id_tahun_pelajaran']
        ]);
    }

    public function deactivateAll()
    {
        // 1. Siapkan query SQL
        $stmt = $this->db->prepare("UPDATE tahun_pelajaran SET status = 'Tidak Aktif'");

        // 2. Jalankan query yang sudah disiapkan
        return $stmt->execute();
    }

    /**
     * Mengubah status satu tahun pelajaran menjadi 'Aktif' berdasarkan ID.
     * Ini juga method yang diperbaiki.
     */
    public function activateById($id)
    {
        // 1. Siapkan query SQL dengan placeholder (?) untuk keamanan
        $stmt = $this->db->prepare("UPDATE tahun_pelajaran SET status = 'Aktif' WHERE id_tahun_pelajaran = ?");

        // 2. Jalankan query sambil menyisipkan nilai ID ke placeholder
        return $stmt->execute([$id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM tahun_pelajaran WHERE id_tahun_pelajaran = ?");
        $stmt->execute([$id]);
    }
    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran WHERE id_tahun_pelajaran = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
