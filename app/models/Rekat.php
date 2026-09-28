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
        // Tiga hal penting di query ini:
        // 1. Sumber baris = penugasan TAHUN AKTIF, DIGABUNG dengan apa pun yang diunggah
        //    tahun aktif (walau penugasannya tercatat di tahun lain — ada 246 unggahan seperti
        //    itu). Jadi tidak ada unggahan yang hilang, tapi sel hantu tanpa unggahan tetap dibuang.
        // 2. Perangkat dicocokkan lewat pasangan (guru, mapel, tingkat), bukan lewat tahun
        //    baris mapel_guru-nya.
        // 3. Hanya jenis perangkat AKTIF (status '1') yang dihitung, agar penyebut sama
        //    dengan yang benar-benar ditawarkan ke guru (10, bukan 17).
        // COUNT(DISTINCT ...) menggantikan SUM(...) supaya satu jenis tak terhitung berkali-kali.
        $sql = "
            SELECT e.id_employe, e.nama, m.id_mapel, m.nama_mapel, src.tingkat,
                   COUNT(DISTINCT jp.id) AS total_perangkat,
                   COUNT(DISTINCT CASE WHEN p.file IS NOT NULL THEN p.jenis_perangkat END) AS terupload
            FROM (
                SELECT mg.id_guru, mg.id_mapel, k.tingkat
                FROM mapel_guru mg
                JOIN kelas k ON k.id_kelas = mg.id_kelas
                WHERE mg.id_tahun_pelajaran = :tahun_mg
                UNION
                SELECT p0.id_employe, mg0.id_mapel, k0.tingkat
                FROM perangkat_mengajar p0
                JOIN mapel_guru mg0 ON mg0.id_mapel_guru = p0.id_mapel_guru
                JOIN kelas k0 ON k0.id_kelas = mg0.id_kelas
                WHERE p0.id_tahun_pelajaran = :tahun_p
            ) src
            JOIN employe e ON e.id_employe = src.id_guru
            JOIN mapel m ON m.id_mapel = src.id_mapel
            CROSS JOIN jenis_perangkat jp
            LEFT JOIN perangkat_mengajar p
                ON p.jenis_perangkat = jp.nama
                AND p.id_employe = e.id_employe
                AND p.id_tahun_pelajaran = :tahun_p2
                AND p.id_mapel_guru IN (
                    SELECT mg2.id_mapel_guru
                    FROM mapel_guru mg2
                    JOIN kelas k2 ON k2.id_kelas = mg2.id_kelas
                    WHERE mg2.id_guru = e.id_employe
                      AND mg2.id_mapel = src.id_mapel
                      AND k2.tingkat = src.tingkat
                )
            WHERE jp.status = '1'
            GROUP BY e.id_employe, e.nama, m.id_mapel, m.nama_mapel, src.tingkat
            ORDER BY e.nama, m.nama_mapel, src.tingkat
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'tahun_mg' => $id_tahun,
            'tahun_p'  => $id_tahun,
            'tahun_p2' => $id_tahun,
        ]);
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
        // Penyebut disamakan dengan rekap: hanya jenis AKTIF (status '1').
        // Subquery pencocokan sengaja TIDAK dikunci tahun: perangkat tahun aktif bisa
        // menempel pada baris mapel_guru tahun lama, tapi pasangan (guru, mapel, tingkat)-nya sama.
        // GROUP BY jp.id supaya satu jenis tidak muncul berkali-kali ketika guru mengunggah
        // jenis itu untuk beberapa kelas pada tingkat yang sama.
        $sql = "
            SELECT jp.nama AS nama_perangkat,
                   CASE WHEN COUNT(p.id) > 0 THEN 'Sudah Upload' ELSE 'Belum Upload' END AS status
            FROM jenis_perangkat jp
            LEFT JOIN perangkat_mengajar p 
                ON p.jenis_perangkat = jp.nama
                AND p.id_employe = :id_guru
                AND p.id_tahun_pelajaran = :id_tahun_pelajaran
                AND p.id_mapel_guru IN (
                    SELECT id_mapel_guru 
                    FROM mapel_guru mg
                    JOIN kelas k ON mg.id_kelas = k.id_kelas
                    WHERE mg.id_guru = :id_guru_mapel AND mg.id_mapel = :id_mapel AND k.tingkat = :tingkat
                )
            WHERE jp.status = '1'
            GROUP BY jp.id, jp.nama
            ORDER BY jp.nama
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_guru' => $id_guru,
            'id_guru_mapel' => $id_guru,
            'id_tahun_pelajaran' => $id_tahun,
            'id_mapel' => $id_mapel,
            'tingkat' => $tingkat
        ]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
