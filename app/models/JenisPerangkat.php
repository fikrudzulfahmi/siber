<?php
// app/models/JenisPerangkat.php

class JenisPerangkat
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $stmt = $this->db->query("SELECT * FROM jenis_perangkat WHERE status=1 ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllAdmin()
    {
        $stmt = $this->db->query("SELECT * FROM jenis_perangkat ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM jenis_perangkat WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($nama)
    {
        $stmt = $this->db->prepare("INSERT INTO jenis_perangkat (nama, status) VALUES (?, 1)");
        return $stmt->execute([$nama]);
    }

    public function update($id, $nama, $status)
    {
        $stmt = $this->db->prepare("UPDATE jenis_perangkat SET nama = ?, status = ? WHERE id = ?");
        return $stmt->execute([$nama, $status, $id]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("UPDATE jenis_perangkat SET status = 0 WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
