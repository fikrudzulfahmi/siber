<?php
// File: app/models/KegiatanModel.php

class KegiatanModel
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Mengambil daftar semua kegiatan
     */
    public function getAllKegiatan()
    {
        $stmt = $this->db->query("SELECT * FROM kegiatan ORDER BY tanggal DESC, jam_mulai DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil detail satu kegiatan
     */
    public function findKegiatan($id_kegiatan)
    {
        $stmt = $this->db->prepare("SELECT * FROM kegiatan WHERE id_kegiatan = ?");
        $stmt->execute([$id_kegiatan]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Simpan Kegiatan Baru
     */
    public function createKegiatan($data)
    {
        $sql = "INSERT INTO kegiatan (nama_kegiatan, tanggal, jam_mulai, jam_selesai, keterangan) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['nama_kegiatan'],
            $data['tanggal'],
            $data['jam_mulai'],
            $data['jam_selesai'],
            $data['keterangan']
        ]);
        return $this->db->lastInsertId();
    }

    /**
     * Simpan Peserta Kegiatan (Guru)
     */
    public function addPeserta($id_kegiatan, $pin_guru)
    {
        $stmt = $this->db->prepare("INSERT INTO kegiatan_peserta (id_kegiatan, pin_guru) VALUES (?, ?)");
        return $stmt->execute([$id_kegiatan, $pin_guru]);
    }

    /**
     * Ambil daftar semua guru untuk pilihan di form (hanya yang aktif)
     */
    public function getDaftarGuru()
    {
        // Filter level != 4 sesuai referensi laporan Anda
        $stmt = $this->db->query("SELECT pin, nama, jabatan FROM employe ORDER BY nama ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * LOGIKA REKAP PRESENSI (Range Waktu)
     * Mengambil daftar peserta dan mencocokkan dengan tabel attendance
     */
    public function getRekapKehadiran($id_kegiatan)
    {
        $kegiatan = $this->findKegiatan($id_kegiatan);
        if (!$kegiatan) return [];

        $tanggal = $kegiatan['tanggal'];

        $sql = "
        SELECT 
            e.nama, 
            e.pin, 
            e.jabatan,
            -- Ambil scan pertama di hari itu (untuk jam datang/mulai)
            (SELECT MIN(scan_date) 
             FROM attendance 
             WHERE pin = e.pin AND DATE(scan_date) = ?
            ) AS jam_masuk,
            -- Ambil scan terakhir di hari itu (untuk jam pulang)
            (SELECT MAX(scan_date) 
             FROM attendance 
             WHERE pin = e.pin AND DATE(scan_date) = ?
            ) AS jam_pulang
        FROM kegiatan_peserta kp
        JOIN employe e ON kp.pin_guru = e.pin
        WHERE kp.id_kegiatan = ?
        ORDER BY e.nama ASC
    ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tanggal, $tanggal, $id_kegiatan]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    /**
     * Update Foto Kegiatan
     */
    // public function updateFoto($id_kegiatan, $fileName)
    // {
    //     $stmt = $this->db->prepare("UPDATE kegiatan SET foto_kegiatan = ? WHERE id_kegiatan = ?");
    //     return $stmt->execute([$fileName, $id_kegiatan]);
    // }

    // Tambahkan ini di dalam class KegiatanModel

    public function saveFotoKegiatan($id_kegiatan, $nama_file)
    {
        $sql = "INSERT INTO kegiatan_foto (id_kegiatan, nama_file) VALUES (?, ?)";
        return $this->db->prepare($sql)->execute([$id_kegiatan, $nama_file]);
    }

    public function getFotosByKegiatan($id_kegiatan)
    {
        $sql = "SELECT * FROM kegiatan_foto WHERE id_kegiatan = ? ORDER BY id_foto DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_kegiatan]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil daftar PIN peserta berdasarkan id_kegiatan
     */
    public function getPesertaByKegiatan($id_kegiatan)
    {
        $stmt = $this->db->prepare("SELECT pin_guru FROM kegiatan_peserta WHERE id_kegiatan = ?");
        $stmt->execute([$id_kegiatan]);
        // Mengembalikan array yang hanya berisi kolom pin_guru (1D Array)
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Update Data Kegiatan
     */
    public function updateKegiatan($id_kegiatan, $data)
    {
        $sql = "UPDATE kegiatan SET nama_kegiatan = ?, tanggal = ?, jam_mulai = ?, jam_selesai = ?, keterangan = ? WHERE id_kegiatan = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['nama_kegiatan'],
            $data['tanggal'],
            $data['jam_mulai'],
            $data['jam_selesai'],
            $data['keterangan'],
            $id_kegiatan
        ]);
    }

    /**
     * Hapus semua peserta dalam suatu kegiatan (Digunakan saat update & hapus)
     */
    public function deletePeserta($id_kegiatan)
    {
        $stmt = $this->db->prepare("DELETE FROM kegiatan_peserta WHERE id_kegiatan = ?");
        return $stmt->execute([$id_kegiatan]);
    }

    /**
     * Hapus Kegiatan (beserta peserta dan foto di database)
     */
    public function deleteKegiatan($id_kegiatan)
    {
        // 1. Hapus Peserta
        $this->deletePeserta($id_kegiatan);

        // 2. Hapus referensi foto di database
        $stmtFoto = $this->db->prepare("DELETE FROM kegiatan_foto WHERE id_kegiatan = ?");
        $stmtFoto->execute([$id_kegiatan]);

        // 3. Hapus Data Utama Kegiatan
        $stmt = $this->db->prepare("DELETE FROM kegiatan WHERE id_kegiatan = ?");
        return $stmt->execute([$id_kegiatan]);
    }
}
