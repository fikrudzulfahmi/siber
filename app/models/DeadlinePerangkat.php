<?php
require_once 'Model.php'; // Pastikan path ini sesuai dengan struktur folder

class DeadlinePerangkat
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    // Ambil semua data deadline, urut berdasarkan tanggal terbaru
    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM deadline_perangkat d JOIN tahun_pelajaran t ON d.id_tahun_pelajaran = t.id_tahun_pelajaran ORDER BY tanggal_deadline DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil deadline berdasarkan tahun pelajaran
    public function getByTahun($id_tahun_pelajaran)
    {
        $stmt = $this->db->prepare("SELECT * FROM deadline_perangkat WHERE id_tahun_pelajaran = ?");
        $stmt->execute([$id_tahun_pelajaran]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getAlltp()
{
    $stmt = $this->db->query("
        SELECT d.*, t.tahun_pelajaran AS tahun_pelajaran, j.nama AS jenis_perangkat
        FROM deadline_perangkat d
        JOIN tahun_pelajaran t ON d.id_tahun_pelajaran = t.id_tahun_pelajaran
        JOIN jenis_perangkat j ON d.id_jenis_perangkat = j.id
        ORDER BY t.tahun_pelajaran DESC, j.nama ASC
    ");
    return $stmt->fetchAll();
}



    // Cek apakah deadline dengan jenis dan tahun pelajaran sudah ada
    public function findByJenisAndTahun($jenis, $id_tahun_pelajaran)
    {
        $stmt = $this->db->prepare("SELECT * FROM deadline_perangkat 
                                    WHERE jenis_perangkat = ? AND id_tahun_pelajaran = ?");
        $stmt->execute([$jenis, $id_tahun_pelajaran]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

public function store($data)
{
    $stmt = $this->db->prepare("INSERT INTO deadline_perangkat 
        (id_tahun_pelajaran, jenis_perangkat, tanggal_deadline, dibuat_pada) 
        VALUES (?, ?, ?, NOW())");
    return $stmt->execute([
        $data['id_tahun_pelajaran'],
        $data['jenis_perangkat'],
        $data['tanggal_deadline']
    ]);
}


    // Perbarui data deadline berdasarkan ID
public function update($id, $data)
{
    $stmt = $this->db->prepare("UPDATE deadline_perangkat SET tanggal_deadline = ? WHERE id_deadline = ?");
    return $stmt->execute([$data['tanggal_deadline'], $id]);
}


    // Hapus data deadline berdasarkan ID
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM deadline_perangkat WHERE id_deadline = ?");
        return $stmt->execute([$id]);
    }
}
