<?php
class User
{
    private $db;
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    // =================== USER ===================
    public function getAll()
    {
        // ✅ Menggunakan GROUP_CONCAT untuk mengambil semua id_level sebagai string, contoh: "1,5"
        $sql = "SELECT e.*, j.jabatan AS kategori_jabatan, 
                   GROUP_CONCAT(ul.id_level) AS user_levels
            FROM employe e
            LEFT JOIN jabatan j ON e.id_jabatan = j.id_jabatan
            LEFT JOIN user_level ul ON e.id_employe = ul.id_employe
            GROUP BY e.id_employe
            ORDER BY e.nama ASC";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Mencari satu user berdasarkan ID, beserta kategori jabatannya.
     */
    public function find($id)
    {
        $sql = "SELECT e.*, j.jabatan AS kategori_jabatan
                FROM employe e
                LEFT JOIN jabatan j ON e.id_jabatan = j.id_jabatan
                WHERE e.id_employe = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername($username)
    {
        // id_jabatan = kategori pegawai (1 Guru, 2 Tenaga Kependidikan, 3 Satpam).
        // Wajib ikut diambil: AuthController menyimpannya ke sesi dan menu/kartu
        // dashboard memakainya untuk mengenali tenaga kependidikan.
        try {
            $stmt = $this->db->prepare("SELECT id_employe, nama, username, password, pin, id_jabatan FROM employe WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // DB lama tanpa kolom id_jabatan: login tetap jalan, jabatan 0.
            $stmt = $this->db->prepare("SELECT id_employe, nama, username, password, pin FROM employe WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }


    public function getAktif()

    {

        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Mengambil daftar semua kategori jabatan untuk dropdown.
     */
    public function getAllJabatan()
    {
        return $this->db->query("SELECT * FROM jabatan ORDER BY jabatan ASC")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Memperbarui data user, termasuk kolom 'jabatan' LAMA dan 'id_jabatan' BARU.
     */
    public function update($data)
    {
        // Hapus 'id_level' dari query UPDATE
        $sql = "UPDATE employe SET 
                nama = ?, 
                pin = ?, 
                jabatan = ?, 
                id_jabatan = ?, 
                no_wa = ?, 
                username = ?";
        $params = [
            $data['nama'],
            $data['pin'],
            $data['jabatan'],
            $data['id_jabatan'],
            $data['no_wa'],
            $data['username'],
        ];

        if (!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id_employe = ?";
        $params[] = $data['id'];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function delete($id)
    {
        // Bersihkan data terkait lebih dulu: tidak semua instalasi memakai
        // ON DELETE CASCADE, dan baris yatim membuat menu/level jadi aneh.
        $pinStmt = $this->db->prepare("SELECT pin FROM employe WHERE id_employe = ?");
        $pinStmt->execute([$id]);
        $pin = $pinStmt->fetchColumn();

        $this->db->prepare("DELETE FROM user_level WHERE id_employe = ?")->execute([$id]);
        if ($pin !== false && $pin !== null && $pin !== '') {
            $this->db->prepare("DELETE FROM jadwal WHERE id_employee = ?")->execute([$pin]);
        }
        $this->db->prepare("DELETE FROM employe WHERE id_employe = ?")->execute([$id]);
    }

    /** Username sudah dipakai? */
    public function usernameExists($username, $kecualiId = null)
    {
        $sql = "SELECT COUNT(*) FROM employe WHERE username = ?";
        $params = [$username];
        if ($kecualiId !== null) {
            $sql .= " AND id_employe <> ?";
            $params[] = $kecualiId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** PIN (dipakai mesin fingerprint) sudah dipakai? */
    public function pinExists($pin, $kecualiId = null)
    {
        $sql = "SELECT COUNT(*) FROM employe WHERE pin = ?";
        $params = [$pin];
        if ($kecualiId !== null) {
            $sql .= " AND id_employe <> ?";
            $params[] = $kecualiId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Simpan pegawai baru. Mengembalikan id_employe yang baru dibuat.
     *
     * Ada dua jalur: normal (id_employe AUTO_INCREMENT) dan cadangan untuk
     * tabel lama yang PK-nya tidak auto-increment — di situ id dihitung manual.
     */
    public function insert($data)
    {
        $nilai = [
            $data['nama'],
            $data['pin'],
            $data['jabatan'],
            (int) $data['id_jabatan'],
            $data['no_wa'],
            $data['username'],
            password_hash($data['password'], PASSWORD_DEFAULT),
        ];

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO employe (nama, pin, jabatan, id_jabatan, no_wa, username, password)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute($nilai);
            return (int) $this->db->lastInsertId();
        } catch (PDOException $e) {
            // Bukan soal id_employe -> teruskan, biar controller yang menangani.
            if (stripos($e->getMessage(), 'id_employe') === false) {
                throw $e;
            }
        }

        $idBaru = (int) $this->db->query("SELECT COALESCE(MAX(id_employe), 0) + 1 FROM employe")->fetchColumn();
        $stmt = $this->db->prepare(
            "INSERT INTO employe (id_employe, nama, pin, jabatan, id_jabatan, no_wa, username, password)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute(array_merge([$idBaru], $nilai));

        return $idBaru;
    }

    /** Ganti seluruh level akses milik seorang pegawai. */
    public function setLevels($idEmploye, array $levels)
    {
        $this->db->prepare("DELETE FROM user_level WHERE id_employe = ?")->execute([$idEmploye]);
        if (!$levels) {
            return;
        }
        $ins = $this->db->prepare("INSERT INTO user_level (id_employe, id_level) VALUES (?, ?)");
        foreach ($levels as $lv) {
            if (ctype_digit((string) $lv)) {
                $ins->execute([$idEmploye, (int) $lv]);
            }
        }
    }

    /** Jumlah pegawai yang memegang sebuah level (dipakai cegah hapus admin terakhir). */
    public function hitungLevel($idLevel)
    {
        $stmt = $this->db->prepare("SELECT COUNT(DISTINCT id_employe) FROM user_level WHERE id_level = ?");
        $stmt->execute([$idLevel]);
        return (int) $stmt->fetchColumn();
    }


    // =================== JADWAL ===================
    public function getJadwal($pin)
    {
        $stmt = $this->db->prepare("SELECT * FROM jadwal WHERE id_employee = ? ORDER BY FIELD(day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
        $stmt->execute([$pin]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addJadwal($data)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO jadwal (id_employee, day, waktu_datang, waktu_pulang) VALUES (?, ?, ?, ?)");
            return $stmt->execute([
                $data['id_user'],
                $data['day'],
                $data['in'],
                $data['out']
            ]);
        } catch (PDOException $e) {
            // Ini akan membantu kamu melihat error apa yang terjadi
            die("Error Database: " . $e->getMessage());
        }
    }



    public function updateJadwal($data)
    {
        $stmt = $this->db->prepare("UPDATE jadwal SET day = ?, waktu_datang = ?, waktu_pulang = ? WHERE id_jadwal = ?");
        $stmt->execute([$data['day'], $data['in'], $data['out'], $data['id']]);
    }

    public function deleteJadwal($id)
    {
        $stmt = $this->db->prepare("DELETE FROM jadwal WHERE id_jadwal = ?");
        $stmt->execute([$id]);
    }
}
