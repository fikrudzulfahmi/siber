<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';
require_once __DIR__ . '/../models/ProgramKerjaModel.php';
require_once __DIR__ . '/../models/TahunPelajaran.php';

class ProgramKerjaController
{
    private $db;

    public function __construct($db)
    {
        // PENTING: $db adalah PDO
        $this->db = $db;
    }

    public function index()
    {
        $id_user  = $_SESSION['user']['id'];
        $id_level = $_SESSION['user']['level'];

        $programKerjaModel = new ProgramKerjaModel($this->db);
        $tahunModel        = new TahunPelajaran($this->db);

        $tahunAktif = $tahunModel->getAktif();

        if (!$tahunAktif) {
            setFlash('warning', 'Tahun pelajaran aktif belum ditentukan.');
            $programKerja = [];
        } else {
            $programKerja = $programKerjaModel->getByUserAndTahun(
                $id_user,
                $tahunAktif['id_tahun_pelajaran']
            );
        }

        require __DIR__ . '/../views/admin/programkerja/index_user.php';
    }

    /**
     * =========================
     * ADMIN: Semua Program Kerja
     * =========================
     */
    public function indexAdmin()
    {
        $id_level = $_SESSION['user']['level'];

        if (!isAnyLevel($id_level, [1, 5])) {
            setFlash('danger', 'Akses ditolak');
            redirect('?controller=dashboard');
        }

        $programKerjaModel = new ProgramKerjaModel($this->db);
        $tahunModel        = new TahunPelajaran($this->db);

        $tahunAktif = $tahunModel->getAktif();

        // filter user (opsional)
        $filterUser = $_GET['user'] ?? null;

        if (!$tahunAktif) {
            setFlash('warning', 'Tahun pelajaran aktif belum ditentukan.');
            $programKerja = [];
        } else {
            $programKerja = $programKerjaModel->getAllByTahun(
                $tahunAktif['id_tahun_pelajaran'],
                $filterUser
            );
        }

        // data user untuk dropdown filter
        $users = $programKerjaModel->getUserPengisiProgramKerja(
            $tahunAktif['id_tahun_pelajaran']
        );

        require __DIR__ . '/../views/admin/programkerja/index_admin.php';
    }


    public function create()
    {
        $tahunModel        = new TahunPelajaran($this->db);
        $tahunAktif = $tahunModel->getAktif();

        if (!$tahunAktif) {
            setFlash('danger', 'Tahun pelajaran aktif belum ditentukan.');
            header('Location: ?controller=programKerja&method=index');
            return;
        }

        require __DIR__ . '/../views/admin/programkerja/create.php';
    }
    public function store()
    {
        $tahunModel = new TahunPelajaran($this->db);
        $tahunAktif = $tahunModel->getAktif();

        if (!$tahunAktif) {
            setFlash('danger', 'Tahun pelajaran aktif belum ditentukan.');
            header('Location: ?controller=programKerja&method=index');
            exit;
        }

        $model = new ProgramKerjaModel($this->db);

        $data = [
            'nama_program'        => $_POST['nama_program'],
            'deskripsi_default'   => $_POST['deskripsi_default'],
            'id_tahun_pelajaran'  => $tahunAktif['id_tahun_pelajaran'],
            'created_by'          => $_SESSION['user']['id']
        ];

        $model->insert($data);

        setFlash('success', 'Program kerja berhasil disimpan.');
        header('Location: ?controller=programKerja&method=index');
        exit;
    }

    public function edit()
    {
        if (!isset($_GET['id'])) {
            setFlash('danger', 'ID program kerja tidak ditemukan.');
            header('Location: ?controller=programKerja&method=index');
            exit;
        }

        $id = $_GET['id'];

        $model = new ProgramKerjaModel($this->db);
        $program = $model->findById($id);

        if (!$program) {
            setFlash('danger', 'Data program kerja tidak ditemukan.');
            header('Location: ?controller=programKerja&method=index');
            exit;
        }

        require __DIR__ . '/../views/admin/programkerja/edit.php';
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            setFlash('danger', 'Akses tidak valid.');
            redirect('program-kerja');
            exit;
        }

        $id_user  = $_SESSION['user']['id'];
        $id_level = $_SESSION['user']['level'];

        $id_program = $_POST['id_program'] ?? null;

        if (!$id_program) {
            setFlash('danger', 'ID program tidak ditemukan.');
            redirect('program-kerja');
            exit;
        }

        $model   = new ProgramKerjaModel($this->db);
        $program = $model->findById($id_program);

        if (!$program) {
            setFlash('danger', 'Data program kerja tidak ditemukan.');
            redirect('program-kerja');
            exit;
        }

        // Proteksi user
        if ($id_level != 1 && $program['created_by'] != $id_user) {
            setFlash('danger', 'Anda tidak memiliki akses.');
            redirect('program-kerja');
            exit;
        }

        $data = [
            'id_program'        => $id_program,
            'nama_program'      => $_POST['nama_program'],
            'deskripsi_default' => $_POST['deskripsi_default']
        ];

        $model->update($data);

        setFlash('success', 'Program kerja berhasil diperbarui.');
        header('Location: ?controller=programKerja&method=index');
        exit;
    }


    public function delete()
    {
        $model = new ProgramKerjaModel($this->db);
        $model->delete($_GET['id']);

        setFlash('success', 'Program kerja berhasil dihapus.');
        header('Location: ?controller=programKerja&method=index');
        exit;
    }
}
