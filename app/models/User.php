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
        $stmt = $this->db->prepare("SELECT id_employe, nama, username, password, pin FROM employe WHERE username = ?");
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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
        $stmt = $this->db->prepare("DELETE FROM employe WHERE id_employe = ?");
        $stmt->execute([$id]);
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
