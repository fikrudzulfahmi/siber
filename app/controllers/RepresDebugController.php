<?php
// File: app/controllers/RepresDebugController.php

require_once 'BaseController.php';
require_once __DIR__ . '/../models/Repres_Debug.php'; // Panggil model debug

class RepresDebugController extends BaseController
{
    private $debugModel;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->debugModel = new Repres_Debug($this->db);
    }

    public function index()
    {
        // --- KONFIGURASI DEBUG ---
        $startDate = '2025-08-01'; // Ganti dengan periode awal yang ingin dicek
        $endDate   = '2025-08-31'; // Ganti dengan periode akhir yang ingin dicek

        // Ganti dengan PIN pegawai yang datanya tidak sesuai
        $pin_pegawai_bermasalah = '57';
        // -------------------------

        header('Content-Type: text/plain; charset=utf-8');
        $this->debugModel->lacakSatuPegawai($pin_pegawai_bermasalah, $startDate, $endDate);
    }
}
