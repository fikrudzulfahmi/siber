<?php

class EkstraModel
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // =========================================================================
    // BAGIAN 1: MANAJEMEN EKSTRA (ADMIN)
    // =========================================================================

    public function getAllEkstra($id_tahun, $id_guru = null, $is_admin = false)
    {
        if ($is_admin) {
            // Jika Admin, tampilkan semua tanpa filter guru
            $sql = "SELECT e.*, g.nama as nama_pengampu 
                FROM ekstra e 
                JOIN employe g ON e.id_guru_pengampu = g.id_employe 
                WHERE e.id_tahun_pelajaran = ? 
                ORDER BY e.nama_ekstra ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_tahun]);
        } else {
            // Jika Guru, filter berdasarkan id_guru yang login (Koordinator OR Pendamping)
            $sql = "SELECT e.*, g.nama as nama_pengampu, 
                CASE WHEN e.id_guru_pengampu = ? THEN 'Koordinator' ELSE 'Pendamping' END as status_peran
                FROM ekstra e 
                JOIN employe g ON e.id_guru_pengampu = g.id_employe 
                LEFT JOIN ekstra_pendamping ep ON e.id_ekstra = ep.id_ekstra
                WHERE e.id_tahun_pelajaran = ? 
                AND (e.id_guru_pengampu = ? OR ep.id_employe = ?)
                GROUP BY e.id_ekstra
                ORDER BY e.nama_ekstra ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id_guru, $id_tahun, $id_guru, $id_guru]);
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function simpanEkstra($data)
    {
        $sql = "INSERT INTO ekstra (nama_ekstra, id_guru_pengampu, id_tahun_pelajaran, keterangan) 
                VALUES (?, ?, ?, ?)";
        return $this->db->prepare($sql)->execute([
            $data['nama_ekstra'],
            $data['id_guru_pengampu'],
            $data['id_tahun_pelajaran'],
            $data['keterangan']
        ]);
    }

    public function getEkstraById($id)
    {
        $sql = "SELECT e.*, g.nama as nama_pengampu FROM ekstra e
        JOIN employe g ON e.id_guru_pengampu = g.id_employe
        WHERE e.id_ekstra = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateEkstra($data)
    {
        $sql = "UPDATE ekstra SET 
            nama_ekstra = ?, 
            id_guru_pengampu = ?, 
            keterangan = ? 
            WHERE id_ekstra = ?";
        return $this->db->prepare($sql)->execute([
            $data['nama_ekstra'],
            $data['id_guru_pengampu'],
            $data['keterangan'],
            $data['id_ekstra']
        ]);
    }

    // Ambil daftar anggota yang sudah bergabung di ekstra tertentu
    public function getAnggotaEkstra($id_ekstra)
    {
        $sql = "SELECT ae.id_ekstra_anggota, ae.id_ploting_siswa, s.nama_siswa AS nama_siswa, k.kelas 
            FROM ekstra_anggota ae
            JOIN ploting_siswa ps ON ae.id_ploting_siswa = ps.id_ploting
            JOIN siswa s ON ps.id_siswa = s.id_siswa
            JOIN kelas k ON ps.id_kelas = k.id_kelas
            WHERE ae.id_ekstra = ? 
            ORDER BY s.nama_siswa ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ekstra]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Ambil daftar siswa dari ploting yang BELUM masuk ke ekstra ini (untuk dipilih)
    public function getSiswaTersedia($id_tahun, $id_ekstra)
    {
        $sql = "SELECT ps.id_ploting, s.nama_siswa AS nama_siswa, k.kelas 
            FROM ploting_siswa ps
            JOIN siswa s ON ps.id_siswa = s.id_siswa
            JOIN kelas k ON ps.id_kelas = k.id_kelas
            WHERE ps.id_tahun = ? 
            AND ps.id_ploting NOT IN (
                SELECT id_ploting_siswa FROM ekstra_anggota WHERE id_ekstra = ?
            )
            ORDER BY k.kelas ASC, s.nama_siswa ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_tahun, $id_ekstra]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tambahAnggota($id_ekstra, $id_ploting)
    {
        $sql = "INSERT INTO ekstra_anggota (id_ekstra, id_ploting_siswa) VALUES (?, ?)";
        return $this->db->prepare($sql)->execute([$id_ekstra, $id_ploting]);
    }

    public function hapusAnggota($id_anggota)
    {
        $sql = "DELETE FROM ekstra_anggota WHERE id_ekstra_anggota = ?";
        return $this->db->prepare($sql)->execute([$id_anggota]);
    }


    // 1. Simpan Jurnal/Kegiatan
    public function insertJurnal($data)
    {
        // 1. Cek duplikasi: Apakah ekstra ini sudah mengisi jurnal di tanggal tersebut?
        $cekSql = "SELECT id_ekstra_kegiatan FROM ekstra_kegiatan 
               WHERE id_ekstra = ? AND tanggal = ?";
        $stmtCek = $this->db->prepare($cekSql);
        $stmtCek->execute([$data['id_ekstra'], $data['tanggal']]);

        if ($stmtCek->fetch()) {
            // Jika sudah ada, jangan insert dan kembalikan false/null
            return false;
        }

        // 2. Jika lolos cek, baru lakukan Insert
        $sql = "INSERT INTO ekstra_kegiatan (id_ekstra, nama_kegiatan, tanggal, isi_kegiatan) 
            VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            $data['id_ekstra'],
            $data['nama_kegiatan'],
            $data['tanggal'],
            $data['isi_kegiatan']
        ]);

        // Kembalikan ID jurnal yang baru saja dibuat jika sukses
        return $success ? $this->db->lastInsertId() : false;
    }

    // 2. Simpan Presensi (Looping dari Controller)
    public function insertPresensi($id_kegiatan, $id_ploting, $status)
    {
        $sql = "INSERT INTO ekstra_presensi (id_ekstra_kegiatan, id_ploting_siswa, status) 
            VALUES (?, ?, ?)";
        return $this->db->prepare($sql)->execute([$id_kegiatan, $id_ploting, $status]);
    }

    // 3. Simpan Foto Kegiatan
    public function insertFoto($id_kegiatan, $nama_file)
    {
        $sql = "INSERT INTO ekstra_foto (id_ekstra_kegiatan, nama_file) VALUES (?, ?)";
        return $this->db->prepare($sql)->execute([$id_kegiatan, $nama_file]);
    }



    // =========================================================================
    // BAGIAN 4: MONITORING (ADMIN & GURU)
    // =========================================================================

    public function getRiwayatKegiatan($id_ekstra)
    {
        // Mengambil data kegiatan dan menggabungkan nama file foto jika ada
        $sql = "SELECT ek.*, ef.nama_file 
            FROM ekstra_kegiatan ek
            LEFT JOIN ekstra_foto ef ON ek.id_ekstra_kegiatan = ef.id_ekstra_kegiatan
            WHERE ek.id_ekstra = ?
            ORDER BY ek.tanggal DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ekstra]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDetailKegiatan($id_kegiatan)
    {
        // Info Kegiatan
        $sql_k = "SELECT ek.*, e.nama_ekstra 
                  FROM ekstra_kegiatan ek 
                  JOIN ekstra e ON ek.id_ekstra = e.id_ekstra 
                  WHERE ek.id_ekstra_kegiatan = ?";
        $stmt_k = $this->db->prepare($sql_k);
        $stmt_k->execute([$id_kegiatan]);
        $kegiatan = $stmt_k->fetch(PDO::FETCH_ASSOC);

        // Daftar Hadir Siswa
        $sql_p = "SELECT ep.*, s.nama_siswa 
                  FROM ekstra_presensi ep 
                  JOIN ploting_siswa ps ON ep.id_ploting_siswa = ps.id_ploting_siswa
                  JOIN siswa s ON ps.id_siswa = s.id_siswa
                  WHERE ep.id_ekstra_kegiatan = ?";
        $stmt_p = $this->db->prepare($sql_p);
        $stmt_p->execute([$id_kegiatan]);
        $presensi = $stmt_p->fetchAll(PDO::FETCH_ASSOC);

        // Foto-foto
        $sql_f = "SELECT * FROM ekstra_foto WHERE id_ekstra_kegiatan = ?";
        $stmt_f = $this->db->prepare($sql_f);
        $stmt_f->execute([$id_kegiatan]);
        $fotos = $stmt_f->fetchAll(PDO::FETCH_ASSOC);

        return [
            'info' => $kegiatan,
            'presensi' => $presensi,
            'fotos' => $fotos
        ];
    }

    // Mengambil detail satu jurnal untuk form edit
    public function getJurnalById($id_kegiatan)
    {
        $sql = "SELECT ek.*, ef.nama_file 
            FROM ekstra_kegiatan ek
            LEFT JOIN ekstra_foto ef ON ek.id_ekstra_kegiatan = ef.id_ekstra_kegiatan
            WHERE ek.id_ekstra_kegiatan = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_kegiatan]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateJurnal($data)
    {
        $sql = "UPDATE ekstra_kegiatan SET 
            nama_kegiatan = ?, 
            tanggal = ?, 
            isi_kegiatan = ? 
            WHERE id_ekstra_kegiatan = ?";
        return $this->db->prepare($sql)->execute([
            $data['nama_kegiatan'],
            $data['tanggal'],
            $data['isi_kegiatan'],
            $data['id_ekstra_kegiatan']
        ]);
    }

    public function getActiveTahun()
    {
        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
        $stmt->execute();
        // Ganti single() dengan fetch(PDO::FETCH_ASSOC)
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    public function updateFoto($id_kegiatan, $nama_file)
    {
        // Cek apakah sudah ada foto sebelumnya
        $cek = "SELECT id_ekstra_foto FROM ekstra_foto WHERE id_ekstra_kegiatan = ?";
        $stmt = $this->db->prepare($cek);
        $stmt->execute([$id_kegiatan]);

        if ($stmt->fetch()) {
            $sql = "UPDATE ekstra_foto SET nama_file = ? WHERE id_ekstra_kegiatan = ?";
        } else {
            $sql = "INSERT INTO ekstra_foto (nama_file, id_ekstra_kegiatan) VALUES (?, ?)";
        }
        return $this->db->prepare($sql)->execute([$nama_file, $id_kegiatan]);
    }

    public function getPresensiByJurnal($id_kegiatan)
    {
        $sql = "SELECT id_ploting_siswa, status FROM ekstra_presensi WHERE id_ekstra_kegiatan = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_kegiatan]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Menghasilkan array [id_ploting => status]
    }

    public function updatePresensi($id_kegiatan, $id_ploting, $status)
    {
        $sql = "UPDATE ekstra_presensi SET status = ? 
            WHERE id_ekstra_kegiatan = ? AND id_ploting_siswa = ?";
        return $this->db->prepare($sql)->execute([$status, $id_kegiatan, $id_ploting]);
    }

    // Menghapus jurnal (Otomatis menghapus foto & presensi karena CASCADE)
    public function deleteJurnal($id_kegiatan)
    {
        $sql = "DELETE FROM ekstra_kegiatan WHERE id_ekstra_kegiatan = ?";
        return $this->db->prepare($sql)->execute([$id_kegiatan]);
    }

    public function getRekapPresensi($id_ekstra)
    {
        $sql = "SELECT 
                s.nama_siswa AS nama_siswa, 
                k.kelas,
                COUNT(CASE WHEN ep.status = 'Hadir' THEN 1 END) as hadir,
                COUNT(CASE WHEN ep.status = 'Izin' THEN 1 END) as izin,
                COUNT(CASE WHEN ep.status = 'Sakit' THEN 1 END) as sakit,
                COUNT(CASE WHEN ep.status = 'Alfa' THEN 1 END) as alfa,
                COUNT(ep.id_ekstra_presensi) as total_pertemuan
            FROM ekstra_anggota ea
            JOIN ploting_siswa ps ON ea.id_ploting_siswa = ps.id_ploting
            JOIN kelas k ON ps.id_kelas = k.id_kelas
            JOIN siswa s ON ps.id_siswa = s.id_siswa
            LEFT JOIN ekstra_presensi ep ON ps.id_ploting = ep.id_ploting_siswa
            LEFT JOIN ekstra_kegiatan ek ON ep.id_ekstra_kegiatan = ek.id_ekstra_kegiatan
            WHERE ea.id_ekstra = ?
            GROUP BY ps.id_ploting
            ORDER BY s.nama_siswa ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ekstra]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =========================================================================
    // BAGIAN 5: INTEGRASI NILAI RAPOR (BARU)
    // =========================================================================

    /**
     * Mengambil periode rapor yang sedang aktif (is_active = 1)
     */
    public function getActiveRapor()
    {
        $sql = "SELECT *, tp.tahun_pelajaran, tp.semester
                FROM rapor_setting 
                JOIN tahun_pelajaran tp ON rapor_setting.id_tahun_pelajaran = tp.id_tahun_pelajaran
                WHERE rapor_setting.is_active = 1 
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil nilai yang sudah tersimpan agar bisa diedit di form massal
     */
    public function getExistingNilai($id_ekstra, $id_rapor)
    {
        $sql = "SELECT id_siswa, nilai, keterangan FROM ekstra_nilai 
                WHERE id_ekstra = ? AND id_rapor = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ekstra, $id_rapor]);

        // Mengembalikan array dengan ID Siswa sebagai KEY agar mudah dipanggil di View
        // Format: [id_siswa => ['nilai' => 'A', 'keterangan' => '...']]
        return $stmt->fetchAll(PDO::FETCH_UNIQUE | PDO::FETCH_ASSOC | PDO::FETCH_GROUP);
    }

    /**
     * Simpan atau Update Nilai Massal menggunakan ON DUPLICATE KEY UPDATE
     */
    public function saveNilaiMassal($id_rapor, $id_ekstra, $data_nilai)
    {
        // Query ini otomatis Update jika data (id_rapor, id_ekstra, id_siswa) sudah ada
        $sql = "INSERT INTO ekstra_nilai (id_rapor, id_ekstra, id_siswa, nilai, keterangan) 
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE nilai = VALUES(nilai), keterangan = VALUES(keterangan)";

        $stmt = $this->db->prepare($sql);

        try {
            $this->db->beginTransaction();
            foreach ($data_nilai as $id_siswa => $row) {
                if (!empty($row['nilai'])) {
                    // Simpan atau Update
                    $stmt->execute([$id_rapor, $id_ekstra, $id_siswa, $row['nilai'], $row['keterangan']]);
                } else {
                    // OPSI: Hapus data jika nilai dikosongkan (jika diinginkan)
                    $del = $this->db->prepare("DELETE FROM ekstra_nilai WHERE id_rapor=? AND id_ekstra=? AND id_siswa=?");
                    $del->execute([$id_rapor, $id_ekstra, $id_siswa]);
                }
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Mengambil anggota ekstra beserta ID Siswa (untuk relasi ke ekstra_nilai)
     */
    public function getAnggotaUntukNilai($id_ekstra)
    {
        $sql = "SELECT s.id_siswa, s.nama_siswa, k.kelas, ps.id_ploting
                FROM ekstra_anggota ea
                JOIN ploting_siswa ps ON ea.id_ploting_siswa = ps.id_ploting
                JOIN siswa s ON ps.id_siswa = s.id_siswa
                JOIN kelas k ON ps.id_kelas = k.id_kelas
                WHERE ea.id_ekstra = ?
                ORDER BY s.nama_siswa ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ekstra]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Mengambil semua guru untuk daftar pilihan
    public function getAllGuru()
    {
        return $this->db->query("SELECT id_employe, nama FROM employe  ORDER BY nama ASC")->fetchAll();
    }

    // Mengambil hanya ID-ID guru yang sudah menjadi pendamping di ekskul tertentu
    public function getIdsPendamping($id_ekstra)
    {
        $sql = "SELECT id_employe FROM ekstra_pendamping WHERE id_ekstra = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ekstra]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Tambahkan fungsi ini di dalam EkstraModel
    public function cekJurnalHariIni($id_ekstra, $tanggal)
    {
        $sql = "SELECT id_ekstra_kegiatan FROM ekstra_kegiatan 
            WHERE id_ekstra = ? AND tanggal = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ekstra, $tanggal]);
        return $stmt->fetch(PDO::FETCH_ASSOC); // Mengembalikan data jika ada, false jika tidak
    }

    // Tambahkan di dalam class EkstraModel
    public function getPendampingAktif($id_ekstra)
    {
        $sql = "SELECT ep.*, g.nama 
            FROM ekstra_pendamping ep
            JOIN employe g ON ep.id_employe = g.id_employe
            WHERE ep.id_ekstra = ?
            ORDER BY g.nama ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ekstra]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insertPresensiGuru($id_kegiatan, $id_guru, $status)
    {
        $sql = "INSERT INTO ekstra_presensi_guru (id_ekstra_kegiatan, id_guru, status) 
            VALUES (?, ?, ?)";
        return $this->db->prepare($sql)->execute([$id_kegiatan, $id_guru, $status]);
    }

    public function getRekapGuru($id_ekstra, $tgl_awal, $tgl_akhir)
    {
        $sql = "SELECT 
                g.nama,
                'Koordinator' as jabatan,
                COUNT(CASE WHEN epg.status = 'Hadir' THEN 1 END) as hadir,
                COUNT(CASE WHEN epg.status = 'Izin' THEN 1 END) as izin,
                COUNT(CASE WHEN epg.status = 'Sakit' THEN 1 END) as sakit,
                COUNT(CASE WHEN epg.status = 'Alfa' THEN 1 END) as alfa
            FROM ekstra e
            JOIN employe g ON e.id_guru_pengampu = g.id_employe
            JOIN ekstra_kegiatan ek ON ek.id_ekstra = e.id_ekstra
            LEFT JOIN ekstra_presensi_guru epg ON epg.id_guru = g.id_employe 
                 AND epg.id_ekstra_kegiatan = ek.id_ekstra_kegiatan
            WHERE e.id_ekstra = ? AND ek.tanggal BETWEEN ? AND ?
            GROUP BY g.id_employe

            UNION ALL

            SELECT 
                g.nama,
                'Pendamping' as jabatan,
                COUNT(CASE WHEN epg.status = 'Hadir' THEN 1 END) as hadir,
                COUNT(CASE WHEN epg.status = 'Izin' THEN 1 END) as izin,
                COUNT(CASE WHEN epg.status = 'Sakit' THEN 1 END) as sakit,
                COUNT(CASE WHEN epg.status = 'Alfa' THEN 1 END) as alfa
            FROM ekstra_pendamping ep
            JOIN employe g ON ep.id_employe = g.id_employe
            JOIN ekstra_kegiatan ek ON ek.id_ekstra = ep.id_ekstra
            LEFT JOIN ekstra_presensi_guru epg ON epg.id_guru = g.id_employe 
                 AND epg.id_ekstra_kegiatan = ek.id_ekstra_kegiatan
            WHERE ep.id_ekstra = ? AND ek.tanggal BETWEEN ? AND ?
            GROUP BY g.id_employe";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ekstra, $tgl_awal, $tgl_akhir, $id_ekstra, $tgl_awal, $tgl_akhir]);
        return $stmt->fetchAll();
    }

    public function getRiwayatKegiatanFiltered($id_ekstra, $tgl_awal, $tgl_akhir)
    {
        $sql = "SELECT 
                ek.id_ekstra_kegiatan,
                ek.tanggal,
                ek.nama_kegiatan,
                ek.isi_kegiatan,
                f.nama_file
            FROM ekstra_kegiatan ek
            LEFT JOIN ekstra_foto f ON ek.id_ekstra_kegiatan = f.id_ekstra_kegiatan
            WHERE ek.id_ekstra = ? 
            AND ek.tanggal BETWEEN ? AND ?
            ORDER BY ek.tanggal ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_ekstra, $tgl_awal, $tgl_akhir]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
