<?php

class Repres
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Menghasilkan rekap presensi untuk semua pegawai dengan prioritas absensi aktual
     */
    public function getRekapPresensi($startDate, $endDate, $idJabatan = null)
    {
        $where = "";
        $params = [];
        if ($idJabatan) {
            $where = "WHERE e.id_jabatan = ?";
            $params[] = $idJabatan;
        }

        // --- UBAHAN 1: Tambahkan e.id_jabatan pada SELECT ---
        $stmtPegawai = $this->db->prepare("
            SELECT e.pin, e.nama, e.id_jabatan, j.jabatan 
            FROM employe e 
            JOIN jabatan j ON e.id_jabatan = j.id_jabatan 
            $where 
            ORDER BY e.nama ASC
        ");
        $stmtPegawai->execute($params);
        $pegawaiList = $stmtPegawai->fetchAll(PDO::FETCH_ASSOC);

        $rekapHasil = [];

        foreach ($pegawaiList as $pegawai) {
            $pin = $pegawai['pin'];
            $summary = array_fill_keys(['Kehadiran', 'Alpa', 'Terlambat', 'Pulang_Cepat', 'Sakit', 'Izin', 'Dinas_Luar', 'Hari_Efektif'], 0);

            $currentDate = new DateTime($startDate);
            $endDateObj = new DateTime($endDate);

            while ($currentDate <= $endDateObj) {
                $tanggal = $currentDate->format('Y-m-d');
                $namaHari = strtolower($currentDate->format('l'));

                // 1. Ambil Jadwal
                $stmtJadwal = $this->db->prepare("
                    SELECT MIN(waktu_datang) AS jam_masuk, MAX(waktu_pulang) AS jam_pulang 
                    FROM jadwal 
                    WHERE id_employee = ? AND day = ?
                ");
                $stmtJadwal->execute([$pin, $namaHari]);
                $jadwal = $stmtJadwal->fetch(PDO::FETCH_ASSOC);

                // 2. Ambil Data Absensi Aktual
                $stmtAbsen = $this->db->prepare("SELECT scan_date, status, keterangan FROM attendance WHERE pin = ? AND DATE(scan_date) = ? ORDER BY scan_date");
                $stmtAbsen->execute([$pin, $tanggal]);
                $absensi = $stmtAbsen->fetchAll(PDO::FETCH_ASSOC);

                $datang = null;
                $pulang = null;
                foreach ($absensi as $absen) {
                    if ($absen['status'] == 'datang' && !$datang) $datang = $absen;
                    if ($absen['status'] == 'pulang') $pulang = $absen;
                }

                // 3. Cek Hari Libur Nasional
                $stmtLibur = $this->db->prepare("SELECT keterangan FROM hari_libur WHERE :tgl BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
                $stmtLibur->execute([':tgl' => $tanggal]);
                $isLibur = $stmtLibur->fetch(PDO::FETCH_ASSOC);

                // --- LOGIKA UTAMA ---

                // KONDISI A: PEGAWAI MELAKUKAN SCAN (Hadir di hari apapun)
                if ($datang) {
                    $summary['Hari_Efektif']++;

                    $keterangan = strtolower(trim($datang['keterangan']));
                    if ($keterangan !== 'hadir') {
                        if ($keterangan == 'sakit') $summary['Sakit']++;
                        elseif ($keterangan == 'izin') $summary['Izin']++;
                        elseif ($keterangan == 'dinas luar') $summary['Dinas_Luar']++;
                    } else {
                        $summary['Kehadiran']++;

                        // Hitung Terlambat & Pulang Cepat
                        $jamJadwalMasuk = ($jadwal && $jadwal['jam_masuk']) ? $jadwal['jam_masuk'] : '08:00:00';
                        $jamJadwalPulang = ($jadwal && $jadwal['jam_pulang']) ? $jadwal['jam_pulang'] : '16:00:00';

                        $waktuDatangAktual = strtotime($datang['scan_date']);
                        $waktuDatangJadwal = strtotime($tanggal . ' ' . $jamJadwalMasuk);
                        if ($waktuDatangAktual > $waktuDatangJadwal) {
                            $selisih = round(($waktuDatangAktual - $waktuDatangJadwal) / 60);
                            if ($selisih > 0) $summary['Terlambat']++;
                        }

                        if ($pulang) {
                            $waktuPulangAktual = strtotime($pulang['scan_date']);
                            $waktuPulangJadwal = strtotime($tanggal . ' ' . $jamJadwalPulang);
                            if ($waktuPulangAktual < $waktuPulangJadwal) {
                                $selisih = round(($waktuPulangJadwal - $waktuPulangAktual) / 60);
                                if ($selisih > 0) $summary['Pulang_Cepat']++;
                            }
                        }
                    }
                }
                // KONDISI B: PEGAWAI TIDAK MELAKUKAN SCAN
                else {
                    // --- UBAHAN 2: Cek apakah dia Satpam ---
                    $isSatpam = ($pegawai['id_jabatan'] == 3);

                    // Hanya dihitung Alpa jika: Dia PUNYA jadwal
                    if ($jadwal && $jadwal['jam_masuk'] !== null) {
                        // Jika BUKAN libur nasional, ATAU jika dia adalah Satpam (Satpam trabas libur)
                        if (!$isLibur || $isSatpam) {
                            $summary['Hari_Efektif']++;
                            $summary['Alpa']++;
                        }
                    }
                }

                $currentDate->modify('+1 day');
            }

            // Gabungkan hasil summary dengan info pegawai
            $rekapHasil[] = array_merge($pegawai, $summary);
        }

        return $rekapHasil;
    }
}
