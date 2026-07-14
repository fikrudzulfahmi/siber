<?php
require_once __DIR__ . '/../models/Mapel.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class MapelController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo); // ⛑️ authGuard aktif di BaseController
    }

    public function index()
    {
        $model = new Mapel($this->db);
        $mapel = $model->getAll();

        require __DIR__ . '/../views/admin/mapel/index.php';
    }

    public function create()
    {
        require __DIR__ . '/../views/admin/mapel/create.php';
    }

    public function create_guru()
    {
        $model = new Mapel($this->db);
        $tahun_aktif = $model->getActiveTahunPelajaran(); // Ambil tahun aktif

        $mapel = $model->find($_GET['id']);
        $tingkat = $mapel['tingkat_mapel'];
        $kelas = $model->getByTingkat($tingkat);
        $users = $this->db->query("SELECT id_employe, nama FROM employe")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin/mapel/create_guru.php';
    }

    public function guru()
    {
        $id_mapel = $_GET['id'] ?? null;
        $model = new Mapel($this->db);

        // Sekarang variabel ini berisi array: ['id_tahun_pelajaran' => 2, 'tahun_pelajaran' => '2025/2026', 'semester' => 'Genap', ...]
        $tahun_aktif = $model->getActiveTahunPelajaran();

        if (!$id_mapel) {
            die("ID mapel tidak tersedia.");
        }

        $mapel = $model->find($id_mapel);

        // Kirim ID-nya saja ke model Guru untuk filter query
        $guruList = $model->Guru($id_mapel, $tahun_aktif['id_tahun_pelajaran']);

        $users = $this->db->query("SELECT id_employe, nama FROM employe")->fetchAll(PDO::FETCH_ASSOC);
        $kelas = $this->db->query("SELECT id_kelas, kelas FROM kelas")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin/mapel/guru.php';
    }

    public function store()
    {
        $model = new Mapel($this->db);
        $model->insert($_POST);
        setFlash('success', 'Mata pelajaran berhasil ditambahkan.');
        header('Location: index.php?controller=mapel&method=index');
    }
    public function store_guru()
    {
        $model = new Mapel($this->db);

        // // Paksa id_tahun_pelajaran mengambil dari database yang statusnya 'Aktif'
        // $_POST['id_tahun_pelajaran'] = $model->getActiveTahunPelajaran();

        // if (!$_POST['id_tahun_pelajaran']) {
        //     die("Tidak ada Tahun Pelajaran yang Aktif di database!");
        // }
        // var_dump($_POST);
        // die();
        $model->insert_guru($_POST);
        setFlash('success', 'Kelas & Guru berhasil ditambahkan.');
        header('Location: index.php?controller=mapel&method=guru&id=' . urlencode($_POST['id_mapel']));
        exit;
    }

    public function edit()
    {
        $model = new Mapel($this->db);
        $mapel = $model->find($_GET['id']);
        $users = $this->db->query("SELECT id_employe, nama FROM employe ")->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/admin/mapel/edit.php';
    }

    public function edit_guru()
    {
        $id_mapel_guru = $_GET['id'] ?? null;
        if (!$id_mapel_guru) {
            die('ID mapel guru tidak ditemukan.');
        }

        $model = new Mapel($this->db);

        // Ambil data tahun aktif dari database
        $tahun_data = $model->getActiveTahunPelajaran();

        // Set variabel sesuai nama yang Anda inginkan
        $id_tahun_aktif = $tahun_data['id_tahun_pelajaran'];
        $nama_tahun = $tahun_data['tahun_pelajaran']; // Untuk teks di header
        $semester = $tahun_data['semester'];           // Untuk teks di header

        $data = $model->findGuru($id_mapel_guru);
        $mapel = $model->find_mapel_by_mapel_guru($id_mapel_guru);

        if (!$data) {
            die('Data tidak ditemukan.');
        }

        $users = $this->db->query("SELECT id_employe, nama FROM employe")->fetchAll(PDO::FETCH_ASSOC);
        $kelas = $this->db->query("SELECT id_kelas, kelas FROM kelas")->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin/mapel/edit_guru.php';
    }

    public function update()
    {
        $model = new Mapel($this->db);
        $model->update($_POST);
        setFlash('success', 'Mata pelajaran berhasil diperbarui.');
        header('Location: index.php?controller=mapel&method=index');
    }
    public function update_guru()
    {
        $id_mapel = trim($_POST['id_mapel'] ?? '');
        $model = new Mapel($this->db);

        // Ambil ID tahun aktif langsung sebelum update untuk memastikan validitas
        $tahun_aktif = $model->getActiveTahunPelajaran();
        $_POST['id_tahun_pelajaran'] = $tahun_aktif['id_tahun_pelajaran'];

        var_dump($data);
        die();

        $model->update_guru($_POST);

        setFlash('success', 'Kelas & Guru berhasil diperbarui.');
        header('Location: index.php?controller=mapel&method=guru&id=' . urlencode($id_mapel));
        exit;
    }

    public function delete()
    {
        $model = new Mapel($this->db); // ✅ fix: sebelumnya salah pakai User
        $model->delete($_GET['id']);
        setFlash('success', 'Mata pelajaran berhasil dihapus.');
        header('Location: index.php?controller=mapel&method=index');
    }

    public function delete_guru()
    {
        $id_mapel = $_GET['id_mapel'] ?? null;
        $id_guru = $_GET['id'] ?? null;

        if (!$id_guru || !$id_mapel) {
            die("ID tidak lengkap.");
        }

        $model = new Mapel($this->db);
        $model->delete_guru($id_guru);

        setFlash('success', 'Guru berhasil dihapus.');
        header("Location: index.php?controller=mapel&method=guru&id=$id_mapel");
        exit;
    }
}
