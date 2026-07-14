<?php
require_once 'BaseController.php';
require_once '../app/models/TahunPelajaran.php';
require_once '../app/models/JenisProgramStruktural.php';
require_once '../app/models/DeadlineProgramStruktural.php';

class DeadlineProgramStrukturalController extends BaseController
{
    public function index()
    {
        $tahunModel = new TahunPelajaran($this->db);
        $tahun_all = $tahunModel->getAlltp();

        $jenisModel = new JenisProgramStruktural($this->db);
        $jenis_program = array_column($jenisModel->getAll(), 'nama');

        $this->view('admin/program_struktural/deadline', [
            'tahun_all' => $tahun_all,
            'jenis_program' => $jenis_program
        ]);
    }

    public function getByTahun()
    {
        if (!isset($_GET['id_tahun_pelajaran'])) {
            echo json_encode(['success' => false, 'message' => 'Parameter tidak lengkap']);
            return;
        }

        $id_tahun = $_GET['id_tahun_pelajaran'];
        $deadlineModel = new DeadlineProgramStruktural($this->db);
        $existing = $deadlineModel->getByTahun($id_tahun);

        $existingMap = [];
        foreach ($existing as $d) {
            $existingMap[$d['jenis_program']] = $d;
        }

        $jenisModel = new JenisProgramStruktural($this->db);
        $allJenis = $jenisModel->getAll();

        $result = [];
        foreach ($allJenis as $j) {
            $nama = $j['nama'];
            if (isset($existingMap[$nama])) {
                $result[] = [
'id_deadline' => $existingMap[$nama]['id_deadline'],
                    'jenis_program' => $nama,
                    'tanggal_deadline' => $existingMap[$nama]['tanggal_deadline']
                ];
            } else {
                $result[] = [
                    'id_deadline' => null,
                    'jenis_program' => $nama,
                    'tanggal_deadline' => ''
                ];
            }
        }

        echo json_encode(['success' => true, 'data' => $result]);
    }

    public function storeSingle()
    {
        if (!isset($_POST['id_tahun_pelajaran']) || !isset($_POST['jenis_program']) || !isset($_POST['tanggal_deadline'])) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        $data = [
            'id_tahun_pelajaran' => $_POST['id_tahun_pelajaran'],
            'jenis_program' => $_POST['jenis_program'],
            'tanggal_deadline' => $_POST['tanggal_deadline']
        ];

        $model = new DeadlineProgramStruktural($this->db);
        $model->store($data);

        echo json_encode(['success' => true]);
    }

    public function update()
    {
        if (!isset($_POST['id_deadline']) || !isset($_POST['tanggal_deadline'])) {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
            return;
        }

        $id = $_POST['id_deadline'];
        $data = ['tanggal_deadline' => $_POST['tanggal_deadline']];
        $model = new DeadlineProgramStruktural($this->db);
        $model->update($id, $data);

        echo json_encode(['success' => true]);
    }
}
