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
        $stmt = $this->db->query("SELECT * FROM jenis_perangkat WHERE status=1");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
