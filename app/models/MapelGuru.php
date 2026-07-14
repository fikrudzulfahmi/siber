<?php

class MapelGuru
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getByGuru($id_guru)
    {
        $query = "SELECT mg.*, m.nama_mapel, mg.tingkat 
                  FROM mapel_guru mg
                  LEFT JOIN mapel m ON m.id_mapel = mg.id_mapel
                  WHERE mg.id_guru = :id_guru";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_guru', $id_guru);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id_mapel_guru)
    {
        $query = "SELECT mg.*, m.nama_mapel, e.nama AS nama_guru 
                  FROM mapel_guru mg
                  LEFT JOIN mapel m ON m.id_mapel = mg.id_mapel
                  LEFT JOIN employe e ON e.id_employe = mg.id_guru
                  WHERE mg.id_mapel_guru = :id_mapel_guru";
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_mapel_guru', $id_mapel_guru);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    public function getByGuruAndTahun($id_guru, $id_tahun_pelajaran)
{
    $query = "SELECT mg.*, m.nama_mapel, mg.tingkat 
              FROM mapel_guru mg
              LEFT JOIN mapel m ON m.id_mapel = mg.id_mapel
              WHERE mg.id_guru = :id_guru AND mg.id_tahun_pelajaran = :id_tahun";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':id_guru', $id_guru);
    $stmt->bindParam(':id_tahun', $id_tahun_pelajaran);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

}
