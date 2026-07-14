<?php

class Presensi
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function getByJabatan($jabatan_id)
    {
        $sql = "SELECT e.*, j.jabatan 
                FROM employe e
                JOIN jabatan j ON j.id_jabatan = e.id_jabatan
                WHERE e.id_jabatan = :jabatan_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['jabatan_id' => $jabatan_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getJadwalHariIni($pin, $hari)
    {
        $stmt = $this->db->prepare("SELECT * FROM jadwal WHERE id_employee = :pin AND day = :hari LIMIT 1");
        $stmt->execute(['pin' => $pin, 'hari' => $hari]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getKehadiranLengkap($pin, $today)
    {
        $hari = strtolower(date('l', strtotime($today)));

        // 1. Cek Libur Nasional
        $libur = $this->cekLibur($today);

        // 2. Cek apakah pegawai ini Satpam (id_jabatan = 3)
        $stmtJabatan = $this->db->prepare("SELECT id_jabatan FROM employe WHERE pin = ?");
        $stmtJabatan->execute([$pin]);
        $pegawaiInfo = $stmtJabatan->fetch(PDO::FETCH_ASSOC);

        $isSatpam = ($pegawaiInfo && $pegawaiInfo['id_jabatan'] == 3);

        // 3. Ambil Data Absensi
        $stmtAbsen = $this->db->prepare("
            SELECT status, scan_date, keterangan 
            FROM attendance 
            WHERE pin = :pin AND DATE(scan_date) = :today
            ORDER BY scan_date ASC
        ");
        $stmtAbsen->execute(['pin' => $pin, 'today' => $today]);
        $attendance = $stmtAbsen->fetchAll(PDO::FETCH_ASSOC);

        $datang = null;
        $pulang = null;
        foreach ($attendance as $absen) {
            if ($absen['status'] == 'datang' && !$datang) $datang = $absen;
            if ($absen['status'] == 'pulang') $pulang = $absen;
        }

        // 4. Ambil Jadwal Pegawai
        $stmtJadwal = $this->db->prepare("
            SELECT MIN(waktu_datang) AS jam_masuk, MAX(waktu_pulang) AS jam_pulang 
            FROM jadwal 
            WHERE id_employee = ? AND day = ?
        ");
        $stmtJadwal->execute([$pin, $hari]);
        $jadwal = $stmtJadwal->fetch(PDO::FETCH_ASSOC);

        // --- LOGIKA JIKA TIDAK ADA ABSEN (TIDAK SCAN) ---
        if (!$datang) {
            // Jika benar-benar tidak punya jadwal (Misal orang kantor di hari Minggu)
            if (!$jadwal || $jadwal['jam_masuk'] === null) {
                return [
                    'keterangan'   => $libur ? "Libur {$libur['keterangan']}" : 'Libur Rutin',
                    'waktu_datang' => '-',
                    'waktu_pulang' => '-',
                ];
            }

            // Jika PUNYA JADWAL TAPI hari ini libur nasional
            if ($libur) {
                if ($isSatpam) {
                    // KHUSUS SATPAM: Tanggal merah tidak berlaku, wajib masuk!
                    return [
                        'keterangan'   => 'Alpa',
                        'waktu_datang' => '-',
                        'waktu_pulang' => '-',
                    ];
                } else {
                    // Pegawai biasa: Dilindungi oleh sistem libur nasional
                    return [
                        'keterangan'   => "Libur {$libur['keterangan']}",
                        'waktu_datang' => '-',
                        'waktu_pulang' => '-',
                    ];
                }
            }

            // Hari kerja biasa, punya jadwal, tapi tidak scan
            return [
                'keterangan'   => 'Alpa',
                'waktu_datang' => '-',
                'waktu_pulang' => '-',
            ];
        }

        // --- LOGIKA JIKA ADA ABSEN (ADA SCAN MASUK) ---
        $keteranganAbsen = strtolower(trim($datang['keterangan']));
        if ($keteranganAbsen !== 'hadir') {
            return [
                'keterangan'   => ucwords($keteranganAbsen),
                'waktu_datang' => '-',
                'waktu_pulang' => '-',
            ];
        }

        $infoIn  = date('H:i', strtotime($datang['scan_date']));
        $infoOut = $pulang ? date('H:i', strtotime($pulang['scan_date'])) : '-';

        $jamJadwalMasuk = ($jadwal && $jadwal['jam_masuk']) ? $jadwal['jam_masuk'] : '08:00:00';
        $jamJadwalPulang = ($jadwal && $jadwal['jam_pulang']) ? $jadwal['jam_pulang'] : '16:00:00';

        $waktuDatangAktual = strtotime($datang['scan_date']);
        $waktuDatangJadwal = strtotime($today . ' ' . $jamJadwalMasuk);
        if ($waktuDatangAktual > $waktuDatangJadwal) {
            $selisih = round(($waktuDatangAktual - $waktuDatangJadwal) / 60);
            if ($selisih > 0) $infoIn .= " (Terlambat {$selisih} menit)";
        }

        if ($pulang) {
            $waktuPulangAktual = strtotime($pulang['scan_date']);
            $waktuPulangJadwal = strtotime($today . ' ' . $jamJadwalPulang);
            if ($waktuPulangAktual < $waktuPulangJadwal) {
                $selisih = round(($waktuPulangJadwal - $waktuPulangAktual) / 60);
                if ($selisih > 0) $infoOut .= " (Pulang Cepat {$selisih} menit)";
            }
        }

        return [
            'keterangan'   => 'Hadir',
            'waktu_datang' => $infoIn,
            'waktu_pulang' => $infoOut,
        ];
    }

    public function cekLibur($tanggal)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM hari_libur 
            WHERE :tanggal BETWEEN tanggal_mulai AND tanggal_selesai
            LIMIT 1
        ");
        $stmt->execute(['tanggal' => $tanggal]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
