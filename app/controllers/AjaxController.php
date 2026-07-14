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

        $stmt = $this->db->prepare("SELECT id_siswa, nama_siswa FROM siswa WHERE id_kelas = ? ORDER BY nama_siswa ASC");
        $stmt->execute([$id_kelas]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
