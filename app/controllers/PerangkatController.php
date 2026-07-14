<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';
require_once '../app/models/TahunPelajaran.php';
require_once '../app/models/Perangkat.php';
require_once '../app/models/DeadlinePerangkat.php';
require_once '../app/models/JenisPerangkat.php'; // pastikan include model ini

class PerangkatController extends BaseController
{
public function index()
{
    $tahunModel = new TahunPelajaran($this->db);
    $perangkatModel = new Perangkat($this->db);
    $deadlineModel = new DeadlinePerangkat($this->db);
    $jenisPerangkatModel = new JenisPerangkat($this->db);

    $tahun_aktif = $tahunModel->getAktif();
    $id_tahun = $tahun_aktif['id_tahun_pelajaran'] ?? null;
    $id_user = $_SESSION['user']['id'];

    // Ambil mapel yang diampu guru
    $mapel_guru = $perangkatModel->getByGuru($id_user);

    // Tangkap filter mapel-kelas
    $id_mapel_guru = $_GET['id_mapel_guru'] ?? null;

    // Ambil perangkat berdasarkan tahun & mapel_guru (jika ada)
    if ($id_mapel_guru) {
        $perangkat = $perangkatModel->getWithDeadline($id_user, $id_tahun, $id_mapel_guru);
    } else {
        $perangkat = []; // kosong kalau belum pilih
    }

    // Ambil deadline & jenis perangkat
    $deadline = $deadlineModel->getByTahun($id_tahun);
    $jenis_perangkat_data = $jenisPerangkatModel->getAll();
    $jenis_perangkat = array_column($jenis_perangkat_data, 'nama');

    // Kirim ke view
    $this->view('admin/perangkat/index', [
        'perangkat' => $perangkat,
        'deadline' => $deadline,
        'tahun_aktif' => $tahun_aktif,
        'jenis_perangkat' => $jenis_perangkat,
        'mapel_guru' => $mapel_guru,
        'id_mapel_guru' => $id_mapel_guru
    ]);
}


public function upload()
{
    $perangkatModel = $this->model('Perangkat');

    $id_employe = $_SESSION['user']['id'];
    $id_tahun = $_POST['id_tahun_pelajaran'] ?? null;
    $id_mapel_guru = $_POST['id_mapel_guru'] ?? null;
$tingkat1 = $_POST['tingkat'] ?? null;

    // ✅ Validasi field wajib
    if (!$id_tahun || !$id_mapel_guru) {
        setFlash('error', 'Tahun pelajaran dan mapel harus dipilih.');
        header('Location: index.php?controller=perangkat&method=index');
        exit;
    }

    if (!isset($_FILES['perangkat'])) {
        setFlash('error', 'Tidak ada file yang diupload.');
        header('Location: index.php?controller=perangkat&method=index');
        exit;
    }

    $files = $_FILES['perangkat'];
    $nama_user = str_replace(' ', '', strtolower($_SESSION['user']['nama']));
    $upload_success = true;
    $messages = [];

foreach ($files['name'] as $jenis => $fileName) {
    if ($files['error'][$jenis] === UPLOAD_ERR_NO_FILE) continue;

    if ($files['error'][$jenis] !== UPLOAD_ERR_OK) {
        $upload_success = false;
        $messages[] = "Error upload file untuk $jenis.";
        continue;
    }

    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    $allowed_extensions = ['pdf', 'doc', 'docx'];
    $max_file_size = 2 * 1024 * 1024; // 2MB

    if (!in_array(strtolower($ext), $allowed_extensions)) {
        $upload_success = false;
        $messages[] = "Jenis file tidak didukung untuk $jenis.";
        continue;
    }

    if ($files['size'][$jenis] > $max_file_size) {
        $upload_success = false;
        $messages[] = "Ukuran file terlalu besar untuk $jenis.";
        continue;
    }

    // 🔍 Ambil nama mapel dan kelas dari id_mapel_guru
$stmt = $this->db->prepare("SELECT m.nama_mapel, k.tingkat 
                            FROM mapel_guru mg
                            JOIN mapel m ON mg.id_mapel = m.id_mapel
                            JOIN kelas k ON mg.id_kelas = k.id_kelas
                            WHERE mg.id_mapel_guru = ?");
$stmt->execute([$id_mapel_guru]);
$mgInfo = $stmt->fetch(PDO::FETCH_ASSOC);

$mapel = $mgInfo['nama_mapel'] ?? 'mapel';
$tingkat = $mgInfo['tingkat'] ?? 'tingkat';


    // 🔤 Format nama file
    $formatSlug = function($text) {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $text), '-'));
    };

    $nama_user = $formatSlug($_SESSION['user']['nama']);
    $mapel_slug = $formatSlug($mapel);
    $tingkat_slug = $formatSlug($tingkat);
    $jenis_slug = $formatSlug($jenis);

    $newFileName = "{$jenis_slug}_{$nama_user}_{$mapel_slug}_{$tingkat_slug}." . strtolower($ext);
    $target = 'uploads/perangkat/' . $newFileName;

    if (move_uploaded_file($files['tmp_name'][$jenis], $target)) {
        $perangkatModel->insertOrUpdate([
            'id_employe' => $id_employe,
            'id_tahun_pelajaran' => $id_tahun,
            'id_mapel_guru' => $id_mapel_guru,
            'tingkat' => $tingkat1,
            'jenis_perangkat' => $jenis,
            'file' => $newFileName
        ]);
        $messages[] = "File $jenis berhasil diupload.";
    } else {
        $upload_success = false;
        $messages[] = "Gagal upload file $jenis.";
    }
}


    $message = implode(' ', $messages);
    setFlash($upload_success ? 'success' : 'error', $message);
    header('Location: index.php?controller=perangkat&method=index');
    exit;
}
public function ajaxForm()
{
    $id_mapel_guru = $_GET['id_mapel_guru'] ?? null;
    $id_user = $_SESSION['user']['id'];
    $tahunModel = new TahunPelajaran($this->db);
    $perangkatModel = new Perangkat($this->db);
    $jenisPerangkatModel = new JenisPerangkat($this->db);
    $deadlineModel = new DeadlinePerangkat($this->db);
    $tingkat = $_GET['tingkat'] ?? null;



    $tahun_aktif = $tahunModel->getAktif();
    $id_tahun = $tahun_aktif['id_tahun_pelajaran'] ?? null;

    $perangkat = $perangkatModel->getWithDeadline($id_user, $id_tahun, $id_mapel_guru, $tingkat);
    $jenis_perangkat_data = $jenisPerangkatModel->getAll();
    $jenis_perangkat = array_column($jenis_perangkat_data, 'nama');
    $deadline = $deadlineModel->getByTahun($id_tahun);

    $this->view('admin/perangkat/form_ajax', [
        'perangkat' => $perangkat,
        'jenis_perangkat' => $jenis_perangkat,
        'deadline' => $deadline,
        'id_tahun' => $id_tahun,
        'id_mapel_guru' => $id_mapel_guru,
        'tingkat' => $tingkat
    ]);
}

public function updateApproval()
{
    $id_upload = $_POST['id_upload'];
    $status = $_POST['status_approval'];

    $perangkatModel = new Perangkat($this->db);
    $perangkatModel->updateApprovalStatus($id_upload, $status);

    echo json_encode(['success' => true]);
}
public function listKurikulum()
{
    $tahunModel = new TahunPelajaran($this->db);
    $perangkatModel = new Perangkat($this->db);
    $jenisPerangkatModel = new JenisPerangkat($this->db);

    $tahun_aktif = $tahunModel->getAktif();
    $id_tahun_pelajaran = $tahun_aktif ? $tahun_aktif['id_tahun_pelajaran'] : null;

    $data['judul'] = 'Daftar Perangkat Guru';
    $data['tahun'] = $tahun_aktif;
    $data['jenis_perangkat'] = $jenisPerangkatModel->all();
    $data['list_perangkat'] = $perangkatModel->getAllWithGuruAndMapel($id_tahun_pelajaran);

    $this->view('perangkat/list_kurikulum', $data);
}


}