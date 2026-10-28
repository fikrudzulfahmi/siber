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

        $id_mapel = trim($_POST['id_mapel'] ?? '');
        $id_kelas = trim($_POST['id_kelas'] ?? '');
        $id_guru  = trim($_POST['id_guru'] ?? '');
        $id_tahun = trim($_POST['id_tahun_pelajaran'] ?? '');

        if ($id_tahun === '') {
            $tahun_aktif = $model->getActiveTahunPelajaran();
            $id_tahun = $tahun_aktif ? (string) $tahun_aktif['id_tahun_pelajaran'] : '';
        }

        if ($id_mapel === '' || $id_kelas === '' || $id_guru === '' || $id_tahun === '') {
            setFlash('danger', 'Kelas, guru, dan tahun pelajaran wajib dipilih.');
            header('Location: index.php?controller=mapel&method=create_guru&id=' . urlencode($id_mapel));
            exit;
        }

        if ($model->isDuplicateGuru($id_mapel, $id_kelas, $id_tahun, null, $id_guru)) {
            setFlash('danger', 'Guru tersebut sudah terdaftar untuk kelas ini pada mapel dan tahun pelajaran yang sama.');
            header('Location: index.php?controller=mapel&method=create_guru&id=' . urlencode($id_mapel));
            exit;
        }

        $model->insert_guru([
            'id_mapel' => $id_mapel,
            'id_kelas' => $id_kelas,
            'id_guru' => $id_guru,
            'id_tahun_pelajaran' => $id_tahun,
        ]);
        setFlash('success', 'Kelas & Guru berhasil ditambahkan.');
        header('Location: index.php?controller=mapel&method=guru&id=' . urlencode($id_mapel));
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
        $id_mapel      = trim($_POST['id_mapel'] ?? '');
        $id_mapel_guru = trim($_POST['id_mapel_guru'] ?? '');
        $id_kelas      = trim($_POST['id_kelas'] ?? '');
        $id_guru       = trim($_POST['id_guru'] ?? '');
        $model = new Mapel($this->db);

        $baris = $id_mapel_guru !== '' ? $model->findGuru($id_mapel_guru) : null;
        if (!$baris) {
            setFlash('danger', 'Data kelas & guru tidak ditemukan.');
            header('Location: index.php?controller=mapel&method=guru&id=' . urlencode($id_mapel));
            exit;
        }

        if ($id_kelas === '' || $id_guru === '') {
            setFlash('danger', 'Kelas dan guru wajib dipilih.');
            header('Location: index.php?controller=mapel&method=edit_guru&id=' . urlencode($id_mapel_guru));
            exit;
        }

        // Tahun pelajaran mengikuti baris yang diedit, bukan tahun aktif, supaya
        // mengedit data tahun lama tidak memindahkannya ke tahun berjalan.
        $id_tahun = trim($_POST['id_tahun_pelajaran'] ?? '');
        if ($id_tahun === '') {
            $id_tahun = (string) $baris['id_tahun_pelajaran'];
        }

        if ($model->isDuplicateGuru($baris['id_mapel'], $id_kelas, $id_tahun, $id_mapel_guru, $id_guru)) {
            setFlash('danger', 'Guru tersebut sudah terdaftar untuk kelas ini pada mapel dan tahun pelajaran yang sama.');
            header('Location: index.php?controller=mapel&method=edit_guru&id=' . urlencode($id_mapel_guru));
            exit;
        }

        $model->update_guru([
            'id_kelas' => $id_kelas,
            'id_guru' => $id_guru,
            'id_tahun_pelajaran' => $id_tahun,
            'id_mapel_guru' => $id_mapel_guru,
        ]);

        setFlash('success', 'Kelas & Guru berhasil diperbarui.');
        header('Location: index.php?controller=mapel&method=guru&id=' . urlencode($id_mapel !== '' ? $id_mapel : $baris['id_mapel']));
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
