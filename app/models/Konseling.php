<?php
class Konseling
{

    protected $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    private function applyWaliKelasFilter(&$sql, &$params, $id_level, $id_user)
    {
        if ($id_level == 3 && $id_user) {
            // Join ke ploting_siswa jika belum ada
            if (strpos($sql, 'JOIN ploting_siswa ps') === false) {
                $sql .= " JOIN ploting_siswa ps ON k.id_siswa = ps.id_siswa";
            }
            if (strpos($sql, 'JOIN kelas kel') === false) {
                $sql .= " JOIN kelas kel ON ps.id_kelas = kel.id_kelas";
            }

            $sql .= (strpos($sql, 'WHERE') === false) ? " WHERE kel.wali_kelas = ?" : " AND kel.wali_kelas = ?";
            $params[] = $id_user;
        }
    }

    // 🔹 Ambil semua data konseling
    public function all($id_kelas = null)
    {
        $sql = "
        SELECT 
            k.*, 
            s.nama_siswa, 
            kat.nama_kategori, 
            kel.kelas AS nama_kelas,
            (SELECT GROUP_CONCAT(e.nama SEPARATOR ', ') 
             FROM employe e 
             WHERE FIND_IN_SET(e.id_employe, k.id_employee)) AS nama_petugas
        FROM konseling k
        JOIN siswa s ON s.id_siswa = k.id_siswa
        JOIN ploting_siswa ps ON s.id_siswa = ps.id_siswa
        JOIN kelas kel ON ps.id_kelas = kel.id_kelas
        JOIN kategori_permasalahan kat ON kat.id_kategori = k.id_kategori
    ";

        $params = [];

        if ($id_kelas !== null) {
            $sql .= " WHERE ps.id_kelas = :id_kelas";
            $params[':id_kelas'] = $id_kelas;
        }

        // 🌟 KUNCI PERBAIKAN: Tambahkan GROUP BY sebelum ORDER BY
        $sql .= " GROUP BY k.id_konseling";
        $sql .= " ORDER BY k.id_konseling DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$row) {
            $row['nama_petugas'] = $row['nama_petugas'] ? explode(', ', $row['nama_petugas']) : [];
        }

        return $data;
    }

    // Method ini sudah ada di model Anda
    public function getKelasWalikelas($id_walikelas)
    {
        // Di model Anda, kolomnya adalah 'id_walikelas'. Sesuaikan jika di DB namanya 'wali_kelas'
        $stmt = $this->db->prepare("SELECT * FROM kelas WHERE wali_kelas = ? ORDER BY kelas ASC");
        $stmt->execute([$id_walikelas]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * ✅ METHOD BARU: Ambil semua siswa dari kelas yang diampu oleh wali kelas.
     */
    public function getSiswaByWaliKelas($id_walikelas)
    {
        $stmt = $this->db->prepare("
            SELECT s.id_siswa, s.nama_siswa 
            FROM siswa s
            JOIN ploting_siswa ps ON s.id_siswa = ps.id_siswa
            JOIN kelas k ON ps.id_kelas = k.id_kelas
            WHERE k.wali_kelas = ?
            ORDER BY s.nama_siswa ASC
        ");
        $stmt->execute([$id_walikelas]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $sql = "
            SELECT k.*, s.nama_siswa, ps.id_kelas, kel.kelas, kat.nama_kategori 
            FROM konseling k 
            JOIN siswa s ON s.id_siswa = k.id_siswa 
            JOIN ploting_siswa ps ON s.id_siswa = ps.id_siswa
            JOIN kelas kel ON ps.id_kelas = kel.id_kelas 
            LEFT JOIN kategori_permasalahan kat ON kat.id_kategori = k.id_kategori 
            WHERE k.id_konseling = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data && !empty($data['id_employee'])) {
            $ids = explode(',', $data['id_employee']);
            $placeholders = rtrim(str_repeat('?,', count($ids)), ',');
            $petugasStmt = $this->db->prepare("SELECT nama FROM employe WHERE id_employe IN ($placeholders)");
            $petugasStmt->execute($ids);
            $data['nama_petugas'] = $petugasStmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $data['nama_petugas'] = [];
        }

        return $data;
    }

    // 🔹 Status & Tindak Lanjut
    public function getStatus($id)
    {
        $stmt = $this->db->prepare("SELECT status FROM konseling WHERE id_konseling = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getTindakLanjut($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM tindak_lanjut_konseling WHERE id_konseling = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tandaiSelesai($id)
    {
        $stmt = $this->db->prepare("UPDATE konseling SET status = 'Selesai' WHERE id_konseling = ?");
        return $stmt->execute([$id]);
    }

    public function simpan($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO konseling 
                (id_kelas, id_siswa, id_kategori, permasalahan, tanggal_masalah, bukti_fisik, dokumen, id_employee, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute($data);
    }

    public function simpanTindakLanjut($id_konseling, $catatan, $tanggal, $bukti)
    {
        $stmt = $this->db->prepare("
            INSERT INTO tindak_lanjut_konseling (id_konseling, catatan, tanggal, bukti) 
            VALUES (?, ?, ?, ?)
        ");
        return $stmt->execute([$id_konseling, $catatan, $tanggal, $bukti]);
    }

    // 🔹 Data dengan Petugas
    public function getAllWithPetugas()
    {
        $stmt = $this->db->query("
            SELECT k.*, s.nama_siswa, kat.nama_kategori
            FROM konseling k
            JOIN siswa s ON s.id_siswa = k.id_siswa
            JOIN kategori_permasalahan kat ON kat.id_kategori = k.id_kategori
            ORDER BY k.id_konseling DESC
        ");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($data as &$k) {
            $ids = explode(',', $k['id_employee']);
            $placeholders = rtrim(str_repeat('?,', count($ids)), ',');
            $petugasStmt = $this->db->prepare("SELECT nama FROM employe WHERE id_employe IN ($placeholders)");
            $petugasStmt->execute($ids);
            $k['nama_petugas'] = $petugasStmt->fetchAll(PDO::FETCH_COLUMN);
        }

        return $data;
    }

    // 🔹 Tahun Pelajaran
    public function getAktifTahunPelajaran()
    {
        $stmt = $this->db->query("SELECT * FROM tahun_pelajaran WHERE status = 'aktif' LIMIT 1");
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAllTahunPelajaran()
    {
        return $this->db->query("SELECT * FROM tahun_pelajaran ORDER BY id_tahun_pelajaran DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRangeByTahunPelajaran($tp)
    {
        $tahun = explode('/', $tp['tahun_pelajaran']);
        if (strtolower($tp['semester']) === 'ganjil') {
            return ['start' => $tahun[0] . "-07-01", 'end' => $tahun[0] . "-12-31"];
        } else {
            return ['start' => $tahun[1] . "-01-01", 'end' => $tahun[1] . "-06-30"];
        }
    }

    // 🔹 REKAPITULASI (Update JOIN ke ploting_siswa)
    public function rekapByKategori($start, $end, $id_level = null, $id_user = null)
    {
        $sql = "
            SELECT kat.nama_kategori, COUNT(*) as total
            FROM konseling k
            JOIN kategori_permasalahan kat ON kat.id_kategori = k.id_kategori
        ";
        $params = [];

        if ($id_level == 3 && $id_user) {
            $sql .= " JOIN ploting_siswa ps ON k.id_siswa = ps.id_siswa 
                      JOIN kelas kel ON ps.id_kelas = kel.id_kelas 
                      WHERE kel.wali_kelas = ? AND k.tanggal_masalah BETWEEN ? AND ?";
            $params = [$id_user, $start, $end];
        } else {
            $sql .= " WHERE k.tanggal_masalah BETWEEN ? AND ?";
            $params = [$start, $end];
        }

        $sql .= " GROUP BY kat.nama_kategori";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function rekapByKelas($start, $end, $id_level = null, $id_user = null)
    {
        $sql = "
            SELECT kel.kelas, COUNT(*) as total
            FROM konseling k
            JOIN ploting_siswa ps ON k.id_siswa = ps.id_siswa
            JOIN kelas kel ON ps.id_kelas = kel.id_kelas
        ";
        $params = [];

        if ($id_level == 3 && $id_user) {
            $sql .= " WHERE kel.wali_kelas = ? AND k.tanggal_masalah BETWEEN ? AND ?";
            $params = [$id_user, $start, $end];
        } else {
            $sql .= " WHERE k.tanggal_masalah BETWEEN ? AND ?";
            $params = [$start, $end];
        }

        $sql .= " GROUP BY kel.kelas";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function rekapByBulan($start, $end, $id_level = null, $id_user = null)
    {
        $sql = "
            SELECT DATE_FORMAT(k.tanggal_masalah, '%Y-%m') as bulan, COUNT(*) as total
            FROM konseling k
        ";
        $params = [];

        // Tambahkan filter untuk Wali Kelas
        if ($id_level == 3 && $id_user) {
            $sql .= " JOIN siswa s ON k.id_siswa = s.id_siswa JOIN kelas kel ON s.id_kelas = kel.id_kelas WHERE kel.wali_kelas = ? AND k.tanggal_masalah BETWEEN ? AND ?";
            $params = [$id_user, $start, $end];
        } else {
            $sql .= " WHERE k.tanggal_masalah BETWEEN ? AND ?";
            $params = [$start, $end];
        }

        $sql .= " GROUP BY DATE_FORMAT(k.tanggal_masalah, '%Y-%m') ORDER BY bulan ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Referensi Dropdown
    public function getKelasList()
    {
        return $this->db->query("SELECT * FROM kelas ORDER BY kelas ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getKategoriList()
    {
        return $this->db->query("SELECT * FROM kategori_permasalahan ORDER BY nama_kategori ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getSiswaByKelas($id_kelas)
    {
        $stmt = $this->db->prepare("
            SELECT s.id_siswa, s.nama_siswa 
            FROM siswa s
            JOIN ploting_siswa ps ON s.id_siswa = ps.id_siswa
            WHERE ps.id_kelas = ? 
            ORDER BY s.nama_siswa ASC
        ");
        $stmt->execute([$id_kelas]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Filter Data Konseling
    public function getByTanggal($start, $end, $id_level = null, $id_user = null)
    {
        $sql = "
            SELECT k.*, s.nama_siswa, kel.kelas, kat.nama_kategori
            FROM konseling k
            JOIN siswa s ON s.id_siswa = k.id_siswa
            JOIN kelas kel ON s.id_kelas = kel.id_kelas
            LEFT JOIN kategori_permasalahan kat ON kat.id_kategori = k.id_kategori
            WHERE k.tanggal_masalah BETWEEN ? AND ?
        ";
        $params = [$start, $end];
        $this->applyWaliKelasFilter($sql, $params, $id_level, $id_user);
        $sql .= " ORDER BY k.tanggal_masalah ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBySemester($id_tp, $id_level = null, $id_user = null)
    {
        // ... (logika untuk mendapatkan $start dan $end dari $id_tp tetap sama) ...
        $tp = $this->db->prepare("SELECT * FROM tahun_pelajaran WHERE id_tahun_pelajaran = ?");
        $tp->execute([$id_tp]);
        $row = $tp->fetch(PDO::FETCH_ASSOC);
        if (!$row) return [];
        $tahun = explode('/', $row['tahun_pelajaran']);
        if (strtolower($row['semester']) === 'ganjil') {
            $start = $tahun[0] . "-07-01";
            $end = $tahun[0] . "-12-31";
        } else {
            $start = $tahun[1] . "-01-01";
            $end = $tahun[1] . "-06-30";
        }

        return $this->getByTanggal($start, $end, $id_level, $id_user);
    }

    public function getByBulan($tahun, $bulan, $id_level = null, $id_user = null)
    {
        $start = "$tahun-$bulan-01";
        $end = date("Y-m-t", strtotime($start));
        return $this->getByTanggal($start, $end, $id_level, $id_user);
    }

    private function filterByMonth($tp, $bulan)
    {
        $tahun = explode('/', $tp['tahun_pelajaran']);
        $tahunPakai = strtolower($tp['semester']) === 'ganjil' ? $tahun[0] : $tahun[1];

        $start = $tahunPakai . '-' . str_pad($bulan, 2, '0', STR_PAD_LEFT) . "-01";
        $end   = date("Y-m-t", strtotime($start));

        return $this->getByTanggal($start, $end);
    }

    public function getByKategori($id_kategori, $id_level = null, $id_user = null)
    {
        $sql = "
            SELECT k.*, s.nama_siswa, kel.kelas, kat.nama_kategori
            FROM konseling k
            JOIN siswa s ON s.id_siswa = s.id_siswa
            JOIN kelas kel ON s.id_kelas = kel.id_kelas
            LEFT JOIN kategori_permasalahan kat ON kat.id_kategori = k.id_kategori
            WHERE k.id_kategori = ?
        ";
        $params = [$id_kategori];
        $this->applyWaliKelasFilter($sql, $params, $id_level, $id_user);
        $sql .= " ORDER BY k.tanggal_masalah ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 🔹 Filter Berdasarkan Kelas
    public function getByKelas($id_kelas)
    {
        $stmt = $this->db->prepare("
            SELECT k.*, s.nama_siswa, kel.kelas, kat.nama_kategori
            FROM konseling k
            JOIN siswa s ON s.id_siswa = k.id_siswa
            JOIN ploting_siswa ps ON s.id_siswa = ps.id_siswa
            JOIN kelas kel ON ps.id_kelas = kel.id_kelas
            LEFT JOIN kategori_permasalahan kat ON kat.id_kategori = k.id_kategori
            WHERE ps.id_kelas = ?
            ORDER BY k.tanggal_masalah ASC
        ");
        $stmt->execute([$id_kelas]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getBySiswa($id_siswa)
    {
        $stmt = $this->db->prepare("
            SELECT k.*, s.nama_siswa, kel.kelas, kat.nama_kategori
            FROM konseling k
            JOIN siswa s ON s.id_siswa = k.id_siswa
            JOIN kelas kel ON s.id_kelas = kel.id_kelas
            LEFT JOIN kategori_permasalahan kat ON kat.id_kategori = k.id_kategori
            WHERE k.id_siswa = ?
            ORDER BY k.tanggal_masalah ASC
        ");
        $stmt->execute([$id_siswa]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function allKategori()
    {
        $stmt = $this->db->query("SELECT * FROM kategori_permasalahan ORDER BY nama_kategori ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
