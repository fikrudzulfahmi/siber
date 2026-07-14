<?php
require_once __DIR__ . '/../models/Siswa.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';
require_once __DIR__ . '/../vendor2/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

class SiswaController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo);
    }

    public function index()
    {
        $model = new Siswa($this->db);

        // Cek apakah ada tahun aktif?
        $tahun_aktif = $model->getTahunAktif();
        if (!$tahun_aktif) {
            setFlash('error', 'PERINGATAN: Belum ada Tahun Ajaran Aktif (Status=1). Data Kelas tidak akan muncul.');
        }

        $siswa = $model->getAll();
        $daftar_kelas = $model->getDaftarKelas();

        require __DIR__ . '/../views/admin/siswa/index.php';
    }

    public function create()
    {
        $model = new Siswa($this->db);
        $kelas = $model->getDaftarKelas();
        require __DIR__ . '/../views/admin/siswa/create.php';
    }

    public function store()
    {
        $model = new Siswa($this->db);
        try {
            $model->insert($_POST);
            setFlash('success', 'Siswa berhasil ditambahkan dan masuk ke kelas aktif.');
        } catch (Exception $e) {
            setFlash('error', 'Gagal menyimpan: ' . $e->getMessage());
        }
        header('Location: index.php?controller=siswa&method=index');
    }

    public function edit()
    {
        $model = new Siswa($this->db);
        $siswa = $model->find($_GET['id']); // Ini sudah include current_id_kelas
        $kelas = $model->getDaftarKelas();
        require __DIR__ . '/../views/admin/siswa/edit.php';
    }

    public function update()
    {
        $model = new Siswa($this->db);
        try {
            $model->update($_POST);
            setFlash('success', 'Data Siswa diperbarui.');
        } catch (Exception $e) {
            setFlash('error', 'Gagal update: ' . $e->getMessage());
        }
        header('Location: index.php?controller=siswa&method=index');
    }

    public function delete()
    {
        $model = new Siswa($this->db);
        $model->delete($_GET['id']);
        setFlash('success', 'Siswa dihapus permanen.');
        header('Location: index.php?controller=siswa&method=index');
    }

    public function uploadExcel()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file_excel'])) {
            $fileTmpPath = $_FILES['file_excel']['tmp_name'];

            try {
                $spreadsheet = IOFactory::load($fileTmpPath);
                $rows = $spreadsheet->getActiveSheet()->toArray();

                $model = new Siswa($this->db);
                $count = 0;

                // Loop baris excel (skip header row 0)
                for ($i = 1; $i < count($rows); $i++) {
                    $row = $rows[$i];
                    if (empty($row[1])) continue; // Skip jika nama kosong

                    // Cari ID Kelas dari nama di Excel
                    $id_kelas = $model->getIdKelasByNama($row[5]);

                    $data = [
                        'nama_siswa' => $row[1],
                        'nisn'       => $row[2],
                        'tempat_lhr' => $row[3],
                        'tgl_lhr'    => date('Y-m-d', strtotime($row[4])),
                        'id_kelas'   => $id_kelas, // Pass ID kelas (bisa null jika tidak ketemu)
                        'alamat'     => $row[6],
                        'nama_wali'  => $row[7],
                        'hp_wali'    => $row[8],
                    ];

                    // Fungsi insert model sudah otomatis handle ploting
                    $model->insert($data);
                    $count++;
                }

                setFlash('success', "$count Siswa berhasil diimport.");
            } catch (Exception $e) {
                setFlash('error', "Error Excel: " . $e->getMessage());
            }
            header("Location: ?controller=siswa&method=index");
            exit();
        }
    }
}
