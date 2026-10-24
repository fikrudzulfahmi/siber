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

        // Filter tahun pada mapel_guru: tabel `kelas` tidak menyimpan tahun,
        // jadi tahun pelajaran ditentukan oleh baris mapel_guru. Tanpa filter ini
        // kelas semester/tahun lalu (mis. IX A-D yang siswanya sudah lulus)
        // tetap muncul di daftar mapel-kelas.
        $filterTahunMG = "";
        $filterTahunKN = "";
        if (!empty($id_tahun)) {
            $filterTahunMG = " AND mg.id_tahun_pelajaran = ? ";
            $params[] = $id_tahun;
            // Kategori otomatis ikut tahun yang sama dengan mapel_guru
            $filterTahunKN = " AND mg.id_tahun_pelajaran = ? ";
            $params[] = $id_tahun;
        }

        $sql = "
    SELECT 
        e.id_employe, e.nama AS nama_guru,
        mg.id_mapel_guru, m.nama_mapel, k.kelas,
        mg.id_tahun_pelajaran AS id_tahun_mapel,
        tp.tahun_pelajaran AS label_tahun_ajaran, tp.semester AS label_semester,
        kn.id_kategori, kn.kategori
    FROM employe e
    -- 1. Ambil mapel-kelas sesuai tahun pilihan (kosong = arsip semua tahun)
    LEFT JOIN mapel_guru mg ON e.id_employe = mg.id_guru $filterTahunMG
    LEFT JOIN mapel m ON mg.id_mapel = m.id_mapel
    LEFT JOIN kelas k ON mg.id_kelas = k.id_kelas
    LEFT JOIN tahun_pelajaran tp ON tp.id_tahun_pelajaran = mg.id_tahun_pelajaran
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
                // Key unik = mapel + kelas + tahun pelajaran. Tahun ikut key supaya
                // kelas dengan nama sama di dua tahun tidak menyatu jadi satu baris.
                $label_tahun = trim(($row['label_tahun_ajaran'] ?? '') . ' - ' . ($row['label_semester'] ?? ''), ' -');
                $mapel_key = $row['nama_mapel'] . ' - ' . $row['kelas'] . ' #' . (int) $row['id_tahun_mapel'];

                if (!isset($data_nested[$id_guru]['mapel_list'][$mapel_key])) {
                    $data_nested[$id_guru]['mapel_list'][$mapel_key] = [
                        'id_mapel_guru' => $row['id_mapel_guru'],
                        'nama_mapel' => $row['nama_mapel'],
                        'kelas' => $row['kelas'],
                        'label_tahun' => $label_tahun,
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

        // Saat tahun tertentu dipilih, guru tanpa mapel di tahun itu tidak perlu
        // tampil sebagai akordeon kosong.
        if (!empty($id_tahun)) {
            foreach ($data_nested as $id_guru => $guru) {
                if (empty($guru['mapel_list'])) {
                    unset($data_nested[$id_guru]);
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
            WHERE n.id_kategori = ? AND " . siswa_aktif_sql('s') . " ORDER BY s.nama_siswa ASC
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
