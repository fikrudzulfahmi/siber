<?php
require_once 'BaseController.php';
require_once '../app/models/Verifikasi.php';

class VerifikasiController extends BaseController
{
    private $model;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->model = new Verifikasi($db);
    }

    public function index()
    {
        $tahun_pelajaran = $this->model->getTahunPelajaran();
        $guru_list = $this->model->getGuru();

        $this->view('admin/verifikasi/index', compact('tahun_pelajaran', 'guru_list'));
    }

    public function getMapelByGuru()
    {
        header('Content-Type: application/json');

        $id_guru = $_GET['id_guru'] ?? null;
        $id_tahun = $_GET['id_tahun'] ?? null;

        if (!$id_guru || !$id_tahun) {
            echo json_encode([]);
            return;
        }

        $data = $this->model->getMapelByGuruAndTahun($id_guru, $id_tahun);
        echo json_encode($data);
    }

    public function getPerangkat()
    {
        header('Content-Type: application/json');

        $id_guru = $_GET['id_guru'] ?? null;
        $id_tahun = $_GET['id_tahun'] ?? null;
        $id_mapel_guru = $_GET['id_mapel_guru'] ?? null;

        if (!$id_guru || !$id_tahun || !$id_mapel_guru) {
            echo json_encode([]);
            return;
        }

        $data = $this->model->getPerangkatByFilter($id_guru, $id_tahun, $id_mapel_guru);
        echo json_encode($data);
    }


public function updateStatus()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        return;
    }

    $id = $_POST['id'] ?? null;
    $status = $_POST['status'] ?? null;

    if (!$id || !$status) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }

    $updated = $this->model->updateStatusApproval($id, $status);

    echo json_encode(['success' => $updated]);
}

public function updateCatatan()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
        return;
    }

    $id = $_POST['id'] ?? null;
    $catatan = $_POST['catatan'] ?? null;

    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        return;
    }

    $updated = $this->model->updateCatatan($id, $catatan);

    echo json_encode(['success' => $updated]);
}

}
