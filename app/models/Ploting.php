<?php
class Ploting
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    // Mengambil data untuk Dropdown (Tahun Ajaran)
    public function getAllTahun()
    {
        return $this->db->query("SELECT * FROM tahun_pelajaran ORDER BY tahun_pelajaran DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mengambil data untuk Dropdown (Kelas)
    public function getAllKelas()
    {
        return $this->db->query("SELECT * FROM kelas ORDER BY tingkat ASC, kelas ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Inti Logic: Ambil siswa berdasarkan plotting (Riwayat)
    // Digunakan untuk menampilkan data di Card Kiri (Sumber) dan Kanan (Hasil)
    public function getSiswaByKelasTahun($id_kelas, $id_tahun)
    {
        $sql = "SELECT s.id_siswa, s.nama_siswa, s.nisn, ps.id_ploting 
                FROM ploting_siswa ps
                JOIN siswa s ON s.id_siswa = ps.id_siswa
                WHERE ps.id_kelas = ? AND ps.id_tahun = ?
                ORDER BY s.nama_siswa ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_kelas, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil siswa yang BELUM punya kelas di tahun ajaran tertentu
    // (termasuk siswa baru yang belum pernah diplot sama sekali)
    // Siswa yang sudah berstatus 'lulus' tidak ikut ditampilkan
    public function getSiswaBelumPunyaKelas($id_tahun)
    {
        $sql = "SELECT s.id_siswa, s.nama_siswa, s.nisn
                FROM siswa s
                WHERE (s.status_siswa IS NULL OR s.status_siswa != 'lulus')
                AND NOT EXISTS (
                    SELECT 1 FROM ploting_siswa ps
                    WHERE ps.id_siswa = s.id_siswa AND ps.id_tahun = ?
                )
                ORDER BY s.nama_siswa ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tandai siswa sebagai LULUS di tahun ajaran tertentu
    // Siswa tidak dimasukkan ke ploting_siswa manapun, cukup update status
    public function luluskanSiswa($id_siswa, $id_tahun)
    {
        $stmt = $this->db->prepare(
            "UPDATE siswa SET status_siswa = 'lulus', tahun_lulus = ? WHERE id_siswa = ?"
        );
        return $stmt->execute([$id_tahun, $id_siswa]);
    }

    // Cek apakah siswa sudah ada di tahun ajaran tujuan (supaya tidak ganda)
    // Perhatikan: Kita cek berdasarkan ID Siswa & Tahun Tujuan (Kelas manapun)
    public function cekSiswaDiTahun($id_siswa, $id_tahun)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM ploting_siswa WHERE id_siswa = ? AND id_tahun = ?");
        $stmt->execute([$id_siswa, $id_tahun]);
        return $stmt->fetchColumn();
    }

    // Simpan Data Ploting Baru
    public function insert($id_siswa, $id_kelas, $id_tahun)
    {
        $stmt = $this->db->prepare("INSERT INTO ploting_siswa (id_siswa, id_kelas, id_tahun) VALUES (?, ?, ?)");
        return $stmt->execute([$id_siswa, $id_kelas, $id_tahun]);
    }
}
