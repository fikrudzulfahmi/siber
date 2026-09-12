<?php
// File: app/controllers/ActivityLogController.php
require_once 'BaseController.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';

/**
 * Menu Activity Log — hanya untuk Administrator (level 1).
 * Menampilkan riwayat aktivitas: aktivitas, user, waktu, dan perangkat yang dipakai.
 */
class ActivityLogController extends BaseController
{
    private $model;

    public function __construct($pdo)
    {
        parent::__construct($pdo); // sudah termasuk cek login

        // Batasi hanya Administrator (level 1)
        $user = $_SESSION['user'] ?? null;
        if (!$user || !isLevel($user['level'] ?? null, 1)) {
            setFlash('danger', 'Akses ditolak. Menu Activity Log hanya untuk Administrator.');
            redirect('?controller=dashboard');
        }

        $this->model = new ActivityLog($this->db);
    }

    /**
     * Daftar aktivitas + filter + pagination.
     */
    public function index()
    {
        if (!$this->model->tabelAda()) {
            setFlash('danger', 'Tabel activity_log belum dibuat. Silakan import file activity_log.sql ke database terlebih dahulu.');
            redirect('?controller=dashboard');
        }

        $filters = [
            'id_employe'    => $_GET['id_employe'] ?? '',
            'aksi'          => $_GET['aksi'] ?? '',
            'modul'         => $_GET['modul'] ?? '',
            'tanggal_awal'  => $_GET['tanggal_awal'] ?? '',
            'tanggal_akhir' => $_GET['tanggal_akhir'] ?? '',
            'q'             => trim($_GET['q'] ?? ''),
        ];

        $perPage = (int)($_GET['per_page'] ?? 25);
        if (!in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 25;
        }

        $total  = $this->model->countTotal($filters);
        $halaman = max(1, (int)($_GET['hal'] ?? 1));
        $totalHalaman = max(1, (int)ceil($total / $perPage));
        if ($halaman > $totalHalaman) {
            $halaman = $totalHalaman;
        }
        $offset = ($halaman - 1) * $perPage;

        $logs = $this->model->getPaginated($filters, $perPage, $offset);

        view('admin/activity_log/index', [
            'judul'        => 'Activity Log',
            'logs'         => $logs,
            'filters'      => $filters,
            'perPage'      => $perPage,
            'halaman'      => $halaman,
            'total'        => $total,
            'totalHalaman' => $totalHalaman,
            'daftarUser'   => $this->model->getDaftarUser(),
            'daftarModul'  => $this->model->getDaftarModul(),
            'daftarAksi'   => ['Buka', 'Akses Data', 'Tambah', 'Ubah', 'Hapus', 'Login', 'Logout', 'Cetak', 'Export', 'Lainnya'],
        ]);
    }

    /**
     * Hapus log: mode=lama (lebih dari N hari) atau mode=semua.
     */
    public function hapus()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            redirect('?controller=activityLog&method=index');
        }

        $mode = $_POST['mode'] ?? 'lama';

        if ($mode === 'semua') {
            $jumlah = $this->model->hapusSemua();
            setFlash('success', "Seluruh riwayat activity log ({$jumlah} data) berhasil dihapus.");
        } else {
            $hari = (int)($_POST['hari'] ?? 90);
            if ($hari < 1) {
                $hari = 90;
            }
            $jumlah = $this->model->hapusLogLama($hari);
            setFlash('success', "{$jumlah} data log yang lebih lama dari {$hari} hari berhasil dihapus.");
        }

        redirect('?controller=activityLog&method=index');
    }
}
