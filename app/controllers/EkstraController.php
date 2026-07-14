<?php
// File: app/controllers/EkstraController.php

require_once __DIR__ . '/../models/EkstraModel.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class EkstraController
{
    protected $db;
    protected $model;

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->model = new EkstraModel($this->db);

        // Proteksi Session
        if (!isset($_SESSION['user']['id'])) {
            header("Location: ?controller=auth&method=login");
            exit();
        }
    }

    /**
     * Menampilkan daftar ekstrakurikuler berdasarkan tahun pelajaran aktif
     */
    public function index()
    {
        $activeTahun = $this->model->getActiveTahun();
        $id_tahun = $activeTahun['id_tahun_pelajaran'] ?? null;
        $id_guru = $_SESSION['user']['id_employe'] ?? $_SESSION['user']['id'];
        $is_admin = isAnyLevel($_SESSION['user']['level'], [1]);

        $daftar_ekstra = $this->model->getAllEkstra($id_tahun, $id_guru, $is_admin);

        // --- TAMBAHAN: Cek status jurnal hari ini ---
        $hari_ini = date('Y-m-d');
        foreach ($daftar_ekstra as $key => $ex) {
            $jurnal = $this->model->cekJurnalHariIni($ex['id_ekstra'], $hari_ini);
            $daftar_ekstra[$key]['sudah_isi_jurnal'] = $jurnal ? true : false;
        }

        $raporAktif = $this->model->getActiveRapor();
        $is_locked = (!$raporAktif || (isset($raporAktif['is_locked']) && $raporAktif['is_locked'] == 1));

        require __DIR__ . '/../views/admin/ekstra/index.php';
    }

    /**
     * Form tambah ekstra (Khusus Admin)
     */
    public function tambah()
    {
        $id_level = $_SESSION['user']['level'];

        // Proteksi: Hanya Admin (Level 1) yang bisa menambah Master Ekstra
        if (!isAnyLevel($id_level, [1])) {
            die("Akses Ditolak: Anda bukan Administrator.");
        }

        // Ambil daftar guru untuk dropdown Penanggung Jawab
        $stmt = $this->db->query("SELECT id_employe, nama FROM employe ORDER BY nama ASC");
        $list_guru = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin/ekstra/tambah.php';
    }

    public function simpan()
    {
        // Ambil ID Tahun dari Session User
        $data = [
            'nama_ekstra'        => $_POST['nama_ekstra'],
            'id_guru_pengampu'   => $_POST['id_guru_pengampu'],
            'id_tahun_pelajaran' => $_SESSION['user']['id_tahun'],
            'keterangan'         => $_POST['keterangan']
        ];

        if ($this->model->simpanEkstra($data)) {
            header("Location: ?controller=ekstra&method=index&status=success");
        } else {
            header("Location: ?controller=ekstra&method=tambah&status=error");
        }
        exit();
    }

    public function edit()
    {
        $id_level = $_SESSION['user']['level'];
        if (!isAnyLevel($id_level, [1])) {
            die("Akses Ditolak.");
        }

        $id_ekstra = $_GET['id_ekstra'];
        $ekstra = $this->model->getEkstraById($id_ekstra);

        // Ambil daftar guru untuk dropdown
        $stmt = $this->db->query("SELECT id_employe, nama FROM employe ORDER BY nama ASC");
        $list_guru = $stmt->fetchAll(PDO::FETCH_ASSOC);

        require __DIR__ . '/../views/admin/ekstra/edit.php';
    }

    public function update()
    {
        $data = [
            'id_ekstra' => $_POST['id_ekstra'],
            'nama_ekstra' => $_POST['nama_ekstra'],
            'id_guru_pengampu' => $_POST['id_guru_pengampu'],
            'keterangan' => $_POST['keterangan']
        ];

        if ($this->model->updateEkstra($data)) {
            header("Location: ?controller=ekstra&method=index&status=updated");
        } else {
            header("Location: ?controller=ekstra&method=edit&id_ekstra=" . $data['id_ekstra'] . "&status=error");
        }
        exit();
    }

    public function anggota()
    {
        $id_ekstra = $_GET['id_ekstra'];
        $id_tahun = $_SESSION['user']['id_tahun'];

        $ekstra = $this->model->getEkstraById($id_ekstra);
        $anggota = $this->model->getAnggotaEkstra($id_ekstra);
        $siswa_tersedia = $this->model->getSiswaTersedia($id_tahun, $id_ekstra);

        // --- BAGIAN GURU/PEMBINA ---
        $guru_tersedia = $this->model->getAllGuru();
        // Ambil data lengkap pembina aktif (untuk tampilan kiri)
        $pendamping_aktif = $this->model->getPendampingAktif($id_ekstra);
        // Ambil hanya ID-nya (untuk checklist kanan)
        $pendamping_saat_ini = $this->model->getIdsPendamping($id_ekstra);
        $id_pendamping_sekarang = array_column($pendamping_saat_ini, 'id_employe');

        require __DIR__ . '/../views/admin/ekstra/anggota.php';
    }
    public function hapusPendampingSatu()
    {
        $id_p = $_GET['id_p'];
        $id_ekstra = $_GET['id_ekstra'];

        $sql = "DELETE FROM ekstra_pendamping WHERE id_pendamping = ?";
        $this->db->prepare($sql)->execute([$id_p]);

        setFlash('success', 'Pendamping berhasil dihapus.');
        header("Location: ?controller=ekstra&method=anggota&id_ekstra=" . $id_ekstra);
        exit();
    }

    public function simpanAnggota()
    {
        $id_ekstra = $_POST['id_ekstra'];
        if (isset($_POST['pilih_siswa']) && is_array($_POST['pilih_siswa'])) {
            foreach ($_POST['pilih_siswa'] as $id_ploting) {
                $this->model->tambahAnggota($id_ekstra, $id_ploting);
            }
        }
        header("Location: ?controller=ekstra&method=anggota&id_ekstra=$id_ekstra&status=success");
        exit();
    }

    public function hapusAnggota()
    {
        $id_anggota = $_GET['id_anggota'];
        $id_ekstra = $_GET['id_ekstra'];
        $this->model->hapusAnggota($id_anggota);
        header("Location: ?controller=ekstra&method=anggota&id_ekstra=$id_ekstra&status=deleted");
        exit();
    }

    /**
     * Form Input Jurnal Kegiatan, Presensi, dan Foto
     */
    public function inputKegiatan()
    {
        // 1. Ambil ID Ekstra dari URL
        $id_ekstra = $_GET['id_ekstra'] ?? null;

        if (!$id_ekstra) {
            die("ID Ekstra tidak ditemukan.");
        }

        // 2. Ambil data master ekstra (untuk nama ekskul & koordinator utama)
        $ekstra = $this->model->getEkstraById($id_ekstra);

        // 3. Ambil daftar anggota (untuk daftar presensi siswa)
        $anggota = $this->model->getAnggotaEkstra($id_ekstra);

        // --- TAMBAHKAN BARIS INI ---
        // 4. Ambil daftar pendamping (agar variabel $pendamping_aktif tersedia di view)
        $pendamping_aktif = $this->model->getPendampingAktif($id_ekstra);
        // ---------------------------

        // 5. Pastikan semua variabel di atas dikirim ke view
        require __DIR__ . '/../views/admin/ekstra/input_kegiatan.php';
    }

    public function simpanKegiatan()
    {
        $id_ekstra = $_POST['id_ekstra'];
        $tanggal = $_POST['tanggal'];

        // 1. Validasi ganda (Cek apakah sudah ada jurnal di tanggal tersebut)
        if ($this->model->cekJurnalHariIni($id_ekstra, $tanggal)) {
            setFlash('error', 'Jurnal untuk tanggal tersebut sudah ada!');
            header("Location: ?controller=ekstra&method=index");
            exit();
        }

        // 2. Jalankan insert Jurnal Utama
        $id_kegiatan = $this->model->insertJurnal([
            'id_ekstra'      => $id_ekstra,
            'nama_kegiatan'  => $_POST['nama_kegiatan'],
            'tanggal'        => $tanggal,
            'isi_kegiatan'   => $_POST['isi_kegiatan']
        ]);

        if ($id_kegiatan) {
            // --- BAGIAN A: SIMPAN PRESENSI SISWA ---
            if (isset($_POST['presensi'])) {
                foreach ($_POST['presensi'] as $id_ploting => $status) {
                    $this->model->insertPresensi($id_kegiatan, $id_ploting, $status);
                }
            }

            // --- BAGIAN B: SIMPAN PRESENSI GURU (TAMBAHAN BARU) ---
            if (isset($_POST['presensi_guru'])) {
                foreach ($_POST['presensi_guru'] as $id_guru => $status) {
                    // Pastikan Anda sudah membuat fungsi insertPresensiGuru di Model
                    $this->model->insertPresensiGuru($id_kegiatan, $id_guru, $status);
                }
            }

            // --- BAGIAN C: SIMPAN FOTO ---
            if (!empty($_FILES['foto']['name'])) {
                $nama_file = "EKSTRA_" . time() . "_" . $_FILES['foto']['name'];
                $target_dir = "../public/uploads/ekstra/";

                // Cek folder, buat jika belum ada
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }

                if (move_uploaded_file($_FILES['foto']['tmp_name'], $target_dir . $nama_file)) {
                    $this->model->insertFoto($id_kegiatan, $nama_file);
                }
            }

            setFlash('success', 'Jurnal, Presensi Siswa & Guru berhasil disimpan!');
        } else {
            setFlash('error', 'Gagal menyimpan data.');
        }

        header("Location: ?controller=ekstra&method=index");
        exit();
    }

    public function riwayat()
    {
        $id_ekstra = $_GET['id_ekstra'];
        $ekstra = $this->model->getEkstraById($id_ekstra);

        // Ambil semua jurnal kegiatan untuk ekstra ini
        $riwayat = $this->model->getRiwayatKegiatan($id_ekstra);

        require __DIR__ . '/../views/admin/ekstra/riwayat.php';
    }

    public function hapusJurnal()
    {
        $id_kegiatan = $_GET['id_kegiatan'];
        $id_ekstra = $_GET['id_ekstra'];

        // Ambil data jurnal untuk cek file foto
        $jurnal = $this->model->getJurnalById($id_kegiatan);

        if ($this->model->deleteJurnal($id_kegiatan)) {
            // Hapus file fisik foto jika ada
            if (!empty($jurnal['nama_file'])) {
                $path = "../public/uploads/ekstra/" . $jurnal['nama_file'];
                if (file_exists($path)) unlink($path);
            }
            setFlash('success', 'Jurnal berhasil dihapus.');
        }

        header("Location: ?controller=ekstra&method=riwayat&id_ekstra=" . $id_ekstra);
        exit();
    }

    public function editJurnal()
    {
        $id_kegiatan = $_GET['id_kegiatan'];
        $id_ekstra = $_GET['id_ekstra'];

        $ekstra = $this->model->getEkstraById($id_ekstra);
        $jurnal = $this->model->getJurnalById($id_kegiatan);
        $anggota = $this->model->getAnggotaEkstra($id_ekstra);
        $presensi_lama = $this->model->getPresensiByJurnal($id_kegiatan);

        require __DIR__ . '/../views/admin/ekstra/edit_kegiatan.php';
    }

    public function updateKegiatan()
    {
        $id_kegiatan = $_POST['id_ekstra_kegiatan'];
        $id_ekstra = $_POST['id_ekstra'];

        // 1. Update Jurnal
        $this->model->updateJurnal([
            'id_ekstra_kegiatan' => $id_kegiatan,
            'nama_kegiatan' => $_POST['nama_kegiatan'],
            'tanggal' => $_POST['tanggal'],
            'isi_kegiatan' => $_POST['isi_kegiatan']
        ]);

        // 2. Update Foto jika ada file baru
        if (!empty($_FILES['foto']['name'])) {
            $nama_file = "EKSTRA_" . time() . "_" . $_FILES['foto']['name'];
            if (move_uploaded_file($_FILES['foto']['tmp_name'], "../public/uploads/ekstra/" . $nama_file)) {
                $this->model->updateFoto($id_kegiatan, $nama_file);
            }
        }

        // 3. Update Presensi
        if (isset($_POST['presensi'])) {
            foreach ($_POST['presensi'] as $id_ploting => $status) {
                $this->model->updatePresensi($id_kegiatan, $id_ploting, $status);
            }
        }

        setFlash('success', 'Jurnal berhasil diperbarui.');
        header("Location: ?controller=ekstra&method=riwayat&id_ekstra=" . $id_ekstra);
        exit();
    }

    public function laporan()
    {
        $id_ekstra = $_GET['id_ekstra'];

        // Ambil data header laporan
        $ekstra = $this->model->getEkstraById($id_ekstra);
        $activeTahun = $this->model->getActiveTahun();

        // Ambil data isi laporan
        $rekap_presensi = $this->model->getRekapPresensi($id_ekstra);
        $riwayat_jurnal = $this->model->getRiwayatKegiatan($id_ekstra);

        require __DIR__ . '/../views/admin/ekstra/laporan.php';
    }

    public function cetak()
    {
        $id_kegiatan = $_GET['id_kegiatan'] ?? null;
        if (!$id_kegiatan) die('ID Jurnal tidak ditemukan');

        $detail = $this->model->getDetailKegiatan($id_kegiatan);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        ob_start();
        require __DIR__ . '/../views/admin/ekstra/cetak_pdf.php';
        $html = ob_get_clean();

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $dompdf->stream("Laporan_Ekstra_" . $id_kegiatan . ".pdf", ["Attachment" => false]);
    }

    public function inputNilaiRapor()
    {
        $id_ekstra = $_GET['id_ekstra'] ?? null;
        if (!$id_ekstra) die("ID Ekstra tidak ditemukan.");

        $ekstra = $this->model->getEkstraById($id_ekstra);
        $raporAktif = $this->model->getActiveRapor();

        if (!$raporAktif) {
            setFlash('error', 'Tidak ada periode rapor yang diaktifkan oleh Admin.');
            header("Location: ?controller=ekstra&method=index");
            exit();
        }

        // Ambil daftar siswa dan nilai yang sudah ada
        $anggota = $this->model->getAnggotaUntukNilai($id_ekstra);
        $nilai_lama = $this->model->getExistingNilai($id_ekstra, $raporAktif['id_rapor']);

        require __DIR__ . '/../views/admin/ekstra/input_nilai_rapor.php';
    }

    public function simpanNilaiRapor()
    {
        $id_ekstra = $_POST['id_ekstra'];
        $id_rapor = $_POST['id_rapor'];
        $data_nilai = $_POST['nilai_siswa']; // Array dari form

        // File: app/controllers/EkstraController.php

        if ($this->model->saveNilaiMassal($id_rapor, $id_ekstra, $data_nilai)) {
            // Menghitung berapa banyak input yang dikirim dari form
            $count = count($data_nilai);
            setFlash('success', "Berhasil! $count data nilai siswa telah diperbarui.");
        } else {
            setFlash('error', 'Terjadi kesalahan saat menyimpan data ke database.');
        }

        header("Location: ?controller=ekstra&method=index");
        exit();
    }

    // Ganti nama update_anggota_terpadu menjadi ini agar sinkron dengan View
    public function simpanAnggotaTerpadu()
    {
        $id_ekstra = $_POST['id_ekstra'];

        // Sesuaikan name input dari view: 'pilih_guru' dan 'pilih_siswa'
        $guru_pendamping = isset($_POST['pilih_guru']) ? $_POST['pilih_guru'] : [];
        $siswa_anggota = isset($_POST['pilih_siswa']) ? $_POST['pilih_siswa'] : [];

        try {
            $this->db->beginTransaction();

            // --- BAGIAN GURU PENDAMPING ---
            // Bersihkan data lama, masukkan yang dicentang baru
            $this->db->prepare("DELETE FROM ekstra_pendamping WHERE id_ekstra = ?")->execute([$id_ekstra]);
            foreach ($guru_pendamping as $id_emp) {
                $this->db->prepare("INSERT INTO ekstra_pendamping (id_ekstra, id_employe) VALUES (?, ?)")
                    ->execute([$id_ekstra, $id_emp]);
            }

            // --- BAGIAN SISWA ANGGOTA ---
            // Perhatian: Gunakan INSERT IGNORE atau cek keberadaan agar tidak duplikat 
            // karena di view sebelah kiri sudah ada tombol hapus satuan
            foreach ($siswa_anggota as $id_ploting) {
                // Cek apakah siswa sudah terdaftar di ekskul ini
                $check = $this->db->prepare("SELECT id_ekstra_anggota FROM ekstra_anggota WHERE id_ekstra = ? AND id_ploting_siswa = ?");
                $check->execute([$id_ekstra, $id_ploting]);

                if ($check->rowCount() == 0) {
                    $this->db->prepare("INSERT INTO ekstra_anggota (id_ekstra, id_ploting_siswa) VALUES (?, ?)")
                        ->execute([$id_ekstra, $id_ploting]);
                }
            }

            $this->db->commit();
            setFlash('success', 'Data Tim Pendamping dan Siswa berhasil diperbarui.');
            header("Location: ?controller=ekstra&method=anggota&id_ekstra=" . $id_ekstra);
        } catch (Exception $e) {
            $this->db->rollBack();
            die("Error Sistem: " . $e->getMessage());
        }
    }

    public function syncAnggotaTerpadu($id_ekstra, $guru_ids, $siswa_ids)
    {
        try {
            $this->db->beginTransaction();

            // 1. Sync Guru Pendamping (Hapus lama, masukkan baru)
            $this->db->prepare("DELETE FROM ekstra_pendamping WHERE id_ekstra = ?")->execute([$id_ekstra]);
            foreach ($guru_ids as $id_emp) {
                $this->db->prepare("INSERT INTO ekstra_pendamping (id_ekstra, id_employe) VALUES (?, ?)")
                    ->execute([$id_ekstra, $id_emp]);
            }

            // 2. Sync Siswa (Hapus lama, masukkan baru)
            // Gunakan metode hapus-simpan agar daftar anggota benar-benar sesuai dengan checkbox di view
            $this->db->prepare("DELETE FROM ekstra_anggota WHERE id_ekstra = ?")->execute([$id_ekstra]);
            foreach ($siswa_ids as $id_ploting) {
                $this->db->prepare("INSERT INTO ekstra_anggota (id_ekstra, id_ploting_siswa) VALUES (?, ?)")
                    ->execute([$id_ekstra, $id_ploting]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function cetakLaporan()
    {
        $id_ekstra = $_POST['id_ekstra'];
        $tgl_awal  = $_POST['tgl_awal'];
        $tgl_akhir = $_POST['tgl_akhir'];
        $ekstra = $this->model->getEkstraById($id_ekstra);

        // Ambil semua data yang dibutuhkan
        $data['title'] = 'Laporan Ekstrakurikuler ';
        $data['ekstra'] = $this->model->getEkstraById($id_ekstra);
        $data['rekap_siswa'] = $this->model->getRekapPresensi($id_ekstra, $tgl_awal, $tgl_akhir); // Sesuaikan model siswa Anda
        $data['rekap_guru'] = $this->model->getRekapGuru($id_ekstra, $tgl_awal, $tgl_akhir);
        $data['jurnal'] = $this->model->getRiwayatKegiatanFiltered($id_ekstra, $tgl_awal, $tgl_akhir);
        $data['title'] = 'Laporan Ekstrakurikuler ' . $ekstra['nama_ekstra'] . '';
        $data['tgl_awal'] = $tgl_awal;
        $data['tgl_akhir'] = $tgl_akhir;

        // Load ke Dompdf
        // Load ke Dompdf
        ob_start();
        extract($data);
        require __DIR__ . '/../views/admin/ekstra/pdf_template.php';
        $html = ob_get_clean();

        // 1. Buat dulu objek Options
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('chroot', realpath(''));

        // 2. Masukkan $options ke dalam Dompdf saat pembuatan objek
        $dompdf = new \Dompdf\Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream('Laporan Ekstra_' . $ekstra['nama_ekstra'] . '_' . $tgl_awal . '-' . $tgl_akhir . '.pdf', ["Attachment" => false]);
    }
}
