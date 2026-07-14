<?php
require_once 'BaseController.php';
require_once '../app/models/VerifikasiProgramStruktural.php';

class VerifikasiProgramStrukturalController extends BaseController
{
    private $model;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->model = new VerifikasiProgramStruktural($db);
    }

    public function index()
    {
        $this->view('admin/verifikasi_program_struktural/index', [
            'tahun_pelajaran' => $this->model->getTahunPelajaran(),
            'pegawai' => $this->model->getPegawaiStruktural()
        ]);
    }

public function getProgram()
{
    header('Content-Type: application/json');

    $id_user = $_GET['id_user'] ?? null;
    $id_tahun = $_GET['id_tahun'] ?? null;

    if (!$id_user || !$id_tahun) {
        echo json_encode([
            'success' => false,
            'message' => 'Parameter id_user atau id_tahun kosong',
            'data' => []
        ]);
        return;
    }

    try {
        $program = $this->model->getProgramByUserAndTahun($id_user, $id_tahun);

        // Cek jika hasil kosong
        if (!$program) {
            echo json_encode([
                'success' => true,
                'message' => 'Tidak ada data program untuk pegawai/tahun tersebut',
                'data' => []
            ]);
            return;
        }

        // Berhasil
        echo json_encode([
            'success' => true,
            'message' => 'Data berhasil diambil',
            'data' => $program
        ]);
    } catch (PDOException $e) {
        // Tangkap error SQL dan tampilkan
        echo json_encode([
            'success' => false,
            'message' => 'Query gagal: ' . $e->getMessage(),
            'data' => []
        ]);
        // Bisa juga log ke file server:
        error_log('getProgram error: ' . $e->getMessage());
    }
}


    public function updateStatus()
    {
        echo json_encode([
            'success' => $this->model->updateStatus(
                $_POST['id'], $_POST['status']
            )
        ]);
    }

    public function updateCatatan()
    {
        echo json_encode([
            'success' => $this->model->updateCatatan(
                $_POST['id'], $_POST['catatan']
            )
        ]);
    }
}
