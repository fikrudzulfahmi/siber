<?php
// File: app/controllers/KegiatanController.php

require_once __DIR__ . '/../models/KegiatanModel.php';
require_once 'BaseController.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class KegiatanController
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Menampilkan daftar kegiatan
     */
    public function index()
    {
        $kegiatanModel = new KegiatanModel($this->db);
        $daftar_kegiatan = $kegiatanModel->getAllKegiatan();

        require __DIR__ . '/../views/admin/kegiatan/index.php';
    }

    /**
     * Menampilkan form tambah kegiatan & daftar guru
     */
    public function tambah()
    {
        $kegiatanModel = new KegiatanModel($this->db);
        $list_guru = $kegiatanModel->getDaftarGuru();

        require __DIR__ . '/../views/admin/kegiatan/tambah.php';
    }

    /**
     * Proses simpan kegiatan dan peserta
     */
    public function simpan()
    {
        $kegiatanModel = new KegiatanModel($this->db);

        // 1. Data utama kegiatan
        $data = [
            'nama_kegiatan' => $_POST['nama_kegiatan'],
            'tanggal'       => $_POST['tanggal'],
            'jam_mulai'     => $_POST['jam_mulai'],
            'jam_selesai'   => $_POST['jam_selesai'],
            'keterangan'    => $_POST['keterangan']
        ];

        // 2. Insert kegiatan
        $id_kegiatan = $kegiatanModel->createKegiatan($data);

        // 3. Insert peserta (guru yang dipilih)
        if (isset($_POST['peserta']) && is_array($_POST['peserta'])) {
            foreach ($_POST['peserta'] as $pin) {
                $kegiatanModel->addPeserta($id_kegiatan, $pin);
            }
        }

        header("Location: ?controller=kegiatan&method=index&status=success");
        exit();
    }

    /**
     * Menampilkan detail kegiatan, rekap absen, dan form upload foto
     */
    public function detail()
    {
        $id_kegiatan = $_GET['id_kegiatan'] ?? null;
        if (!$id_kegiatan) die('ID Kegiatan tidak ditemukan');

        $kegiatanModel = new KegiatanModel($this->db);
        $kegiatan = $kegiatanModel->findKegiatan($id_kegiatan);
        $rekap_absen = $kegiatanModel->getRekapKehadiran($id_kegiatan);

        require __DIR__ . '/../views/admin/kegiatan/detail.php';
    }

    /**
     * Proses upload foto kegiatan
     */
    public function uploadFoto()
    {
        $id_kegiatan = $_POST['id_kegiatan'];
        $kegiatanModel = new KegiatanModel($this->db);

        if (isset($_FILES['foto'])) {
            $files = $_FILES['foto'];
            $targetDir = $_SERVER['DOCUMENT_ROOT'] . "/public/uploads/kegiatan/";
            if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

            for ($i = 0; $i < count($files['name']); $i++) {
                if ($files['error'][$i] == 0) {
                    $fileTmp = $files['tmp_name'][$i];
                    $extension = strtolower(pathinfo($files['name'][$i], PATHINFO_EXTENSION));
                    $fileName = "keg_" . $id_kegiatan . "_" . time() . "_" . $i . ".jpg";
                    $targetFile = $targetDir . $fileName;

                    // Load Image
                    if ($extension == 'png') $img = @imagecreatefrompng($fileTmp);
                    elseif ($extension == 'gif') $img = @imagecreatefromgif($fileTmp);
                    else $img = @imagecreatefromjpeg($fileTmp);

                    if ($img) {
                        // Logika Kompresi: Resize jika lebar > 1200px
                        $w = imagesx($img);
                        $h = imagesy($img);
                        $maxW = 1200;

                        if ($w > $maxW) {
                            $newH = ($h / $w) * $maxW;
                            $tmp = imagecreatetruecolor($maxW, $newH);
                            imagecopyresampled($tmp, $img, 0, 0, 0, 0, $maxW, $newH, $w, $h);
                            imagejpeg($tmp, $targetFile, 60); // Kualitas 60% untuk target < 200kb
                            imagedestroy($tmp);
                        } else {
                            imagejpeg($img, $targetFile, 60);
                        }
                        imagedestroy($img);

                        // Simpan ke tabel kegiatan_foto
                        $kegiatanModel->saveFotoKegiatan($id_kegiatan, $fileName);
                    }
                }
            }
            header("Location: ?controller=kegiatan&method=detail&id_kegiatan=$id_kegiatan&upload=success");
        }
        exit();
    }

    public function cetak()
    {
        $id_kegiatan = $_GET['id_kegiatan'] ?? null;
        if (!$id_kegiatan) die('ID Kegiatan tidak ditemukan');

        $kegiatanModel = new KegiatanModel($this->db);
        $kegiatan = $kegiatanModel->findKegiatan($id_kegiatan);
        $rekap_absen = $kegiatanModel->getRekapKehadiran($id_kegiatan);
        $fotos = $kegiatanModel->getFotosByKegiatan($id_kegiatan);

        // Konfigurasi DOMPDF
        $options = new Options();
        $options->set('isRemoteEnabled', true); // Penting agar bisa meload gambar
        $dompdf = new Dompdf($options);

        // Load View khusus cetak (kita buat filenya di langkah berikutnya)
        ob_start();
        require __DIR__ . '/../views/admin/kegiatan/cetak_pdf.php';
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output ke browser (Attachment: false berarti preview di browser)
        $dompdf->stream("Laporan_Kegiatan_" . $id_kegiatan . ".pdf", ["Attachment" => false]);
    }

    /**
     * Menampilkan form edit kegiatan
     */
    public function edit()
    {
        $id_kegiatan = $_GET['id_kegiatan'] ?? null;
        if (!$id_kegiatan) die('ID Kegiatan tidak ditemukan');

        $kegiatanModel = new KegiatanModel($this->db);

        $kegiatan = $kegiatanModel->findKegiatan($id_kegiatan);
        if (!$kegiatan) die('Data Kegiatan tidak ditemukan');

        $list_guru = $kegiatanModel->getDaftarGuru();
        // Ambil array PIN guru yang menjadi peserta
        $peserta_terpilih = $kegiatanModel->getPesertaByKegiatan($id_kegiatan);

        require __DIR__ . '/../views/admin/kegiatan/edit.php';
    }

    /**
     * Proses update data kegiatan
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_kegiatan = $_POST['id_kegiatan'];
            $kegiatanModel = new KegiatanModel($this->db);

            // 1. Update data utama kegiatan
            $data = [
                'nama_kegiatan' => $_POST['nama_kegiatan'],
                'tanggal'       => $_POST['tanggal'],
                'jam_mulai'     => $_POST['jam_mulai'],
                'jam_selesai'   => $_POST['jam_selesai'],
                'keterangan'    => $_POST['keterangan']
            ];
            $kegiatanModel->updateKegiatan($id_kegiatan, $data);

            // 2. Update peserta (Hapus yang lama, insert yang baru)
            $kegiatanModel->deletePeserta($id_kegiatan);
            if (isset($_POST['peserta']) && is_array($_POST['peserta'])) {
                foreach ($_POST['peserta'] as $pin) {
                    $kegiatanModel->addPeserta($id_kegiatan, $pin);
                }
            }

            header("Location: ?controller=kegiatan&method=index&status=updated");
            exit();
        }
    }

    /**
     * Proses hapus data kegiatan
     */
    public function hapus()
    {
        $id_kegiatan = $_GET['id_kegiatan'] ?? null;
        if ($id_kegiatan) {
            $kegiatanModel = new KegiatanModel($this->db);

            // Opsional: Hapus file fisik foto jika ada
            $fotos = $kegiatanModel->getFotosByKegiatan($id_kegiatan);
            foreach ($fotos as $foto) {
                $filePath = $_SERVER['DOCUMENT_ROOT'] . "/public/uploads/kegiatan/" . $foto['nama_file'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            // Hapus dari database
            $kegiatanModel->deleteKegiatan($id_kegiatan);
        }

        header("Location: ?controller=kegiatan&method=index&status=deleted");
        exit();
    }
}
