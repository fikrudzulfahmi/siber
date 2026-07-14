<?php
// File: app/models/RekapIndividuModel.php (Lengkap & Final)

class RekapIndividuModel
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function getAllPegawai()
    {
        $stmt = $this->db->query("SELECT pin, nama FROM employe ORDER BY nama ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPegawaiInfo($pin)
    {
        $stmt = $this->db->prepare("SELECT e.nama, j.jabatan FROM employe e JOIN jabatan j ON e.id_jabatan = j.id_jabatan WHERE e.pin = ?");
        $stmt->execute([$pin]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function generateRekapLengkap($pin, $periode)
    {
        list($year, $month) = explode('-', $periode);
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

        $rincian = [];
        $summary = array_fill_keys(['Hadir', 'Alpa', 'Terlambat', 'Pulang_Cepat', 'Sakit', 'Izin', 'Dinas_Luar', 'Hari_Efektif'], 0);

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $currentDate = "$year-$month-" . str_pad($d, 2, '0', STR_PAD_LEFT);
            $dayName = strtolower(date('l', strtotime($currentDate)));

            $daily = ['tanggal' => $currentDate, 'datang' => '', 'pulang' => '', 'keterangan' => ''];

            // Cek libur
            $stmtLibur = $this->db->prepare("SELECT keterangan FROM hari_libur WHERE :tgl BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
            $stmtLibur->execute([':tgl' => $currentDate]);
            if ($libur = $stmtLibur->fetch()) {
                $daily['keterangan'] = $libur['keterangan'];
                $rincian[] = $daily;
                continue;
            }

            // Ambil jam masuk paling pagi DAN jam pulang paling akhir
            $stmtJadwal = $this->db->prepare("
                SELECT MIN(waktu_datang) AS jam_masuk, MAX(waktu_pulang) AS jam_pulang 
                FROM jadwal 
                WHERE id_employee = ? AND day = ?
            ");
            $stmtJadwal->execute([$pin, $dayName]);
            $jadwal = $stmtJadwal->fetch(PDO::FETCH_ASSOC);

            if (!$jadwal || $jadwal['jam_masuk'] === null) {
                $daily['keterangan'] = 'Tidak Ada Jadwal';
                $rincian[] = $daily;
                continue;
            }

            $summary['Hari_Efektif']++;

            // Cek Absensi
            $stmtAbsen = $this->db->prepare("SELECT scan_date, status, keterangan FROM attendance WHERE pin = ? AND DATE(scan_date) = ? ORDER BY scan_date");
            $stmtAbsen->execute([$pin, $currentDate]);
            $absensi = $stmtAbsen->fetchAll(PDO::FETCH_ASSOC);

            $datang = null;
            $pulang = null;
            foreach ($absensi as $absen) {
                if ($absen['status'] == 'datang' && !$datang) $datang = $absen;
                if ($absen['status'] == 'pulang') $pulang = $absen;
            }

            if (!$datang) {
                $daily['keterangan'] = 'Alpa';
                $summary['Alpa']++;
            } else {
                $keterangan = strtolower(trim($datang['keterangan']));
                if ($keterangan !== 'hadir') {
                    $daily['keterangan'] = $datang['keterangan'];
                    if ($keterangan == 'sakit') $summary['Sakit']++;
                    elseif ($keterangan == 'izin') $summary['Izin']++;
                    elseif ($keterangan == 'dinas luar') $summary['Dinas_Luar']++;
                } else {
                    $summary['Hadir']++;

                    $waktuDatangAktual = date('H:i:s', strtotime($datang['scan_date']));
                    $waktuDatangJadwal = date('H:i:s', strtotime($jadwal['jam_masuk']));
                    $daily['datang'] = date('H:i', strtotime($waktuDatangAktual));

                    // Cek Terlambat
                    if ($waktuDatangAktual > $waktuDatangJadwal) {
                        $selisihMenit = round((strtotime($waktuDatangAktual) - strtotime($waktuDatangJadwal)) / 60);
                        if ($selisihMenit > 1) { // Toleransi 1 menit
                            $daily['datang'] .= " (Terlambat {$selisihMenit} Menit)";
                            $summary['Terlambat']++;
                        }
                    }

                    // Cek Pulang Cepat
                    if ($pulang) {
                        $waktuPulangAktual = date('H:i:s', strtotime($pulang['scan_date']));
                        $waktuPulangJadwal = date('H:i:s', strtotime($jadwal['jam_pulang']));
                        $daily['pulang'] = date('H:i', strtotime($waktuPulangAktual));

                        if ($waktuPulangAktual < $waktuPulangJadwal) {
                            $selisihMenit = round((strtotime($waktuPulangJadwal) - strtotime($waktuPulangAktual)) / 60);
                            if ($selisihMenit > 0) {
                                $daily['pulang'] .= " (Pulang Cepat {$selisihMenit} Menit)";
                                $summary['Pulang_Cepat']++;
                            }
                        }
                    }
                    $daily['keterangan'] = 'Hadir';
                }
            }
            $rincian[] = $daily;
        }

        return ['rincian' => $rincian, 'summary' => $summary];
    }
}
