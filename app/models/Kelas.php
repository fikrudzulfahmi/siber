<?php
class Kelas
{
    private $db;
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }




    public function getAll()
    {
        return $this->db->query("SELECT * FROM kelas
        JOIN employe ON employe.id_employe = kelas.wali_kelas
        ")->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getById($id_kelas) //untuk kehadiran
    {
        $stmt = $this->db->prepare("SELECT * FROM kelas WHERE id_kelas = ?");
        $stmt->execute([$id_kelas]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM kelas WHERE id_kelas = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($data)
    {
        $stmt = $this->db->prepare("INSERT INTO kelas (kelas, wali_kelas, tingkat) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['kelas'],
            $data['id_walikelas'],
            $data['tingkat']
        ]);
    }

    public function update($data)
    {
        $sql = "UPDATE kelas SET kelas = ?, tingkat = ?, wali_kelas = ? WHERE id_kelas = ?";
        $params = [
            $data['kelas'],
            $data['tingkat'],
            $data['walikelas'],
            $data['id_kelas']
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }


    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM kelas WHERE id_kelas = ?");
        $stmt->execute([$id]);
    }
}
