<?php
require_once 'Model.php';

class Rekat extends Model
{
    /**
     * Peta label jenis perangkat LAMA → nama jenis di master saat ini.
     *
     * perangkat_mengajar menyimpan NAMA jenis (bukan id), dan labelnya bergeser antar tahun
     * ajaran (admin mengganti nama jenis). Akibatnya unggahan berlabel lama — mis. siswa/guru
     * mengunggah 'Pengumpulan Soal STS' padahal master kini hanya punya 'Soal STS' — TIDAK
     * PERNAH cocok saat rekap dicocokkan lewat nama, sehingga muncul sebagai "belum upload"
     * di tahun mana pun. Peta ini membuat rekap tetap mengenali label lama TANPA mengubah
     * satu baris data pun.
     *
     * Hanya label yang memang sudah HILANG dari master yang dipetakan. ('Analisis Ulangan
     * Harian/Proyek' sengaja tidak dipetakan: padanannya ambigu antara 'Analisis Penilaian'
     * dan 'Analisis Butir Soal'.)
     */
    const ALIAS_JENIS = [
        'Pengumpulan Soal STS'           => 'Soal STS',
        'Kaldik (APE, AHE)'              => 'Kaldik, APE, AHE',
        'Modul Ajar (RPP)'               => 'RPP / RPM',
        'Capaian Pembelajaran (CP)'      => 'CP, TP, ATP',
        'Tujuan Pembelajaran (TP)'       => 'CP, TP, ATP',
        'Alur Tujuan Pembelajaran (ATP)' => 'CP, TP, ATP',
    ];

    /**
     * Ekspresi SQL yang menormalkan label jenis tersimpan ke nama master saat ini.
     * Dipakai bersama oleh rekap dan detail agar aturannya hanya ada di satu tempat.
     */
    public function ekspresiAliasJenis($kolom = 'p.jenis_perangkat')
    {
        $kapan = '';
        foreach (self::ALIAS_JENIS as $lama => $baru) {
            $kapan .= ' WHEN ' . $this->db->quote($lama) . ' THEN ' . $this->db->quote($baru);
        }
        return "CASE {$kolom}{$kapan} ELSE {$kolom} END";
    }

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
        // Pencocokan jenis memakai ekspresi alias: label lama dianggap sama dengan nama master
        // saat ini, sehingga unggahan berlabel lama tetap terhitung (tanpa mengubah data).
        // Yang dihitung DISTINCT adalah jp.nama (nama kanonik), bukan label mentah, agar dua
        // label berbeda yang menunjuk jenis yang sama tidak dihitung sebagai dua.
        // 4. Nama guru diambil dari src.id_guru (bukan e.id_employe) dengan nama pengganti
        //    'Guru id N': baris mapel_guru bisa merujuk pegawai yang barisnya sudah tidak ada
        //    di `employe`. Dengan JOIN biasa guru itu HILANG dari rekap, dan nama berisi string
        //    kosong tampil sebagai sel kosong — LEFT JOIN + COALESCE menutup dua-duanya.
        // 5. Subquery pencocokan unggahan TIDAK dikunci id_guru (hanya mapel + tingkat), karena
        //    untuk pegawai yatim e.id_employe bernilai NULL sehingga tak ada yang cocok dan
        //    hasilnya 0 unggahan. Tetap aman: baris p wajib milik src.id_guru.
        $aliasJenis = $this->ekspresiAliasJenis('p.jenis_perangkat');
        $sql = "
            SELECT src.id_guru AS id_employe,
                   COALESCE(NULLIF(e.nama, ''), CONCAT('Guru id ', src.id_guru)) AS nama,
                   m.id_mapel, m.nama_mapel, src.tingkat,
                   COUNT(DISTINCT jp.id) AS total_perangkat,
                   COUNT(DISTINCT CASE WHEN p.file IS NOT NULL THEN jp.nama END) AS terupload
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
            LEFT JOIN employe e ON e.id_employe = src.id_guru
            JOIN mapel m ON m.id_mapel = src.id_mapel
            CROSS JOIN jenis_perangkat jp
            LEFT JOIN perangkat_mengajar p
                ON {$aliasJenis} = jp.nama
                AND p.id_employe = src.id_guru
                AND p.id_tahun_pelajaran = :tahun_p2
                AND p.id_mapel_guru IN (
                    SELECT mg2.id_mapel_guru
                    FROM mapel_guru mg2
                    JOIN kelas k2 ON k2.id_kelas = mg2.id_kelas
                    WHERE mg2.id_mapel = src.id_mapel
                      AND k2.tingkat = src.tingkat
                )
            WHERE jp.status = '1'
            GROUP BY src.id_guru, e.nama, m.id_mapel, m.nama_mapel, src.tingkat
            ORDER BY nama, m.nama_mapel, src.tingkat
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
        // Pencocokan jenis juga memakai ekspresi alias, agar label lama tetap dikenali.
        $aliasJenis = $this->ekspresiAliasJenis('p.jenis_perangkat');
        $sql = "
            SELECT jp.nama AS nama_perangkat,
                   CASE WHEN COUNT(p.id) > 0 THEN 'Sudah Upload' ELSE 'Belum Upload' END AS status
            FROM jenis_perangkat jp
            LEFT JOIN perangkat_mengajar p 
                ON {$aliasJenis} = jp.nama
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
