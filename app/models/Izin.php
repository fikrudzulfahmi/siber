<?php
require_once __DIR__ . '/../helpers/SiswaAktif.php';

class Izin
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function getTahunAktif()
    {
        $stmt = $this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
        return $stmt->fetchColumn(); // Mengembalikan ID atau false
    }

    /**
     * Daftar perizinan siswa — hanya siswa yang TER-PLOTING di tahun pelajaran AKTIF.
     * Kelas & wali kelas diambil dari ploting tahun aktif (bukan id_kelas lama di perizinan).
     */
    public function getAllWithSiswaKelas($id_kelas = null, $id_tahun = null)
    {
        if ($id_tahun === null) {
            $id_tahun = $this->getTahunAktif();
        }

        // Tidak ada tahun aktif → tidak ada data yang relevan
        if (!$id_tahun) {
            return [];
        }

        $sql = "
            SELECT 
                i.id_perizinan, i.id_siswa, i.id_kelas, i.keperluan,
                i.waktu_meninggalkan, i.waktu_kembali, i.nama_rekom,
                i.keterangan, i.tindakan, s.nama_siswa, k.kelas AS nama_kelas
            FROM perizinan i
            JOIN siswa s ON i.id_siswa = s.id_siswa
            JOIN ploting_siswa ps ON ps.id_siswa = s.id_siswa AND ps.id_tahun = ?
            JOIN kelas k ON ps.id_kelas = k.id_kelas
            WHERE " . siswa_aktif_sql('s') . "
        ";
        $params = [$id_tahun];

        // ✅ LOGIKA FILTER (kelas dari ploting tahun aktif, bukan id_kelas lama)
        if ($id_kelas !== null) {
            $sql .= " AND ps.id_kelas = ?";
            $params[] = $id_kelas;
        }

        $sql .= " ORDER BY i.waktu_meninggalkan DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    public function simpan($data)
    {
        $stmt = $this->db->prepare("INSERT INTO perizinan (id_siswa, id_kelas, keperluan, waktu_meninggalkan, waktu_kembali, nama_rekom)
                                     VALUES (?, ?, ?, ?, ?, ?)");
        return $stmt->execute($data);
    }
    public function findById($id)
    {
        $stmt = $this->db->prepare("
        SELECT p.*, s.nama_siswa, k.kelas
        FROM perizinan p
        JOIN siswa s ON p.id_siswa = s.id_siswa
        JOIN kelas k ON p.id_kelas = k.id_kelas
        WHERE p.id_perizinan = ?
    ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($data)
    {
        $stmt = $this->db->prepare("UPDATE perizinan SET id_siswa = ?, id_kelas = ?, keperluan = ?, waktu_meninggalkan = ?, waktu_kembali = ?, keterangan = ?, tindakan = ? WHERE id_perizinan = ?");
        return $stmt->execute($data);
    }
    public function getByDateRange($tanggalAwal, $tanggalAkhir, $id_kelas = null)
    {
        $sql = "
            SELECT 
                i.id_perizinan, i.id_siswa, i.id_kelas, i.keperluan,
                i.waktu_meninggalkan, i.waktu_kembali, i.nama_rekom,
                i.keterangan, i.tindakan, s.nama_siswa, k.kelas AS nama_kelas
            FROM perizinan i
            JOIN siswa s ON i.id_siswa = s.id_siswa
            JOIN kelas k ON i.id_kelas = k.id_kelas
            WHERE DATE(i.waktu_meninggalkan) BETWEEN ? AND ?
        ";
        $params = [$tanggalAwal, $tanggalAkhir];

        // ✅ LOGIKA FILTER BARU
        if ($id_kelas !== null) {
            $sql .= " AND i.id_kelas = ?";
            $params[] = $id_kelas;
        }

        $sql .= " ORDER BY i.waktu_meninggalkan DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM perizinan WHERE id_perizinan = ?");
        $stmt->execute([$id]);
    }
}
