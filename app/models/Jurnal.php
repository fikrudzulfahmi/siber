<?php
class Jurnal
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }

    // --- BAGIAN 1: FILTER TAHUN PELAJARAN (UTAMA) ---

    // 1. Ambil Kelas (Filter Guru & Tahun Aktif)
    public function getKelasByGuru($id_guru, $id_tahun)
    {
        $stmt = $this->db->prepare("
            SELECT DISTINCT k.id_kelas, k.kelas
            FROM mapel_guru mg
            JOIN kelas k ON mg.id_kelas = k.id_kelas
            WHERE mg.id_guru = ? AND mg.id_tahun_pelajaran = ?
            ORDER BY k.kelas ASC
        ");
        $stmt->execute([$id_guru, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 2. Ambil Mapel (Filter Guru, Kelas & Tahun Aktif)
    public function getMapelByGuruKelasTahun($id_guru, $id_kelas, $id_tahun)
    {
        $stmt = $this->db->prepare("
            SELECT mg.id_mapel_guru, m.nama_mapel
            FROM mapel_guru mg
            JOIN mapel m ON mg.id_mapel = m.id_mapel
            WHERE mg.id_guru = ? 
              AND mg.id_kelas = ? 
              AND mg.id_tahun_pelajaran = ?
        ");
        $stmt->execute([$id_guru, $id_kelas, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 3. Ambil Siswa dari PLOTING (Filter Kelas & Tahun Aktif)
    // Mengambil dari tabel ploting_siswa agar sesuai tahun ajaran
    public function getSiswaByKelas($id_kelas, $id_tahun)
    {
        $stmt = $this->db->prepare("
            SELECT s.id_siswa, s.nama_siswa 
            FROM ploting_siswa ps
            JOIN siswa s ON ps.id_siswa = s.id_siswa
            WHERE ps.id_kelas = ? 
              AND ps.id_tahun = ?
              AND s.status = 'Aktif'
            ORDER BY s.nama_siswa ASC
        ");
        $stmt->execute([$id_kelas, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. [UPDATE] Ambil TP (Filter Mapel & Tahun Aktif)
    // Kita join balik ke mapel_guru untuk memastikan TP ini milik tahun yang aktif
    public function getTPByMapelGuru($id_mapel_guru, $id_tahun)
    {
        $stmt = $this->db->prepare("
            SELECT tp.id_tp, tp.tujuan_pembelajaran 
            FROM tp
            JOIN mapel_guru mg ON tp.id_mapel_guru = mg.id_mapel_guru
            WHERE tp.id_mapel_guru = ? 
              AND mg.id_tahun_pelajaran = ?
        ");
        $stmt->execute([$id_mapel_guru, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // --- BAGIAN 2: CRUD JURNAL ---

    public function simpanJurnal($data)
    {
        $query = "INSERT INTO jurnal (
                  id_kelas, id_mapel_guru, id_tp, materi,
                  jam_mulai, jam_akhir, catatan_kehadiran, catatan_pembelajaran
              ) VALUES (
                  :id_kelas, :id_mapel_guru, :id_tp, :materi, 
                  :jam_mulai, :jam_akhir, :catatan_kehadiran, :catatan_pembelajaran
              )";
        $stmt = $this->db->prepare($query);
        $stmt->execute([
            ':id_kelas' => $data['id_kelas'],
            ':id_mapel_guru' => $data['id_mapel_guru'],
            ':id_tp' => $data['id_tp'],
            ':materi' => $data['materi'],
            ':jam_mulai' => $data['jam_mulai'],
            ':jam_akhir' => $data['jam_akhir'],
            ':catatan_kehadiran' => $data['catatan_kehadiran'],
            ':catatan_pembelajaran' => $data['catatan_pembelajaran']
        ]);
        return $this->db->lastInsertId();
    }

    public function simpanKehadiran($id_jurnal, $kehadiran)
    {
        $query = "INSERT INTO jurnal_kehadiran (id_jurnal, id_siswa, status) VALUES (:id_jurnal, :id_siswa, :status)";
        $stmt = $this->db->prepare($query);
        foreach ($kehadiran as $id_siswa => $status) {
            $stmt->execute([':id_jurnal' => $id_jurnal, ':id_siswa' => $id_siswa, ':status' => $status]);
        }
    }

    public function cekJurnalHariIni($id_kelas, $id_mapel_guru)
    {
        $query = "SELECT COUNT(*) FROM jurnal WHERE id_kelas = ? AND id_mapel_guru = ? AND DATE(created_at) = CURDATE()";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id_kelas, $id_mapel_guru]);
        return $stmt->fetchColumn() > 0;
    }

    public function findById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM jurnal WHERE id_jurnal = :id");
        $stmt->execute([':id' => $id]);
        $jurnal = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($jurnal) {
            $stmt2 = $this->db->prepare("SELECT * FROM jurnal_kehadiran WHERE id_jurnal = :id");
            $stmt2->execute([':id' => $id]);
            $jurnal['kehadiran'] = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        }
        return $jurnal;
    }

    public function getJurnalByGuru($id_user)
    {
        $stmt = $this->db->prepare("SELECT j.*, k.kelas, m.nama_mapel, tp.tujuan_pembelajaran
        FROM jurnal j
        JOIN mapel_guru mg ON j.id_mapel_guru = mg.id_mapel_guru
        JOIN kelas k ON j.id_kelas = k.id_kelas
        JOIN mapel m ON mg.id_mapel = m.id_mapel
        LEFT JOIN tp ON j.id_tp = tp.id_tp
        WHERE mg.id_guru = ?
        ORDER BY j.created_at DESC");
        $stmt->execute([$id_user]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function update($data)
    {
        $sql = "UPDATE jurnal 
                SET id_kelas = :id_kelas, 
                    id_mapel_guru = :id_mapel_guru, 
                    id_tp = :id_tp, 
                    materi = :materi, 
                    jam_mulai= :jam_mulai, 
                    jam_akhir= :jam_akhir,
                    catatan_kehadiran = :catatan_kehadiran, 
                    catatan_pembelajaran = :catatan_pembelajaran
                WHERE id_jurnal = :id";

        $stmt = $this->db->prepare($sql);

        // Pastikan parameter sesuai dengan input name di view
        $stmt->execute([
            ':id' => $data['id'],
            ':id_kelas' => $data['id_kelas'],
            ':id_mapel_guru' => $data['id_mapel_guru'],
            ':id_tp' => $data['id_tp'], // Ini sekarang string ID tunggal
            ':materi' => $data['materi'],
            ':jam_mulai' => $data['jam_mulai'],
            ':jam_akhir' => $data['jam_akhir'],
            ':catatan_kehadiran' => $data['catatan_kehadiran'],
            ':catatan_pembelajaran' => $data['catatan_pembelajaran']
        ]);

        // Update Kehadiran: Hapus lama, insert baru
        $del = $this->db->prepare("DELETE FROM jurnal_kehadiran WHERE id_jurnal = ?");
        $del->execute([$data['id']]);

        $ins = $this->db->prepare("INSERT INTO jurnal_kehadiran (id_jurnal, id_siswa, status) VALUES (?, ?, ?)");

        if (isset($data['kehadiran'])) {
            foreach ($data['kehadiran'] as $id_siswa => $status) {
                $ins->execute([$data['id'], $id_siswa, $status]);
            }
        }
    }



    // Ambil riwayat jurnal berdasarkan Guru & Tahun Aktif
    public function getRiwayatJurnal($id_guru, $id_tahun)
    {
        // Asumsi: Di tabel 'jurnal' ada kolom 'id_tp' yang isinya string ID dipisah koma (contoh: "1,2,5")

        $sql = "SELECT 
                    j.*, 
                    m.nama_mapel, 
                    k.kelas,
                    -- SUBQUERY PENGGANTI --
                    (
                        SELECT GROUP_CONCAT(t.tujuan_pembelajaran SEPARATOR '<br>&bull; ') 
                        FROM tp t 
                        -- Mencari ID TP di dalam kolom j.id_tp --
                        WHERE FIND_IN_SET(t.id_tp, REPLACE(j.id_tp, ' ', '')) > 0
                    ) as tujuan_pembelajaran_list
                FROM jurnal j
                JOIN mapel_guru mg ON j.id_mapel_guru = mg.id_mapel_guru
                JOIN mapel m ON mg.id_mapel = m.id_mapel
                JOIN kelas k ON mg.id_kelas = k.id_kelas
                WHERE mg.id_guru = :id_guru 
                  AND mg.id_tahun_pelajaran = :id_tahun
                ORDER BY j.tanggal DESC, j.jam_mulai ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id_guru', $id_guru);
        $stmt->bindParam(':id_tahun', $id_tahun);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Mengambil rekap jurnal berdasarkan tanggal.
     * Logic: Ambil Jadwal di hari itu, Join dengan Jurnal untuk cek sudah isi/belum.
     */
    public function getRekapSemuaGuruHariIni($tanggal, $id_tahun)
    {
        $namaHari = [
            'Sunday'    => 'Minggu',
            'Monday'    => 'Senin',
            'Tuesday'   => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday'  => 'Kamis',
            'Friday'    => 'Jumat',
            'Saturday'  => 'Sabtu'
        ];
        // Pastikan tanggal valid sebelum konversi hari
        $hari = $namaHari[date('l', strtotime($tanggal))];

        $sql = "SELECT
                e.nama,
                m.nama_mapel,
                k.kelas,
                jp.jam_mulai,
                jp.jam_selesai,
                j.id_jurnal,
                CASE WHEN j.id_jurnal IS NOT NULL THEN 'Sudah' ELSE 'Belum' END AS status_jurnal
            FROM jadwal_pelajaran jp
            JOIN mapel_guru mg ON jp.id_mapel_guru = mg.id_mapel_guru
            JOIN employe e ON mg.id_guru = e.id_employe
            JOIN mapel m ON mg.id_mapel = m.id_mapel
            JOIN kelas k ON mg.id_kelas = k.id_kelas
            LEFT JOIN jurnal j
                ON j.id_mapel_guru = mg.id_mapel_guru
                AND DATE(j.created_at) = ?
            WHERE jp.hari = ? AND mg.id_tahun_pelajaran = ?
            ORDER BY e.nama ASC, jp.jam_mulai ASC";
        // Note: Saya ubah order by nama dulu agar grouping di view rapi

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$tanggal, $hari, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Method Baru: Ambil detail jurnal (Disesuaikan dengan tabel employe & mapel_guru)
     */
    public function getJurnalById($id_jurnal)
    {
        $query = "SELECT 
                j.*,
                m.nama_mapel,
                k.kelas AS nama_kelas,
                e.nama AS nama_guru,
                jp.jam_mulai, 
                jp.jam_selesai AS jam_akhir,
                -- Fungsi untuk menggabungkan banyak TP menjadi satu string
                GROUP_CONCAT(t.tujuan_pembelajaran SEPARATOR '\n') as tujuan_pembelajaran 
              FROM jurnal j
              JOIN mapel_guru mg ON j.id_mapel_guru = mg.id_mapel_guru
              JOIN mapel m ON mg.id_mapel = m.id_mapel
              JOIN kelas k ON mg.id_kelas = k.id_kelas
              JOIN employe e ON mg.id_guru = e.id_employe
              LEFT JOIN jadwal_pelajaran jp ON jp.id_mapel_guru = mg.id_mapel_guru
              LEFT JOIN tp t ON j.id_tp = t.id_tp 
              WHERE j.id_jurnal = :id
              GROUP BY j.id_jurnal"; // Wajib ada GROUP BY jika pakai GROUP_CONCAT

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id_jurnal);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Method Baru: Ambil absensi (Asumsi tabel 'detail_jurnal' dan 'siswa' standar)
     */
    public function getAbsensiByJurnal($id_jurnal)
    {
        // Gunakan nama tabel sesuai database Anda: jurnal_kehadiran
        $query = "SELECT s.nama_siswa, jk.status 
              FROM jurnal_kehadiran jk
              JOIN siswa s ON jk.id_siswa = s.id_siswa
              WHERE jk.id_jurnal = :id";

        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':id', $id_jurnal);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM jurnal WHERE id_jurnal = ?");
        $stmt->execute([$id]);
    }

    public function getSetting($key)
    {
        $sql = "SELECT status FROM settings WHERE key_setting = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['status'] : 'false';
    }

    public function updateSetting($key, $status_string)
    {
        // $status_string akan berisi teks 'true' atau 'false'
        $sql = "UPDATE settings SET status = ? WHERE key_setting = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$status_string, $key]);
    }
}
