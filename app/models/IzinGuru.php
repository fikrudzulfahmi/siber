<?php
class IzinGuru
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getAll()
    {
        $sql = "SELECT * FROM izin_pegawai ORDER BY created_at DESC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAllUsers()
    {
        $sql = "SELECT pin, nama, no_wa FROM employe ORDER BY nama ASC";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    // ➕ BARU: Method untuk mengambil satu user berdasarkan PIN
    public function getUserByPin($pin)
    {
        $stmt = $this->db->prepare("SELECT pin, nama, no_wa FROM employe WHERE pin = :pin");
        $stmt->execute([':pin' => $pin]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM izin_pegawai WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function ajukanIzin($pin, $jenis, $keterangan, $tgl_mulai, $tgl_selesai)
    {
        $sql = "INSERT INTO izin_pegawai (pin, jenis, keterangan, tanggal_mulai, tanggal_selesai, status_approval)
                VALUES (:pin, :jenis, :keterangan, :tgl_mulai, :tgl_selesai, 'pending')";
        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':pin' => $pin,
            ':jenis' => $jenis,
            ':keterangan' => $keterangan,
            ':tgl_mulai' => $tgl_mulai,
            ':tgl_selesai' => $tgl_selesai
        ]);
    }

    // 🔧 PERBAIKAN: Menggabungkan logika update dan approval menjadi satu method
    public function updateIzin($id, $pin, $jenis, $keterangan, $tanggal_mulai, $tanggal_selesai, $status, $alasan_ditolak = null, $catatan_approval = null)
    {
        $alasan = (strtolower($status) == 'ditolak') ? $alasan_ditolak : null;
        $catatan = (strtolower($status) == 'disetujui') ? $catatan_approval : null;

        $stmt = $this->db->prepare("\n            UPDATE izin_pegawai \n            SET pin = :pin, jenis = :jenis, keterangan = :keterangan, \n                tanggal_mulai = :tanggal_mulai, tanggal_selesai = :tanggal_selesai,\n                status_approval = :status, alasan_ditolak = :alasan_ditolak, catatan_approval = :catatan_approval\n            WHERE id = :id\n        ");

        $success = $stmt->execute([
            'id'               => $id,
            'pin'              => $pin,
            'jenis'            => $jenis,
            'keterangan'       => $keterangan,
            'tanggal_mulai'    => $tanggal_mulai,
            'tanggal_selesai'  => $tanggal_selesai,
            'status'           => $status,
            'alasan_ditolak'   => $alasan,
            'catatan_approval' => $catatan
        ]);

        // Jika update berhasil DAN status disetujui
        if ($success && strtolower($status) === 'disetujui') {
            // Ambil id_employe berdasarkan pin
            $userStmt = $this->db->prepare("SELECT id_employe FROM employe WHERE pin = ?");
            $userStmt->execute([$pin]);
            $id_employe = $userStmt->fetchColumn();

            // Hanya lanjutkan jika id_employe ditemukan
            if ($id_employe) {
                $izinData = [
                    'id_employe'      => $id_employe, // ✅ ID Employe sekarang disertakan
                    'pin'             => $pin,
                    'jenis'           => $jenis,
                    'keterangan'      => $keterangan, // ✅ Keterangan sekarang disertakan
                    'tanggal_mulai'   => $tanggal_mulai,
                    'tanggal_selesai' => $tanggal_selesai,
                ];
                $this->masukkanKeAttendance($izinData);
            }
        }

        return $success;
    }

    /**
     * GANTI SELURUH METHOD INI
     */
    private function masukkanKeAttendance($izin)
    {
        // ✅ PERBAIKAN: Menggunakan kolom 'pin' dan 'date'
        // Pastikan nama kolom ini (pin, date, status, keterangan) sudah sama persis
        // dengan yang ada di tabel 'attendance' Anda di phpMyAdmin.
        $sql = "INSERT INTO attendance (pin, scan_date, status, keterangan) 
            VALUES (:pin, :date, :status, :keterangan)";

        $stmt = $this->db->prepare($sql);

        $start = new DateTime($izin['tanggal_mulai']);
        $end = new DateTime($izin['tanggal_selesai']);
        $interval = new DateInterval('P1D'); // Interval 1 hari
        $period = new DatePeriod($start, $interval, $end->modify('+1 day'));

        foreach ($period as $date) {
            $stmt->execute([
                ':pin'        => $izin['pin'], // ✅ Diubah dari id_employe kembali ke pin
                ':date'       => $date->format('Y-m-d'),
                ':status'     => 'datang',
                ':keterangan' => $izin['jenis']
            ]);
        }
    }


    public function deleteById($id)
    {
        $stmt = $this->db->prepare("DELETE FROM izin_pegawai WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }
}
