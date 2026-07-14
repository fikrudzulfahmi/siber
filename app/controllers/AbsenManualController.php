<?php
require_once __DIR__ . '/../models/AbsenManual.php';
require_once 'BaseController.php';

class AbsenManualController extends BaseController
{
    private $model;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->model = new AbsenManual($this->db);
    }

    public function index()
    {
        $pegawai_list = $this->model->getAllUsers();
        require __DIR__ . '/../views/admin/presensi/absen_manual.php';
    }

public function store()
{
    $pin        = $_POST['pin'];
    $status     = $_POST['status'];
    $keterangan = $_POST['keterangan'];
    $tanggal    = $_POST['tanggal'];
    $jam        = $_POST['jam']; // ambil input jam

    $scan_date = $tanggal . ' ' . $jam . ':00'; // format lengkap

    $success = $this->model->tambahAbsen($pin, $status, $keterangan, $scan_date);

    if ($success) {
        setFlash('success', 'Absen manual berhasil ditambahkan.');
    } else {
        setFlash('error', 'Gagal menambahkan absen.');
    }

    header('Location: index.php?controller=absenManual&method=index');
}

}
