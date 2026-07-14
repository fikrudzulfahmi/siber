<?php
require_once __DIR__ . '/../models/Kategori.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once __DIR__ . '/../vendor2/autoload.php';
require_once __DIR__ . '/../vendor/autoload.php'; // pastikan autoload DomPDF
require_once 'BaseController.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class KategoriController
{
    protected $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function index()
    {
        // 1. Cek parameter wajib ID Mapel Guru
        if (!isset($_GET['id_mapel_guru'])) {
            header('Location: ' . BASEURL . '/penilaian');
            exit;
        }

        $id_mapel_guru = $_GET['id_mapel_guru'];

        // 2. Instansiasi Model
        $model = new Kategori($this->db);

        // 3. Ambil Tahun Pelajaran yang Aktif
        $activeTahun = $model->getActiveTahun();

        if (!$activeTahun) {
            echo "Error: Belum ada Tahun Pelajaran yang diset Aktif oleh Admin.";
            exit;
        }

        // --- TAMBAHKAN VALIDASI CEK KATEGORI DI SINI ---
        // Gunakan id_tahun_pelajaran dari tahun yang sedang aktif
        $cekKategori = $model->isKategoriExist($id_mapel_guru, $activeTahun['id_tahun_pelajaran']);

        // 4. Siapkan Data untuk View
        $data = [
            'active_tahun' => $activeTahun,
            'kategoriList' => $model->getAllKategoriByMapel($id_mapel_guru, $activeTahun['id_tahun_pelajaran']),
            'info'         => $model->getInfoMapel($id_mapel_guru),
            'cekKategori'  => $cekKategori // Masukkan ke array data jika view Anda menggunakan $data['cekKategori']
        ];

        // 5. Panggil View
        // Karena view Anda menggunakan require langsung, variabel $cekKategori harus didefinisikan secara eksplisit
        require __DIR__ . '/../views/admin/kategori/index.php';
    }

    public function create()
    {
        $id_mapel_guru = $_GET['id_mapel_guru'] ?? null;
        if (!$id_mapel_guru) die('ID Mapel Guru tidak ditemukan.');

        $model = new Kategori($this->db);

        // 1. Ambil info mapel dan tahun aktif
        $info = $model->getInfoMapelGuru($id_mapel_guru);
        $id_tahun_aktif = $info['id_tahun']; // Mengambil ID Tahun dari jadwal guru

        // 2. Cek apakah kategori sudah pernah dibuat untuk tahun ini
        $cekKategori = $model->isKategoriExist($id_mapel_guru, $id_tahun_aktif);

        // 3. Logika Proteksi: Jika sudah ada, jangan kasih akses ke form create
        if ($cekKategori) {
            setFlash('error', 'Akses ditolak! Kategori untuk semester ini sudah ada.');
            header("Location: ?controller=kategori&method=index&id_mapel_guru=" . $id_mapel_guru);
            exit;
        }

        // 4. Jika belum ada, baru tampilkan view form tambah
        require __DIR__ . '/../views/admin/kategori/create.php';
    }

    public function store()
    {
        // Mengubah array nama_ns menjadi JSON agar bisa disimpan di database
        $nama_ns = json_encode($_POST['nama_ns']);

        $data = [
            'id_mapel_guru' => $_POST['id_mapel_guru'],
            'id_tahun_pelajaran' => $_POST['id_tahun_pelajaran'],
            'kategori' => $_POST['kategori'],
            'banyak_ns' => $_POST['banyak_ns'],
            'nama_ns' => $nama_ns,
            'bobot_ns' => $_POST['bobot_ns'],
            'bobot_sts' => $_POST['bobot_sts'],
            'bobot_sas' => $_POST['bobot_sas']
        ];

        $model = new Kategori($this->db);

        // 1. Simpan Kategori dengan pengecekan validasi di Model
        $id_kategori_baru = $model->create($data);

        // Cek apakah proses create berhasil atau ditolak karena duplikat
        if ($id_kategori_baru === false) {
            // Jika duplikat, kirim flash error dan hentikan proses
            setFlash('error', 'Gagal! Anda sudah membuat kategori nilai untuk mata pelajaran ini di semester/tahun pelajaran tersebut.');
            header("Location: ?controller=kategori&method=index&id_mapel_guru=" . $_POST['id_mapel_guru']);
            exit; // Penting untuk menghentikan eksekusi kode di bawahnya
        }

        // 2. Isi tabel nilai dengan siswa dari PLOTING_SISWA (Hanya jalan jika create berhasil)
        $model->populateNilaiForNewKategori($id_kategori_baru, $_POST['id_mapel_guru']);

        setFlash('success', 'Kategori berhasil ditambahkan dan data siswa berhasil di-generate.');
        header("Location: ?controller=kategori&method=index&id_mapel_guru=" . $_POST['id_mapel_guru']);
        exit;
    }

    public function nilai()
    {
        $id_kategori = $_GET['id'] ?? null;
        if (!$id_kategori) die('ID kategori tidak ditemukan.');
        $id_mapel_guru = $_GET['id_mapel_guru'] ?? null;
        if (!$id_mapel_guru) die('ID mapel guru tidak ditemukan.');

        $model = new Kategori($this->db);
        $info = $model->getInfoMapelGuru($id_mapel_guru);
        $nilaiList = $model->getByKategori($id_kategori);
        $kategoriInfo = $model->find($id_kategori);

        require __DIR__ . '/../views/admin/kategori/nilai.php';
    }

    public function updateNilai()
    {
        $id_kategori = $_POST['id_kategori'] ?? null;
        $id_mapel_guru = $_POST['id_mapel_guru'] ?? null;
        $nilai = $_POST['nilai'] ?? [];

        if (!$id_kategori || !$id_mapel_guru || empty($nilai)) {
            setFlash('danger', 'Data tidak lengkap.');
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $model = new Kategori($this->db);
        foreach ($nilai as $id_nilai => $item) {
            // PERBAIKAN: Kirim $id_kategori sebagai parameter ketiga
            $model->updateNilai($id_nilai, $item, $id_kategori);
        }

        setFlash('success', 'Semua nilai berhasil diperbarui.');
        header('Location: index.php?controller=kategori&method=nilai&id=' . urlencode($id_kategori) . '&id_mapel_guru=' . urlencode($id_mapel_guru));
        exit;
    }

    public function edit()
    {
        $id_kategori = $_GET['id'] ?? null;
        if (!$id_kategori) die('ID Kategori tidak ditemukan.');

        $model = new Kategori($this->db);
        $kategori = $model->find($id_kategori);
        if (!$kategori) die('Data kategori tidak ditemukan.');

        require __DIR__ . '/../views/admin/kategori/edit.php';
    }

    public function updateKategori()
    {
        $nama_ns = json_encode($_POST['nama_ns']);

        $data = [
            'id_kategori' => $_POST['id_kategori'],
            'kategori' => $_POST['kategori'],
            'banyak_ns' => $_POST['banyak_ns'],
            'nama_ns' => $nama_ns,
            'bobot_ns' => $_POST['bobot_ns'],
            'bobot_sts' => $_POST['bobot_sts'],
            'bobot_sas' => $_POST['bobot_sas']
        ];

        $model = new Kategori($this->db);
        $model->update($data);
        setFlash('success', 'Kategori berhasil diperbarui.');
        header("Location: ?controller=kategori&method=index&id_mapel_guru=" . $_POST['id_mapel_guru']);
    }

    public function delete()
    {
        $id_kategori = $_GET['id'] ?? null;
        $id_mapel_guru = $_GET['id_mapel_guru'] ?? null;

        if (!$id_kategori || !$id_mapel_guru) {
            setFlash('danger', 'Gagal menghapus: ID tidak ditemukan.');
            header('Location: ' . $_SERVER['HTTP_REFERER']);
            exit;
        }

        $model = new Kategori($this->db);
        if ($model->delete($id_kategori)) {
            setFlash('success', 'Kategori dan semua nilai terkait berhasil dihapus.');
        } else {
            setFlash('danger', 'Gagal menghapus kategori.');
        }
        header('Location: index.php?controller=kategori&method=index&id_mapel_guru=' . $id_mapel_guru);
        exit;
    }
    public function exportExcel()
    {
        // 1. Bersihkan buffer di awal agar tidak ada peringatan/output yang nyelip
        if (ob_get_level()) ob_end_clean();

        $modelKategori = new Kategori($this->db);
        $id_kategori = $_GET['id_kategori'];
        $id_mapel_guru = $_GET['id_mapel_guru'];

        $kategori = $modelKategori->find($id_kategori);
        $info = $modelKategori->getInfoMapelGuru($id_mapel_guru);
        $nilaiList = $modelKategori->getNilaiByKategori($id_kategori);

        // PERBAIKAN DI SINI: Jika nama_ns null, ganti dengan string JSON kosong '{}'
        $customNama = json_decode($kategori['nama_ns'] ?? '{}', true);

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // -- Header Judul --
        $sheet->setCellValue('A1', 'DATA NILAI: ' . strtoupper($kategori['kategori']));
        $sheet->setCellValue('A2', 'Mapel: ' . $info['nama_mapel'] . ' | Kelas: ' . $info['kelas']);

        // -- Header Tabel --
        $sheet->setCellValue('A4', 'ID_NILAI');
        $sheet->setCellValue('B4', 'No');
        $sheet->setCellValue('C4', 'Nama Siswa');

        $col = 'D';
        $banyak_ns = $kategori['banyak_ns'] ?? 0;
        for ($i = 1; $i <= $banyak_ns; $i++) {
            $sheet->setCellValue($col . '4', $customNama['n' . $i] ?? 'N' . $i);
            $col++;
        }
        $sheet->setCellValue($col++ . '4', 'STS');
        $sheet->setCellValue($col++ . '4', 'SAS');

        // -- Isi Data --
        $rowNum = 5;
        foreach ($nilaiList as $idx => $n) {
            $sheet->setCellValue('A' . $rowNum, $n['id_nilai']);
            $sheet->setCellValue('B' . $rowNum, $idx + 1);
            $sheet->setCellValue('C' . $rowNum, $n['nama_siswa']);

            $colData = 'D';
            for ($i = 1; $i <= $banyak_ns; $i++) {
                $sheet->setCellValue($colData . $rowNum, $n['n' . $i]);
                $colData++;
            }
            $sheet->setCellValue($colData++ . $rowNum, $n['sts']);
            $sheet->setCellValue($colData++ . $rowNum, $n['sas']);
            $rowNum++;
        }

        // -- Proses Download Tanpa Output Sampingan --
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="Nilai_' . urlencode($kategori['kategori']) . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
    public function importExcel()
    {
        $file = $_FILES['file_excel']['tmp_name'];
        $id_kategori = $_POST['id_kategori'];
        $id_mapel_guru = $_POST['id_mapel_guru'];

        // Instansiasi lokal
        $modelKategori = new Kategori($this->db);
        $kategori = $modelKategori->find($id_kategori);

        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file);
        $spreadsheet = $reader->load($file);
        $sheetData = $spreadsheet->getActiveSheet()->toArray();

        // Looping mulai baris ke-5 (index 4)
        for ($i = 4; $i < count($sheetData); $i++) {
            $id_nilai = $sheetData[$i][0]; // Kolom A: ID_NILAI
            if (empty($id_nilai)) continue;

            $updateData = [];
            // Ambil N1 sampai N10 berdasarkan banyak_ns
            for ($j = 1; $j <= 10; $j++) {
                if ($j <= $kategori['banyak_ns']) {
                    $updateData['n' . $j] = $sheetData[$i][2 + $j]; // Data nilai mulai kolom D (index 3)
                } else {
                    $updateData['n' . $j] = null;
                }
            }

            $posisiSTS = 3 + $kategori['banyak_ns'];
            $posisiSAS = 4 + $kategori['banyak_ns'];

            $updateData['sts'] = $sheetData[$i][$posisiSTS];
            $updateData['sas'] = $sheetData[$i][$posisiSAS];

            // Update ke database
            $modelKategori->updateNilai($id_nilai, $updateData, $id_kategori);
        }

        header("Location: ?controller=kategori&method=nilai&id=$id_kategori&id_mapel_guru=$id_mapel_guru");
        exit;
    }
    public function exportPdf()
    {
        // 1. Bersihkan buffer agar pesan "Deprecated" tidak merusak file PDF
        if (ob_get_level()) ob_end_clean();

        // 2. Instansiasi Model secara lokal
        $modelKategori = new Kategori($this->db);
        $id_kategori = $_GET['id_kategori'];
        $id_mapel_guru = $_GET['id_mapel_guru'];

        $kategori = $modelKategori->find($id_kategori);
        $info = $modelKategori->getInfoMapelGuru($id_mapel_guru);
        $nilaiList = $modelKategori->getNilaiByKategori($id_kategori);

        // 3. Fix Deprecated: Pastikan tidak null
        $customNama = json_decode($kategori['nama_ns'] ?? '{}', true);
        $banyak_ns = $kategori['banyak_ns'] ?? 0;

        // 4. Inisialisasi Dompdf dengan Namespace yang benar
        // Jika masih error "Class not found", pastikan vendor/autoload.php sudah di-include
        try {
            $options = new \Dompdf\Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isRemoteEnabled', true);

            $dompdf = new \Dompdf\Dompdf($options);
        } catch (\Exception $e) {
            die("Error: Library Dompdf tidak ditemukan. Pastikan sudah diinstal via composer.");
        }

        // 5. Bangun HTML untuk PDF
        $html = '
    <style>
        table { width: 100%; border-collapse: collapse; font-family: sans-serif; font-size: 12px; }
        th, td { border: 1px solid #333; padding: 8px; text-align: center; }
        .header { text-align: center; margin-bottom: 20px; }
        .text-left { text-align: left; }
    </style>
    <div class="header">
        <h3>LAPORAN NILAI: ' . strtoupper($kategori['kategori']) . '</h3>
        <p>Mata Pelajaran: ' . $info['nama_mapel'] . ' | Kelas: ' . $info['kelas'] . '</p>
    </div>
    <table>
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th width="30">No</th>
                <th class="text-left">Nama Siswa</th>';

        for ($i = 1; $i <= $banyak_ns; $i++) {
            $html .= '<th>' . ($customNama['n' . $i] ?? 'N' . $i) . '</th>';
        }

        $html .= '  <th>STS</th>
                <th>SAS</th>
                <th style="background-color: #e8f5e9;">Rapor</th>
            </tr>
        </thead>
        <tbody>';

        foreach ($nilaiList as $idx => $n) {
            $html .= '<tr>
            <td>' . ($idx + 1) . '</td>
            <td class="text-left">' . htmlspecialchars($n['nama_siswa']) . '</td>';

            for ($i = 1; $i <= $banyak_ns; $i++) {
                $html .= '<td>' . ($n['n' . $i] ?? '-') . '</td>';
            }

            $html .= '<td>' . ($n['sts'] ?? '-') . '</td>
                  <td>' . ($n['sas'] ?? '-') . '</td>
                  <td style="font-weight:bold;">' . round($n['nilai_raport'], 2) . '</td>
        </tr>';
        }

        $html .= '</tbody></table>';

        // 6. Render dan Stream
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $nama_file = "Nilai_" . str_replace(' ', '_', $kategori['kategori']) . ".pdf";
        $dompdf->stream($nama_file, ["Attachment" => false]);
        exit;
    }
}
