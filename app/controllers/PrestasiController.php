<?php
// File: app/controllers/PrestasiController.php

require_once __DIR__ . '/BaseController.php';

class PrestasiController extends BaseController
{
    protected $prestasiModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        // Memanggil model menggunakan method dari BaseController
        $this->prestasiModel = $this->model('PrestasiModel');
    }

    public function index()
    {
        // Ambil ID Tahun dari session atau filter URL
        $id_tahun = $_GET['id_tahun'] ?? $_SESSION['user']['id_tahun'];

        $data = [
            'title' => 'Daftar Prestasi Siswa',
            'list_tahun' => $this->prestasiModel->getAllTahunPelajaran(),
            'daftar_prestasi' => $this->prestasiModel->getAllPrestasiWithSiswa($id_tahun),
            'id_tahun_aktif' => $id_tahun
        ];

        // Mengirim data ke view menggunakan method dari BaseController
        $this->view('admin/prestasi/index', $data);
    }

    public function tambah()
    {
        $id_tahun = $_SESSION['user']['id_tahun'];

        $data = [
            'title' => 'Input Prestasi Kolektif',
            'list_siswa' => $this->prestasiModel->getSiswaByPlotting($id_tahun) // Ambil data plotting
        ];

        $this->view('admin/prestasi/tambah', $data);
    }

    public function simpan()
    {
        // 1. Handle Upload Sertifikat
        $nama_file = null;
        if (!empty($_FILES['sertifikat']['name'])) {
            $extension = pathinfo($_FILES['sertifikat']['name'], PATHINFO_EXTENSION);
            $nama_file = "CERT_" . time() . "_" . uniqid() . "." . $extension;
            $target_path = "../public/uploads/sertifikat/" . $nama_file;

            move_uploaded_file($_FILES['sertifikat']['tmp_name'], $target_path);
        }

        // 2. Gabungkan logika Juara (Dropdown + Manual)
        $juara = ($_POST['juara_select'] === 'Lainnya') ? $_POST['juara_custom'] : $_POST['juara_select'];

        // 3. Susun data untuk prestasi_kegiatan
        $dataKegiatan = [
            $_POST['nama_kegiatan'],
            $_POST['jenis_prestasi'],
            $_POST['tingkat'],
            $juara,
            $_POST['penyelenggara'],
            $_POST['tgl_kegiatan'],
            $nama_file,
            $_SESSION['user']['id_tahun'], // Relasi manual ke tahun_pelajaran
            $_POST['keterangan_tambahan']
        ];

        // 4. Ambil array ID Plotting siswa yang terpilih
        $peserta_dipilih = $_POST['pilih_siswa'] ?? [];

        if (empty($peserta_dipilih)) {
            header("Location: ?controller=prestasi&method=tambah&status=no_student");
            exit();
        }

        // 5. Eksekusi simpan kolektif di Model
        if ($this->prestasiModel->insertPrestasiKolektif($dataKegiatan, $peserta_dipilih)) {
            header("Location: ?controller=prestasi&method=index&status=success");
        } else {
            header("Location: ?controller=prestasi&method=tambah&status=error");
        }
        exit();
    }

    public function edit()
    {
        $id_prestasi = $_GET['id'];
        $id_tahun = $_SESSION['user']['id_tahun'];

        // 1. Ambil data induk kegiatan
        $prestasi = $this->prestasiModel->getPrestasiById($id_prestasi);

        // 2. Ambil daftar ID siswa yang sudah terdaftar di prestasi ini
        $peserta_saat_ini = $this->prestasiModel->getPesertaByKegiatan($id_prestasi);
        // Ubah ke array satu dimensi berisi ID Plotting saja agar mudah dicek di View
        $ids_peserta = array_column($peserta_saat_ini, 'id_plotting_siswa');

        // 3. Ambil semua daftar siswa yang tersedia untuk dipilih ulang
        $list_siswa = $this->prestasiModel->getSiswaByPlotting($id_tahun);

        $data = [
            'title' => 'Edit Prestasi Siswa',
            'prestasi' => $prestasi,
            'ids_peserta' => $ids_peserta,
            'list_siswa' => $list_siswa
        ];

        $this->view('admin/prestasi/edit', $data);
    }

    public function update()
    {
        $id_kegiatan = $_POST['id_prestasi_kegiatan'];
        $prestasi_lama = $this->prestasiModel->getPrestasiById($id_kegiatan);

        // 1. Handle Upload Sertifikat (Hanya jika ada file baru)
        $nama_file = $prestasi_lama['file_sertifikat'];
        if (!empty($_FILES['sertifikat']['name'])) {
            // Hapus file lama jika ada
            if ($nama_file && file_exists("../public/uploads/sertifikat/" . $nama_file)) {
                unlink("../public/uploads/sertifikat/" . $nama_file);
            }

            $extension = pathinfo($_FILES['sertifikat']['name'], PATHINFO_EXTENSION);
            $nama_file = "CERT_" . time() . "_" . uniqid() . "." . $extension;
            move_uploaded_file($_FILES['sertifikat']['tmp_name'], "../public/uploads/sertifikat/" . $nama_file);
        }

        // 2. Logika Juara
        $juara = ($_POST['juara_select'] === 'Lainnya') ? $_POST['juara_custom'] : $_POST['juara_select'];

        // 3. Data Kegiatan
        $dataKegiatan = [
            'nama_kegiatan'   => $_POST['nama_kegiatan'],
            'jenis_prestasi'  => $_POST['jenis_prestasi'],
            'tingkat'         => $_POST['tingkat'],
            'juara'           => $juara,
            'penyelenggara'   => $_POST['penyelenggara'],
            'tgl_kegiatan'    => $_POST['tgl_kegiatan'],
            'file_sertifikat' => $nama_file,
            'keterangan'      => $_POST['keterangan_tambahan'],
            'id_kegiatan'     => $id_kegiatan
        ];

        // 4. Daftar Peserta Baru
        $peserta_baru = $_POST['pilih_siswa'] ?? [];

        // 5. Eksekusi Update Kolektif
        if ($this->prestasiModel->updatePrestasiKolektif($dataKegiatan, $peserta_baru)) {
            header("Location: ?controller=prestasi&method=index&status=updated");
        } else {
            header("Location: ?controller=prestasi&method=edit&id=$id_kegiatan&status=error");
        }
        exit();
    }

    public function hapus()
    {
        $id = $_GET['id'];
        $data = $this->prestasiModel->getPrestasiById($id);

        if ($this->prestasiModel->deletePrestasi($id)) {
            if (!empty($data['file_sertifikat'])) {
                $path = "../public/uploads/sertifikat/" . $data['file_sertifikat'];
                if (file_exists($path)) unlink($path);
            }
            header("Location: ?controller=prestasi&method=index&status=deleted");
        } else {
            header("Location: ?controller=prestasi&method=index&status=error");
        }
        exit();
    }
}
