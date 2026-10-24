<?php
require_once __DIR__ . '/../models/SiswaKeluar.php';
require_once __DIR__ . '/BaseController.php';

/**
 * Halaman "PD Keluar": mengeluarkan siswa karena mengundurkan diri, mutasi,
 * meninggal, atau alasan lain — sekaligus mengembalikan statusnya bila salah.
 */
class SiswaKeluarController extends BaseController
{
    private $model;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->model = new SiswaKeluar($this->db);
    }

    public function index()
    {
        $id_level    = $_SESSION['user']['level'];
        $tahun_list  = $this->model->getAllTahun();
        $tahun_aktif = $this->model->getTahunAktifId();

        $filter = [
            'tahun'   => $_GET['tahun'] ?? '',
            'status'  => $_GET['status'] ?? '',
            'q'       => trim((string) ($_GET['q'] ?? '')),
            'riwayat' => isset($_GET['riwayat']),
        ];

        $riwayat     = $this->model->getRiwayat($filter);
        $alasan      = SiswaKeluar::daftarAlasan();
        $ringkasan   = $this->model->getRingkasan();
        $id_tahun_form = $filter['tahun'] !== '' ? (int) $filter['tahun'] : $tahun_aktif;
        $kelas_list  = $id_tahun_form ? $this->model->getKelasByTahun($id_tahun_form) : [];

        // Prefill dari tombol "Keluarkan" di halaman Data Siswa.
        $pra_siswa = null;
        $pra_id    = (int) ($_GET['id_siswa'] ?? 0);
        if ($pra_id) {
            $pra_siswa = $this->model->cariAktif($pra_id);
            if ($pra_siswa) {
                $id_tahun_form = $tahun_aktif;
                $kelas_list    = $this->model->getKelasByTahun($id_tahun_form);
            }
        }

        require __DIR__ . '/../views/admin/siswakeluar/index.php';
    }

    /** JSON: siswa aktif pada satu kelas/tahun (untuk dropdown). */
    public function siswa()
    {
        $id_kelas = (int) ($_GET['id_kelas'] ?? 0);
        $id_tahun = (int) ($_GET['id_tahun'] ?? 0);

        $data = [];
        if ($id_kelas && $id_tahun) {
            $data = $this->model->getSiswaByKelas($id_kelas, $id_tahun);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'success', 'data' => $data]);
        exit;
    }

    public function keluarkan()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=siswaKeluar&method=index');
            exit;
        }

        $hasil = $this->model->keluarkan(
            $_POST['id_siswa'] ?? 0,
            $_POST['status'] ?? '',
            $_POST['tanggal'] ?? '',
            $_POST['keterangan'] ?? '',
            $_POST['id_tahun'] ?? 0,
            $_SESSION['user']['nama'] ?? ($_SESSION['user']['username'] ?? '')
        );

        setFlash($hasil['ok'] ? 'success' : 'error', $hasil['pesan']);
        header('Location: index.php?controller=siswaKeluar&method=index');
        exit;
    }

    public function kembalikan()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?controller=siswaKeluar&method=index');
            exit;
        }

        $hasil = $this->model->kembalikan(
            $_POST['id_keluar'] ?? 0,
            $_SESSION['user']['nama'] ?? ($_SESSION['user']['username'] ?? '')
        );

        setFlash($hasil['ok'] ? 'success' : 'error', $hasil['pesan']);
        header('Location: index.php?controller=siswaKeluar&method=index' . (isset($_POST['riwayat']) ? '&riwayat=1' : ''));
        exit;
    }
}
