<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';
require_once __DIR__ . '/../models/JadwalModel.php'; // Pastikan nama file model sesuai

class JadwalController extends BaseController // Pastikan extends BaseController jika pakai $this->db atau setFlash
{
    private $model;

    public function __construct($pdo)
    {
        // Jika BaseController punya constructor, panggil parent
        // parent::__construct($pdo); 
        
        $this->model = new JadwalModel($pdo);
    }

   public function index()
{
    // 1. Ganti getAllKelas() jadi getKelas() sesuai nama fungsi Anda
    $kelasList = $this->model->getKelas(); 

    if (empty($kelasList)) {
        die("Data kelas kosong.");
    }

    $id_kelas = $_GET['id_kelas'] ?? $kelasList[0]['id_kelas'];

    // 2. AMBIL TAHUN AKTIF
    $tahun_aktif = $this->model->getActiveTahunPelajaran();
    $id_tahun    = $tahun_aktif['id_tahun_pelajaran'];

    // 3. KIRIM ID TAHUN KE MODEL
    // (Sebelumnya Anda mungkin hanya mengirim $id_kelas saja)
    $jadwal = $this->model->getJadwalByKelas($id_kelas, $id_tahun);

    require __DIR__ . '/../views/admin/jadwal/index.php';
}

    public function create()
    {
        // PERBAIKAN: Gunakan $this->model (sesuai constructor)
        $model = $this->model; 

        $tahun_aktif = $model->getActiveTahunPelajaran();
    
        if (!$tahun_aktif) {
            setFlash('danger', 'Tidak ada Tahun Pelajaran yang berstatus Aktif.');
            header('Location: index.php?controller=dashboard');
            exit;
        }

        $kelasList = $model->getkelas();

        $id_kelas_terpilih = $_GET['id_kelas'] ?? null;
        $hari_terpilih     = $_GET['hari'] ?? null;

        require __DIR__ . '/../views/admin/jadwal/create.php';
    }

    // --- AJAX METHODS ---

    public function getMapelGuruByKelas()
    {
        header('Content-Type: application/json; charset=utf-8');
        $id_kelas = $_GET['id_kelas'] ?? null;

        if (!$id_kelas) {
            echo json_encode([]);
            exit;
        }

        // PERBAIKAN: Ambil tahun aktif dulu
        $tahun_aktif = $this->model->getActiveTahunPelajaran();
        $id_tahun    = $tahun_aktif['id_tahun_pelajaran'];

        // Kirim tahun ke model
        $data = $this->model->getMapelGuruByKelas($id_kelas, $id_tahun);
        
        echo json_encode($data);
        exit;
    }

    public function getJamTerpakaiByKelasDanHari()
    {
        header('Content-Type: application/json; charset=utf-8');
        $id_kelas = $_GET['id_kelas'] ?? null;
        $hari     = $_GET['hari'] ?? null;

        if (!$id_kelas || !$hari) {
            echo json_encode([]);
            exit;
        }

        // PERBAIKAN: Ambil tahun aktif dulu
        $tahun_aktif = $this->model->getActiveTahunPelajaran();
        $id_tahun    = $tahun_aktif['id_tahun_pelajaran'];

        // Kirim tahun ke model
        $data = $this->model->getJamTerpakai($id_kelas, $hari, $id_tahun);
        
        echo json_encode(array_values($data));
        exit;
    }

    // --- STORE METHOD ---

    public function store()
    {
        // PERBAIKAN: Definisikan $model dari properti class
        $model = $this->model; 

        $tahun_aktif_data = $model->getActiveTahunPelajaran();
        $id_tahun_aktif   = $tahun_aktif_data['id_tahun_pelajaran'];

        $id_mapel_guru = $_POST['id_mapel_guru'];
        $id_kelas      = $_POST['id_kelas'];
        $hari          = $_POST['hari'];
        $jam_mulai     = $_POST['jam_mulai'];
        $jam_selesai   = $_POST['jam_selesai'];

        // Validasi Logic
        if ($jam_mulai > $jam_selesai) {
            setFlash('danger', 'Jam mulai tidak boleh lebih besar dari jam selesai.');
            header('Location: index.php?controller=jadwal&method=create&id_kelas=' . $id_kelas . '&hari=' . $hari);
            exit;
        }

        // Validasi Bentrok
        $isBentrok = $model->cekJadwalBentrok($id_kelas, $hari, $jam_mulai, $jam_selesai, $id_tahun_aktif);
        
        if ($isBentrok) {
            setFlash('danger', 'Jadwal bentrok! Jam tersebut sudah terisi.');
            header('Location: index.php?controller=jadwal&method=create&id_kelas=' . $id_kelas . '&hari=' . $hari);
            exit;
        }

        $data = [
            'id_mapel_guru'      => $id_mapel_guru,
            'id_tahun_pelajaran' => $id_tahun_aktif,
            'hari'               => $hari,
            'jam_mulai'          => $jam_mulai,
            'jam_selesai'        => $jam_selesai
        ];

        try {
            $model->insertJadwal($data);
            setFlash('success', 'Jadwal berhasil disimpan.');
            // Redirect balik ke index dengan filter kelas yang sama agar user nyaman
            header('Location: index.php?controller=jadwal&method=index&id_kelas=' . $id_kelas);
        } catch (Exception $e) {
            setFlash('danger', 'Gagal menyimpan: ' . $e->getMessage());
            header('Location: index.php?controller=jadwal&method=create');
        }
    }

    public function delete()
    {
        // PERBAIKAN: Gunakan $this->model
        $model = $this->model;

        $id = $_GET['id'] ?? null;
        $id_kelas = $_GET['id_kelas'] ?? ''; // Default empty string agar tidak error di URL
        
        if ($id) {
             // Pastikan nama method di model adalah 'delete' atau 'deleteJadwal' (sesuaikan dengan Model)
            $model->deleteJadwal($id); 
            setFlash('success', 'Jadwal berhasil dihapus.');
        } else {
            setFlash('danger', 'ID Jadwal tidak ditemukan.');
        }

        header("Location: index.php?controller=jadwal&method=index&id_kelas=" . $id_kelas);
        exit;
    }
}