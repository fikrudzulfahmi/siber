<?php
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';
require_once '../app/models/Presensi.php';

class PresensiController extends BaseController
{
    public function index()
    {
        date_default_timezone_set('Asia/Jakarta');
        $today           = date('Y-m-d');
        $tanggalHariIni  = formatTanggalIndo($today, false, true);
        $presensiModel   = new Presensi($this->db);

        // ✅ Ambil data guru & tendik
        $guru_list   = $presensiModel->getByJabatan(1); // jabatan guru
        $tendik_list = $presensiModel->getByJabatan(2); // jabatan tendik

        $guru_kehadiran   = [];
        $tendik_kehadiran = [];

        foreach ($guru_list as $guru) {
            $kehadiran = $presensiModel->getKehadiranLengkap($guru['pin'], $today);
            $guru_kehadiran[] = array_merge($guru, $kehadiran);
        }

        foreach ($tendik_list as $tendik) {
            $kehadiran = $presensiModel->getKehadiranLengkap($tendik['pin'], $today);
            $tendik_kehadiran[] = array_merge($tendik, $kehadiran);
        }


        // Hitung hadir & tidak hadir
        $jumlah_hadir       = 0;
        $jumlah_tidak_hadir = 0;
        $jumlah_terlambat   = 0;
        $jumlah_pulang_cepat = 0;

        foreach (array_merge($guru_kehadiran, $tendik_kehadiran) as $data) {
            if ($data['keterangan'] === 'Hadir') {
                $jumlah_hadir++;
                if (strpos($data['waktu_datang'], 'Terlambat') !== false) $jumlah_terlambat++;
                if (strpos($data['waktu_pulang'], 'Pulang cepat') !== false) $jumlah_pulang_cepat++;
            } else {
                $jumlah_tidak_hadir++;
            }
        }


        require __DIR__ . '/../views/admin/presensi/index.php';
    }
}
