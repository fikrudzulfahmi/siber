<?php
require_once 'Model.php';

class Rekat extends Model
{
    public function getTahunPelajaran()
    {
        $stmt = $this->db->query("SELECT * FROM tahun_pelajaran ORDER BY tahun_pelajaran DESC, semester DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRekapPerangkat($id_tahun)
    {
        $sql = "
            SELECT e.id_employe, e.nama, m.id_mapel, m.nama_mapel, k.tingkat,
                   COUNT(DISTINCT jp.id) AS total_perangkat,
                   SUM(CASE WHEN p.file IS NOT NULL THEN 1 ELSE 0 END) AS terupload
            FROM employe e
            JOIN mapel_guru mg ON e.id_employe = mg.id_guru
            JOIN mapel m ON mg.id_mapel = m.id_mapel
            JOIN kelas k ON mg.id_kelas = k.id_kelas
            CROSS JOIN jenis_perangkat jp
            LEFT JOIN perangkat_mengajar p 
                ON p.jenis_perangkat = jp.nama
                AND p.id_employe = e.id_employe
                AND p.id_tahun_pelajaran = :id_tahun_pelajaran
                AND p.id_mapel_guru = mg.id_mapel_guru
            GROUP BY e.id_employe, e.nama, m.id_mapel, m.nama_mapel, k.tingkat
            ORDER BY e.nama, m.nama_mapel, k.tingkat
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_tahun_pelajaran' => $id_tahun]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Gabungkan per guru
        $result = [];
        foreach ($rows as $row) {
            if (!isset($result[$row['id_employe']])) {
                $result[$row['id_employe']] = [
                    'id_employe' => $row['id_employe'],
                    'nama' => $row['nama'],
                    'mapel_badges' => []
                ];
            }
            $result[$row['id_employe']]['mapel_badges'][] = [
                'nama_mapel' => $row['nama_mapel'],
                'tingkat' => $row['tingkat'],
                'id_mapel' => $row['id_mapel'],
                'total_perangkat' => $row['total_perangkat'],
                'terupload' => $row['terupload']
            ];
        }
        return array_values($result);
    }

    public function getDetailPerangkat($id_tahun, $id_guru, $id_mapel, $tingkat)
    {
        $sql = "
            SELECT jp.nama AS nama_perangkat,
                   CASE WHEN p.file IS NOT NULL THEN 'Sudah Upload' ELSE 'Belum Upload' END AS status
            FROM jenis_perangkat jp
            LEFT JOIN perangkat_mengajar p 
                ON p.jenis_perangkat = jp.nama
                AND p.id_employe = :id_guru
                AND p.id_tahun_pelajaran = :id_tahun_pelajaran
                AND p.id_mapel_guru IN (
                    SELECT id_mapel_guru 
                    FROM mapel_guru mg
                    JOIN kelas k ON mg.id_kelas = k.id_kelas
                    WHERE mg.id_guru = :id_guru AND mg.id_mapel = :id_mapel AND k.tingkat = :tingkat
                )
            ORDER BY jp.nama
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_guru' => $id_guru,
            'id_tahun_pelajaran' => $id_tahun,
            'id_mapel' => $id_mapel,
            'tingkat' => $tingkat
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
