<?php
class PrestasiModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // Ambil daftar tahun untuk filter di index
    public function getAllTahunPelajaran()
    {
        return $this->db->query("SELECT * FROM tahun_pelajaran ORDER BY tahun_pelajaran DESC, semester DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil data untuk halaman INDEX (Menampilkan prestasi + daftar nama siswa)
    public function getAllPrestasiWithSiswa($id_tahun)
    {
        $query = "SELECT pk.*, GROUP_CONCAT(s.nama_siswa SEPARATOR ', ') as nama_peserta
                  FROM prestasi_kegiatan pk
                  LEFT JOIN prestasi_peserta pp ON pk.id_prestasi_kegiatan = pp.id_prestasi_kegiatan
                  LEFT JOIN ploting_siswa ps ON pp.id_plotting_siswa = ps.id_ploting
                  LEFT JOIN siswa s ON ps.id_siswa = s.id_siswa
                  WHERE pk.id_tahun_pelajaran = ?
                  GROUP BY pk.id_prestasi_kegiatan
                  ORDER BY pk.tgl_kegiatan DESC";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil detail satu prestasi untuk EDIT/HAPUS
    public function getPrestasiById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM prestasi_kegiatan WHERE id_prestasi_kegiatan = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Ambil daftar siswa untuk CHECKBOX di form tambah/edit
    public function getSiswaByPlotting($id_tahun)
    {
        $query = "SELECT ps.id_ploting, s.nama_siswa, k.kelas 
                  FROM ploting_siswa ps
                  JOIN siswa s ON ps.id_siswa = s.id_siswa
                  JOIN kelas k ON ps.id_kelas = k.id_kelas
                  WHERE ps.id_tahun = ? 
                  ORDER BY k.kelas, s.nama_siswa";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil peserta yang sudah terdaftar (untuk EDIT)
    public function getPesertaByKegiatan($id_kegiatan)
    {
        $stmt = $this->db->prepare("SELECT id_plotting_siswa FROM prestasi_peserta WHERE id_prestasi_kegiatan = ?");
        $stmt->execute([$id_kegiatan]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Simpan Kolektif
    public function insertPrestasiKolektif($dataKegiatan, $dataPeserta)
    {
        try {
            $this->db->beginTransaction();
            $sqlKegiatan = "INSERT INTO prestasi_kegiatan 
                            (nama_kegiatan, jenis_prestasi, tingkat, juara, penyelenggara, tgl_kegiatan, file_sertifikat, id_tahun_pelajaran, keterangan_tambahan) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sqlKegiatan);
            // Pastikan jumlah parameter (9) cocok dengan input di controller
            $stmt->execute($dataKegiatan);
            $id_kegiatan = $this->db->lastInsertId();

            $sqlPeserta = "INSERT INTO prestasi_peserta (id_prestasi_kegiatan, id_plotting_siswa) VALUES (?, ?)";
            $stmtPeserta = $this->db->prepare($sqlPeserta);
            foreach ($dataPeserta as $id_plot) {
                $stmtPeserta->execute([$id_kegiatan, $id_plot]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updatePrestasiKolektif($dataKegiatan, $dataPeserta)
    {
        try {
            $this->db->beginTransaction();
            $sql = "UPDATE prestasi_kegiatan SET 
                    nama_kegiatan = ?, jenis_prestasi = ?, tingkat = ?, 
                    juara = ?, penyelenggara = ?, tgl_kegiatan = ?, 
                    file_sertifikat = ?, keterangan_tambahan = ? 
                    WHERE id_prestasi_kegiatan = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array_values($dataKegiatan));

            $this->db->prepare("DELETE FROM prestasi_peserta WHERE id_prestasi_kegiatan = ?")
                ->execute([$dataKegiatan['id_kegiatan']]);

            $sqlInsert = "INSERT INTO prestasi_peserta (id_prestasi_kegiatan, id_plotting_siswa) VALUES (?, ?)";
            $stmtInsert = $this->db->prepare($sqlInsert);
            foreach ($dataPeserta as $id_plot) {
                $stmtInsert->execute([$dataKegiatan['id_kegiatan'], $id_plot]);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deletePrestasi($id)
    {
        // Karena tidak ada Foreign Key fisik, kita hapus manual peserta dulu baru kegiatan
        try {
            $this->db->beginTransaction();
            $this->db->prepare("DELETE FROM prestasi_peserta WHERE id_prestasi_kegiatan = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM prestasi_kegiatan WHERE id_prestasi_kegiatan = ?")->execute([$id]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
