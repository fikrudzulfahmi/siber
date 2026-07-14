<?php
// app/models/Pengingat.php
class Pengingat
{
    private $db;
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function catatPengingat($id_guru, $tanggal)
    {
        // Coba masukkan data, jika sudah ada (karena UNIQUE KEY), abaikan errornya
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO pengingat_jurnal (id_guru, tanggal_pengingat) 
            VALUES (?, ?)
        ");
        $stmt->execute([$id_guru, $tanggal]);
    }

    public function getGuruYangSudahDiingatkan($tanggal)
    {
        $stmt = $this->db->prepare("SELECT id_guru FROM pengingat_jurnal WHERE tanggal_pengingat = ?");
        $stmt->execute([$tanggal]);
        // Kembalikan dalam format [id_guru => true] agar mudah dicek
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }
}
