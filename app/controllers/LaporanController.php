<?php
require_once __DIR__ . '/../models/LaporanModel.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once __DIR__ . '/../vendor2/autoload.php'; // pastikan autoload DomPDF
require_once 'BaseController.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LaporanController
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function index()
    {
        // 1. Ambil ID Tahun dari URL
        $id_tahun = $_GET['id_tahun'] ?? null;

        $laporanModel = new LaporanModel($this->db);

        // 2. Ambil data laporan dengan filter
        $data_laporan_lengkap = $laporanModel->getDataLaporanLengkap($id_tahun);

        // 3. Ambil daftar tahun pelajaran untuk dropdown filter
        // (Sesuaikan nama tabel 'tahun_pelajaran' dengan milik Anda)
        $stmtTahun = $this->db->query("SELECT * FROM tahun_pelajaran ORDER BY tahun_pelajaran DESC, semester DESC");
        $daftar_tahun = $stmtTahun->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin/laporan/index.php';
    }

    public function getNilaiAjax()
    {
        $id_kategori = $_GET['id_kategori'] ?? null;
        if (!$id_kategori) die('Error: ID Kategori tidak ditemukan.');

        $laporanModel = new LaporanModel($this->db);
        $hasil_laporan = $laporanModel->getNilaiByKategori($id_kategori);

        require __DIR__ . '/../views/admin/laporan/_hasil_nilai.php';
    }

    /**
     * ✅ METHOD EXPORT EXCEL YANG DIPERBAIKI
     * Sekarang menggunakan getNilaiByKategori, bukan getLaporanLeger.
     */
    public function exportExcel()
    {
        $id_kategori = $_GET['id_kategori'] ?? null;
        if (!$id_kategori) die('Error: ID Kategori tidak ditemukan.');

        $laporanModel = new LaporanModel($this->db);
        $data_laporan = $laporanModel->getNilaiByKategori($id_kategori);
        $info_kategori = $laporanModel->findKategoriInfo($id_kategori);

        // Ambil info nama kustom & banyak_ns dari baris pertama data
        $banyak_ns = $data_laporan[0]['banyak_ns'] ?? 0;
        $customNama = json_decode($data_laporan[0]['nama_ns'] ?? '{}', true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Susun Header secara Dinamis
        $headers = ['No', 'Nama Siswa'];
        for ($i = 1; $i <= $banyak_ns; $i++) {
            $headers[] = !empty($customNama['n' . $i]) ? $customNama['n' . $i] : 'N' . $i;
        }
        // Tambahkan header sisa
        $headers = array_merge($headers, ['Rata-rata', 'STS', 'SAS', 'Nilai Raport', 'Tuntas (%)', 'Nilai Kurang']);

        // Tulis Header ke Excel (Baris 4)
        $sheet->fromArray($headers, NULL, 'A4');

        // Isi Data
        $row = 5;
        $no = 1;
        foreach ($data_laporan as $nilai) {
            $rowData = [$no++, $nilai['nama_siswa']];

            // Isi N sesuai jumlah yang aktif saja
            for ($i = 1; $i <= $banyak_ns; $i++) {
                $rowData[] = $nilai['n' . $i];
            }

            // Tambahkan sisa kolom
            $rowData[] = number_format($nilai['rata'] ?? 0, 2);
            $rowData[] = $nilai['sts'];
            $rowData[] = $nilai['sas'];
            $rowData[] = number_format($nilai['nilai_raport'] ?? 0, 2);
            $rowData[] = round($nilai['persentase_tuntas'] ?? 0) . '%';
            $rowData[] = $nilai['jumlah_nilai_kosong'];

            $sheet->fromArray($rowData, NULL, 'A' . $row);
            $row++;
        }

        // Atur Lebar Kolom
        $sheet->getColumnDimension('B')->setAutoSize(true);
        for ($i = 'C'; $i <= 'R'; $i++) {
            $sheet->getColumnDimension($i)->setWidth(12);
        }

        // Kirim file ke browser
        $writer = new Xlsx($spreadsheet);
        $filename = 'laporan-nilai-' . str_replace(' ', '-', strtolower($info_kategori['kategori'])) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    }
}
