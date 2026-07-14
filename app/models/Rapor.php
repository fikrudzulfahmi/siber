<?php
class Rapor
{
    private $db;
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    // Mengambil setting rapor yang sedang diaktifkan admin
    public function getActiveSetting()
    {
        return $this->db->query("SELECT rs.*, tp.tahun_pelajaran, tp.semester 
                                FROM rapor_setting rs 
                                JOIN tahun_pelajaran tp ON rs.id_tahun_pelajaran = tp.id_tahun_pelajaran 
                                WHERE rs.is_active = 1")->fetch(PDO::FETCH_ASSOC);
    }

    // Mengambil data rapor siswa (absensi, catatan, kenaikan)
    public function getRaporSiswa($id_rapor, $id_siswa)
    {
        $stmt = $this->db->prepare("SELECT * FROM rapor_siswa WHERE id_rapor = ? AND id_siswa = ?");
        $stmt->execute([$id_rapor, $id_siswa]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Mengambil rekap nilai akhir per mapel untuk satu siswa
    public function getNilaiAkhirSiswa($id_siswa, $id_tahun, $jenis_rapor, $id_kelas)
    {
        $kolomNilai = ($jenis_rapor == 'tengah') ? 'n.sts' : 'n.nilai_raport';

        $sql = "SELECT 
                m.nama_mapel, 
                IFNULL($kolomNilai, 0) as nilai_final
            FROM mapel_guru mg
            JOIN mapel m ON mg.id_mapel = m.id_mapel
            LEFT JOIN kategori_nilai k ON k.id_mapel_guru = mg.id_mapel_guru
            LEFT JOIN nilai n ON n.id_kategori = k.id_kategori AND n.id_siswa = ?
            WHERE mg.id_kelas = ? 
            AND mg.id_tahun_pelajaran = ?
            AND m.nama_mapel != 'BIMBINGAN KONSELING'
            GROUP BY m.id_mapel
            ORDER BY m.id_mapel ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_siswa, $id_kelas, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    // Ambil data Ekstrakurikuler berdasarkan id_rapor_siswa
    public function getEkstraSiswa($id_rapor_siswa)
    {
        $sql = "SELECT * FROM rapor_ekstra WHERE id_rapor_siswa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_rapor_siswa]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil data Prestasi berdasarkan id_rapor_siswa
    public function getPrestasiSiswa($id_rapor_siswa)
    {
        $sql = "SELECT * FROM rapor_prestasi WHERE id_rapor_siswa = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_rapor_siswa]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function initRaporSiswa($id_rapor, $id_siswa)
    {
        $sql = "INSERT INTO rapor_siswa (id_rapor, id_siswa, sakit, izin, alfa, catatan_walikelas, status_kenaikan) 
            VALUES (?, ?, 0, 0, 0, '', '')";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id_rapor, $id_siswa]);
    }

    public function getPeringkatKelas($id_kelas, $id_tahun, $jenis_rapor)
    {
        $kolomNilai = ($jenis_rapor == 'tengah') ? 'n.sts' : 'n.nilai_raport';

        // Menggunakan subquery agar pembacaan nilai per mapel di-grouping terlebih dahulu
        $sql = "SELECT 
                tabel_nilai.id_siswa, 
                tabel_nilai.nama_siswa,
                tabel_nilai.nisn,
                SUM(tabel_nilai.nilai_final) as total_nilai,
                ROW_NUMBER() OVER (
                    ORDER BY SUM(tabel_nilai.nilai_final) DESC, tabel_nilai.nama_siswa ASC
                ) as peringkat
            FROM (
                SELECT 
                    s.id_siswa, 
                    s.nama_siswa,
                    s.nisn,
                    m.id_mapel,
                    MAX(IFNULL($kolomNilai, 0)) as nilai_final
                FROM siswa s
                JOIN ploting_siswa ps ON s.id_siswa = ps.id_siswa
                JOIN mapel_guru mg ON mg.id_kelas = ps.id_kelas
                JOIN mapel m ON mg.id_mapel = m.id_mapel
                LEFT JOIN kategori_nilai kn ON kn.id_mapel_guru = mg.id_mapel_guru
                LEFT JOIN nilai n ON n.id_kategori = kn.id_kategori AND n.id_siswa = s.id_siswa
                WHERE ps.id_kelas = ? 
                  AND mg.id_tahun_pelajaran = ?
                  AND m.nama_mapel != 'BIMBINGAN KONSELING'
                GROUP BY s.id_siswa, m.id_mapel
            ) as tabel_nilai
            GROUP BY tabel_nilai.id_siswa
            ORDER BY total_nilai DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_kelas, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateRaporSiswa($id_rapor, $data)
    {
        // $data berisi array dari form: sakit, izin, alfa, catatan, status_kenaikan
        foreach ($data['sakit'] as $id_siswa => $val) {
            $sql = "INSERT INTO rapor_siswa (id_rapor, id_siswa, sakit, izin, alfa, catatan_walikelas, status_kenaikan) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                sakit = VALUES(sakit), 
                izin = VALUES(izin), 
                alfa = VALUES(alfa), 
                catatan_walikelas = VALUES(catatan_walikelas),
                status_kenaikan = VALUES(status_kenaikan)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $id_rapor,
                $id_siswa,
                $data['sakit'][$id_siswa],
                $data['izin'][$id_siswa],
                $data['alfa'][$id_siswa],
                $data['catatan_walikelas'][$id_siswa],
                $data['status_kenaikan'][$id_siswa] ?? null
            ]);
        }
    }
    // Tambahkan/Update fungsi ini di Rapor.php (Model)
    public function updateRaporSiswaDirect($id_rapor_siswa, $data)
    {
        $sql = "UPDATE rapor_siswa SET 
            sakit = ?, 
            izin = ?, 
            alfa = ?, 
            catatan_walikelas = ? 
            WHERE id_rapor_siswa = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['sakit'],
            $data['izin'],
            $data['alfa'],
            $data['catatan'], // Ini akan masuk ke kolom catatan_walikelas
            $id_rapor_siswa
        ]);
    }
    public function getAllSettings()
    {
        return $this->db->query("
        SELECT rs.*, tp.tahun_pelajaran, tp.semester 
        FROM rapor_setting rs
        JOIN tahun_pelajaran tp ON rs.id_tahun_pelajaran = tp.id_tahun_pelajaran
        ORDER BY rs.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createSetting($data)
    {
        $stmt = $this->db->prepare("
        INSERT INTO rapor_setting (id_tahun_pelajaran, jenis_rapor, tgl_pembagian, is_kenaikan, is_active, is_locked) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
        return $stmt->execute([
            $data['id_tahun_pelajaran'],
            $data['jenis_rapor'],
            $data['tgl_pembagian'],
            $data['is_kenaikan'],
            0, // Default tidak aktif saat dibuat
            0  // Default tidak terkunci
        ]);
    }

    // Tambahkan di dalam Model Anda

    public function getSettingById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM rapor_setting WHERE id_rapor = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateSetting($id, $data)
    {
        $stmt = $this->db->prepare("
        UPDATE rapor_setting 
        SET id_tahun_pelajaran = ?, 
            jenis_rapor = ?, 
            tgl_pembagian = ?, 
            is_kenaikan = ?
        WHERE id_rapor = ?
    ");
        return $stmt->execute([
            $data['id_tahun_pelajaran'],
            $data['jenis_rapor'],
            $data['tgl_pembagian'],
            $data['is_kenaikan'],
            $id
        ]);
    }


    public function activateSetting($id_rapor)
    {
        try {
            $this->db->beginTransaction();
            // Matikan semua yang aktif
            $this->db->query("UPDATE rapor_setting SET is_active = 0");
            // Aktifkan satu yang dipilih
            $stmt = $this->db->prepare("UPDATE rapor_setting SET is_active = 1 WHERE id_rapor = ?");
            $stmt->execute([$id_rapor]);
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function toggleLock($id_rapor, $status)
    {
        $stmt = $this->db->prepare("UPDATE rapor_setting SET is_locked = ? WHERE id_rapor = ?");
        return $stmt->execute([$status, $id_rapor]);
    }

    // Tambahkan di dalam class Rapor
    public function getProgresNilaiSiswa($id_kelas, $id_tahun, $jenis_rapor)
    {
        $kolomNilai = ($jenis_rapor == 'tengah') ? 'n.sts' : 'n.nilai_raport';

        $sql = "
    SELECT 
        s.id_siswa,
        s.nama_siswa,
        (SELECT COUNT(DISTINCT mg2.id_mapel) 
         FROM mapel_guru mg2 
         JOIN mapel m2 ON mg2.id_mapel = m2.id_mapel
         WHERE mg2.id_kelas = ? AND mg2.id_tahun_pelajaran = ? AND m2.nama_mapel != 'BIMBINGAN KONSELING') as total_mapel,
        COUNT(DISTINCT CASE WHEN $kolomNilai IS NOT NULL AND $kolomNilai > 0 AND m.nama_mapel != 'BIMBINGAN KONSELING' THEN mg.id_mapel END) as mapel_terisi
    FROM ploting_siswa ps
    JOIN siswa s ON ps.id_siswa = s.id_siswa
    LEFT JOIN mapel_guru mg ON mg.id_kelas = ps.id_kelas AND mg.id_tahun_pelajaran = ps.id_tahun
    LEFT JOIN kategori_nilai kn ON kn.id_mapel_guru = mg.id_mapel_guru
    LEFT JOIN nilai n ON n.id_kategori = kn.id_kategori AND n.id_siswa = s.id_siswa
    LEFT JOIN mapel m ON mg.id_mapel = m.id_mapel
    WHERE ps.id_kelas = ? AND ps.id_tahun = ? 
    GROUP BY s.id_siswa
    ORDER BY s.nama_siswa ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_kelas, $id_tahun, $id_kelas, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetailProgresSiswa($id_siswa, $id_kelas, $id_tahun, $jenis_rapor)
    {
        $kolomNilai = ($jenis_rapor == 'tengah') ? 'n.sts' : 'n.nilai_raport';

        $sql = "
    SELECT 
        m.nama_mapel,
        e.nama as nama_guru,
        IFNULL($kolomNilai, 0) as nilai
    FROM mapel_guru mg
    JOIN mapel m ON mg.id_mapel = m.id_mapel
    JOIN employe e ON mg.id_guru = e.id_employe
    LEFT JOIN kategori_nilai kn ON kn.id_mapel_guru = mg.id_mapel_guru
    LEFT JOIN nilai n ON n.id_kategori = kn.id_kategori AND n.id_siswa = ?
    WHERE mg.id_kelas = ? AND mg.id_tahun_pelajaran = ? AND m.nama_mapel != 'BIMBINGAN KONSELING'
    GROUP BY m.id_mapel, e.id_employe
    ORDER BY m.nama_mapel ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_siswa, $id_kelas, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPrestasiKolektifSiswa($id_siswa, $id_tahun)
    {
        // Mengambil data dari tabel prestasi_kolektif yang terhubung via peserta_prestasi
        // Kita format outputnya agar sesuai dengan struktur tabel rapor_prestasi (jenis_prestasi & keterangan)
        $sql = "SELECT 
                pk.jenis_prestasi, 
                CONCAT(pk.juara, ' ', pk.nama_kegiatan, ' tingkat ', pk.tingkat) as keterangan
            FROM prestasi_peserta pp
            JOIN prestasi_kegiatan pk ON pp.id_prestasi_kegiatan = pk.id_prestasi_kegiatan
            JOIN ploting_siswa ps ON pp.id_plotting_siswa = ps.id_ploting
            WHERE ps.id_siswa = ? AND ps.id_tahun = ?
            ORDER BY pk.tgl_kegiatan DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_siswa, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getEkstraOtomatisSiswa($id_siswa, $id_rapor)
    {
        // Mengacu pada gambar 2: id_nilai_ekstra, id_rapor, id_ekstra, id_siswa, nilai, keterangan
        $sql = "SELECT 
                e.nama_ekstra as nama_kegiatan, 
                ne.nilai, 
                ne.keterangan
            FROM ekstra_nilai ne
            JOIN ekstra e ON ne.id_ekstra = e.id_ekstra
            WHERE ne.id_siswa = ? AND ne.id_rapor = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_siswa, $id_rapor]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // REKAP KEHADIRAN OTOMATIS DARI JURNAL UNTUK RAPOR
    // =========================================================================
    public function getRekapKehadiranSemester($id_siswa, $id_tahun)
    {
        $sql = "SELECT
            SUM(CASE WHEN final_status = 'S' THEN 1 ELSE 0 END) as total_sakit,
            SUM(CASE WHEN final_status = 'I' THEN 1 ELSE 0 END) as total_izin,
            SUM(CASE WHEN final_status = 'A' THEN 1 ELSE 0 END) as total_alfa
        FROM (
            SELECT
                jk.status as final_status,
                DATE(j.created_at) as tgl,
                ROW_NUMBER() OVER(
                    PARTITION BY DATE(j.created_at)
                    ORDER BY CASE jk.status WHEN 'A' THEN 1 WHEN 'S' THEN 2 WHEN 'I' THEN 3 ELSE 4 END
                ) as rn
            FROM jurnal_kehadiran jk
            JOIN jurnal j ON jk.id_jurnal = j.id_jurnal
            JOIN ploting_siswa ps ON jk.id_siswa = ps.id_siswa AND j.id_kelas = ps.id_kelas
            WHERE jk.id_siswa = ?
              AND ps.id_tahun = ?
              AND jk.status IN ('S', 'I', 'A')
        ) AS ranked
        WHERE rn = 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_siswa, $id_tahun]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
