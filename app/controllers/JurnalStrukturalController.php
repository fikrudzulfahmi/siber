<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';
require_once __DIR__ . '/../models/JurnalStrukturalModel.php';
require_once __DIR__ . '/../models/ProgramKerjaModel.php';
require_once __DIR__ . '/../models/TahunPelajaran.php';

class JurnalStrukturalController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db; // PDO
    }

    /**
     * =========================
     * FORM INPUT JURNAL
     * =========================
     */
    public function index()
    {
        $id_user = $_SESSION['user']['id'];

        $tahunModel   = new TahunPelajaran($this->db);
        $programModel = new ProgramKerjaModel($this->db);

        $tahunAktif = $tahunModel->getAktif();

        if (!$tahunAktif || empty($tahunAktif['id_tahun_pelajaran'])) {
            setFlash('danger', 'Tahun pelajaran aktif belum ditentukan.');
            redirect('?controller=jurnalStruktural&method=index');
            exit;
        }

        // ✅ Program kerja HANYA milik user
        $programKerja = $programModel->getByUserAndTahun(
            $id_user,
            $tahunAktif['id_tahun_pelajaran']
        );

        require __DIR__ . '/../views/admin/jurnal_struktural/index.php';
    }

    /**
     * =========================
     * SIMPAN JURNAL
     * =========================
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('?controller=jurnalStruktural');
            exit;
        }

        $id_user = $_SESSION['user']['id'];

        $tahunModel = new TahunPelajaran($this->db);
        $tahunAktif = $tahunModel->getAktif();

        if (!$tahunAktif) {
            setFlash('danger', 'Tahun pelajaran aktif belum ditentukan.');
            redirect('?controller=jurnalStruktural');
            exit;
        }


        $model = new JurnalStrukturalModel($this->db);

        // =====================
        // SIMPAN JURNAL UTAMA
        // =====================
        $dataJurnal = [
            'id_employe'        => $_SESSION['user']['id'],
            'id_tahun_pelajaran' => (int)$tahunAktif['id_tahun_pelajaran'],
            'tanggal'            => date('Y-m-d'),
            'catatan_akhir'      => $_POST['catatan_akhir'] ?? null,
            'created_at'         => date('Y-m-d H:i:s')
        ];


        $id_jurnal = $model->insertJurnal($dataJurnal);

        // =====================
        // SIMPAN DETAIL PROGRAM KERJA
        // =====================
        if (!empty($_POST['program'])) {
            foreach ($_POST['program'] as $row) {
                if (empty($row['id_program'])) {
                    continue;
                }

                $model->insertProgramKerja([
                    'id_jurnal'           => $id_jurnal,
                    'id_program'          => $row['id_program'],
                    'deskripsi_realisasi' => $row['deskripsi']
                ]);
            }
        }

        setFlash('success', 'Jurnal struktural berhasil disimpan.');
        redirect('?controller=jurnalStruktural&method=index');
        exit;
    }

    public function history()
    {
        $id_user = $_SESSION['user']['id'];

        $tahunModel = new TahunPelajaran($this->db);
        $tahunAktif = $tahunModel->getAktif();

        if (!$tahunAktif) {
            setFlash('warning', 'Tahun pelajaran aktif belum ditentukan.');
            redirect('?controller=jurnalStruktural');
            exit;
        }

        $model = new JurnalStrukturalModel($this->db);

        $jurnals = $model->getHistoryByUserAndTahun(
            $id_user,
            $tahunAktif['id_tahun_pelajaran']
        );

        require '../app/views/admin/jurnal_struktural/history.php';
    }


    public function edit()
    {
        if (!isset($_GET['id'])) {
            setFlash('danger', 'ID jurnal tidak ditemukan.');
            redirect('?controller=jurnalStruktural&method=history');
            exit;
        }

        $id_jurnal = (int) $_GET['id'];
        $id_user   = $_SESSION['user']['id'];

        $model = new JurnalStrukturalModel($this->db);

        $jurnal = $model->getById($id_jurnal);

        if (!$jurnal || $jurnal['id_employe'] != $id_user) {
            setFlash('danger', 'Anda tidak memiliki akses.');
            redirect('?controller=jurnalStruktural&method=history');
            exit;
        }

        // MASTER program kerja (dropdown)
        $programKerja = $model->getAllProgramKerja();

        // DETAIL jurnal
        $programList = $model->getProgramKerjaByJurnal($id_jurnal);

        require '../app/views/admin/jurnal_struktural/edit.php';
    }



    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('?controller=jurnalStruktural&method=history');
            exit;
        }

        $id_jurnal = (int) $_POST['id_jurnal'];
        $id_user   = $_SESSION['user']['id'];

        $model = new JurnalStrukturalModel($this->db);

        $jurnal = $model->getById($id_jurnal);

        if (!$jurnal || $jurnal['id_employe'] != $id_user) {
            setFlash('danger', 'Akses ditolak.');
            redirect('?controller=jurnalStruktural&method=history');
            exit;
        }

        // Update jurnal utama
        $model->updateJurnal([
            'id_jurnal'     => $id_jurnal,
            'catatan_akhir' => $_POST['catatan_akhir'] ?? null
        ]);

        // Reset detail program kerja
        $model->deleteProgramKerjaByJurnal($id_jurnal);

        if (!empty($_POST['program'])) {
            foreach ($_POST['program'] as $row) {
                if (empty($row['id_program'])) {
                    continue;
                }

                $model->insertProgramKerja([
                    'id_jurnal'           => $id_jurnal,
                    'id_program'          => $row['id_program'],
                    'deskripsi_realisasi' => $row['deskripsi_realisasi']
                ]);
            }
        }

        setFlash('success', 'Jurnal berhasil diperbarui.');
        redirect('?controller=jurnalStruktural&method=history');
        exit;
    }


    public function historyAdmin()
    {
        $model = new JurnalStrukturalModel($this->db);
        $tahunModel = new TahunPelajaran($this->db);
        $tahunAktif = $tahunModel->getAktif();

        if (!$tahunAktif) {
            setFlash('danger', 'Tahun pelajaran aktif belum ditentukan');
            redirect('?controller=dashboard');
            exit;
        }

        $tanggal = $_GET['tanggal'] ?? date('Y-m-d');

        $data = [
            'tahunAktif' => $tahunAktif,
            'tanggal'    => $tanggal,
            'jurnalList' => $model->getHistoryAdminHarian(
                $tahunAktif['id_tahun_pelajaran'],
                $tanggal
            )
        ];
        extract($data);
        require '../app/views/admin/jurnal_struktural/history_admin.php';
    }
}
