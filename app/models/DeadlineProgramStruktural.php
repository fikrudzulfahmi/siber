<?php
require_once 'Model.php';

class DeadlineProgramStruktural
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    // Ambil deadline berdasarkan tahun pelajaran
    public function getByTahun($id_tahun)
    {
        $stmt = $this->db->prepare("SELECT * FROM deadline_program_struktural WHERE id_tahun_pelajaran = ?");
        $stmt->execute([$id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Simpan deadline baru
    public function store($data)
    {
        $stmt = $this->db->prepare("INSERT INTO deadline_program_struktural 
            (id_tahun_pelajaran, jenis_program, tanggal_deadline, dibuat_pada) 
            VALUES (?, ?, ?, NOW())");
        return $stmt->execute([
            $data['id_tahun_pelajaran'],
            $data['jenis_program'],
            $data['tanggal_deadline']
        ]);
    }

    // Update deadline
public function update($id, $data)
{
    $stmt = $this->db->prepare(
        "UPDATE deadline_program_struktural 
         SET tanggal_deadline = ? 
         WHERE id_deadline = ?"
    );
    return $stmt->execute([$data['tanggal_deadline'], $id]);
}


    // Hapus deadline
    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM deadline_program_struktural WHERE id_deadline = ?");
        return $stmt->execute([$id]);
    }
}
