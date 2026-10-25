<?php
/**
 * Urutkan siswa per kelas (drag & drop).
 * Hak akses: admin (1), walikelas (3, hanya kelasnya), waka kurikulum (5),
 * waka kesiswaan (6).
 */

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/UrutanSiswaModel.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';

class UrutkanSiswaController extends BaseController
{
    private $model;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->model = new UrutanSiswaModel($this->db);
    }

    public function index()
    {
        $semuaKelas = sec_has_level([1, 5, 6]);
        $id_employe = (int) ($_SESSION['user']['id'] ?? 0);

        $daftar_kelas = $semuaKelas
            ? $this->model->getKelasSemua()
            : $this->model->getKelasWalas($id_employe);

        if (!$daftar_kelas) {
            setFlash('warning', 'Menu ini untuk walikelas, waka kurikulum, waka kesiswaan, dan admin. Akun Anda belum terdaftar sebagai walikelas.');
            header('Location: ?controller=dashboard&method=index');
            exit;
        }

        $daftar_tahun = $this->model->getSemuaTahun();
        $id_tahun = (int) ($_GET['id_tahun'] ?? 0);
        if ($id_tahun <= 0) {
            $id_tahun = $this->model->getTahunAktif();
        }

        $id_kelas = (int) ($_GET['id_kelas'] ?? 0);
        $kelas = null;
        foreach ($daftar_kelas as $dk) {
            if ($id_kelas > 0 && (int) $dk['id_kelas'] === $id_kelas) {
                $kelas = $dk;
                break;
            }
        }
        if (!$kelas) {
            $kelas = $daftar_kelas[0];
        }

        $data = [
            'title' => 'Urutkan Siswa',
            'daftar_kelas' => $daftar_kelas,
            'daftar_tahun' => $daftar_tahun,
            'kelas' => $kelas,
            'id_kelas' => (int) $kelas['id_kelas'],
            'id_tahun' => $id_tahun,
            'siswa' => $this->model->getSiswa((int) $kelas['id_kelas'], $id_tahun),
            'bisa_semua_kelas' => $semuaKelas,
            'boleh_ubah' => true,
        ];

        $this->view('admin/urutkanSiswa/index', $data);
    }

    public function simpan()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['ok' => false, 'error' => 'Metode tidak didukung.'], 405);
        }

        $id_kelas = (int) ($_POST['id_kelas'] ?? 0);
        $id_tahun = (int) ($_POST['id_tahun'] ?? 0);
        $urutan = $_POST['urutan'] ?? [];
        if (!is_array($urutan)) {
            $urutan = [];
        }

        if (!$this->bolehKelas($id_kelas)) {
            return $this->json(['ok' => false, 'error' => 'Anda tidak berhak mengubah urutan kelas ini.'], 403);
        }
        if ($id_tahun <= 0 || !$this->model->getKelas($id_kelas)) {
            return $this->json(['ok' => false, 'error' => 'Kelas atau tahun pelajaran tidak valid.'], 422);
        }

        try {
            $jumlah = $this->model->simpan($id_kelas, $id_tahun, $urutan);
        } catch (Throwable $e) {
            return $this->json(['ok' => false, 'error' => 'Urutan gagal disimpan. Coba lagi.'], 500);
        }

        return $this->json([
            'ok' => true,
            'jumlah' => $jumlah,
            'pesan' => 'Urutan ' . $jumlah . ' siswa berhasil disimpan.',
        ]);
    }

    public function reset()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->json(['ok' => false, 'error' => 'Metode tidak didukung.'], 405);
        }

        $id_kelas = (int) ($_POST['id_kelas'] ?? 0);
        $id_tahun = (int) ($_POST['id_tahun'] ?? 0);

        if (!$this->bolehKelas($id_kelas)) {
            return $this->json(['ok' => false, 'error' => 'Anda tidak berhak mengubah urutan kelas ini.'], 403);
        }
        if ($id_tahun <= 0) {
            return $this->json(['ok' => false, 'error' => 'Tahun pelajaran tidak valid.'], 422);
        }

        try {
            $jumlah = $this->model->reset($id_kelas, $id_tahun);
        } catch (Throwable $e) {
            return $this->json(['ok' => false, 'error' => 'Urutan gagal direset. Coba lagi.'], 500);
        }

        return $this->json([
            'ok' => true,
            'jumlah' => $jumlah,
            'pesan' => 'Urutan dikembalikan ke abjad.',
        ]);
    }

    /** Admin/waka boleh semua kelas; walikelas hanya kelas yang diampu. */
    private function bolehKelas($id_kelas)
    {
        if ($id_kelas <= 0) {
            return false;
        }
        $kelas = $this->model->getKelas($id_kelas);
        if (!$kelas) {
            return false;
        }
        if (sec_has_level([1, 5, 6])) {
            return true;
        }

        return (int) $kelas['wali_kelas'] === (int) ($_SESSION['user']['id'] ?? 0);
    }

    private function json(array $payload, $status = 200)
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
