<?php
class Siswa
{
    private $db;
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }


    public function getTahunAktif()
    {
        $stmt = $this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 1 LIMIT 1");
        return $stmt->fetchColumn(); // Mengembalikan ID atau false
    }

    public function getAll()
    {
        $id_tahun_aktif = $this->getTahunAktif();

        // Gunakan LEFT JOIN agar siswa yang belum dapat kelas tetap muncul
        $sql = "SELECT s.*, k.kelas as nama_kelas, k.id_kelas as current_id_kelas
                FROM siswa s
                LEFT JOIN ploting_siswa ps ON s.id_siswa = ps.id_siswa AND ps.id_tahun = ?
                LEFT JOIN kelas k ON ps.id_kelas = k.id_kelas
                ORDER BY s.nama_siswa ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_tahun_aktif]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $id_tahun_aktif = $this->getTahunAktif();

        // Ambil data siswa + kelas dia di tahun aktif (untuk form edit)
        $sql = "SELECT s.*, ps.id_kelas as current_id_kelas
                FROM siswa s
                LEFT JOIN ploting_siswa ps ON s.id_siswa = ps.id_siswa AND ps.id_tahun = ?
                WHERE s.id_siswa = ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_tahun_aktif, $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function insert($data)
    {
        $id_tahun_aktif = $this->getTahunAktif();

        try {
            $this->db->beginTransaction();

            // 1. Insert Data Biodata Siswa (Tanpa id_kelas)
            $stmt = $this->db->prepare("INSERT INTO siswa (
                nama_siswa, nisn, tempat_lhr, tgl_lhr, alamat, nama_wali, hp_wali
            ) VALUES (?, ?, ?, ?, ?, ?, ?)");

            $stmt->execute([
                $data['nama_siswa'],
                $data['nisn'],
                $data['tempat_lhr'],
                $data['tgl_lhr'],
                $data['alamat'],
                $data['nama_wali'],
                $data['hp_wali']
            ]);

            $new_id_siswa = $this->db->lastInsertId();

            // 2. Insert ke Ploting Siswa (Jika ada tahun aktif & user memilih kelas)
            if ($id_tahun_aktif && !empty($data['id_kelas'])) {
                $stmt2 = $this->db->prepare("INSERT INTO ploting_siswa (id_siswa, id_kelas, id_tahun) VALUES (?, ?, ?)");
                $stmt2->execute([$new_id_siswa, $data['id_kelas'], $id_tahun_aktif]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e; // Lempar error ke controller
        }
    }

    public function update($data)
    {
        $id_tahun_aktif = $this->getTahunAktif();

        try {
            $this->db->beginTransaction();

            // 1. Update Biodata
            $sql = "UPDATE siswa SET 
                    nama_siswa = ?, nisn = ?, tempat_lhr = ?, tgl_lhr = ?, 
                    alamat = ?, nama_wali = ?, hp_wali = ?
                    WHERE id_siswa = ?";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['nama_siswa'],
                $data['nisn'],
                $data['tempat_lhr'],
                $data['tgl_lhr'],
                $data['alamat'],
                $data['nama_wali'],
                $data['hp_wali'],
                $data['id_siswa']
            ]);

            // 2. Update/Insert Ploting Kelas (Hanya untuk tahun aktif)
            if ($id_tahun_aktif && !empty($data['id_kelas'])) {
                // Cek apakah sudah ada record ploting untuk siswa ini di tahun aktif?
                $cek = $this->db->prepare("SELECT id_ploting FROM ploting_siswa WHERE id_siswa = ? AND id_tahun = ?");
                $cek->execute([$data['id_siswa'], $id_tahun_aktif]);
                $exists = $cek->fetch();

                if ($exists) {
                    // Update kelasnya
                    $upd = $this->db->prepare("UPDATE ploting_siswa SET id_kelas = ? WHERE id_ploting = ?");
                    $upd->execute([$data['id_kelas'], $exists['id_ploting']]);
                } else {
                    // Jika belum ada (misal siswa pindahan), buat baru
                    $ins = $this->db->prepare("INSERT INTO ploting_siswa (id_siswa, id_kelas, id_tahun) VALUES (?, ?, ?)");
                    $ins->execute([$data['id_siswa'], $data['id_kelas'], $id_tahun_aktif]);
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    // Hapus siswa beserta semua riwayat plotingnya
    public function delete($id)
    {
        try {
            $this->db->beginTransaction();

            // Hapus riwayat ploting dulu (Constraint Foreign Key)
            $this->db->prepare("DELETE FROM ploting_siswa WHERE id_siswa = ?")->execute([$id]);

            // Baru hapus siswanya
            $this->db->prepare("DELETE FROM siswa WHERE id_siswa = ?")->execute([$id]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // Helper untuk Dropdown Filter & Form
    public function getDaftarKelas()
    {
        return $this->db->query("SELECT * FROM kelas ORDER BY tingkat ASC, kelas ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    // Helper Pencarian ID Kelas by Nama (Untuk Excel)
    public function getIdKelasByNama($nama_kelas)
    {
        $stmt = $this->db->prepare("SELECT id_kelas FROM kelas WHERE kelas LIKE ? LIMIT 1");
        $stmt->execute([trim($nama_kelas)]);
        return $stmt->fetchColumn();
    }
}
