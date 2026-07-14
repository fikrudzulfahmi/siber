<?php
// File: app/controllers/RekapIndividuController.php

require_once 'BaseController.php';
require_once __DIR__ . '/../models/RekapIndividuModel.php';
// Pastikan path ke autoload.php dari DomPDF sudah benar
require_once __DIR__ . '/../vendor/autoload.php'; // pastikan autoload DomPDF

use Dompdf\Dompdf;
use Dompdf\Options;

class RekapIndividuController extends BaseController
{
    private $rekapIndividuModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->rekapIndividuModel = new RekapIndividuModel($this->db);
    }

    // Fungsi helper untuk format tanggal, diletakkan di dalam controller
    private function formatTanggalIndoFull($tanggal)
    {
        if (empty($tanggal) || $tanggal == '0000-00-00') return '';
        $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $timestamp = strtotime($tanggal);
        return $hari[date('l', $timestamp)] . ', ' . date('d', $timestamp) . ' ' . $bulan[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
    }

    private function formatTanggalIndo($tanggal, $format = 'full')
    {
        if (empty($tanggal) || $tanggal == '0000-00-00') return '';

        $hari = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
        $bulan = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

        $timestamp = strtotime($tanggal);

        switch ($format) {
            case 'short':
                return date('d', $timestamp) . ' ' . $bulan[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
            case 'month_year':
                return $bulan[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
            default: // full
                return $hari[date('l', $timestamp)] . ', ' . date('d', $timestamp) . ' ' . $bulan[(int)date('m', $timestamp)] . ' ' . date('Y', $timestamp);
        }
    }

    public function index()
    {
        $listPegawai = $this->rekapIndividuModel->getAllPegawai();

        $rekapData = null;
        $infoPegawai = null;
        $periode = $_POST['periode'] ?? date('Y-m');
        $pin = $_POST['pin'] ?? null;

        $rekapDataFormatted = null;

        if ($pin) {
            $rekapData = $this->rekapIndividuModel->generateRekapLengkap($pin, $periode);
            $infoPegawai = $this->rekapIndividuModel->getPegawaiInfo($pin);

            // Siapkan data yang sudah diformat untuk View
            if ($rekapData) {
                $rekapDataFormatted = $rekapData;
                foreach ($rekapData['rincian'] as $key => $rincian) {
                    $rekapDataFormatted['rincian'][$key]['tanggal_formatted'] = $this->formatTanggalIndoFull($rincian['tanggal']);
                }
            }
        }

        require __DIR__ . '/../views/admin/rekap_individu/index.php';
    }

    public function cetak()
    {
        $pin = $_GET['pin'] ?? null;
        $periode = $_GET['periode'] ?? date('Y-m');

        if (!$pin) die("Error: Pegawai belum dipilih.");

        $rekapData = $this->rekapIndividuModel->generateRekapLengkap($pin, $periode);
        $infoPegawai = $this->rekapIndividuModel->getPegawaiInfo($pin);

        // Siapkan data yang sudah diformat untuk template PDF
        if ($rekapData) {
            foreach ($rekapData['rincian'] as $key => $rincian) {
                $rekapData['rincian'][$key]['tanggal_formatted'] = $this->formatTanggalIndo($rincian['tanggal']);
            }
        }

        // ✅ PERBAIKAN FORMAT PERIODE TANGGAL
        $tanggalAwal = "$periode-01";
        $tanggalAkhir = date("Y-m-t", strtotime($periode));

        $periodeFormatted = [
            'awal' => $this->formatTanggalIndo($tanggalAwal, 'short'),
            'akhir' => $this->formatTanggalIndo($tanggalAkhir, 'short')
        ];

        $tanggalCetak = $this->formatTanggalIndo(date('Y-m-d'), 'short');

        ob_start();
        include __DIR__ . '/../views/admin/rekap_individu/cetak.php';
        $html = ob_get_clean();
        // Konfigurasi dan inisialisasi DomPDF
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Menampilkan PDF di browser
        $filename = "Presensi Individu_ " . $infoPegawai['nama'] . "_" . $periode . ".pdf";
        $dompdf->stream($filename, ["Attachment" => 0]);
        exit;
    }
}
