<?php
// File: app/models/LegerModel.php

class LegerModel
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Mengambil data untuk filter, disesuaikan dengan hak akses.
     */
    public function getFilterOptions($id_user, $user_levels_string)
    {
        $options = [
            'tahun_pelajaran_list' => [],
            'kelas_list' => [],
            'kelas_walas' => null
        ];

        // Semua user bisa memilih tahun pelajaran
        $stmtThp = $this->db->query("SELECT * FROM tahun_pelajaran ORDER BY tahun_pelajaran DESC");
        $options['tahun_pelajaran_list'] = $stmtThp->fetchAll(PDO::FETCH_ASSOC);

        // LOGIKA HAK AKSES BARU
        if (isAnyLevel($user_levels_string, [1, 5])) { // Admin & Kurikulum
            $stmtKelas = $this->db->query("SELECT id_kelas, kelas FROM kelas ORDER BY kelas ASC");
            $options['kelas_list'] = $stmtKelas->fetchAll(PDO::FETCH_ASSOC);
        } elseif (isLevel($user_levels_string, 3)) { // Wali Kelas
            $stmtKelas = $this->db->prepare("SELECT id_kelas, kelas FROM kelas WHERE wali_kelas = ?");
            $stmtKelas->execute([$id_user]);
            $options['kelas_walas'] = $stmtKelas->fetch(PDO::FETCH_ASSOC);
        }

        return $options;
    }

    public function getKelasInfo($id_kelas)
    {
        $stmt = $this->db->prepare("SELECT kelas FROM kelas WHERE id_kelas = ?");
        $stmt->execute([$id_kelas]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getLegerData($id_kelas, $id_tahun_pelajaran)
    {
        // Query menggunakan ploting_siswa sebagai acuan daftar siswa di kelas tersebut
        $query = "
    SELECT 
        ps.id_siswa, 
        s.nama_siswa, 
        m.kode_mapel, 
        n.nilai_raport
    FROM ploting_siswa ps
    JOIN siswa s ON ps.id_siswa = s.id_siswa
    -- Hubungkan ke kategori_nilai dan mapel_guru
    -- Pastikan mapel_guru juga difilter berdasarkan id_kelas yang sama
    LEFT JOIN kategori_nilai kn ON kn.id_tahun_pelajaran = ps.id_tahun
    LEFT JOIN mapel_guru mg ON kn.id_mapel_guru = mg.id_mapel_guru
    LEFT JOIN mapel m ON mg.id_mapel = m.id_mapel
    -- Ambil nilai yang spesifik
    LEFT JOIN nilai n ON (n.id_siswa = ps.id_siswa AND n.id_kategori = kn.id_kategori)
    WHERE ps.id_kelas = :id_kelas 
      AND ps.id_tahun = :id_tahun_pelajaran
      AND mg.id_kelas = :id_kelas_filter -- KUNCI PERBAIKAN: Filter mapel berdasarkan kelas
    ORDER BY s.nama_siswa ASC, m.nama_mapel ASC
    ";

        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':id_kelas' => $id_kelas,
            ':id_tahun_pelajaran' => $id_tahun_pelajaran,
            ':id_kelas_filter' => $id_kelas // Tambahkan parameter ini
        ]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Proses data mentah menjadi format Leger
        $leger = [];
        $mapel_header = [];

        foreach ($results as $row) {
            $id_s = $row['id_siswa'];

            // Inisialisasi data siswa jika belum ada
            if (!isset($leger[$id_s])) {
                $leger[$id_s] = [
                    'nama_siswa' => $row['nama_siswa'],
                    'nilai' => []
                ];
            }

            // Masukkan nilai jika mapel ditemukan
            if ($row['kode_mapel']) {
                $leger[$id_s]['nilai'][$row['kode_mapel']] = $row['nilai_raport'];
                $mapel_header[$row['kode_mapel']] = true;
            }
        }

        ksort($mapel_header);

        return [
            'leger' => $leger,
            'mapel_header' => array_keys($mapel_header)
        ];
    }
}
