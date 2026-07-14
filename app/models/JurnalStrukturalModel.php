<?php

class JurnalStrukturalModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db; // PDO
    }

    /**
     * =========================
     * SIMPAN JURNAL UTAMA
     * =========================
     */
    public function insertJurnal($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO jurnal_struktural
            (id_employe, id_tahun_pelajaran, tanggal, catatan_akhir, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $data['id_employe'],
            $data['id_tahun_pelajaran'],
            $data['tanggal'],
            $data['catatan_akhir']
        ]);

        return $this->db->lastInsertId();
    }

    /**
     * =========================
     * SIMPAN PROGRAM KERJA JURNAL
     * =========================
     */
    public function insertProgramKerja($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO jurnal_program_kerja
            (id_jurnal, id_program, deskripsi_realisasi)
            VALUES (?, ?, ?)
        ");

        return $stmt->execute([
            $data['id_jurnal'],
            $data['id_program'],
            $data['deskripsi_realisasi']
        ]);
    }

    public function getHistoryByUserAndTahun($id_user, $id_tahun)
    {
        $sql = "
        SELECT 
            js.id_jurnal,
            js.tanggal,
            js.catatan_akhir,

           GROUP_CONCAT(
    CONCAT(
        '• <strong>',
        pk.nama_program,
        '</strong> <br> ',
        '<i>', COALESCE(jpk.deskripsi_realisasi, '-'), '</i>'
    )
    SEPARATOR '<hr>'
) AS ringkasan_program


        FROM jurnal_struktural js
        LEFT JOIN jurnal_program_kerja jpk 
            ON jpk.id_jurnal = js.id_jurnal
        LEFT JOIN program_kerja pk 
            ON pk.id_program = jpk.id_program

        WHERE js.id_employe = ?
          AND js.id_tahun_pelajaran = ?

        GROUP BY js.id_jurnal
        ORDER BY js.tanggal DESC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_user, $id_tahun]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllProgramKerja()
    {
        return $this->db->query("
        SELECT id_program, nama_program
        FROM program_kerja
        ORDER BY nama_program
    ")->fetchAll(PDO::FETCH_ASSOC);
    }


    public function isOwnedByUser($id_jurnal, $id_user)
    {
        $stmt = $this->db->prepare("
        SELECT COUNT(*) FROM jurnal_struktural
        WHERE id_jurnal = ? AND id_employe = ?
    ");

        $stmt->execute([$id_jurnal, $id_user]);
        return $stmt->fetchColumn() > 0;
    }

    public function getProgramKerjaByJurnal($id_jurnal)
    {
        $stmt = $this->db->prepare("
        SELECT jpk.*, pk.nama_program
        FROM jurnal_program_kerja jpk
        JOIN program_kerja pk ON pk.id_program = jpk.id_program
        WHERE jpk.id_jurnal = ?
    ");
        $stmt->execute([$id_jurnal]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateJurnal($data)
    {
        $stmt = $this->db->prepare("
        UPDATE jurnal_struktural
        SET catatan_akhir = ?
        WHERE id_jurnal = ?
    ");
        return $stmt->execute([
            $data['catatan_akhir'],
            $data['id_jurnal']
        ]);
    }

    public function deleteProgramKerjaByJurnal($id_jurnal)
    {
        $stmt = $this->db->prepare("
        DELETE FROM jurnal_program_kerja
        WHERE id_jurnal = ?
    ");
        return $stmt->execute([$id_jurnal]);
    }



    public function getHistoryAdminHarian($idTahunPelajaran, $tanggal)
    {
        $sql = "
        SELECT 
            js.id_jurnal,
            js.tanggal,
            e.nama AS nama_pegawai,
            js.catatan_akhir,

            GROUP_CONCAT(
                CONCAT(
                    '• <strong>', pk.nama_program, '</strong><br>',
                    '<i>', jpk.deskripsi_realisasi, '</i>'
                )
                SEPARATOR '<hr>'
            ) AS ringkasan_program

        FROM jurnal_struktural js
        JOIN employe e 
            ON e.id_employe = js.id_employe
        LEFT JOIN jurnal_program_kerja jpk 
            ON jpk.id_jurnal = js.id_jurnal
        LEFT JOIN program_kerja pk 
            ON pk.id_program = jpk.id_program

        WHERE js.id_tahun_pelajaran = ?
          AND js.tanggal = ?

        GROUP BY js.id_jurnal
        ORDER BY e.nama ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$idTahunPelajaran, $tanggal]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id)
    {
        $stmt = $this->db->prepare("
        SELECT *
        FROM jurnal_struktural
        WHERE id_jurnal = ?
    ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
