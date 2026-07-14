<?php
// File: app/controllers/IzinController.php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/Izin.php';
require_once __DIR__ . '/../models/Siswa.php';      // Diasumsikan ada untuk mengambil data siswa
require_once __DIR__ . '/../models/Kehadiran.php';  // Digunakan untuk mengambil data kelas
require_once __DIR__ . '/../vendor/autoload.php';   // Panggil DomPDF untuk fitur cetak

use Dompdf\Dompdf;
use Dompdf\Options;

class IzinController extends BaseController
{
    private $izinModel;
    private $siswaModel;
    private $kelasModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->izinModel = new Izin($this->db);
        $this->siswaModel = new Siswa($this->db);
        $this->kelasModel = new Kehadiran($this->db);
    }

    /**
     * Menampilkan halaman utama (daftar semua perizinan)
     */
    public function index()
    {
        $user = $_SESSION['user'];
        $id_kelas_filter = null;

        // ✅ LOGIKA HAK AKSES BARU
        // Cek apakah user adalah Wali Kelas (level 3) dan bukan pimpinan
        if (isLevel($user['level'], 3) && !isAnyLevel($user['level'], [1, 5, 6, 7])) {
            $id_kelas_filter = $user['id_kelas'] ?? null;
        }

        $izin_list = $this->izinModel->getAllWithSiswaKelas($id_kelas_filter);

        // Kirim data yang sudah difilter dan juga level user ke view
        view('admin/izin/index', [
            'izin' => $izin_list,
            'id_level' => $user['level'] // Variabel ini yang hilang sebelumnya
        ]);
    }


    /**
     * Menampilkan form untuk membuat izin baru
     */
    public function create()
    {
        require __DIR__ . '/../views/admin/izin/create.php';
    }

    /**
     * Menyimpan data dari form create
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_kelas = ($_SESSION['user']['level'] == 4) ? $_POST['id_kelas'] : $_SESSION['user']['id_kelas'];

            $data = [
                $_POST['id_siswa'],
                $id_kelas,
                $_POST['keperluan'],
                $_POST['waktu_meninggalkan'],
                $_POST['waktu_kembali'] ?: null,
                $_SESSION['user']['nama']
            ];

            if ($this->izinModel->simpan($data)) {
                setFlash('success', 'Data perizinan berhasil disimpan.');
            } else {
                setFlash('error', 'Gagal menyimpan data perizinan.');
            }
            header('Location: ?controller=izin&method=index');
            exit();
        }
    }

    /**
     * Menampilkan form untuk mengedit izin
     */
    public function edit()
    {
        $id = $_GET['id'] ?? null;
        $izin = $this->izinModel->findById($id);
        require __DIR__ . '/../views/admin/izin/edit.php';
    }

    /**
     * Memperbarui data dari form edit
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_perizinan = $_POST['id_perizinan'];

            $data = [
                $_POST['id_siswa'],
                $_POST['id_kelas'],
                $_POST['keperluan'],
                $_POST['waktu_meninggalkan'],
                $_POST['waktu_kembali'] ?: null,
                $_POST['keterangan'],
                $_POST['tindakan'],
                $id_perizinan
            ];

            if ($this->izinModel->update($data)) {
                setFlash('success', 'Data perizinan berhasil diperbarui.');
            } else {
                setFlash('error', 'Gagal memperbarui data perizinan.');
            }
            header('Location: ?controller=izin&method=index');
            exit();
        }
    }

    /**
     * Mencetak rekap perizinan dalam rentang tanggal ke PDF
     */
    public function rekap()
    {
        $user = $_SESSION['user'];
        $id_kelas_filter = null;

        // ✅ LOGIKA HAK AKSES BARU
        if (isLevel($user['level'], 3) && !isAnyLevel($user['level'], [1, 5, 6, 7])) {
            $id_kelas_filter = $user['id_kelas'] ?? null;
        }

        $tanggalAwal = $_GET['tanggal_awal'] ?? date('Y-m-01');
        $tanggalAkhir = $_GET['tanggal_akhir'] ?? date('Y-m-t');
        $tanggalCetak = date('d F Y');

        // Panggil getByDateRange dengan filter yang benar
        $izin = $this->izinModel->getByDateRange($tanggalAwal, $tanggalAkhir, $id_kelas_filter);

        ob_start();
        // Variabel $izin sekarang sudah berisi data yang benar
        require __DIR__ . '/../views/admin/izin/rekap.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("Rekap Izin " . $tanggalAwal . "_sd_" . $tanggalAkhir . ".pdf", ["Attachment" => 0]);
        exit();
    }

    /**
     * ✅ FUNGSI BARU: Mencetak SATU surat izin ke PDF
     */
    public function cetak()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            die('ID Izin tidak ditemukan.');
        }

        $izin = $this->izinModel->findById($id);
        $tanggalCetak = date('d F Y');

        ob_start();
        require __DIR__ . '/../views/admin/izin/cetak.php';
        $html = ob_get_clean();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait'); // Menggunakan kertas A5
        $dompdf->render();
        $dompdf->stream("Surat Izin - " . $izin['nama_siswa'] . ".pdf", ["Attachment" => 0]);
        exit();
    }

    public function delete()
    {
        $model = new Izin($this->db); // ✅ fix: sebelumnya salah pakai User
        $model->delete($_GET['id']);
        setFlash('success', 'Perizinan siswa berhasil dihapus.');
        header('Location: index.php?controller=izin&method=index');
    }
}
