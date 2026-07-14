<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';
require_once __DIR__ . '/../models/Konseling.php';
require_once __DIR__ . '/../vendor/autoload.php'; // pastikan autoload DomPDF

use Dompdf\Dompdf;

class KonselingController extends BaseController
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

        // 2. Siapkan variabel filter, default-nya null (tidak memfilter)
        $id_kelas_filter = null;

        // 3. ✅ LOGIKA HAK AKSES BARU
        // Cek apakah user adalah Wali Kelas (level 3) dan bukan pimpinan
        if (isLevel($user['level'], 3) && !isAnyLevel($user['level'], [1, 5, 6, 7])) {
            // Jika ya, gunakan id_kelas miliknya untuk memfilter
            // Pastikan 'id_kelas' ada di session saat wali kelas login
            $id_kelas_filter = $user['id_kelas'] ?? null;
        }

        // 4. Panggil method 'all' di model dengan filter
        $konseling_list = $this->model->all($id_kelas_filter);

        // 5. Kirim data yang sudah difilter ke view
        view('admin/konseling/index', [
            'konseling' => $konseling_list,
            'id_level'  => $user['level'] // Kirim level untuk logika tombol di view
        ]);
    }


    public function create()
    {
        $user = $_SESSION['user'];
        $kategori = $this->model->allKategori();
        $employee = $this->db->query("SELECT id_employe, nama FROM employe")->fetchAll();
        $siswa = [];
        $kelas = [];

        // ✅ LOGIKA HAK AKSES BARU
        // Jika yang login adalah Wali Kelas (level 3)
        if (isLevel($user['level'], 3)) {
            // Ambil HANYA kelas yang diampu oleh wali kelas ini
            // Kita gunakan 'id' dari session yang merupakan id_employe
            $kelas = $this->model->getKelasWalikelas($user['id']);

            // Jika hanya ada satu kelas, langsung load siswanya
            if (count($kelas) === 1) {
                $siswa = $this->model->getSiswaByKelas($kelas[0]['id_kelas']);
            }
        } else {
            // Jika Admin, BK, dll., ambil semua kelas
            $kelas = $this->model->getKelasList();
        }

        view('admin/konseling/create', compact('kelas', 'siswa', 'employee', 'kategori'));
    }


    public function store()
    {
        $bukti = null;
        if (!empty($_FILES['bukti_fisik']['name'])) {
            $bukti = time() . '_' . basename($_FILES['bukti_fisik']['name']);
            move_uploaded_file($_FILES['bukti_fisik']['tmp_name'], "uploads/konseling/$bukti");
        }

        $doc = null;
        if (!empty($_FILES['dokumen']['name'])) {
            $doc = time() . '_' . basename($_FILES['dokumen']['name']);
            move_uploaded_file($_FILES['dokumen']['tmp_name'], "uploads/konseling/$doc");
        }

        $id_employee = implode(',', $_POST['id_employee']);

        $this->model->simpan([
            $_POST['id_kelas'],
            $_POST['id_siswa'],
            $_POST['id_kategori'],   // kategori baru
            $_POST['permasalahan'],
            $_POST['tanggal_masalah'],
            $bukti,
            $doc,
            $id_employee,
            'Berlangsung'
        ]);


        setFlash('success', 'Konseling berhasil ditambahkan.');
        redirect('?controller=konseling&method=index');
    }

    public function tindakLanjut()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            redirect('?controller=konseling&method=index');
        }

        $statusRow = $this->model->getStatus($id);
        if (!$statusRow) {
            setFlash('error', 'Data konseling tidak ditemukan.');
            redirect('?controller=konseling&method=index');
        }

        $status = $statusRow['status'];
        $tindaklanjut = $this->model->getTindakLanjut($id);

        view('admin/konseling/tindaklanjut', compact('id', 'status', 'tindaklanjut'));
    }

    public function selesai()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            setFlash('error', 'ID tidak valid.');
            redirect('?controller=konseling&method=index');
        }

        $this->model->tandaiSelesai($id);
        setFlash('success', 'Status konseling ditandai sebagai selesai.');
        redirect('?controller=konseling&method=index');
    }

    public function simpanTindakLanjut()
    {
        $id_konseling = $_POST['id_konseling'];
        $catatan = $_POST['catatan'];
        $tanggal = $_POST['tanggal'];

        $statusRow = $this->model->getStatus($id_konseling);
        if ($statusRow && strtolower($statusRow['status']) === 'selesai') {
            setFlash('success', 'Tidak dapat menambah tindak lanjut karena status sudah selesai.');
            redirect("?controller=konseling&method=tindakLanjut&id=$id_konseling");
            return;
        }

        $bukti = null;
        if (!empty($_FILES['bukti']['name'])) {
            $bukti = time() . '_' . $_FILES['bukti']['name'];
            move_uploaded_file($_FILES['bukti']['tmp_name'], "uploads/tindaklanjut/$bukti");
        }

        $this->model->simpanTindakLanjut($id_konseling, $catatan, $tanggal, $bukti);

        setFlash('success', 'Tindak lanjut berhasil disimpan.');
        redirect("?controller=konseling&method=tindakLanjut&id=$id_konseling");
    }

    public function cetak()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            setFlash('error', 'ID konseling tidak valid.');
            redirect('?controller=konseling&method=index');
            return;
        }

        // Ambil data konseling dan tindak lanjut
        $konseling = $this->model->find($id);
        $tindakLanjut = $this->model->getTindakLanjut($id);

        // Ambil tanggal cetak untuk footer
        $tanggalCetak = date('d F Y');

        // Ambil HTML view
        ob_start();
        require __DIR__ . '/../views/admin/konseling/cetak.php';
        $html = ob_get_clean();

        // Buat PDF dengan Dompdf
        $dompdf = new Dompdf();
        $dompdf->setPaper('A4', 'portrait'); // portrait, bisa diubah ke landscape
        $dompdf->loadHtml($html);
        $dompdf->render();
        $dompdf->stream("laporan_konseling_{$id}.pdf", ["Attachment" => false]);
        $dompdf->set_option('isRemoteEnabled', true);
    }
}
