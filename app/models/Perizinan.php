<?php
class Perizinan
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function getAll()
    {
        $stmt = $this->db->prepare("
            SELECT p.*, s.nama_siswa, k.nama_kelas
            FROM perizinan p
            JOIN siswa s ON p.id_siswa = s.id_siswa
            JOIN kelas k ON p.id_kelas = k.id_kelas
            ORDER BY p.waktu_meninggalkan DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO perizinan (id_siswa, id_kelas, keperluan, waktu_meninggalkan, waktu_kembali, nama_rekom)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([
            $data['id_siswa'],
            $data['id_kelas'],
            $data['keperluan'],
            $data['waktu_meninggalkan'],
            $data['waktu_kembali'],
            $data['nama_rekom']
        ]);
    }
}
