<?php
class AbsenManual
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAllUsers()
    {
        $sql = "SELECT * FROM employe ORDER BY nama ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

public function tambahAbsen($pin, $status, $keterangan, $scan_date)
{
    $sql = "INSERT INTO attendance (scan_date, pin, verify_mode, io_mode, status, keterangan) 
            VALUES (:scan_date, :pin, 1, 1, :status, :keterangan)";
    $stmt = $this->db->prepare($sql);

    return $stmt->execute([
        ':scan_date'   => $scan_date,
        ':pin'         => $pin,
        ':status'      => $status,
        ':keterangan'  => $keterangan
    ]);
}

}
