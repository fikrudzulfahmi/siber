<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';
require_once __DIR__ . '/../models/Kehadiran.php';
require_once __DIR__ . '/../models/Kelas.php';
require_once __DIR__ . '/../vendor/autoload.php'; // pastikan autoload DomPDF

use Dompdf\Dompdf;
use Dompdf\Options;

class KehadiranController
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }


    // Di dalam file: app/controllers/KehadiranController.php

    // Di dalam file: app/controllers/KehadiranController.php

    // Di dalam file: app/controllers/KehadiranController.php

    public function index()
    {
        $kehadiranModel = new Kehadiran($this->db);

        $tanggal_terpilih = $_POST['tanggal'] ?? date('Y-m-d');
        $id_kelas_terpilih = $_POST['id_kelas'] ?? 'semua';

        // 🔹 Ambil tahun aktif
        $tahunAktif = $kehadiranModel->getTahunAktif();

        if (!$tahunAktif) {
            die("Tidak ada tahun pelajaran aktif.");
        }

        $id_tahun = $tahunAktif['id_tahun_pelajaran'];

        $daftar_kelas = $kehadiranModel->getAllKelas();
        $user_level = $_SESSION['user']['level'];

        // 🔹 Kirim id_tahun ke model
        $data = $kehadiranModel->getMasterRekapHarian(
            $tanggal_terpilih,
            $id_kelas_terpilih,
            $id_tahun
        );

        $data['daftar_kelas'] = $daftar_kelas;
        $data['tanggal_terpilih'] = $tanggal_terpilih;
        $data['id_kelas_terpilih'] = $id_kelas_terpilih;
        $data['user_level'] = $user_level;

        extract($data);
        require __DIR__ . '/../views/admin/kehadiran_admin/index.php';
    }
    // Sesuaikan juga method cetakPdf agar bisa menerima filter kelas
    public function cetakPdf()
    {
        $tanggal  = $_GET['tanggal'] ?? date('Y-m-d');
        $id_kelas = $_GET['id_kelas'] ?? 'semua';

        $kehadiranModel = new Kehadiran($this->db);

        // 🔴 Ambil tahun aktif
        $tahunAktif = $kehadiranModel->getTahunAktif();

        if (!$tahunAktif) {
            die("Tidak ada tahun pelajaran aktif.");
        }

        // ⚠ Sesuaikan dengan nama kolom sebenarnya
        $id_tahun = $tahunAktif['id_tahun_pelajaran'];

        // 🔴 Kirim id_tahun ke model
        $data = $kehadiranModel->getMasterRekapHarian(
            $tanggal,
            $id_kelas,
            $id_tahun
        );

        $data['tanggal_terpilih'] = $tanggal;

        extract($data);

        ob_start();
        require __DIR__ . '/../views/admin/kehadiran_admin/kehadiran_pdf.php';
        $html = ob_get_clean();

        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $dompdf->stream("rekap-kehadiran-{$tanggal}.pdf", ["Attachment" => false]);
            exit();
        } catch (Exception $e) {
            $errorMessage = 'Error Dompdf: ' . $e->getMessage() . "\n";
            file_put_contents('debug_log.txt', $errorMessage, FILE_APPEND);
            die($errorMessage);
        }
    }

    public function rekapBulanan()
    {
        $kehadiranModel = new Kehadiran($this->db);

        $user_level = $_SESSION['user']['level'];
        $id_user = $_SESSION['user']['id'];

        $bulan_terpilih = $_POST['bulan'] ?? date('m');
        $tahun_terpilih = $_POST['tahun'] ?? date('Y');

        // 🔴 TAMBAHKAN INI
        $tahunAktif = $kehadiranModel->getTahunAktif();
        if (!$tahunAktif) {
            die("Tidak ada tahun pelajaran aktif.");
        }
        $id_tahun = $tahunAktif['id_tahun_pelajaran']; // sesuaikan nama kolom

        $daftar_kelas_untuk_filter = [];
        $id_kelas_terpilih = null;

        if ($user_level == 3) {
            $kelas_wali = $kehadiranModel->getKelasByWali($id_user);
            if ($kelas_wali) {
                $daftar_kelas_untuk_filter[] = $kelas_wali;
                $id_kelas_terpilih = $kelas_wali['id_kelas'];
            }
        } else {
            $daftar_kelas_untuk_filter = $kehadiranModel->getAllKelas();
            $id_kelas_terpilih = $_POST['id_kelas'] ?? null;
        }

        $data['rekap_bulanan'] = [];

        if ($id_kelas_terpilih) {
            $data['rekap_bulanan'] = $kehadiranModel->getRekapBulanan(
                $bulan_terpilih,
                $tahun_terpilih,
                $id_kelas_terpilih,
                $id_tahun // 🔴 PARAMETER KE-4
            );
        }

        $data['bulan_terpilih'] = $bulan_terpilih;
        $data['tahun_terpilih'] = $tahun_terpilih;
        $data['id_kelas_terpilih'] = $id_kelas_terpilih;
        $data['daftar_kelas'] = $daftar_kelas_untuk_filter;
        $data['user_level'] = $user_level;

        extract($data);
        require __DIR__ . '/../views/admin/kehadiran_admin/rekap_bulanan.php';
    }

    public function cetakRekapBulanan()
    {
        // 1. Ambil parameter dari URL
        $bulan = $_GET['bulan'] ?? date('m');
        $tahun = $_GET['tahun'] ?? date('Y');
        $id_kelas = $_GET['id_kelas'] ?? null;

        if (!$id_kelas) {
            die("Error: Kelas tidak dipilih.");
        }

        $kehadiranModel = new Kehadiran($this->db);

        // 🔴 1. TAMBAHKAN PENGECEKAN TAHUN AKTIF DI SINI
        $tahunAktif = $kehadiranModel->getTahunAktif();
        if (!$tahunAktif) {
            die("Tidak ada tahun pelajaran aktif.");
        }
        $id_tahun = $tahunAktif['id_tahun_pelajaran']; // sesuaikan nama kolom jika berbeda

        // 🔴 2. TAMBAHKAN $id_tahun SEBAGAI PARAMETER KE-4
        $data['rekap_bulanan'] = $kehadiranModel->getRekapBulanan($bulan, $tahun, $id_kelas, $id_tahun);

        // 3. Siapkan data lain untuk view
        $data['bulan_terpilih'] = $bulan;
        $data['tahun_terpilih'] = $tahun;

        // Ambil nama kelas untuk judul laporan
        $kelasInfo = $this->db->prepare("SELECT kelas FROM kelas WHERE id_kelas = ?");
        $kelasInfo->execute([$id_kelas]);
        $data['nama_kelas'] = $kelasInfo->fetchColumn();

        extract($data);

        // 4. Render view PDF ke dalam variabel
        ob_start();
        // Arahkan ke file view PDF yang baru
        require __DIR__ . '/../views/admin/kehadiran_admin/rekap_bulanan_pdf.php';
        $html = ob_get_clean();

        // 5. Generate PDF dengan Dompdf
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape'); // Gunakan landscape karena tabelnya lebar
        $dompdf->render();
        $dompdf->stream("rekap-bulanan-{$nama_kelas}-{$bulan}-{$tahun}.pdf", ["Attachment" => false]);

        exit();
    }
}
