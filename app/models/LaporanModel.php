<?php
// File: app/models/LaporanModel.php

class LaporanModel
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function getDataLaporanLengkap($id_tahun = null)
    {
        $params = [];
        $whereClause = " WHERE e.id_level != 4 ";

        // Kita gunakan variabel untuk filter tahun di dalam LEFT JOIN kn
        $filterTahunKN = "";
        if (!empty($id_tahun)) {
            // Hanya ambil kategori yang tahunnya sesuai pilihan
            $filterTahunKN = " AND mg.id_tahun_pelajaran = ? ";
            $params[] = $id_tahun;
        }

        $sql = "
    SELECT 
        e.id_employe, e.nama AS nama_guru,
        mg.id_mapel_guru, m.nama_mapel, k.kelas,
        kn.id_kategori, kn.kategori
    FROM employe e
    -- 1. Ambil SEMUA jadwal guru tanpa filter tahun di sini agar mapel selalu muncul
    LEFT JOIN mapel_guru mg ON e.id_employe = mg.id_guru 
    LEFT JOIN mapel m ON mg.id_mapel = m.id_mapel
    LEFT JOIN kelas k ON mg.id_kelas = k.id_kelas
    -- 2. Ambil kategori HANYA yang sesuai tahun pilihan
    LEFT JOIN kategori_nilai kn ON mg.id_mapel_guru = kn.id_mapel_guru $filterTahunKN
    $whereClause
    ORDER BY e.nama ASC, m.nama_mapel ASC, k.kelas ASC, kn.kategori ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data_nested = [];
        foreach ($results as $row) {
            $id_guru = $row['id_employe'];

            if (!isset($data_nested[$id_guru])) {
                $data_nested[$id_guru] = [
                    'nama_guru' => $row['nama_guru'],
                    'mapel_list' => []
                ];
            }

            if (!empty($row['id_mapel_guru'])) {
                // Gunakan Nama Mapel + Kelas sebagai Key Unik (Deduplication)
                // Ini supaya jika di mapel_guru ada baris dobel semester 1 & 2, dia jadi 1 baris saja
                $mapel_key = $row['nama_mapel'] . ' - ' . $row['kelas'];

                if (!isset($data_nested[$id_guru]['mapel_list'][$mapel_key])) {
                    $data_nested[$id_guru]['mapel_list'][$mapel_key] = [
                        'id_mapel_guru' => $row['id_mapel_guru'],
                        'nama_mapel' => $row['nama_mapel'],
                        'kelas' => $row['kelas'],
                        'kategori_list' => []
                    ];
                }

                // Masukkan kategori jika ada (hasil filter $filterTahunKN di SQL)
                if (!empty($row['id_kategori'])) {
                    $data_nested[$id_guru]['mapel_list'][$mapel_key]['kategori_list'][] = [
                        'id_kategori' => $row['id_kategori'],
                        'kategori' => $row['kategori']
                    ];
                }
            }
        }
        return $data_nested;
    }
    public function getNilaiByKategori($id_kategori)
    {
        $stmt = $this->db->prepare("
            SELECT n.*, s.nama_siswa, kn.banyak_ns, kn.nama_ns
            FROM nilai n
            JOIN siswa s ON s.id_siswa = n.id_siswa
            JOIN kategori_nilai kn ON kn.id_kategori = n.id_kategori
            WHERE n.id_kategori = ? ORDER BY s.nama_siswa ASC
        ");
        $stmt->execute([$id_kategori]);
        $nilaiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- Logika Perhitungan Ketuntasan ---
        foreach ($nilaiList as $key => $nilai) {
            $jumlah_n = intval($nilai['banyak_ns']);
            $total_skor_seharusnya = $jumlah_n + 2; // (N sejumlah banyak_ns + STS + SAS)

            $skor_terisi = 0;
            for ($i = 1; $i <= $jumlah_n; $i++) {
                if (!empty($nilai['n' . $i]) && $nilai['n' . $i] > 0) {
                    $skor_terisi++;
                }
            }
            if (!empty($nilai['sts']) && $nilai['sts'] > 0) $skor_terisi++;
            if (!empty($nilai['sas']) && $nilai['sas'] > 0) $skor_terisi++;

            // Hindari pembagian dengan nol jika total_skor_seharusnya adalah 0
            if ($total_skor_seharusnya > 0) {
                $persentase = ($skor_terisi / $total_skor_seharusnya) * 100;
            } else {
                $persentase = 0;
            }

            $nilai_kosong = $total_skor_seharusnya - $skor_terisi;

            $nilaiList[$key]['jumlah_nilai_kosong'] = $nilai_kosong;
            $nilaiList[$key]['persentase_tuntas'] = $persentase;
        }
        return $nilaiList;
    }
    public function findKategoriInfo($id_kategori)
    {
        $stmt = $this->db->prepare("
            SELECT kn.kategori, m.nama_mapel, k.kelas
            FROM kategori_nilai kn
            JOIN mapel_guru mg ON kn.id_mapel_guru = mg.id_mapel_guru
            JOIN mapel m ON mg.id_mapel = m.id_mapel
            JOIN kelas k ON mg.id_kelas = k.id_kelas
            WHERE kn.id_kategori = ?
        ");
        $stmt->execute([$id_kategori]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
