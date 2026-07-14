<?php
class Setting
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Mengambil semua setting yang berawalan 'wa_'
    public function getAllWASettings()
    {
        $stmt = $this->db->query("SELECT * FROM settings WHERE key_setting LIKE 'wa_%' ORDER BY id_setting ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Update status berdasarkan key
    public function updateByKey($key, $status)
    {
        $stmt = $this->db->prepare("UPDATE settings SET status = :status WHERE key_setting = :key");
        return $stmt->execute([
            ':status' => $status,
            ':key' => $key
        ]);
    }
}
