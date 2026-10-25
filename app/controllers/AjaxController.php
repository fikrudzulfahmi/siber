<?php
// File: app/controllers/AjaxController.php

require_once 'BaseController.php';

class AjaxController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo);
        // Pastikan user sudah login untuk bisa meminta data ini
        authGuard();
    }

    /**
     * Menggantikan file: ajax/get-mapel-by-kelas.php
     */
    public function getMapelByKelas()
    {
        // Pastikan parameter yang dibutuhkan ada
        if (!isset($_GET['id_kelas']) || !isset($_SESSION['user']['id'])) {
            echo json_encode(['error' => 'Parameter tidak lengkap']);
            exit;
        }

        $id_kelas = $_GET['id_kelas'];
        $id_guru = $_SESSION['user']['id'];

        // Salin query dari file lama Anda ke sini
        $stmt = $this->db->prepare("
            SELECT mg.id_mapel_guru, m.nama_mapel
            FROM mapel_guru mg
            JOIN mapel m ON mg.id_mapel = m.id_mapel
            WHERE mg.id_guru = :id_guru AND mg.id_kelas = :id_kelas
            ORDER BY m.nama_mapel ASC
        ");
        $stmt->execute(['id_guru' => $id_guru, 'id_kelas' => $id_kelas]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Menggantikan file: ajax/get-siswa-by-kelas.php atau get-siswa.php
     */
    public function getSiswaByKelas()
    {
        if (!isset($_GET['id_kelas'])) {
            echo json_encode(['error' => 'Parameter tidak lengkap']);
            exit;
        }

        $id_kelas = $_GET['id_kelas'];

        // Urutan manual (menu Urutkan Siswa) ikut dipakai di dropdown ini.
        $id_tahun = (int) ($_SESSION['user']['id_tahun'] ?? 0);
        if (!$id_tahun) {
            $id_tahun = (int) ($this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' ORDER BY id_tahun_pelajaran DESC LIMIT 1")->fetchColumn() ?: 0);
        }

        $stmt = $this->db->prepare("
            SELECT s.id_siswa, s.nama_siswa,
                   (SELECT MIN(p2.urutan) FROM ploting_siswa p2
                     WHERE p2.id_siswa = s.id_siswa
                       AND p2.id_kelas = s.id_kelas
                       AND p2.id_tahun = :id_tahun) AS urutan_tampil
            FROM siswa s
            WHERE s.id_kelas = :id_kelas
              AND " . siswa_aktif_sql('s') . "
            ORDER BY (urutan_tampil IS NULL) ASC, urutan_tampil ASC, s.nama_siswa ASC
        ");
        $stmt->execute(['id_kelas' => $id_kelas, 'id_tahun' => $id_tahun]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // urutan_tampil hanya dipakai untuk ORDER BY, jangan ikut ke JSON.
        foreach ($data as &$baris) {
            unset($baris['urutan_tampil']);
        }
        unset($baris);

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Menggantikan file: ajax/get-tp-by-mapel.php
     */
    public function getTpByMapel()
    {
        if (!isset($_GET['id_mapel_guru'])) {
            echo json_encode(['error' => 'Parameter tidak lengkap']);
            exit;
        }

        $id_mapel_guru = $_GET['id_mapel_guru'];

        $stmt = $this->db->prepare("SELECT id_tp, tujuan_pembelajaran FROM tujuan_pembelajaran WHERE id_mapel_guru = ?");
        $stmt->execute([$id_mapel_guru]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
