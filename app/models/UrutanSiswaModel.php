<?php
/**
 * Model urutan tampil siswa per kelas per tahun pelajaran.
 * Kolom: ploting_siswa.urutan (INT, NULL = belum diatur → abjad).
 */

require_once __DIR__ . '/../helpers/SiswaAktif.php';
require_once __DIR__ . '/../helpers/UrutanSiswa.php';

class UrutanSiswaModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /** Tahun pelajaran aktif; kalau tidak ada yang aktif, pakai yang terbaru. */
    public function getTahunAktif()
    {
        $id = $this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1")->fetchColumn();
        if (!$id) {
            $id = $this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran ORDER BY id_tahun_pelajaran DESC LIMIT 1")->fetchColumn();
        }
        return $id ? (int) $id : 0;
    }

    public function getSemuaTahun()
    {
        return $this->db->query("SELECT id_tahun_pelajaran, tahun_pelajaran, semester, status
                                 FROM tahun_pelajaran
                                 ORDER BY id_tahun_pelajaran DESC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getKelasSemua()
    {
        return $this->db->query("SELECT id_kelas, kelas, tingkat, wali_kelas
                                 FROM kelas
                                 ORDER BY tingkat ASC, kelas ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getKelasWalas($id_employe)
    {
        $stmt = $this->db->prepare("SELECT id_kelas, kelas, tingkat, wali_kelas
                                    FROM kelas
                                    WHERE wali_kelas = ?
                                    ORDER BY tingkat ASC, kelas ASC");
        $stmt->execute([(int) $id_employe]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getKelas($id_kelas)
    {
        $stmt = $this->db->prepare("SELECT id_kelas, kelas, tingkat, wali_kelas FROM kelas WHERE id_kelas = ? LIMIT 1");
        $stmt->execute([(int) $id_kelas]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Daftar siswa aktif satu kelas/tahun, sudah mengikuti urutan manual. */
    public function getSiswa($id_kelas, $id_tahun)
    {
        $sql = "SELECT ps.id_ploting, ps.urutan, s.id_siswa, s.nama_siswa, s.nisn
                FROM ploting_siswa ps
                JOIN siswa s ON s.id_siswa = ps.id_siswa
                WHERE ps.id_kelas = ? AND ps.id_tahun = ?
                  AND " . siswa_aktif_sql('s') . "
                GROUP BY ps.id_ploting, ps.urutan, s.id_siswa, s.nama_siswa, s.nisn
                ORDER BY " . urutan_siswa_order('ps', 's');
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $id_kelas, (int) $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Simpan urutan. Hanya id_ploting yang benar-benar milik kelas/tahun ini
     * yang dipakai; siswa yang tidak ikut terkirim ditaruh di belakang
     * mengikuti urutan lamanya, lalu seluruh kelas dinomori ulang 1..N.
     *
     * @return int jumlah baris yang dinomori
     */
    public function simpan($id_kelas, $id_tahun, array $urutan)
    {
        $sah = [];
        foreach ($this->getSiswa($id_kelas, $id_tahun) as $row) {
            $sah[(int) $row['id_ploting']] = true;
        }

        $final = [];
        foreach ($urutan as $id) {
            $id = (int) $id;
            if ($id > 0 && isset($sah[$id]) && !isset($final[$id])) {
                $final[$id] = true;
            }
        }
        foreach (array_keys($sah) as $id) {
            if (!isset($final[$id])) {
                $final[$id] = true;
            }
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE ploting_siswa
                                        SET urutan = ?
                                        WHERE id_ploting = ? AND id_kelas = ? AND id_tahun = ?");
            $n = 0;
            foreach (array_keys($final) as $id) {
                $n++;
                $stmt->execute([$n, $id, (int) $id_kelas, (int) $id_tahun]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }

        return $n;
    }

    /** Kembalikan ke abjad: kosongkan urutan seluruh kelas/tahun. */
    public function reset($id_kelas, $id_tahun)
    {
        $stmt = $this->db->prepare("UPDATE ploting_siswa SET urutan = NULL WHERE id_kelas = ? AND id_tahun = ?");
        $stmt->execute([(int) $id_kelas, (int) $id_tahun]);
        return $stmt->rowCount();
    }

    /** Nomor urut berikutnya (dipakai saat siswa baru diploting). */
    public function urutanBerikutnya($id_kelas, $id_tahun)
    {
        $stmt = $this->db->prepare("SELECT COALESCE(MAX(urutan), 0) FROM ploting_siswa WHERE id_kelas = ? AND id_tahun = ?");
        $stmt->execute([(int) $id_kelas, (int) $id_tahun]);
        return ((int) $stmt->fetchColumn()) + 1;
    }
}
