<?php
// File: app/models/Repres_Debug.php
// Skrip ini KHUSUS untuk melacak perhitungan satu pegawai hari per hari.

class Repres_Debug
{
    private $db;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    public function lacakSatuPegawai($pin, $startDate, $endDate)
    {
        // 1. Dapatkan info pegawai yang akan dilacak
        $stmtPegawai = $this->db->prepare("
            SELECT e.pin, e.nama, j.jabatan 
            FROM employe e 
            JOIN jabatan j ON e.id_jabatan = j.id_jabatan 
            WHERE e.pin = ?
        ");
        $stmtPegawai->execute([$pin]);
        $pegawai = $stmtPegawai->fetch(PDO::FETCH_ASSOC);

        if (!$pegawai) {
            echo "Pegawai dengan PIN $pin tidak ditemukan.";
            return;
        }

        echo "=========================================================\n";
        echo "MELACAK REKAP UNTUK: " . $pegawai['nama'] . "\n";
        echo "PERIODE: $startDate s/d $endDate\n";
        echo "=========================================================\n\n";

        $summary = array_fill_keys(['Kehadiran', 'Alpa', 'Terlambat', 'Pulang_Cepat', 'Sakit', 'Izin', 'Dinas_Luar', 'Hari_Efektif'], 0);

        // 2. Lakukan perulangan untuk setiap hari
        $currentDate = new DateTime($startDate);
        $endDateObj = new DateTime($endDate);

        while ($currentDate <= $endDateObj) {
            $tanggal = $currentDate->format('Y-m-d');
            $namaHari = strtolower($currentDate->format('l'));

            echo "--- Tanggal: " . $tanggal . " (" . ucfirst($namaHari) . ") ---\n";

            // Cek hari libur
            $stmtLibur = $this->db->prepare("SELECT keterangan FROM hari_libur WHERE :tgl BETWEEN tanggal_mulai AND tanggal_selesai LIMIT 1");
            $stmtLibur->execute([':tgl' => $tanggal]);
            if ($stmtLibur->fetch()) {
                echo "   -> Status: HARI LIBUR. Dilewati.\n\n";
                $currentDate->modify('+1 day');
                continue;
            }

            // Cek jadwal
            $stmtJadwal = $this->db->prepare("SELECT MIN(waktu_datang) AS jam_masuk, MAX(waktu_pulang) AS jam_pulang FROM jadwal WHERE id_employee = ? AND day = ?");
            $stmtJadwal->execute([$pin, $namaHari]);
            $jadwal = $stmtJadwal->fetch(PDO::FETCH_ASSOC);

            if (!$jadwal || $jadwal['jam_masuk'] === null) {
                echo "   -> Status: Tidak Ada Jadwal. Dilewati.\n\n";
                $currentDate->modify('+1 day');
                continue;
            }

            $summary['Hari_Efektif']++;
            echo "   -> Jadwal Ditemukan: Masuk " . $jadwal['jam_masuk'] . ", Pulang " . $jadwal['jam_pulang'] . ". (Hari Efektif: " . $summary['Hari_Efektif'] . ")\n";

            // Cek absensi
            $stmtAbsen = $this->db->prepare("SELECT scan_date, status, keterangan FROM attendance WHERE pin = ? AND DATE(scan_date) = ? ORDER BY scan_date");
            $stmtAbsen->execute([$pin, $tanggal]);
            $absensi = $stmtAbsen->fetchAll(PDO::FETCH_ASSOC);

            $datang = null;
            $pulang = null;
            foreach ($absensi as $absen) {
                if ($absen['status'] == 'datang' && !$datang) $datang = $absen;
                if ($absen['status'] == 'pulang') $pulang = $absen;
            }

            if (!$datang) {
                $summary['Alpa']++;
                echo "   -> Keputusan: ALPA (Tidak ada absen masuk). (Total Alpa: " . $summary['Alpa'] . ")\n\n";
            } else {
                echo "   -> Absensi Ditemukan: Masuk " . date('H:i:s', strtotime($datang['scan_date'])) . ", Keterangan '" . $datang['keterangan'] . "'\n";

                $keterangan = strtolower(trim($datang['keterangan']));
                if ($keterangan !== 'hadir') {
                    if ($keterangan == 'sakit') $summary['Sakit']++;
                    elseif ($keterangan == 'izin') $summary['Izin']++;
                    elseif ($keterangan == 'dinas-luar') $summary['Dinas_Luar']++;
                    echo "   -> Keputusan: " . ucfirst($keterangan) . "\n\n";
                } else {
                    $summary['Kehadiran']++;
                    echo "   -> Keputusan: HADIR. (Total Hadir: " . $summary['Kehadiran'] . ")\n";

                    $waktuDatang = strtotime($datang['scan_date']);
                    $waktuSeharusnya = strtotime($jadwal['jam_masuk']);

                    if ($waktuDatang > $waktuSeharusnya) {
                        $selisihMenit = round(($waktuDatang - $waktuSeharusnya) / 60);
                        echo "     - Cek Terlambat: Aktual(" . date('H:i:s', $waktuDatang) . ") > Jadwal(" . $jadwal['jam_masuk'] . "). Selisih: $selisihMenit menit.\n";
                        if ($selisihMenit > 5) {
                            $summary['Terlambat']++;
                            echo "     - KESIMPULAN: Dihitung TERLAMBAT. (Total Terlambat: " . $summary['Terlambat'] . ")\n";
                        } else {
                            echo "     - KESIMPULAN: Masih dalam toleransi.\n";
                        }
                    } else {
                        echo "     - Cek Terlambat: Tepat Waktu.\n";
                    }

                    if ($pulang) {
                        echo "   -> Absen Pulang: " . date('H:i:s', strtotime($pulang['scan_date'])) . "\n";
                        if (strtotime($pulang['scan_date']) < strtotime($jadwal['jam_pulang'])) {
                            $summary['Pulang_Cepat']++;
                            echo "     - KESIMPULAN: Dihitung PULANG CEPAT. (Total Pulang Cepat: " . $summary['Pulang_Cepat'] . ")\n";
                        } else {
                            echo "     - Cek Pulang Cepat: Tepat Waktu.\n";
                        }
                    }
                }
            }

            $currentDate->modify('+1 day');
            echo "\n";
        }

        echo "=========================================================\n";
        echo "HASIL AKHIR SUMMARY:\n";
        print_r($summary);
        echo "=========================================================\n";
    }
}
