<?php
/**
 * Pencatatan peserta didik keluar (lulus, mutasi, mengundurkan diri, meninggal,
 * atau alasan lain) beserta pembatalannya.
 *
 * Data siswa TIDAK dihapus: status disimpan di `siswa.status_siswa`, sedangkan
 * alasan + tanggal + keterangan disimpan di tabel `siswa_keluar` sebagai riwayat.
 * Baris ploting_siswa dan seluruh nilai tetap utuh, hanya disembunyikan dari
 * daftar/nilai/rapor oleh siswa_aktif_sql().
 */
require_once __DIR__ . '/../helpers/SiswaAktif.php';

class SiswaKeluar
{
    private $db;
    private static $tabelSiap = false;

    public function __construct($db)
    {
        $this->db = $db;
        $this->siapkanTabel();
    }

    /** Alasan keluar; nilainya disimpan apa adanya di siswa.status_siswa. */
    public static function daftarAlasan()
    {
        return [
            'mengundurkan diri' => 'Mengundurkan diri',
            'mutasi'            => 'Mutasi / pindah sekolah',
            'meninggal'         => 'Meninggal dunia',
            'lulus'             => 'Lulus',
            'keluar'            => 'Keluar (alasan lain)',
        ];
    }

    /**
     * Tabel riwayat dibuat otomatis supaya deploy tidak perlu langkah SQL manual.
     * Bila user DB tidak punya hak CREATE, aplikasi tetap jalan dan hanya
     * menampilkan pesan error saat menyimpan.
     */
    private function siapkanTabel()
    {
        if (self::$tabelSiap) {
            return;
        }
        self::$tabelSiap = true;

        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS siswa_keluar (
                id_keluar INT AUTO_INCREMENT PRIMARY KEY,
                id_siswa INT NOT NULL,
                id_tahun INT NULL,
                status VARCHAR(30) NOT NULL,
                tanggal DATE NOT NULL,
                keterangan VARCHAR(255) NULL,
                dicatat_oleh VARCHAR(100) NULL,
                dibuat_pada DATETIME NOT NULL,
                dibatalkan_oleh VARCHAR(100) NULL,
                dibatalkan_pada DATETIME NULL,
                KEY idx_siswa (id_siswa),
                KEY idx_status (status),
                KEY idx_tanggal (tanggal)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
        } catch (Throwable $e) {
            error_log('[SISWA-KELUAR] gagal menyiapkan tabel: ' . $e->getMessage());
        }
    }

    public function getAllTahun()
    {
        return $this->db->query("SELECT * FROM tahun_pelajaran ORDER BY tahun_pelajaran DESC")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTahunAktifId()
    {
        try {
            $id = $this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 1 LIMIT 1")->fetchColumn();
            return $id ? (int) $id : 0;
        } catch (Throwable $e) {
            return 0;
        }
    }

    public function getKelasByTahun($id_tahun)
    {
        $sql = "SELECT DISTINCT k.id_kelas, k.kelas, k.tingkat
                FROM kelas k
                JOIN ploting_siswa ps ON ps.id_kelas = k.id_kelas
                WHERE ps.id_tahun = ?
                ORDER BY k.tingkat ASC, k.kelas ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Daftar siswa yang masih aktif di satu kelas/tahun (untuk dropdown). */
    public function getSiswaByKelas($id_kelas, $id_tahun)
    {
        $sql = "SELECT s.id_siswa, s.nama_siswa, s.nisn
                FROM ploting_siswa ps
                JOIN siswa s ON s.id_siswa = ps.id_siswa
                WHERE ps.id_kelas = ? AND ps.id_tahun = ?
                  AND " . siswa_aktif_sql('s') . "
                ORDER BY (ps.urutan IS NULL) ASC, ps.urutan ASC, s.nama_siswa ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $id_kelas, (int) $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function cariSiswa($id_siswa)
    {
        $stmt = $this->db->prepare("SELECT id_siswa, nama_siswa, nisn, status_siswa FROM siswa WHERE id_siswa = ?");
        $stmt->execute([(int) $id_siswa]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function cariAktif($id_siswa)
    {
        $sql = "SELECT s.id_siswa, s.nama_siswa, s.nisn,
                       (SELECT k.kelas FROM ploting_siswa ps JOIN kelas k ON k.id_kelas = ps.id_kelas
                         WHERE ps.id_siswa = s.id_siswa ORDER BY ps.id_tahun DESC LIMIT 1) AS kelas_terakhir
                FROM siswa s
                WHERE s.id_siswa = ? AND " . siswa_aktif_sql('s');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $id_siswa]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Keluarkan siswa. Mengembalikan ['ok'=>bool, 'pesan'=>string].
     */
    public function keluarkan($id_siswa, $status, $tanggal, $keterangan, $id_tahun, $oleh)
    {
        $id_siswa = (int) $id_siswa;
        $status   = strtolower(trim((string) $status));
        $alasan   = self::daftarAlasan();

        if (!isset($alasan[$status])) {
            return ['ok' => false, 'pesan' => 'Alasan keluar tidak dikenali.'];
        }

        $tanggal = trim((string) $tanggal);
        $dt = DateTime::createFromFormat('Y-m-d', $tanggal);
        if (!$dt || $dt->format('Y-m-d') !== $tanggal) {
            return ['ok' => false, 'pesan' => 'Tanggal keluar tidak valid (format YYYY-MM-DD).'];
        }

        $siswa = $this->cariSiswa($id_siswa);
        if (!$siswa) {
            return ['ok' => false, 'pesan' => 'Siswa tidak ditemukan.'];
        }
        if (!$this->cariAktif($id_siswa)) {
            return ['ok' => false, 'pesan' => $siswa['nama_siswa'] . ' sudah berstatus ' . $siswa['status_siswa'] . '.'];
        }

        $keterangan = trim((string) $keterangan);
        if (function_exists('mb_substr')) {
            $keterangan = mb_substr($keterangan, 0, 255);
        } else {
            $keterangan = substr($keterangan, 0, 255);
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("INSERT INTO siswa_keluar
                (id_siswa, id_tahun, status, tanggal, keterangan, dicatat_oleh, dibuat_pada)
                VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $id_siswa,
                $id_tahun ? (int) $id_tahun : null,
                $status,
                $tanggal,
                $keterangan !== '' ? $keterangan : null,
                $oleh !== '' ? $oleh : null,
            ]);

            // tahun_lulus dipakai juga sebagai tahun keluar (kolom lama, dipakai rapor).
            $stmt = $this->db->prepare("UPDATE siswa SET status_siswa = ?, tahun_lulus = ? WHERE id_siswa = ?");
            $stmt->execute([$status, $dt->format('Y'), $id_siswa]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[SISWA-KELUAR] gagal menyimpan: ' . $e->getMessage());
            return ['ok' => false, 'pesan' => 'Gagal menyimpan: data keluar tidak tersimpan.'];
        }

        return ['ok' => true, 'pesan' => $siswa['nama_siswa'] . ' dicatat keluar (' . $alasan[$status] . ').'];
    }

    /**
     * Batalkan catatan keluar: siswa kembali aktif dan muncul lagi di daftar
     * nilai/rapor/ploting. Status hanya dikosongkan bila masih sama dengan
     * status pada catatan ini (agar tidak menimpa catatan yang lebih baru).
     */
    public function kembalikan($id_keluar, $oleh)
    {
        $stmt = $this->db->prepare("SELECT * FROM siswa_keluar WHERE id_keluar = ?");
        $stmt->execute([(int) $id_keluar]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return ['ok' => false, 'pesan' => 'Catatan keluar tidak ditemukan.'];
        }
        if (!empty($row['dibatalkan_pada'])) {
            return ['ok' => false, 'pesan' => 'Catatan ini sudah dibatalkan sebelumnya.'];
        }

        $siswa = $this->cariSiswa((int) $row['id_siswa']);
        if (!$siswa) {
            return ['ok' => false, 'pesan' => 'Data siswa sudah tidak ada.'];
        }

        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("UPDATE siswa_keluar
                SET dibatalkan_oleh = ?, dibatalkan_pada = NOW()
                WHERE id_keluar = ? AND dibatalkan_pada IS NULL");
            $stmt->execute([$oleh !== '' ? $oleh : null, (int) $id_keluar]);

            $stmt = $this->db->prepare("UPDATE siswa
                SET status_siswa = NULL, tahun_lulus = NULL
                WHERE id_siswa = ? AND LOWER(TRIM(COALESCE(status_siswa, ''))) = ?");
            $stmt->execute([(int) $row['id_siswa'], strtolower(trim((string) $row['status']))]);

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('[SISWA-KELUAR] gagal membatalkan: ' . $e->getMessage());
            return ['ok' => false, 'pesan' => 'Gagal membatalkan catatan keluar.'];
        }

        return ['ok' => true, 'pesan' => $siswa['nama_siswa'] . ' dikembalikan ke status aktif.'];
    }

    /**
     * Catat kelulusan dari menu Ploting tanpa mengubah status lagi
     * (Ploting sudah mengubahnya), supaya riwayat PD keluar tetap lengkap
     * dan kelulusan bisa dibatalkan dari halaman yang sama.
     */
    public function catatLulus($id_siswa, $id_tahun, $oleh, $keterangan = 'Diluluskan dari menu Ploting')
    {
        try {
            $cek = $this->db->prepare("SELECT id_keluar FROM siswa_keluar
                WHERE id_siswa = ? AND status = 'lulus' AND dibatalkan_pada IS NULL LIMIT 1");
            $cek->execute([(int) $id_siswa]);
            if ($cek->fetchColumn()) {
                return;
            }

            $stmt = $this->db->prepare("INSERT INTO siswa_keluar
                (id_siswa, id_tahun, status, tanggal, keterangan, dicatat_oleh, dibuat_pada)
                VALUES (?, ?, 'lulus', CURDATE(), ?, ?, NOW())");
            $stmt->execute([
                (int) $id_siswa,
                $id_tahun ? (int) $id_tahun : null,
                $keterangan,
                $oleh !== '' ? $oleh : null,
            ]);
        } catch (Throwable $e) {
            error_log('[SISWA-KELUAR] gagal mencatat kelulusan: ' . $e->getMessage());
        }
    }

    public function getRiwayat($filter = [])
    {
        $where = [];
        $params = [];

        if (!empty($filter['riwayat'])) {
            // tampilkan semua, termasuk yang sudah dibatalkan
        } else {
            $where[] = 'sk.dibatalkan_pada IS NULL';
        }

        if (!empty($filter['status']) && isset(self::daftarAlasan()[$filter['status']])) {
            $where[] = 'sk.status = ?';
            $params[] = $filter['status'];
        }

        if (!empty($filter['tahun'])) {
            $where[] = 'sk.id_tahun = ?';
            $params[] = (int) $filter['tahun'];
        }

        if (!empty($filter['q'])) {
            $where[] = '(s.nama_siswa LIKE ? OR s.nisn LIKE ?)';
            $params[] = '%' . $filter['q'] . '%';
            $params[] = '%' . $filter['q'] . '%';
        }

        $sql = "SELECT sk.*, s.nama_siswa, s.nisn,
                       k.kelas AS kelas_saat_keluar,
                       t.tahun_pelajaran
                FROM siswa_keluar sk
                JOIN siswa s ON s.id_siswa = sk.id_siswa
                LEFT JOIN ploting_siswa ps ON ps.id_siswa = sk.id_siswa AND ps.id_tahun = sk.id_tahun
                LEFT JOIN kelas k ON k.id_kelas = ps.id_kelas
                LEFT JOIN tahun_pelajaran t ON t.id_tahun_pelajaran = sk.id_tahun";

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY sk.tanggal DESC, sk.id_keluar DESC LIMIT 500';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Ringkasan jumlah per alasan (untuk kartu statistik). */
    public function getRingkasan()
    {
        $hasil = [];
        try {
            $rows = $this->db->query("SELECT status, COUNT(*) AS jumlah FROM siswa_keluar
                WHERE dibatalkan_pada IS NULL GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $hasil[$r['status']] = (int) $r['jumlah'];
            }
        } catch (Throwable $e) {
            // diamkan: ringkasan hanya pelengkap tampilan
        }
        return $hasil;
    }
}
