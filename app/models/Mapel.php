<?php
class Mapel
{
    private $db;
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }




    public function getAll()
    {
        return $this->db->query("SELECT * FROM mapel")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function Guru($id_mapel = null, $id_tahun = null) // Tambahkan parameter id_tahun
    {
        $sql = "SELECT mapel_guru.*, mapel.nama_mapel, kelas.kelas, employe.nama 
            FROM mapel_guru
            JOIN mapel ON mapel.id_mapel = mapel_guru.id_mapel
            JOIN kelas ON kelas.id_kelas = mapel_guru.id_kelas
            JOIN employe ON employe.id_employe = mapel_guru.id_guru";

        $conditions = [];
        $params = [];

        if ($id_mapel) {
            $conditions[] = "mapel_guru.id_mapel = :id_mapel";
            $params['id_mapel'] = $id_mapel;
        }

        if ($id_tahun) {
            $conditions[] = "mapel_guru.id_tahun_pelajaran = :id_tahun";
            $params['id_tahun'] = $id_tahun;
        }

        if (!empty($conditions)) {
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM mapel WHERE id_mapel = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function find_mapel_by_mapel_guru($id_mapel_guru)
    {
        $stmt = $this->db->prepare("SELECT mapel.* 
        FROM mapel_guru 
        JOIN mapel ON mapel.id_mapel = mapel_guru.id_mapel 
        WHERE mapel_guru.id_mapel_guru = ?");
        $stmt->execute([$id_mapel_guru]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findGuru($id)
    {
        $stmt = $this->db->prepare("
        SELECT * FROM mapel_guru WHERE id_mapel_guru = :id
    ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByTingkat($tingkat)
    {
        $stmt = $this->db->prepare("SELECT * FROM kelas WHERE tingkat = :tingkat");
        $stmt->execute(['tingkat' => $tingkat]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function insert($data)
    {

        $stmt = $this->db->prepare("INSERT INTO mapel (kode_mapel, nama_mapel, tingkat_mapel) VALUES (?, ?, ?)");
        $stmt->execute([
            $data['kode_mapel'],
            $data['mapel'],
            $data['tingkat']
        ]);
    }
    public function insert_guru($data)
    {
        // Tambahkan id_tahun_pelajaran ke dalam query
        $stmt = $this->db->prepare("INSERT INTO mapel_guru (id_mapel, id_kelas, id_guru, id_tahun_pelajaran) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['id_mapel'],
            $data['id_kelas'],
            $data['id_guru'],
            $data['id_tahun_pelajaran'] // Data baru
        ]);
    }


    public function update($data)
    {

        $sql = "UPDATE mapel SET kode_mapel = ?, nama_mapel = ?, tingkat_mapel = ? WHERE id_mapel = ?";
        $params = [
            $data['kode_mapel'],
            $data['mapel'],
            $data['tingkat'],
            $data['id_mapel']
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    public function update_guru($data)
    {
        $stmt = $this->db->prepare("
        UPDATE mapel_guru 
        SET id_kelas = :id_kelas, id_guru = :id_guru, id_tahun_pelajaran = :id_tahun 
        WHERE id_mapel_guru = :id_mapel_guru
    ");
        return $stmt->execute([
            'id_kelas' => $data['id_kelas'],
            'id_guru' => $data['id_guru'],
            'id_tahun' => $data['id_tahun_pelajaran'], // Update tahun juga jika perlu
            'id_mapel_guru' => $data['id_mapel_guru']
        ]);
    }

    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM mapel WHERE id_mapel = ?");
        $stmt->execute([$id]);
    }

    public function delete_guru($id)
    {
        $stmt = $this->db->prepare("DELETE FROM mapel_guru WHERE id_mapel_guru = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount();
    }
    public function getByGuru($id_guru)
    {
        $sql = "SELECT mg.*, m.nama_mapel, k.tingkat
            FROM mapel_guru mg
            JOIN mapel m ON m.id_mapel = mg.id_mapel
            JOIN kelas k ON k.id_kelas = mg.id_kelas
            WHERE mg.id_guru = :id_guru";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_guru' => $id_guru]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveTahunPelajaran()
    {
        // Mengambil semua kolom (*) agar kita punya data nama tahun dan semester
        $stmt = $this->db->prepare("SELECT * FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1");
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
