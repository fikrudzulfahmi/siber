<?php
require_once __DIR__ . '/../models/Tp.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class TpController extends BaseController
{
    private $TpModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo); // sudah menyimpan ke $this->db
        $this->TpModel = new Tp($this->db);
    }

    public function index()
    {
        $id_employe = $_SESSION['user']['id'];
        $model = $this->TpModel;

        // 1. Ambil data tahun aktif
        $tahun_aktif_data = $model->getActiveTahunPelajaran();
        $id_tahun_aktif = $tahun_aktif_data['id_tahun_pelajaran'];
        $nama_tahun = $tahun_aktif_data['tahun_pelajaran'];
        $semester = $tahun_aktif_data['semester'];

        // 2. Kirim id_tahun ke model
        $kelasList1 = $model->getAllKelasByGuru($id_employe, $id_tahun_aktif);

        require __DIR__ . '/../views/admin/tp/index.php';
    }

    public function tujuan()
    { {
            $id_mapel_guru = $_GET['id_mapel_guru'] ?? null;
            if (!$id_mapel_guru) die('ID mapel guru tidak ditemukan.');

            $model = new Tp($this->db);
            $info = $model->getInfoMapelGuru($id_mapel_guru);  // data nama mapel dan kelas
            $tujuanList = $model->getByMapelGuru($id_mapel_guru); // daftar kategori
            require __DIR__ . '/../views/admin/tp/tujuan.php';
        }
    }

    public function create()
    {
        $id_mapel_guru = $_GET['id_mapel_guru'] ?? null;
        if (!$id_mapel_guru) {
            die('ID Mapel Guru tidak ditemukan.');
        }

        $model = $this->TpModel;
        $info = $model->getInfoMapelGuru($id_mapel_guru);

        // AMBIL DATA TAHUN AKTIF UNTUK HEADER
        $tahun_aktif = $model->getActiveTahunPelajaran();

        require __DIR__ . '/../views/admin/tp/create.php';
    }


    public function store()
    {
        $id_mapel_guru = $_POST['id_mapel_guru'] ?? null;
        $model = new Tp($this->db);
        $model->insert($_POST);
        setFlash('success', 'Mata pelajaran berhasil ditambahkan.');
        header('Location: index.php?controller=tp&method=tujuan&id_mapel_guru=' . $id_mapel_guru);
    }

    public function edit()
    {
        $model = $this->TpModel;
        $tp = $model->find($_GET['id']);

        // AMBIL DATA TAHUN AKTIF UNTUK HEADER
        $tahun_aktif = $model->getActiveTahunPelajaran();

        require __DIR__ . '/../views/admin/tp/edit.php';
    }

    public function update()
    {
        $id_mapel_guru = $_POST['id_mapel_guru'] ?? null;
        $model = new Tp($this->db);
        $model->update($_POST);
        setFlash('success', 'Nilai berhasil diperbarui.');
        header('Location: index.php?controller=tp&method=tujuan&id_mapel_guru=' . $id_mapel_guru);
    }

    public function delete()
    {
        $id_mapel_guru = $_GET['id_mapel_guru'] ?? null;
        $model = new Tp($this->db); // ✅ fix: sebelumnya salah pakai User
        $model->delete($_GET['id']);
        setFlash('success', 'Mata pelajaran berhasil dihapus.');
        header('Location: index.php?controller=tp&method=tujuan&id_mapel_guru=' . $id_mapel_guru);
    }


    public function rekaptp()
    {
        $id_guru_terpilih = $_POST['id_guru'] ?? 'semua';
        $model = $this->TpModel;

        $tahun_aktif = $model->getActiveTahunPelajaran();
        $id_tahun = $tahun_aktif['id_tahun_pelajaran'];

        // PASTIKAN URUTANNYA: $id_tahun dulu, baru $id_guru_terpilih
        $data['rekap_tp'] = $model->getRekapTp($id_tahun, $id_guru_terpilih);

        $data['daftar_guru'] = $model->getGuruForFilter($id_tahun);
        $data['id_guru_terpilih'] = $id_guru_terpilih;
        $data['tahun_info'] = $tahun_aktif;

        extract($data);
        require __DIR__ . '/../views/admin/jurnal/rekaptp.php';
    }

    // Method untuk menangani permintaan detail TP (AJAX)
    public function getDetail()
    {
        header('Content-Type: application/json');

        $id_mapel_guru = $_GET['id'] ?? 0;

        $tpModel = new Tp($this->db);
        $detailTPs = $tpModel->getDetailTPById($id_mapel_guru);

        echo json_encode($detailTPs);
    }
}
