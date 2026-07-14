<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';
require_once '../app/models/TahunPelajaran.php';
require_once '../app/models/DeadlinePerangkat.php';
require_once '../app/models/JenisPerangkat.php';

class DeadlinePerangkatController extends BaseController
{
    private $model;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->model = new DeadlinePerangkat($pdo);
    }

    public function index()
    {
        $tahunModel = new TahunPelajaran($this->db);
        $tahun_all = $tahunModel->getAlltp();

        $jenisPerangkatModel = new JenisPerangkat($this->db);
        $jenis_perangkat_data = $jenisPerangkatModel->getAll();
        $jenis_perangkat = array_column($jenis_perangkat_data, 'nama');

        $deadlines = $this->model->getAll();

        $this->view('admin/deadline/index', [
            'deadlines' => $deadlines,
            'tahun_all' => $tahun_all,
            'jenis_perangkat' => $jenis_perangkat
        ]);
    }
public function getByTahun()
{
    if (!isset($_GET['id_tahun_pelajaran'])) {
        echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
        return;
    }

    $id_tahun = $_GET['id_tahun_pelajaran'];

    // Ambil semua jenis perangkat
    $jenisPerangkatModel = new JenisPerangkat($this->db);
    $allJenis = $jenisPerangkatModel->getAll(); // return: id, nama

    // Ambil deadline yang sudah ada
    $existing = $this->model->getByTahun($id_tahun);
    $existingMap = [];
    foreach ($existing as $e) {
        $existingMap[$e['jenis_perangkat']] = $e;
    }

    $result = [];
    foreach ($allJenis as $jenis) {
        $nama = $jenis['nama'];
        if (isset($existingMap[$nama])) {
            $result[] = [
                'id_deadline' => $existingMap[$nama]['id_deadline'],
                'jenis_perangkat' => $nama,
                'tanggal_deadline' => $existingMap[$nama]['tanggal_deadline']
            ];
        } else {
            $result[] = [
                'id_deadline' => null, // belum ada di DB
                'jenis_perangkat' => $nama,
                'tanggal_deadline' => ''
            ];
        }
    }

    echo json_encode(['success' => true, 'data' => $result]);
}



    public function create()
    {
        $tahunModel = new TahunPelajaran($this->db);
        $tahun_all = $tahunModel->getAlltp();

        $jenisPerangkatModel = new JenisPerangkat($this->db);
        $jenis_perangkat_data = $jenisPerangkatModel->getAll();
        $jenis_perangkat = array_column($jenis_perangkat_data, 'nama');

        $this->view('admin/deadline/create', [
            'tahun_all' => $tahun_all,
            'jenis_perangkat' => $jenis_perangkat
        ]);
    }

    public function store()
    {
        if (!isset($_POST['id_tahun_pelajaran']) || !isset($_POST['deadline'])) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
            return;
        }

        $id_tahun_pelajaran = $_POST['id_tahun_pelajaran'];
        $deadlines = $_POST['deadline']; // bentuk: ['PROTA' => '2025-08-15', ...]

        foreach ($deadlines as $jenis => $tanggal_deadline) {
            $data = [
                'id_tahun_pelajaran' => $id_tahun_pelajaran,
                'jenis_perangkat' => $jenis,
                'tanggal_deadline' => $tanggal_deadline
            ];

            $this->model->store($data);
        }

        echo json_encode(['success' => true]);
    }
    public function storeSingle()
{
    if (!isset($_POST['id_tahun_pelajaran']) || !isset($_POST['jenis_perangkat']) || !isset($_POST['tanggal_deadline'])) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        return;
    }

    $data = [
        'id_tahun_pelajaran' => $_POST['id_tahun_pelajaran'],
        'jenis_perangkat' => $_POST['jenis_perangkat'],
        'tanggal_deadline' => $_POST['tanggal_deadline']
    ];

    $this->model->store($data);

    echo json_encode(['success' => true]);
}

public function update()
{
    if (!isset($_POST['id_deadline']) || !isset($_POST['tanggal_deadline'])) {
        echo json_encode(['success' => false, 'message' => 'Data tidak lengkap.']);
        return;
    }

    $id = $_POST['id_deadline'];
    $data = [
        'tanggal_deadline' => $_POST['tanggal_deadline']
    ];

    $this->model->update($id, $data);
    echo json_encode(['success' => true]);
}


    public function delete()
    {
        $id = $_POST['id_deadline'];
        $this->model->delete($id);
        echo json_encode(['success' => true]);
    }
}
