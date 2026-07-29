<?php
require_once __DIR__ . '/../models/Ploting.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class PlotingController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo);
    }

    public function index()
    {
        $model = new Ploting($this->db);

        // Siapkan data untuk dropdown filter
        $tahun_ajaran = $model->getAllTahun();
        $daftar_kelas = $model->getAllKelas();

        require __DIR__ . '/../views/admin/ploting/index.php';
    }

    public function anggota()
    {
        $model = new Ploting($this->db);

        // Siapkan data untuk dropdown filter
        $tahun_ajaran = $model->getAllTahun();
        $daftar_kelas = $model->getAllKelas();

        require __DIR__ . '/../views/admin/ploting/anggota.php';
    }

    // API untuk AJAX: Mengambil daftar siswa
    public function get_data_siswa()
    {
        // Pastikan request method POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id_kelas = $_POST['id_kelas'] ?? 0;
        $id_tahun = $_POST['id_tahun'] ?? 0;

        $model = new Ploting($this->db);
        $data = $model->getSiswaByKelasTahun($id_kelas, $id_tahun);

        // Return JSON
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // API untuk AJAX: Mengambil daftar siswa yang BELUM punya kelas (siswa baru)
    public function get_siswa_baru()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $id_tahun = $_POST['id_tahun'] ?? 0;

        $model = new Ploting($this->db);
        $data = $model->getSiswaBelumPunyaKelas($id_tahun);

        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    // API untuk AJAX: Proses Simpan
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $ids_siswa = $_POST['ids_siswa'] ?? []; // Array ID Siswa
        $id_kelas_tujuan = $_POST['id_kelas_tujuan'];
        $id_tahun_tujuan = $_POST['id_tahun_tujuan'];

        $model = new Ploting($this->db);
        $sukses = 0;
        $gagal = 0;

        foreach ($ids_siswa as $id_siswa) {
            // Validasi: Apakah siswa ini sudah terdaftar di tahun ajaran tersebut?
            $exist = $model->cekSiswaDiTahun($id_siswa, $id_tahun_tujuan);

            if ($exist == 0) {
                $model->insert($id_siswa, $id_kelas_tujuan, $id_tahun_tujuan);
                $sukses++;
            } else {
                $gagal++; // Siswa sudah punya kelas di tahun itu
            }
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'msg' => "$sukses siswa berhasil dipindahkan. $gagal gagal/sudah ada.",
            'inserted' => $sukses
        ]);
        exit;
    }

    // API untuk AJAX: Meluluskan siswa terpilih (tidak dimasukkan ke kelas manapun)
    public function luluskan()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit;
        }

        $ids_siswa = $_POST['ids_siswa'] ?? [];
        $id_tahun = $_POST['id_tahun'] ?? 0;

        $model = new Ploting($this->db);
        $sukses = 0;

        foreach ($ids_siswa as $id_siswa) {
            $model->luluskanSiswa($id_siswa, $id_tahun);
            $sukses++;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status' => 'success',
            'msg' => "$sukses siswa berhasil diluluskan.",
            'inserted' => $sukses
        ]);
        exit;
    }

    // Export Data Anggota Kelas Lengkap ke Excel
    public function export_excel()
    {
        $id_kelas = $_GET['id_kelas'] ?? 0;
        $id_tahun = $_GET['id_tahun'] ?? 0;

        $model = new Ploting($this->db);
        $data_siswa = $model->getSiswaLengkapByKelasTahun($id_kelas, $id_tahun);

        if (empty($data_siswa)) {
            setFlash('error', 'Tidak ada data siswa untuk diexport pada kelas dan tahun ajaran tersebut.');
            header('Location: ?controller=ploting&method=anggota');
            exit;
        }

        $nama_kelas = $data_siswa[0]['nama_kelas'];
        // Ganti karakter '/' agar aman untuk nama file (misal 2026/2027 jadi 2026-2027)
        $tahun_ajaran = str_replace('/', '-', $data_siswa[0]['tahun_pelajaran']);
        
        require_once __DIR__ . '/../vendor2/autoload.php';

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = ['No', 'Nama Siswa', 'NISN', 'Tempat Lahir', 'Tanggal Lahir', 'Alamat', 'Nama Wali', 'No. HP Wali'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $sheet->getStyle($col . '1')->getFont()->setBold(true);
            $col++;
        }

        $rowNum = 2;
        $no = 1;
        foreach ($data_siswa as $row) {
            $sheet->setCellValue('A' . $rowNum, $no++);
            $sheet->setCellValue('B' . $rowNum, $row['nama_siswa']);
            $sheet->setCellValueExplicit('C' . $rowNum, $row['nisn'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $rowNum, $row['tempat_lhr']);
            $sheet->setCellValue('E' . $rowNum, $row['tgl_lhr']);
            $sheet->setCellValue('F' . $rowNum, $row['alamat']);
            $sheet->setCellValue('G' . $rowNum, $row['nama_wali']);
            $sheet->setCellValueExplicit('H' . $rowNum, $row['hp_wali'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $rowNum++;
        }

        foreach (range('A', 'H') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $filename = "Data_Siswa_{$nama_kelas}_{$tahun_ajaran}.xlsx";

        // Bersihkan output buffer jika ada sebelumnya (mencegah file excel rusak)
        if (ob_get_length()) {
            ob_end_clean();
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}
