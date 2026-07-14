<?php
class Kehadiran
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getKelasByWali($id_guru)
    {
        // Sekarang mengambil id_kelas dan nama kelasnya (di-alias sebagai 'kelas')
        $stmt = $this->db->prepare("SELECT id_kelas, kelas FROM kelas WHERE wali_kelas = ? LIMIT 1");
        $stmt->execute([$id_guru]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Mengembalikan array ['id_kelas' => 123, 'kelas' => 'X IPA 1']
    }

    public function getAllKelas()
    {
        $stmt = $this->db->query("SELECT id_kelas, kelas FROM kelas ORDER BY kelas ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getTahunAktif()
    {
        $stmt = $this->db->query("
        SELECT id_tahun_pelajaran 
        FROM tahun_pelajaran 
        WHERE status = 'Aktif'
        LIMIT 1
    ");
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Method utama untuk mengambil data rekap kehadiran.
     * Sudah mendukung filter per kelas.
     */
    public function getMasterRekapHarian($tanggal, $id_kelas = null, $id_tahun = null)
    {
        if (!$id_tahun) {
            throw new Exception("ID Tahun wajib diisi");
        }

        // 1. Tentukan hari
        $namaHari = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu'
        ];

        $hari = $namaHari[date('l', strtotime($tanggal))];
        $hari_lowercase = strtolower($hari);

        /*
    ===============================
    2. QUERY JADWAL (SUDAH FIX)
    ===============================
    */
        $sqlJadwal = "
        SELECT
            k.id_kelas, 
            k.kelas AS nama_kelas,
            jp.jam_mulai, 
            jp.jam_selesai,
            m.nama_mapel, 
            e.nama AS nama_guru,
            CASE WHEN j.id_jurnal IS NOT NULL THEN 1 ELSE 0 END AS sudah_isi_jurnal
        FROM jadwal_pelajaran jp
        JOIN mapel_guru mg 
            ON jp.id_mapel_guru = mg.id_mapel_guru 
            AND mg.id_tahun_pelajaran = :id_tahun
        JOIN kelas k ON mg.id_kelas = k.id_kelas
        LEFT JOIN mapel m ON mg.id_mapel = m.id_mapel
        LEFT JOIN employe e ON mg.id_guru = e.id_employe
        LEFT JOIN jurnal j 
            ON jp.id_mapel_guru = j.id_mapel_guru 
            AND DATE(j.created_at) = :tanggal
        WHERE LOWER(jp.hari) = :hari
    ";

        $params = [
            ':hari'      => $hari_lowercase,
            ':tanggal'   => $tanggal,
            ':id_tahun'  => $id_tahun   // ✅ WAJIB ADA
        ];

        if ($id_kelas && $id_kelas != 'semua') {
            $sqlJadwal .= " AND k.id_kelas = :id_kelas";
            $params[':id_kelas'] = $id_kelas;
        }

        $sqlJadwal .= " ORDER BY k.kelas, jp.jam_mulai";

        $stmtJadwal = $this->db->prepare($sqlJadwal);
        $stmtJadwal->execute($params);
        $jadwalAktif = $stmtJadwal->fetchAll(PDO::FETCH_ASSOC);

        $data['jadwal_harian'] = $jadwalAktif;


        /*
    ===============================
    3. QUERY KEHADIRAN (SUDAH BENAR)
    ===============================
    */

        $sqlKehadiran = "
        SELECT 
            jk.id_siswa,
            s.nama_siswa,
            j.jam_mulai,
            j.jam_akhir,
            jk.status,
            j.id_kelas
        FROM jurnal_kehadiran jk
        JOIN jurnal j ON jk.id_jurnal = j.id_jurnal
        JOIN ploting_siswa ps ON jk.id_siswa = ps.id_siswa
        JOIN siswa s ON ps.id_siswa = s.id_siswa
        WHERE DATE(j.created_at) = :tanggal
          AND jk.status != 'H'
          AND ps.id_tahun = :id_tahun
          AND s.status = 'Aktif'
    ";

        $paramsKehadiran = [
            ':tanggal'  => $tanggal,
            ':id_tahun' => $id_tahun
        ];

        if ($id_kelas && $id_kelas != 'semua') {
            $sqlKehadiran .= " AND ps.id_kelas = :id_kelas";
            $paramsKehadiran[':id_kelas'] = $id_kelas;
        }

        $stmtKehadiran = $this->db->prepare($sqlKehadiran);
        $stmtKehadiran->execute($paramsKehadiran);

        $kehadiran = [];

        foreach ($stmtKehadiran->fetchAll(PDO::FETCH_ASSOC) as $k) {
            for ($jam = (int)$k['jam_mulai']; $jam <= (int)$k['jam_akhir']; $jam++) {
                $kehadiran[$k['id_kelas']][$jam][$k['id_siswa']] =
                    "- {$k['nama_siswa']} ({$k['status']})";
            }
        }

        $data['kehadiran'] = $kehadiran;

        return $data;
    }

    // Di dalam file: app/models/Kehadiran.php

    // Di dalam file: app/models/Kehadiran.php

    public function getRekapBulanan($bulan, $tahun, $id_kelas, $id_tahun)
    {
        // ==============================
        // 1. Ambil daftar siswa berdasarkan ploting
        // ==============================
        $stmtSiswa = $this->db->prepare("
        SELECT s.id_siswa, s.nama_siswa
        FROM ploting_siswa ps
        JOIN siswa s ON ps.id_siswa = s.id_siswa
        WHERE ps.id_kelas = ?
          AND ps.id_tahun = ?
          AND s.status = 'Aktif'
        ORDER BY s.nama_siswa ASC
    ");
        $stmtSiswa->execute([$id_kelas, $id_tahun]);
        $daftarSiswa = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

        if (empty($daftarSiswa)) {
            return ['siswa' => [], 'total_hari' => 0];
        }

        // ==============================
        // 2. Ambil hari yang memiliki jurnal
        // ==============================
        $stmtHariJurnal = $this->db->prepare("
        SELECT DISTINCT DAY(created_at) as tanggal
        FROM jurnal
        WHERE id_kelas = ?
          AND MONTH(created_at) = ?
          AND YEAR(created_at) = ?
    ");
        $stmtHariJurnal->execute([$id_kelas, $bulan, $tahun]);
        $hariDenganJurnal = $stmtHariJurnal->fetchAll(PDO::FETCH_COLUMN);

        // ==============================
        // 3. Ambil data absensi (pakai window function)
        // ==============================
        $sqlAbsen = "
        SELECT id_siswa, tanggal, status
        FROM (
            SELECT
                jk.id_siswa,
                DAY(j.created_at) as tanggal,
                jk.status,
                ROW_NUMBER() OVER(
                    PARTITION BY jk.id_siswa, DAY(j.created_at)
                    ORDER BY
                        CASE jk.status
                            WHEN 'A' THEN 1
                            WHEN 'S' THEN 2
                            WHEN 'I' THEN 3
                            ELSE 4
                        END
                ) as rn
            FROM jurnal_kehadiran jk
            JOIN jurnal j ON jk.id_jurnal = j.id_jurnal
            WHERE j.id_kelas = :id_kelas
              AND MONTH(j.created_at) = :bulan
              AND YEAR(j.created_at) = :tahun
              AND jk.status != 'H'
        ) AS ranked_absences
        WHERE rn = 1
    ";

        $stmtAbsen = $this->db->prepare($sqlAbsen);
        $stmtAbsen->execute([
            ':id_kelas' => $id_kelas,
            ':bulan'    => $bulan,
            ':tahun'    => $tahun
        ]);

        $absensi = [];
        while ($row = $stmtAbsen->fetch(PDO::FETCH_ASSOC)) {
            $absensi[$row['id_siswa']][$row['tanggal']] = $row['status'];
        }

        // ==============================
        // 4. Susun Rekap Final
        // ==============================
        $rekapFinal = [];
        $jumlahHari = cal_days_in_month(CAL_GREGORIAN, $bulan, $tahun);

        foreach ($daftarSiswa as $siswa) {

            $rekapSiswa = [
                'id_siswa'   => $siswa['id_siswa'],
                'nama_siswa' => $siswa['nama_siswa'],
                'kehadiran'  => [],
                'total_S'    => 0,
                'total_I'    => 0,
                'total_A'    => 0
            ];

            for ($tgl = 1; $tgl <= $jumlahHari; $tgl++) {

                $status = '0'; // default belum ada data

                if (in_array($tgl, $hariDenganJurnal)) {
                    $status = $absensi[$siswa['id_siswa']][$tgl] ?? 'H';
                }

                $rekapSiswa['kehadiran'][$tgl] = $status;

                if ($status == 'S') $rekapSiswa['total_S']++;
                if ($status == 'I') $rekapSiswa['total_I']++;
                if ($status == 'A') $rekapSiswa['total_A']++;
            }

            $rekapFinal[] = $rekapSiswa;
        }

        return [
            'siswa'      => $rekapFinal,
            'total_hari' => $jumlahHari
        ];
    }
}
