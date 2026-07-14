<?php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/Repres.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once __DIR__ . '/../vendor/autoload.php'; // DomPDF
require_once __DIR__ . '/../vendor2/autoload.php'; // phpoffice

use Dompdf\Dompdf;
use Dompdf\Options;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

class RepresController extends BaseController
{
    private $represModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->represModel = new Repres($this->db);
    }

    /**
     * Tampilkan halaman rekap presensi
     */
    public function index()
    {
        $startDate = $_GET['start'] ?? date('Y-m-01');
        $endDate   = $_GET['end'] ?? date('Y-m-t');
        $idJabatan = $_GET['jabatan'] ?? null;

        // Ambil rekap presensi
        $rekap = $this->represModel->getRekapPresensi($startDate, $endDate, $idJabatan);

        // Ambil list jabatan untuk dropdown
        $stmt = $this->db->query("SELECT id_jabatan, jabatan FROM jabatan ORDER BY jabatan");
        $listJabatan = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin/repres/index.php';
    }

    /**
     * Cetak PDF rekap presensi
     */
    public function cetak()
    {
        $startDate = $_GET['start'] ?? date('Y-m-01');
        $endDate   = $_GET['end'] ?? date('Y-m-t');
        $idJabatan = $_GET['jabatan'] ?? null;

        $rekap = $this->represModel->getRekapPresensi($startDate, $endDate, $idJabatan);

        // Info filter
        $infoFilter = "Periode: " . formatTanggalIndo($startDate) . " s/d " . formatTanggalIndo($endDate);
        if ($idJabatan) {
            $jab = $this->db->prepare("SELECT jabatan FROM jabatan WHERE id_jabatan = ?");
            $jab->execute([$idJabatan]);
            if ($row = $jab->fetch(PDO::FETCH_ASSOC)) {
                $infoFilter .= " | Jabatan: {$row['jabatan']}";
            }
        }

        $tanggalCetak = formatTanggalIndo(date('Y-m-d'));

        // Render view cetak
        ob_start();
        include __DIR__ . '/../views/admin/repres/cetak.php';
        $html = ob_get_clean();

        // Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        // Stream PDF
        $dompdf->stream("rekap_presensi.pdf", ["Attachment" => 0]);
        exit;
    }
    /**
 * Export rekap presensi ke Excel
 */
public function exportExcel()
{
    $startDate = $_GET['start'] ?? date('Y-m-01');
    $endDate   = $_GET['end'] ?? date('Y-m-t');
    $idJabatan = $_GET['jabatan'] ?? null;

    $rekap = $this->represModel->getRekapPresensi($startDate, $endDate, $idJabatan);

    $infoFilter = "Periode: " . formatTanggalIndo($startDate) . " s/d " . formatTanggalIndo($endDate);
    if ($idJabatan) {
        $jab = $this->db->prepare("SELECT jabatan FROM jabatan WHERE id_jabatan = ?");
        $jab->execute([$idJabatan]);
        if ($row = $jab->fetch(PDO::FETCH_ASSOC)) {
            $infoFilter .= " | Jabatan: {$row['jabatan']}";
        }
    }

    $tanggalCetak = formatTanggalIndo(date('Y-m-d'));

    // 🔹 Buat Spreadsheet
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Rekap Presensi');

    // Header judul
    $sheet->mergeCells('A1:J1');
    $sheet->setCellValue('A1', 'Rekap Presensi Pegawai');
    $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A2:J2');
    $sheet->setCellValue('A2', 'SMP Bustanul Ulum & MA Raudlatul Mutaalimin');
    $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A3:J3');
    $sheet->setCellValue('A3', $infoFilter);
    $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    $sheet->mergeCells('A4:J4');
    $sheet->setCellValue('A4', "Tanggal cetak: $tanggalCetak");
    $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Table header
    $header = ['Nama', 'Jabatan', 'Hari Efektif', 'Kehadiran', 'Alpa', 'Terlambat', 'Pulang Cepat', 'Sakit', 'Izin', 'Dinas Luar'];
    $sheet->fromArray($header, NULL, 'A6');

    // Styling header
    $sheet->getStyle('A6:J6')->getFont()->setBold(true);
    $sheet->getStyle('A6:J6')->getFill()
          ->setFillType(Fill::FILL_SOLID)
          ->getStartColor()->setARGB('FFCCE5FF');
    $sheet->getStyle('A6:J6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

    // Table content
    $rowNum = 7;
    foreach ($rekap as $row) {
        $sheet->setCellValue("A$rowNum", $row['nama']);
        $sheet->setCellValue("B$rowNum", $row['jabatan']);
        $sheet->setCellValue("C$rowNum", $row['Hari_Efektif'] ?? '-');
        $sheet->setCellValue("D$rowNum", $row['Kehadiran']);
        $sheet->setCellValue("E$rowNum", $row['Alpa']);
        $sheet->setCellValue("F$rowNum", $row['Terlambat']);
        $sheet->setCellValue("G$rowNum", $row['Pulang_Cepat']);
        $sheet->setCellValue("H$rowNum", $row['Sakit']);
        $sheet->setCellValue("I$rowNum", $row['Izin']);
        $sheet->setCellValue("J$rowNum", $row['Dinas_Luar']);

        // Alternating row color
        $fillColor = ($rowNum % 2 == 0) ? 'FFFFFFFF' : 'FFF2F2F2';
        $sheet->getStyle("A$rowNum:J$rowNum")->getFill()
              ->setFillType(Fill::FILL_SOLID)
              ->getStartColor()->setARGB($fillColor);

        $rowNum++;
    }

    // Borders
    $sheet->getStyle('A6:J' . ($rowNum - 1))
          ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

    // Auto width
    foreach (range('A','J') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

// Alignment untuk numeric / data tengah
foreach (range(3, 10) as $colIndex) { // C-J
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex);
    $sheet->getStyle($colLetter . "7:" . $colLetter . ($rowNum - 1))
          ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

    // Output Excel
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="rekap_presensi.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

}
