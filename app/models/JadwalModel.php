<?php
class JadwalModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getActiveTahunPelajaran()
    {
        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getKelas()
    {
        $stmt = $this->db->query("SELECT id_kelas, kelas FROM kelas");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

   public function getJadwalByKelas($id_kelas, $id_tahun)
{
    // Tambahkan filter: AND j.id_tahun_pelajaran = :id_tahun
    $sql = "SELECT j.*, m.nama_mapel, m.kode_mapel, e.nama AS nama_guru 
            FROM jadwal_pelajaran j
            JOIN mapel_guru mg ON j.id_mapel_guru = mg.id_mapel_guru
            JOIN mapel m ON mg.id_mapel = m.id_mapel
            JOIN employe e ON mg.id_guru = e.id_employe
            WHERE mg.id_kelas = :id_kelas 
            AND j.id_tahun_pelajaran = :id_tahun  -- <--- INI KUNCINYA
            ORDER BY FIELD(j.hari, 'Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'), j.jam_mulai ASC";

    $stmt = $this->db->prepare($sql);
    $stmt->execute([
        'id_kelas' => $id_kelas,
        'id_tahun' => $id_tahun
    ]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


    public function cekJadwalBentrok($id_kelas, $hari, $jam_mulai, $jam_selesai, $id_tahun)
    {
        // Logika: Cari jadwal di kelas yang sama, tahun sama, hari sama, 
        // dimana jam input beririsan dengan jam yang sudah ada.
        $sql = "SELECT COUNT(*) FROM jadwal_pelajaran j
                JOIN mapel_guru mg ON j.id_mapel_guru = mg.id_mapel_guru
                WHERE mg.id_kelas = :id_kelas
                AND j.id_tahun_pelajaran = :id_tahun
                AND j.hari = :hari
                AND (
                    (:jam_mulai >= j.jam_mulai AND :jam_mulai < j.jam_selesai) OR
                    (:jam_selesai > j.jam_mulai AND :jam_selesai <= j.jam_selesai) OR
                    (j.jam_mulai >= :jam_mulai AND j.jam_selesai <= :jam_selesai)
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_kelas'  => $id_kelas,
            'id_tahun'  => $id_tahun,
            'hari'      => $hari,
            'jam_mulai' => $jam_mulai,
            'jam_selesai' => $jam_selesai
        ]);

        return $stmt->fetchColumn() > 0;
    }

    // 4. Ambil Jam yang sudah terpakai (Untuk JS updateJamOptions)
   
    // Di dalam file: JadwalModel.php

    public function getMapelGuruByKelas($id_kelas, $id_tahun) {
        $sql = "SELECT mg.id_mapel_guru, m.nama_mapel, m.kode_mapel, e.nama as nama_guru
                FROM mapel_guru mg
                JOIN mapel m ON mg.id_mapel = m.id_mapel
                JOIN employe e ON mg.id_guru = e.id_employe
                WHERE mg.id_kelas = :id_kelas AND mg.id_tahun_pelajaran = :id_tahun";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_kelas' => $id_kelas, 'id_tahun' => $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getMapelGuru()
    {
        $stmt = $this->db->prepare("
        SELECT mg.id_mapel_guru, m.nama_mapel, e.nama AS nama_guru
        FROM mapel_guru mg
        JOIN mapel m ON mg.id_mapel = m.id_mapel
        JOIN employe e ON mg.id_guru = e.id_employe
    ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Di dalam file: JadwalModel.php

     public function getJamTerpakai($id_kelas, $hari, $id_tahun)
    {
        $sql = "SELECT jam_mulai, jam_selesai FROM jadwal_pelajaran j
                JOIN mapel_guru mg ON j.id_mapel_guru = mg.id_mapel_guru
                WHERE mg.id_kelas = :id_kelas 
                AND j.hari = :hari 
                AND j.id_tahun_pelajaran = :id_tahun";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_kelas' => $id_kelas, 
            'hari' => $hari,
            'id_tahun' => $id_tahun
        ]);
        
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert range jam ke array flat (misal 1-3 jadi [1, 2, 3])
        $jamTerpakai = [];
        foreach ($result as $row) {
            for ($i = $row['jam_mulai']; $i < $row['jam_selesai']; $i++) { 
                // Catatan: logika < jam_selesai atau <= tergantung sistem sekolah Anda.
                // Biasanya jika jam 1-2, berarti selesai sebelum jam 3 mulai, atau selesai di akhir jam 2.
                // Asumsi disini: input jam 1 s/d 1 = 1 jam pelajaran.
                // Mari pakai logika inclusive:
            }
             for ($i = $row['jam_mulai']; $i <= $row['jam_selesai']; $i++) {
                 $jamTerpakai[] = (int)$i;
             }
        }
        return array_unique($jamTerpakai);
    }

    public function insertJadwal($data)
    {
       $sql = "INSERT INTO jadwal_pelajaran 
                (id_mapel_guru, id_tahun_pelajaran, hari, jam_mulai, jam_selesai) 
                VALUES (:id_mapel_guru, :id_tahun, :hari, :jam_mulai, :jam_selesai)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'id_mapel_guru' => $data['id_mapel_guru'],
            'id_tahun'      => $data['id_tahun_pelajaran'], // Ambil dari Controller
            'hari'          => $data['hari'],
            'jam_mulai'     => $data['jam_mulai'],
            'jam_selesai'   => $data['jam_selesai']
        ]);
    }

    // Di dalam file: JadwalModel.php

    public function deleteJadwal($id_jadwal)
    {
        // Gunakan prepared statement untuk keamanan
        $stmt = $this->db->prepare("DELETE FROM jadwal_pelajaran WHERE id_jadwal = ?");
        return $stmt->execute([$id_jadwal]);
    }
}
