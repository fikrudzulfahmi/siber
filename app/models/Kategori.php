<?php
class Kategori
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // 1. Ambil Tahun Aktif (FIX ERROR single())
    public function getActiveTahun()
    {
        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
        $stmt->execute();
        // Ganti single() dengan fetch(PDO::FETCH_ASSOC)
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // 2. Ambil Kategori berdasarkan Mapel & Tahun (FIX ERROR resultSet())
    public function getAllKategoriByMapel($id_mapel_guru, $id_tahun_aktif)
    {
        $sql = "SELECT a.*, b.tahun_pelajaran, b.semester, a.banyak_ns
            FROM kategori_nilai a
            JOIN tahun_pelajaran b ON a.id_tahun_pelajaran = b.id_tahun_pelajaran
            WHERE a.id_mapel_guru = :id_mapel_guru
            AND a.id_tahun_pelajaran = :id_tahun
            ORDER BY a.id_kategori DESC";

        $stmt = $this->db->prepare($sql);
        // Binding parameter
        $stmt->bindValue(':id_mapel_guru', $id_mapel_guru);
        $stmt->bindValue(':id_tahun', $id_tahun_aktif);

        $stmt->execute();

        // Ganti resultSet() dengan fetchAll(PDO::FETCH_ASSOC)
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Info Mapel Header
    public function getInfoMapel($id_mapel_guru)
    {
        $sql = "SELECT a.nama_mapel, b.kelas, c.id_mapel_guru 
                FROM mapel a 
                JOIN mapel_guru c ON a.id_mapel = c.id_mapel
                JOIN kelas b ON c.id_kelas = b.id_kelas
                WHERE c.id_mapel_guru = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id_mapel_guru);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByMapelGuru($id_mapel_guru)
    {
        $stmt = $this->db->prepare("SELECT kn.*, m.nama_mapel, k.kelas, thp.tahun_pelajaran, thp.semester 
            FROM kategori_nilai kn
            JOIN mapel_guru mg ON mg.id_mapel_guru = kn.id_mapel_guru
            JOIN kelas k ON k.id_kelas = mg.id_kelas
            JOIN mapel m ON m.id_mapel = mg.id_mapel
            JOIN tahun_pelajaran thp ON thp.id_tahun_pelajaran = kn.id_tahun_pelajaran
            WHERE kn.id_mapel_guru = ? ORDER BY kn.kategori ASC");
        $stmt->execute([$id_mapel_guru]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Di dalam file: Kategori.php

    public function getByKategori($id_kategori)
    {
        $stmt = $this->db->prepare("
            SELECT n.*, s.nama_siswa, kn.banyak_ns
            FROM nilai n
            JOIN siswa s ON s.id_siswa = n.id_siswa
            JOIN kategori_nilai kn ON kn.id_kategori = n.id_kategori
            WHERE n.id_kategori = ? ORDER BY s.nama_siswa ASC
        ");
        $stmt->execute([$id_kategori]);
        $nilaiList = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // --- LOGIKA PERHITUNGAN KETUNTASAN BARU ---
        foreach ($nilaiList as $key => $nilai) {
            $jumlah_n = intval($nilai['banyak_ns']);
            $total_skor_seharusnya = $jumlah_n + 2; // (N sejumlah banyak_ns + STS + SAS)

            // Hitung berapa banyak skor yang sudah terisi
            $skor_terisi = 0;
            // Hitung dari N1 sampai N sebanyak 'banyak_ns'
            for ($i = 1; $i <= $jumlah_n; $i++) {
                if (!empty($nilai['n' . $i]) && $nilai['n' . $i] > 0) {
                    $skor_terisi++;
                }
            }
            if (!empty($nilai['sts']) && $nilai['sts'] > 0) $skor_terisi++;
            if (!empty($nilai['sas']) && $nilai['sas'] > 0) $skor_terisi++;

            // Hitung persentase dan nilai kosong
            $persentase = ($skor_terisi / $total_skor_seharusnya) * 100;
            $nilai_kosong = $total_skor_seharusnya - $skor_terisi;

            $nilaiList[$key]['jumlah_nilai_kosong'] = $nilai_kosong;
            $nilaiList[$key]['persentase_tuntas'] = $persentase;
        }
        return $nilaiList;
    }


    // --- PERBAIKAN 1: Ambil juga id_tahun di info mapel ---
    public function getInfoMapelGuru($id_mapel_guru)
    {
        $stmt = $this->db->prepare("SELECT m.nama_mapel, k.kelas, mg.id_mapel_guru, mg.id_kelas, mg.id_tahun_pelajaran AS id_tahun 
            FROM mapel_guru mg
            JOIN mapel m ON m.id_mapel = mg.id_mapel
            JOIN kelas k ON k.id_kelas = mg.id_kelas
            WHERE mg.id_mapel_guru = ?");
        $stmt->execute([$id_mapel_guru]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM kategori_nilai WHERE id_kategori = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function isKategoriExist($id_mapel_guru, $id_tahun)
    {
        $stmt = $this->db->prepare("SELECT id_kategori FROM kategori_nilai WHERE id_mapel_guru = ? AND id_tahun_pelajaran = ?");
        $stmt->execute([$id_mapel_guru, $id_tahun]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Mengembalikan data jika ada, false jika tidak ada
    }

    public function create($data)
    {
        // Validasi internal di Model agar data tetap konsisten
        $checkSql = "SELECT COUNT(*) FROM kategori_nilai 
                 WHERE id_mapel_guru = :id_mapel_guru 
                 AND id_tahun_pelajaran = :id_tahun_pelajaran";

        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([
            ':id_mapel_guru' => $data['id_mapel_guru'],
            ':id_tahun_pelajaran' => $data['id_tahun_pelajaran']
        ]);

        if ($checkStmt->fetchColumn() > 0) {
            return false; // Menghentikan proses jika duplikat ditemukan
        }

        $sql = "INSERT INTO kategori_nilai (id_mapel_guru, id_tahun_pelajaran, kategori, banyak_ns, nama_ns, bobot_ns, bobot_sts, bobot_sas) 
            VALUES (:id_mapel_guru, :id_tahun_pelajaran, :kategori, :banyak_ns, :nama_ns, :bobot_ns, :bobot_sts, :bobot_sas)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return $this->db->lastInsertId();
    }

    // --- PERBAIKAN 2: Populate berdasarkan PLOTING SISWA ---
    public function populateNilaiForNewKategori($id_kategori, $id_mapel_guru)
    {
        // 1. Ambil ID Kelas & ID Tahun dari Mapel Guru
        $info = $this->getInfoMapelGuru($id_mapel_guru);

        if (!$info) return false;

        $id_kelas = $info['id_kelas'];
        $id_tahun = $info['id_tahun']; // Tahun aktif mapel tersebut

        // 2. Ambil Siswa dari tabel PLOTING_SISWA (Sesuai Tahun & Kelas)
        // Bukan dari tabel siswa langsung
        $stmtSiswa = $this->db->prepare("
            SELECT ps.id_siswa 
            FROM ploting_siswa ps
            WHERE ps.id_kelas = ? AND ps.id_tahun = ?
        ");
        $stmtSiswa->execute([$id_kelas, $id_tahun]);
        $siswaList = $stmtSiswa->fetchAll(PDO::FETCH_ASSOC);

        if (empty($siswaList)) return false; // Tidak ada siswa terdaftar

        // 3. Insert ke tabel nilai
        $stmtInsert = $this->db->prepare("INSERT INTO nilai (id_kategori, id_siswa) VALUES (?, ?)");

        $this->db->beginTransaction(); // Pakai transaksi biar aman
        try {
            foreach ($siswaList as $siswa) {
                // Cek dulu biar tidak duplikat (opsional tapi disarankan)
                $cek = $this->db->prepare("SELECT id_nilai FROM nilai WHERE id_kategori = ? AND id_siswa = ?");
                $cek->execute([$id_kategori, $siswa['id_siswa']]);

                if ($cek->rowCount() == 0) {
                    $stmtInsert->execute([$id_kategori, $siswa['id_siswa']]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function update($data)
    {
        $sql = "UPDATE kategori_nilai SET kategori = :kategori, banyak_ns = :banyak_ns, nama_ns = :nama_ns, 
                bobot_ns = :bobot_ns, bobot_sts = :bobot_sts, bobot_sas = :bobot_sas 
                WHERE id_kategori = :id_kategori";
        return $this->db->prepare($sql)->execute($data);
    }

    public function updateNilai($id_nilai, $data, $id_kategori)
    {
        $kat = $this->find($id_kategori);

        // TAMBAHKAN PENGAMAN INI:
        if (!$kat) {
            return false; // Berhenti jika kategori tidak ditemukan
        }

        $banyak_ns = intval($kat['banyak_ns'] ?? 0);

        // 1. Hitung Rata-rata NS (Formatif)
        $total_n = 0;
        $count_n = 0;
        $params = [];
        for ($i = 1; $i <= 10; $i++) {
            $val = (isset($data['n' . $i]) && $data['n' . $i] !== '') ? floatval($data['n' . $i]) : null;
            $params['n' . $i] = $val;
            if ($i <= $banyak_ns && !is_null($val)) {
                $total_n += $val;
                $count_n++;
            }
        }
        $rata_ns = ($count_n > 0) ? ($total_n / $count_n) : 0;

        // --- TAMBAHAN FLOOR DI SINI ---
        // Membulatkan rata-rata NS ke bawah sebelum dikalikan bobot
        $rata_ns = floor($rata_ns);
        // ------------------------------

        // 2. Logika Bobot Rapor
        $sts = (isset($data['sts']) && $data['sts'] !== '') ? floatval($data['sts']) : null;
        $sas = (isset($data['sas']) && $data['sas'] !== '') ? floatval($data['sas']) : null;

        // Rumus Rata-rata Tertimbang
        $pembilang = ($rata_ns * floatval($kat['bobot_ns'] ?? 0));
        $penyebut = floatval($kat['bobot_ns'] ?? 0);

        if (!is_null($sts)) {
            $pembilang += ($sts * floatval($kat['bobot_sts'] ?? 0));
            $penyebut += floatval($kat['bobot_sts'] ?? 0);
        }
        if (!is_null($sas)) {
            $pembilang += ($sas * floatval($kat['bobot_sas'] ?? 0));
            $penyebut += floatval($kat['bobot_sas'] ?? 0);
        }

        $nilai_raport = ($penyebut > 0) ? ($pembilang / $penyebut) : 0;

        $stmt = $this->db->prepare("UPDATE nilai SET 
        n1=:n1, n2=:n2, n3=:n3, n4=:n4, n5=:n5, n6=:n6, n7=:n7, n8=:n8, n9=:n9, n10=:n10, 
        sts=:sts, sas=:sas, rata=:rata, nilai_raport=:nilai_raport WHERE id_nilai=:id_nilai");

        return $stmt->execute(array_merge($params, [
            'sts' => $sts,
            'sas' => $sas,
            'rata' => $rata_ns,
            'nilai_raport' => $nilai_raport,
            'id_nilai' => $id_nilai
        ]));
    }
    public function getNilaiByKategori($id_kategori)
    {
        // Query untuk mengambil data nilai gabungan dengan data siswa
        $sql = "SELECT n.*, s.nama_siswa 
            FROM nilai n 
            JOIN siswa s ON n.id_siswa = s.id_siswa 
            WHERE n.id_kategori = ? 
            ORDER BY s.nama_siswa ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_kategori]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id_kategori)
    {
        $stmt = $this->db->prepare("DELETE FROM kategori_nilai WHERE id_kategori = ?");
        return $stmt->execute([$id_kategori]);
    }
}
