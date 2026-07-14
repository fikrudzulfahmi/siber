<?php
require_once 'Model.php';

class Verifikasi extends Model
{
    // Ambil list tahun pelajaran aktif/seluruhnya
    public function getTahunPelajaran()
    {
        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran ORDER BY tahun_pelajaran DESC, semester DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getGuru()
    {
        $sql = "
            SELECT DISTINCT e.id_employe, e.nama
            FROM employe e
            JOIN mapel_guru mg ON e.id_employe = mg.id_guru
            ORDER BY e.nama
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil mapel & tingkat yang diampu guru (tanpa filter tahun pelajaran)
    public function getMapelByGuruAndTahun($id_guru, $id_tahun = null)
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT m.nama_mapel, k.tingkat, MIN(mg.id_mapel_guru) AS id_mapel_guru
FROM mapel_guru mg
JOIN mapel m ON mg.id_mapel = m.id_mapel
JOIN kelas k ON mg.id_kelas = k.id_kelas
WHERE mg.id_guru = ?
GROUP BY m.nama_mapel, k.tingkat
ORDER BY m.nama_mapel, k.tingkat

        ");
        $stmt->execute([$id_guru]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

public function getPerangkatByFilter($id_guru, $id_tahun, $id_mapel_guru)
{
    $sql = "
        SELECT jp.nama AS jenis_perangkat, 
               p.file, 
               COALESCE(p.status_approval, 'belum upload') AS status_approval,
               p.catatan,
               p.id
        FROM jenis_perangkat jp
        LEFT JOIN perangkat_mengajar p ON 
            p.jenis_perangkat = jp.nama AND 
            p.id_employe = ? AND 
            p.id_tahun_pelajaran = ? AND 
            p.id_mapel_guru = ?
        ORDER BY jp.nama
    ";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([$id_guru, $id_tahun, $id_mapel_guru]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Update status
public function updateStatusApproval($id_perangkat, $status)
{
    $allowed = ['pending', 'disetujui', 'ditolak'];
    if (!in_array($status, $allowed)) return false;

    $stmt = $this->db->prepare("UPDATE perangkat_mengajar SET status_approval = ? WHERE id = ?");
    return $stmt->execute([$status, $id_perangkat]);
}

// Update catatan
public function updateCatatan($id_perangkat, $catatan)
{
    $stmt = $this->db->prepare("UPDATE perangkat_mengajar SET catatan = ? WHERE id = ?");
    return $stmt->execute([$catatan, $id_perangkat]);
}

}
