<?php
// ✅ TAMBAHKAN 2 BARIS INI DI PALING ATAS
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'BaseController.php';
require_once __DIR__ . '/../models/Konseling.php';
require_once __DIR__ . '/../vendor/autoload.php'; // pastikan autoload DomPDF

use Dompdf\Dompdf;
use Dompdf\Options;

class RekonController extends BaseController
{
    private $model;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->model = new Konseling($pdo);
        authGuard(); // Melindungi seluruh controller
    }

    public function index()
    {
        // 1. Ambil data user dari session
        $user = $_SESSION['user'];
        $id_kelas_filter = null; // Filter untuk wali kelas
        $id_user_filter = null;  // Filter ID user (untuk model)

        // 2. ✅ LOGIKA HAK AKSES BARU
        // Cek apakah user adalah Wali Kelas (level 3) dan bukan pimpinan
        if (isLevel($user['level'], 3) && !isAnyLevel($user['level'], [1, 5, 6, 7])) {
            // Untuk Wali Kelas, kita akan memfilter berdasarkan id_employe mereka
            $id_user_filter = $user['id'];
        }

        $tpAktif = $this->model->getAktifTahunPelajaran();
        if (!$tpAktif) {
            // Tangani kasus jika tidak ada tahun pelajaran aktif
            die("Error: Tidak ada Tahun Pelajaran yang aktif. Silakan atur di administrasi.");
        }
        $range = $this->model->getRangeByTahunPelajaran($tpAktif);

        // 3. Panggil method rekap dengan membawa info user untuk filter
        // Catatan: Model Anda sudah dirancang untuk menerima id_level=3 dan id_user
        $kategoriData = $this->model->rekapByKategori($range['start'], $range['end'], (isLevel($user['level'], 3) ? 3 : null), $id_user_filter);
        $kelasData = $this->model->rekapByKelas($range['start'], $range['end'], (isLevel($user['level'], 3) ? 3 : null), $id_user_filter);
        $bulanData = $this->model->rekapByBulan($range['start'], $range['end'], (isLevel($user['level'], 3) ? 3 : null), $id_user_filter);

        $tahunPelajaran = $this->model->getAllTahunPelajaran();
        $kategoriList = $this->model->getKategoriList();

        // 4. Filter daftar kelas untuk dropdown filter cetak
        if ($id_user_filter !== null) {
            // Jika wali kelas, dropdown hanya berisi kelasnya sendiri
            $kelasList = $this->model->getKelasWalikelas($user['id']);
        } else {
            // Jika admin/pimpinan, tampilkan semua kelas
            $kelasList = $this->model->getKelasList();
        }

        view('admin/rekon/index', compact('tpAktif', 'kategoriData', 'kelasData', 'bulanData', 'tahunPelajaran', 'kategoriList', 'kelasList'));
    }

    public function getSiswa()
    {
        $user = $_SESSION['user'];
        $siswa = [];

        // ✅ LOGIKA YANG DISEMPURNAKAN
        // Cek apakah user adalah Wali Kelas (level 3) dan bukan pimpinan
        if (isLevel($user['level'], 3) && !isAnyLevel($user['level'], [1, 5, 6, 7])) {
            // Jika Wali Kelas, langsung ambil siswa dari kelas yang diampu (ID user = ID employe)
            $siswa = $this->model->getSiswaByWaliKelas($user['id']);
        } else {
            // Jika Admin atau pimpinan, ambil siswa berdasarkan id_kelas dari request AJAX
            if (isset($_GET['id_kelas'])) {
                $siswa = $this->model->getSiswaByKelas($_GET['id_kelas']);
            }
        }

        header('Content-Type: application/json');
        echo json_encode($siswa);
        exit();
    }

    public function cetak()
    {
        $user = $_SESSION['user'];
        $id_user_filter = null;
        $id_level_filter = null; // Defaultnya tidak ada level spesifik

        if (isLevel($user['level'], 3) && !isAnyLevel($user['level'], [1, 5, 6, 7])) {
            $id_user_filter = $user['id'];
            $id_level_filter = 3;
        }

        $mode = $_GET['mode'] ?? 'semua';
        $data = [];
        $infoFilter = '';

        switch ($mode) {
            case 'semua':
                $start = $_GET['start_date'];
                $end = $_GET['end_date'];
                $data = $this->model->getByTanggal($start, $end, $id_level_filter, $id_user_filter);
                $infoFilter = "Periode " . date('d/m/Y', strtotime($start)) . " - " . date('d/m/Y', strtotime($end));
                break;
            case 'semester':
                $id_tp = $_GET['id_tahun_pelajaran'];
                // TERAPKAN FILTER HAK AKSES
                $data = $this->model->getBySemester($id_tp, $id_level_filter, $id_user_filter);
                // Tambahkan info semester jika perlu
                break;
            case 'bulan':
                // Format input 'bulan' adalah 'YYYY-MM'
                $bulanTahun = $_GET['bulan'];
                list($tahun, $bulan) = explode('-', $bulanTahun);
                // TERAPKAN FILTER HAK AKSES
                $data = $this->model->getByBulan($tahun, $bulan, $id_level_filter, $id_user_filter);
                $infoFilter = "Bulan " . date('F Y', strtotime($bulanTahun . '-01'));
                break;
            case 'kategori':
                $id_kategori = $_GET['id_kategori'];
                // TERAPKAN FILTER HAK AKSES
                $data = $this->model->getByKategori($id_kategori, $id_level_filter, $id_user_filter);
                break;
            case 'kelas':
                $id_kelas = $_GET['id_kelas'];
                $data = $this->model->getByKelas($id_kelas); // Filter by kelas sudah spesifik
                break;
            case 'siswa':
                $id_siswa = $_GET['id_siswa'];
                $data = $this->model->getBySiswa($id_siswa); // Filter by siswa sudah spesifik
                break;
        }

        $tanggalCetak = date('d F Y');

        // 🔹 Render view ke buffer
        ob_start();
        include __DIR__ . '/../views/admin/rekon/cetak.php';
        $html = ob_get_clean();

        // 🔹 Setup Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // 🔹 Stream ke browser (Attachment=0 → tampil di browser, bukan download langsung)
        $dompdf->stream("rekap_konseling_{$mode}.pdf", ["Attachment" => 0]);
        exit;
    }
}
