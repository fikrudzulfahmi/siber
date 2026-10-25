<?php
/**
 * PengingatDeadline
 *
 * Sumber tunggal logika "pengingat deadline" untuk dashboard:
 *  - perangkat pembelajaran (per guru/mapel)
 *  - program struktural (per jabatan struktural)
 *
 * Dipisah dari controller supaya bisa diuji tanpa login/HTTP dan supaya
 * dashboard semua role memakai aturan yang sama.
 */
class PengingatDeadline
{
    /** Level yang punya kewajiban menyusun program struktural (termasuk tendik). */
    public const LEVEL_STRUKTURAL = [5, 6, 7, 10, 11, 12, 13, 14, 15, 16];


    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Apakah user wajib menyusun program struktural?
     *
     * Aturan:
     *  1. Punya level struktural (lihat LEVEL_STRUKTURAL), atau
     *  2. Sudah pernah mengunggah program struktural (bukti punya jabatan),
     *     supaya akun lama/level baru tetap mendapat pengingat.
     */
    public function bolehProgramStruktural(array $userLevelsInt, $id_employe): bool
    {
        $levels = array_map('intval', $userLevelsInt);

        // 1. Level di session menunjukkan jabatan struktural / tenaga kependidikan.
        if (array_intersect($levels, self::LEVEL_STRUKTURAL)) {
            return true;
        }

        if (!$id_employe) {
            return false;
        }

        try {
            // 2. Jabatan resmi di tabel employe (id_level) - sumber utama di aplikasi ini.
            //    Menangkap akun yang level session-nya tidak lengkap/beda.
            $stmt = $this->db->prepare("SELECT id_level FROM employe WHERE id_employe = ? LIMIT 1");
            $stmt->execute([$id_employe]);
            $jabatan = (int) $stmt->fetchColumn();

            if ($jabatan && in_array($jabatan, self::LEVEL_STRUKTURAL, true)) {
                return true;
            }

            // 3. Sudah pernah mengunggah program -> dianggap punya jabatan struktural.
            $stmt = $this->db->prepare("SELECT 1 FROM program_struktural WHERE id_employe = ? LIMIT 1");
            $stmt->execute([$id_employe]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            log_message('PengingatDeadline::bolehProgramStruktural gagal: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Pengingat deadline perangkat pembelajaran untuk satu guru pada satu tahun.
     */
    public function perangkat($id_employe, $id_tahun)
    {
        if (!$id_employe || !$id_tahun) {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT d.jenis_perangkat, d.tanggal_deadline,
                   CASE WHEN pm.id IS NOT NULL THEN 1 ELSE 0 END AS sudah_upload,
                   COALESCE(pm.status_approval, 'belum upload') AS status_approval
            FROM deadline_perangkat d
            JOIN mapel_guru mg ON mg.id_guru = :id_employe
            JOIN mapel m ON mg.id_mapel = m.id_mapel
            LEFT JOIN perangkat_mengajar pm ON pm.jenis_perangkat = d.jenis_perangkat
                AND pm.id_tahun_pelajaran = d.id_tahun_pelajaran
                AND pm.id_employe = :id_employe2
            WHERE d.id_tahun_pelajaran = :id_tahun
            GROUP BY d.jenis_perangkat, d.tanggal_deadline, pm.id, pm.status_approval
            ORDER BY d.tanggal_deadline ASC
        ");
        $stmt->execute([
            'id_tahun'    => $id_tahun,
            'id_employe'  => $id_employe,
            'id_employe2' => $id_employe,
        ]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today = $this->hariIni();
        foreach ($rows as &$d) {
            if ($d['status_approval'] === 'Ditolak') {
                $d['status'] = 'ditolak';
                $d['sisa_hari'] = null;
            } elseif ((int) $d['sudah_upload'] === 1) {
                $d['status'] = 'selesai';
                $d['sisa_hari'] = null;
            } else {
                $tgl = new DateTime($d['tanggal_deadline']);
                $d['sisa_hari'] = $today->diff($tgl)->invert ? 0 : $today->diff($tgl)->days;
                $d['status'] = ($tgl < $today) ? 'lewat' : (($d['sisa_hari'] <= 7) ? 'dekat' : 'aman');
            }
        }
        unset($d);

        return $rows;
    }

    /**
     * Pengingat deadline program struktural untuk satu user pada satu tahun.
     */
    public function programStruktural($id_employe, $id_tahun)
    {
        if (!$id_tahun) {
            return [];
        }

        // LEFT JOIN: kalau tabel program_struktural belum ada di suatu instalasi,
        // dashboard tetap jalan (deadline saja tanpa status unggahan).
        $stmt = $this->db->prepare("
            SELECT d.jenis_program, d.tanggal_deadline,
                   p.file,
                   COALESCE(p.status_approval, 'Belum dikirim') AS status_approval
            FROM deadline_program_struktural d
            LEFT JOIN program_struktural p
                ON p.jenis_program = d.jenis_program
                AND p.id_employe = :id_employe
                AND p.id_tahun_pelajaran = d.id_tahun_pelajaran
            WHERE d.id_tahun_pelajaran = :id_tahun
            ORDER BY d.tanggal_deadline ASC
        ");
        $stmt->execute(['id_employe' => $id_employe, 'id_tahun' => $id_tahun]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $today = $this->hariIni();
        $out = [];
        foreach ($rows as $r) {
            $tgl = new DateTime($r['tanggal_deadline']);
            $sisa = ($tgl > $today) ? $today->diff($tgl)->days : 0;

            if ($r['status_approval'] === 'Ditolak') {
                $status = 'ditolak';
                $sisa = null;
            } elseif (!empty($r['file'])) {
                $status = 'selesai';
                $sisa = null;
            } elseif ($tgl < $today) {
                $status = 'lewat';
            } else {
                $status = 'belum upload';
            }

            $out[] = [
                'jenis_program'   => $r['jenis_program'],
                'file'            => $r['file'],
                'status_approval' => $r['status_approval'],
                'status'          => $status,
                'sisa_hari'       => $sisa,
                'deadline'        => $r['tanggal_deadline'],
            ];
        }

        return $out;
    }

    /** Tanggal hari ini (00:00, zona Asia/Jakarta) untuk hitung sisa hari. */
    private function hariIni()
    {
        $today = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
        $today->setTime(0, 0, 0);
        return $today;
    }
}
