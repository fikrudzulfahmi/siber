<?php
require_once 'Model.php';
require_once 'JenisPerangkatAlias.php';

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

    /**
     * Mapel + tingkat yang diampu guru PADA TAHUN PELAJARAN YANG DIPILIH.
     *
     * Sumbernya DUA, sama seperti Rekap Perangkat:
     *   1. penugasan guru pada tahun itu, dan
     *   2. apa pun yang diunggah guru itu pada tahun itu.
     * (1) saja tidak cukup: unggahan bisa menempel pada baris mapel_guru tahun lain — kalau
     * tidak digabung, unggahan itu tak bisa diverifikasi admin karena pilihannya tak muncul.
     *
     * Sebelumnya parameter $id_tahun diabaikan sama sekali, sehingga guru kelas 8 masih
     * ditawari tingkat 7 dari tahun lalu dan paling sedikit satu unggahan (34 unggahan
     * tahun aktif menempel di baris non-acuan) terbaca "Belum upload".
     */
    public function getMapelByGuruAndTahun($id_guru, $id_tahun = null)
    {
        if ($id_tahun === null) {
            $id_tahun = $this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1")->fetchColumn();
        }

        $sql = "
            SELECT nama_mapel, tingkat, MIN(id_mapel_guru) AS id_mapel_guru
            FROM (
                SELECT m.nama_mapel, k.tingkat, mg.id_mapel_guru
                FROM mapel_guru mg
                JOIN mapel m ON mg.id_mapel = m.id_mapel
                JOIN kelas k ON mg.id_kelas = k.id_kelas
                WHERE mg.id_guru = :id_guru_a AND mg.id_tahun_pelajaran = :id_tahun_a

                UNION

                SELECT m2.nama_mapel, k2.tingkat, mg2.id_mapel_guru
                FROM perangkat_mengajar p
                JOIN mapel_guru mg2 ON mg2.id_mapel_guru = p.id_mapel_guru
                JOIN mapel m2 ON mg2.id_mapel = m2.id_mapel
                JOIN kelas k2 ON k2.id_kelas = mg2.id_kelas
                WHERE p.id_employe = :id_guru_b AND p.id_tahun_pelajaran = :id_tahun_b
            ) x
            GROUP BY nama_mapel, tingkat
            ORDER BY nama_mapel, CAST(tingkat AS UNSIGNED)
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_guru_a'   => $id_guru,
            ':id_tahun_a'  => $id_tahun,
            ':id_guru_b'   => $id_guru,
            ':id_tahun_b'  => $id_tahun,
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Daftar perangkat satu mapel-tingkat milik guru pada tahun terpilih.
     *
     * Pencocokan unggahan memakai PASANGAN (guru, mapel, tingkat) + normalisasi label lama,
     * sama seperti Rekap Perangkat. Sebelumnya dicocokkan lewat SATU id_mapel_guru hasil
     * MIN() lintas tahun, sehingga unggahan yang menempel pada baris mapel_guru lain — atau
     * berlabel lama — terbaca "Belum upload" padahal file-nya ada (dan sudah terhitung di Rekap).
     * Satu baris per jenis perangkat; bila ada beberapa unggahan pada tingkat yang sama,
     * yang diambil adalah unggahan terbaru (id terbesar).
     */
    public function getPerangkatByFilter($id_guru, $id_tahun, $id_mapel_guru)
    {
        // Pasangan (mapel, tingkat) yang jadi acuan pencocokan, dari pilihan di dropdown.
        $stmt = $this->db->prepare("
            SELECT mg.id_mapel, k.tingkat
            FROM mapel_guru mg
            JOIN kelas k ON k.id_kelas = mg.id_kelas
            WHERE mg.id_mapel_guru = ?
        ");
        $stmt->execute([$id_mapel_guru]);
        $acuan = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$acuan) return [];

        $aliasJenis = JenisPerangkatAlias::ekspresi($this->db, 'p2.jenis_perangkat');
        $sql = "
            SELECT jp.nama AS jenis_perangkat,
                   p.file,
                   COALESCE(p.status_approval, 'belum upload') AS status_approval,
                   p.catatan,
                   p.id
            FROM jenis_perangkat jp
            LEFT JOIN perangkat_mengajar p ON p.id = (
                SELECT p2.id
                FROM perangkat_mengajar p2
                WHERE p2.id_employe = ?
                  AND p2.id_tahun_pelajaran = ?
                  AND {$aliasJenis} = jp.nama
                  AND p2.id_mapel_guru IN (
                      SELECT mg2.id_mapel_guru
                      FROM mapel_guru mg2
                      JOIN kelas k2 ON k2.id_kelas = mg2.id_kelas
                      WHERE mg2.id_mapel = ? AND k2.tingkat = ?
                  )
                ORDER BY p2.id DESC
                LIMIT 1
            )
            WHERE jp.status = '1'
            ORDER BY jp.nama
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_guru, $id_tahun, $acuan['id_mapel'], $acuan['tingkat']]);
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
