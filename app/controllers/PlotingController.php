<?php
require_once __DIR__ . '/../models/Ploting.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class PlotingController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo);
    }

    public function index()
    {
        $model = new Ploting($this->db);

        // Siapkan data untuk dropdown filter
        $tahun_ajaran = $model->getAllTahun();
        $daftar_kelas = $model->getAllKelas();

        require __DIR__ . '/../views/admin/ploting/index.php';
    }

    // API untuk AJAX: Mengambil daftar siswa
    public function get_data_siswa()
    {
        // Pastikan request method POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id_kelas = $_POST['id_kelas'] ?? 0;
        $id_tahun = $_POST['id_tahun'] ?? 0;

        $model = new Ploting($this->db);
        $data = $model->getSiswaByKelasTahun($id_kelas, $id_tahun);

        // Return JSON
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // API untuk AJAX: Mengambil daftar siswa yang BELUM punya kelas (siswa baru)
    public function get_siswa_baru()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id_tahun = $_POST['id_tahun'] ?? 0;

        $model = new Ploting($this->db);
        $data = $model->getSiswaBelumPunyaKelas($id_tahun);

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // API untuk AJAX: Proses Simpan
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $ids_siswa = $_POST['ids_siswa'] ?? []; // Array ID Siswa
        $id_kelas_tujuan = $_POST['id_kelas_tujuan'];
        $id_tahun_tujuan = $_POST['id_tahun_tujuan'];

        $model = new Ploting($this->db);
        $sukses = 0;
        $gagal = 0;

        foreach ($ids_siswa as $id_siswa) {
            // Validasi: Apakah siswa ini sudah terdaftar di tahun ajaran tersebut?
            $exist = $model->cekSiswaDiTahun($id_siswa, $id_tahun_tujuan);

            if ($exist == 0) {
                $model->insert($id_siswa, $id_kelas_tujuan, $id_tahun_tujuan);
                $sukses++;
            } else {
                $gagal++; // Siswa sudah punya kelas di tahun itu
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'msg' => "$sukses siswa berhasil dipindahkan. $gagal gagal/sudah ada.",
            'inserted' => $sukses
        ]);
        exit;
    }

    // API untuk AJAX: Meluluskan siswa terpilih (tidak dimasukkan ke kelas manapun)
    public function luluskan()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $ids_siswa = $_POST['ids_siswa'] ?? [];
        $id_tahun = $_POST['id_tahun'] ?? 0;

        $model = new Ploting($this->db);
        $sukses = 0;

        foreach ($ids_siswa as $id_siswa) {
            $model->luluskanSiswa($id_siswa, $id_tahun);
            $sukses++;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'msg' => "$sukses siswa berhasil diluluskan.",
            'inserted' => $sukses
        ]);
        exit;
    }
}
