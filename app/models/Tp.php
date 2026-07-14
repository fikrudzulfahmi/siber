<?php
class Tp
{
    private $db;
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }


    // --- TAMBAHKAN FUNGSI INI ---
    public function getActiveTahunPelajaran()
    {
        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function getAllKelasByGuru($id_employe, $id_tahun_aktif)
    {
        $stmt = $this->db->prepare("
            SELECT 
                k.id_kelas, k.kelas, mg.id_mapel, m.kode_mapel, 
                m.nama_mapel, m.tingkat_mapel, mg.id_mapel_guru, 
                COUNT(tp.id_tp) AS jumlah_tp
            FROM mapel_guru mg
            JOIN kelas k ON mg.id_kelas = k.id_kelas
            JOIN mapel m ON mg.id_mapel = m.id_mapel
            LEFT JOIN tp tp ON mg.id_mapel_guru = tp.id_mapel_guru 
            WHERE mg.id_guru = :id_employe 
            AND mg.id_tahun_pelajaran = :id_tahun -- Filter tahun aktif
            GROUP BY k.id_kelas, k.kelas, mg.id_mapel, m.kode_mapel, m.nama_mapel, m.tingkat_mapel, mg.id_mapel_guru
        ");
        $stmt->execute([
            'id_employe' => $id_employe,
            'id_tahun'   => $id_tahun_aktif
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id_tp)
    {
        $stmt = $this->db->prepare("SELECT * FROM tp WHERE id_tp = ?");
        $stmt->execute([$id_tp]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function insert($data)
    {

        $stmt = $this->db->prepare("INSERT INTO tp (id_mapel_guru, tujuan_pembelajaran) VALUES (?, ?)");
        $stmt->execute([
            $data['id_mapel_guru'],
            $data['tp']
        ]);
    }

    public function getGuruNames($idList)
    {
        $ids = array_filter(array_map('intval', explode(',', $idList)));
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT nama FROM employe WHERE id_employe IN ($placeholders)");
        $stmt->execute($ids);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function getTp($id_tp)
    {
        $stmt = $this->db->prepare("SELECT tujuan_pembelajaran  FROM tp
        WHERE tp.id_tp = ?");
        $stmt->execute([$id_tp]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getByMapelGuru($id_mapel_guru)
    {
        $stmt = $this->db->prepare("SELECT tp.id_tp, tp.tujuan_pembelajaran, mg.id_mapel_guru  FROM tp tp
        JOIN mapel_guru mg ON mg.id_mapel_guru = tp.id_mapel_guru
        WHERE tp.id_mapel_guru = ?");
        $stmt->execute([$id_mapel_guru]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getInfoMapelGuru($id_mapel_guru)
    {
        $stmt = $this->db->prepare("SELECT m.nama_mapel, k.kelas, mg.id_mapel_guru
        FROM mapel_guru mg
        JOIN mapel m ON m.id_mapel = mg.id_mapel
        JOIN kelas k ON k.id_kelas = mg.id_kelas
        WHERE mg.id_mapel_guru = ?");
        $stmt->execute([$id_mapel_guru]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($data)
    {

        $sql = "UPDATE tp SET tujuan_pembelajaran = ? WHERE id_tp = ?";
        $params = [
            $data['tp'],
            $data['id_tp']
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }


    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM tp WHERE id_tp = ?");
        $stmt->execute([$id]);
    }


    public function getGuruForFilter($id_tahun)
    {
        $sql = "SELECT DISTINCT e.id_employe, e.nama 
            FROM employe e 
            JOIN mapel_guru mg ON e.id_employe = mg.id_guru 
            WHERE mg.id_tahun_pelajaran = :id_tahun
            ORDER BY e.nama ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_tahun' => $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * MODIFIKASI method yang mengambil data rekap TP.
     * Tambahkan parameter $id_guru.
     */
    // SEBELUMNYA: public function getRekapTp($id_guru = 'semua', $id_tahun)
    // PERBAIKAN:
    public function getRekapTp($id_tahun, $id_guru = 'semua')
    {
        $sql = "
        SELECT e.nama as nama_guru, m.nama_mapel, k.kelas as nama_kelas, mg.id_mapel_guru,
               (SELECT COUNT(*) FROM tp WHERE id_mapel_guru = mg.id_mapel_guru) as jumlah_tp
        FROM mapel_guru mg
        JOIN employe e ON mg.id_guru = e.id_employe
        JOIN mapel m ON mg.id_mapel = m.id_mapel
        JOIN kelas k ON mg.id_kelas = k.id_kelas
        WHERE mg.id_tahun_pelajaran = :id_tahun
    ";

        $params = [':id_tahun' => $id_tahun];

        if ($id_guru && $id_guru != 'semua') {
            $sql .= " AND e.id_employe = :id_guru";
            $params[':id_guru'] = $id_guru;
        }

        $sql .= " ORDER BY e.nama, m.nama_mapel ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Mengambil detail TP berdasarkan id_mapel_guru.
     */
    public function getDetailTPById($id_mapel_guru)
    {
        $sql = "SELECT tujuan_pembelajaran FROM tp WHERE id_mapel_guru = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_mapel_guru]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
