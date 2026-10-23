<?php
require_once __DIR__ . '/../models/IzinGuru.php';
require_once __DIR__ . '/../config.php';
require_once 'BaseController.php';

class IzinGuruController extends BaseController
{
    private $model;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->model = new IzinGuru($this->db);
    }

    public function index()
    {
        $izin_list = $this->model->getAll();
        $pegawai_list = $this->model->getAllUsers();
        require __DIR__ . '/../views/admin/presensi/izin_guru.php';
    }

    public function store()
    {
        // 🔧 PERBAIKAN: Tambahkan validasi dasar
        if (empty($_POST['pin']) || empty($_POST['jenis']) || empty($_POST['tanggal_mulai']) || empty($_POST['tanggal_selesai'])) {
            setFlash('error', 'Semua kolom wajib diisi.');
            header('Location: index.php?controller=izinGuru&method=index');
            exit();
        }

        $success = $this->model->ajukanIzin(
            $_POST['pin'],
            $_POST['jenis'],
            $_POST['keterangan'],
            $_POST['tanggal_mulai'],
            $_POST['tanggal_selesai']
        );

        if ($success) {
            // 🔧 Logika notifikasi dipindahkan ke helper
            $pegawai = $this->model->getUserByPin($_POST['pin']);
            if ($pegawai) {
                $namaClean = trim($pegawai['nama']);
                $pesan = "📢 *Pengajuan Izin Pegawai/Guru*\n\n" .
                    "Nama      : {$namaClean}\n" .
                    "Jenis     : {$_POST['jenis']}\n" .
                    "Tanggal   : {$_POST['tanggal_mulai']} s/d {$_POST['tanggal_selesai']}\n" .
                    "Alasan    : {$_POST['keterangan']}\n\n" .
                    "Status    : *Menunggu persetujuan*\n\n" .
                    "Segera lakukan persetujuan pada laman https://siber.ingintau.my.id \n" .
                    "Terima kasih.\n\n*SIBER PPRM*";

                $this->_sendWhatsAppNotification(WHATSAPP_RECIPIENTS, $pesan);
            }
            setFlash('success', 'Izin berhasil diajukan.');
        } else {
            setFlash('error', 'Gagal mengajukan izin.');
        }

        header('Location: index.php?controller=izinGuru&method=index');
        exit();
    }



    public function update()
    {
        $id = $_POST['id'] ?? null;
        $statusKunci = null;
        if (!sec_has_level(1)) {
            $stmt = $this->db->prepare("SELECT ip.pin, ip.status, e.id_employe FROM izin_pegawai ip LEFT JOIN employe e ON ip.pin = e.pin WHERE ip.id = ?");
            $stmt->execute([$id]);
            $izin = $stmt->fetch(PDO::FETCH_ASSOC);

            $isOwner = false;
            if ($izin) {
                if (!empty($_SESSION['user']['id']) && (int)$izin['id_employe'] === (int)$_SESSION['user']['id']) {
                    $isOwner = true;
                } elseif (!empty($_SESSION['user']['pin']) && (string)$izin['pin'] === (string)$_SESSION['user']['pin']) {
                    $isOwner = true;
                }
            }

            if (!$isOwner) {
                sec_require_level(1, 'Anda tidak memiliki hak akses untuk mengubah izin ini.');
            }
            // Hanya administrator yang boleh mengubah status persetujuan.
            $statusKunci = $izin['status'] ?? null;
        }

        $id = $_POST['id'];
        $status = sec_has_level(1)
            ? ($_POST['status_approval'] ?? $statusKunci)
            : ($statusKunci ?? $_POST['status_approval']);
        $alasan_ditolak = $_POST['alasan_ditolak'] ?? null;
        $catatan_approval = $_POST['catatan_approval'] ?? null;

        $success = $this->model->updateIzin(
            $id,
            $_POST['pin'],
            $_POST['jenis'],
            $_POST['keterangan'],
            $_POST['tanggal_mulai'],
            $_POST['tanggal_selesai'],
            $status,
            $alasan_ditolak,
            $catatan_approval
        );

        if ($success) {
            $pegawai = $this->model->getUserByPin($_POST['pin']);
            if ($pegawai && !empty($pegawai['no_wa'])) {
                $pesan = '';
                if ($status === 'disetujui') {
                    $pesan = "✅ *Pengajuan Izin Disetujui*\n\n" .
                        "Halo {$pegawai['nama']},\n" .
                        "Pengajuan izin Anda telah *disetujui*.\n\n" .
                        "📌 Jenis Izin: {$_POST['jenis']}\n" .
                        "📅 Tanggal   : {$_POST['tanggal_mulai']} s/d {$_POST['tanggal_selesai']}\n" .
                        "📝 Alasan    : {$_POST['keterangan']}\n";

                    if (!empty($catatan_approval)) {
                        $pesan .= "\n✍️ Catatan Persetujuan: {$catatan_approval}\n";
                    }

                    $pesan .= "\nTerima kasih.\n\n*SIBER PPRM*";
                } elseif ($status === 'ditolak') {
                    $alasanText = !empty($alasan_ditolak) ? $alasan_ditolak : "Silakan hubungi bagian terkait.";
                    $pesan = "❌ *Pengajuan Izin Ditolak*\n\n" .
                        "Halo {$pegawai['nama']},\n" .
                        "Mohon maaf, pengajuan izin Anda *ditolak*.\n\n" .
                        "🚫 *Keterangan:* {$alasanText}\n\n" .
                        "*SIBER PPRM*";
                }

                // Kirim notifikasi hanya ke pegawai yang bersangkutan
                if ($pesan) {
                    $this->_sendWhatsAppNotification($pegawai['no_wa'], $pesan);
                }
            }

            setFlash('success', 'Status izin berhasil diperbarui.');
        } else {
            setFlash('error', 'Gagal memperbarui izin.');
        }

        header('Location: index.php?controller=izinGuru&method=index');
        exit();
    }

    private function _sendWhatsAppNotification($target, $message)
    {
        $targets = is_array($target) ? $target : [$target];

        foreach ($targets as $nomor) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => 'https://api.fonnte.com/send',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => ['target' => $nomor, 'message' => $message],
                CURLOPT_HTTPHEADER => ["Authorization: " . FONNTE_TOKEN], // 🔧 Menggunakan konstanta dari config
            ]);
            curl_exec($curl);
            curl_close($curl);
        }
    }

    public function delete()
    {
        $id = $_GET['id'] ?? null;
        if (!sec_has_level(1)) {
            $stmt = $this->db->prepare("SELECT ip.pin, e.id_employe FROM izin_pegawai ip LEFT JOIN employe e ON ip.pin = e.pin WHERE ip.id = ?");
            $stmt->execute([$id]);
            $izin = $stmt->fetch(PDO::FETCH_ASSOC);

            $isOwner = false;
            if ($izin) {
                if (!empty($_SESSION['user']['id']) && (int)$izin['id_employe'] === (int)$_SESSION['user']['id']) {
                    $isOwner = true;
                } elseif (!empty($_SESSION['user']['pin']) && (string)$izin['pin'] === (string)$_SESSION['user']['pin']) {
                    $isOwner = true;
                }
            }

            if (!$isOwner) {
                sec_require_level(1, 'Anda tidak memiliki hak akses untuk menghapus izin ini.');
            }
        }

        $id = $_GET['id'];
        $this->model->deleteById($id);
        setFlash('success', 'Izin berhasil dihapus.');
        header('Location: index.php?controller=izinGuru&method=index');
        exit();
    }
}
