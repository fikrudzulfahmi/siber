<?php
require_once 'BaseController.php';
require_once '../app/models/TahunPelajaran.php';
require_once '../app/models/ProgramStruktural.php';
require_once '../app/models/JenisProgramStruktural.php';
require_once '../app/models/DeadlineProgramStruktural.php';

class ProgramStrukturalController extends BaseController
{
    private $allowedLevels = [5,6,10,11,12,13,14,15,16];

    private function getUserLevels()
    {
        if (!isset($_SESSION['user']['level'])) return [];
        $decoded = base64_decode($_SESSION['user']['level']);
        $userLevels = array_map('intval', explode(',', $decoded));
        return array_values(array_intersect($userLevels, $this->allowedLevels));
    }

    private function checkAccess()
    {
        return count($this->getUserLevels()) > 0;
    }

public function index()
{
    if (!$this->checkAccess()) {
        setFlash('error', 'Anda tidak memiliki akses ke Program Struktural');
        header('Location: index.php');
        exit;
    }

    $tahunModel   = new TahunPelajaran($this->db);
    $programModel = new ProgramStruktural($this->db);
    $jenisModel   = new JenisProgramStruktural($this->db);
    $deadlineModel = new DeadlineProgramStruktural($this->db);

    $tahun = $tahunModel->getAktif();
    $id_tahun = $tahun['id_tahun_pelajaran'] ?? null;
    $id_user  = $_SESSION['user']['id'];

    $program = $programModel->getByUser($id_user, $id_tahun);
    $jenis_program = array_column($jenisModel->getAll(), 'nama');

    /* Ambil Deadline */
    $deadlines = [];
    if ($id_tahun) {
        $rows = $deadlineModel->getByTahun($id_tahun);
        foreach ($rows as $d) {
            $deadlines[$d['jenis_program']] = $d['tanggal_deadline'];
        }
    }

    // ================================
    // Hitung level yang diperbolehkan
    // ================================
    $allowedLevels = [5,6,10,11,12,13,14,15,16];
    $userLevelsEncoded = $_SESSION['user']['level'] ?? '';
    $filteredLevels = [];

    if (!empty($userLevelsEncoded)) {
        $decoded = base64_decode($userLevelsEncoded);
        $allLevels = array_map('intval', explode(',', $decoded));
        $filteredLevels = array_values(array_intersect($allLevels, $allowedLevels));
    }

    $levelText = '';
    if (!empty($filteredLevels)) {
        $levelText = levelDisplay($filteredLevels);
    }

    $namaUser = $_SESSION['user']['nama'] ?? 'User';

    $this->view('admin/program_struktural/index', [
        'tahun' => $tahun,
        'program' => $program,
        'jenis_program' => $jenis_program,
        'deadlines' => $deadlines,
        'levelText' => $levelText,
        'namaUser' => $namaUser
    ]);
}


    public function upload()
    {
        if (!$this->checkAccess()) {
            setFlash('error', 'Anda tidak memiliki izin upload Program Struktural');
            header('Location: index.php');
            exit;
        }

        if (!isset($_FILES['program'])) {
            setFlash('error', 'Tidak ada file diupload');
            header('Location: index.php?controller=programStruktural&method=index');
            exit;
        }

        $programModel = new ProgramStruktural($this->db);

        $id_user   = $_SESSION['user']['id'];
        $nama_user = $_SESSION['user']['nama'];
        $id_tahun  = $_POST['id_tahun_pelajaran'] ?? null;

        if (!$id_tahun) {
            setFlash('error', 'Tahun pelajaran tidak valid');
            header('Location: index.php?controller=programStruktural&method=index');
            exit;
        }

        $files = $_FILES['program'];
        $messages = [];
        $success = true;

        $slug = fn($t) => strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $t), '-'));

        foreach ($files['name'] as $jenis => $fileName) {
            if ($files['error'][$jenis] === UPLOAD_ERR_NO_FILE) continue;

            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf','doc','docx'])) {
                $success = false;
                $messages[] = "File $jenis tidak valid";
                continue;
            }

            $newName = $slug($jenis) . '_' . $id_tahun . '_' . $slug($nama_user) . '.' . $ext;

            $dir = 'uploads/program_struktural/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            if (move_uploaded_file($files['tmp_name'][$jenis], $dir.$newName)) {
                $programModel->insertOrUpdate([
                    'id_employe' => $id_user,
                    'id_tahun_pelajaran' => $id_tahun,
                    'user_level' => implode(',', $this->getUserLevels()),
                    'jenis_program' => $jenis,
                    'file' => $newName
                ]);
                $messages[] = "File $jenis berhasil diupload";
            } else {
                $success = false;
                $messages[] = "File $jenis gagal diupload";
            }
        }

        setFlash($success ? 'success' : 'error', implode('<br>', $messages));
        header('Location: index.php?controller=programStruktural&method=index');
        exit;
    }
}
