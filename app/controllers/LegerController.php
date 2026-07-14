<?php
// File: app/controllers/LegerController.php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/LegerModel.php';
// ... (use statements untuk PhpSpreadsheet) ...
require_once __DIR__ . '/../vendor2/autoload.php'; // pastikan autoload  phpoffice

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class LegerController extends BaseController
{
    private $legerModel;

    public function __construct(PDO $db)
    {
        parent::__construct($db);
        $this->legerModel = new LegerModel($this->db);
        authGuard(); // Melindungi halaman ini
    }

    public function index()
    {
        $user = $_SESSION['user'];

        // Panggil model dengan parameter yang benar (ID dan string level)
        $filterOptions = $this->legerModel->getFilterOptions($user['id'], $user['level']);

        $data_to_view = [
            'title' => 'Cetak Leger Nilai',
            'leger_data' => null,
            'info_kelas' => null,
            'filter_terpilih' => [],
            'id_level' => $user['level'] // Kirim level ke view
        ];

        // Gabungkan hasil dari model ke data yang akan dikirim ke view
        $data_to_view = array_merge($data_to_view, $filterOptions);

        // Logika saat form di-submit
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_tp = $_POST['id_tahun_pelajaran'];
            $id_kelas = $_POST['id_kelas'] ?? ($data_to_view['kelas_walas']['id_kelas'] ?? null);

            if ($id_tp && $id_kelas) {
                $data_to_view['leger_data'] = $this->legerModel->getLegerData($id_kelas, $id_tp);
                $data_to_view['info_kelas'] = $this->legerModel->getKelasInfo($id_kelas);
                // Siapkan filter untuk link export
                $data_to_view['filter_terpilih'] = ['id_tahun_pelajaran' => $id_tp, 'id_kelas' => $id_kelas];
            }
        }

        // Gunakan fungsi view() yang benar
        view('admin/leger/index', $data_to_view);
    }


    public function exportExcel()
    {
        // Ambil filter dari parameter URL (GET)
        $id_kelas = $_GET['id_kelas'] ?? null;
        $id_tahun_pelajaran = $_GET['id_tahun_pelajaran'] ?? null;

        if (!$id_kelas || !$id_tahun_pelajaran) {
            die("Error: Informasi Kelas atau Tahun Pelajaran tidak lengkap untuk export.");
        }

        $legerModel = new LegerModel($this->db);
        $leger_data = $legerModel->getLegerData($id_kelas, $id_tahun_pelajaran);
        $info_kelas = $legerModel->getKelasInfo($id_kelas);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Leger ' . $info_kelas['kelas']);

        // Judul Laporan
        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'LEGER NILAI AKHIR SISWA');
        $sheet->setCellValue('A2', 'Kelas: ' . $info_kelas['kelas']);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        // Header Tabel
        $sheet->setCellValue('A4', 'No');
        $sheet->setCellValue('B4', 'Nama Siswa');
        $columnIndex = 'C';
        foreach ($leger_data['mapel_header'] as $mapel) {
            $sheet->setCellValue($columnIndex . '4', $mapel);
            $columnIndex++;
        }

        // Styling Header
        $lastColumn = chr(ord('B') + count($leger_data['mapel_header']));
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '0D6EFD']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]]
        ];
        $sheet->getStyle('A4:' . $lastColumn . '4')->applyFromArray($headerStyle);

        // Isi Data
        $row = 5;
        $no = 1;
        foreach ($leger_data['leger'] as $data_siswa) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $data_siswa['nama_siswa']);
            $columnIndex = 'C';
            foreach ($leger_data['mapel_header'] as $mapel) {
                $nilai = $data_siswa['nilai'][$mapel] ?? '-';
                $sheet->setCellValue($columnIndex . $row, $nilai);
                $sheet->getStyle($columnIndex . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $columnIndex++;
            }
            $row++;
        }

        // Styling Border untuk isi tabel
        $bodyStyle = ['borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]]];
        $sheet->getStyle('A4:' . $lastColumn . ($row - 1))->applyFromArray($bodyStyle);

        // Atur Lebar Kolom
        $sheet->getColumnDimension('A')->setWidth(5);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $columnIndex = 'C';
        foreach ($leger_data['mapel_header'] as $mapel) {
            $sheet->getColumnDimension($columnIndex)->setWidth(15);
            $columnIndex++;
        }

        // Kirim file ke browser
        $writer = new Xlsx($spreadsheet);
        $filename = 'leger-kelas-' . str_replace(' ', '-', strtolower($info_kelas['kelas'])) . '-' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit();
    }
}
