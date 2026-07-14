<?php
class Penilaian
{
    private $db;
    public function __construct($pdo)
    {
        $this->db = $pdo;
    }



    public function getAllKelasByGuru($id_employe)
    {
        // 1. Ambil tahun pelajaran yang aktif dengan benar
        $sql_tahun = "SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1";
        $stmt_tahun = $this->db->query($sql_tahun); // Gunakan query() untuk SQL sederhana tanpa parameter
        $tahun = $stmt_tahun->fetch(PDO::FETCH_ASSOC);

        $id_tahun_pelajaran = $tahun['id_tahun_pelajaran'] ?? null;

        // Jika tidak ada tahun aktif, langsung return array kosong agar tidak error di JOIN
        if (!$id_tahun_pelajaran) {
            return [];
        }

        // 2. Query utama dengan bind parameter yang lengkap
        $stmt = $this->db->prepare("
        SELECT 
            k.id_kelas, 
            k.kelas, 
            mg.id_mapel, 
            m.kode_mapel, 
            m.nama_mapel, 
            m.tingkat_mapel, 
            mg.id_mapel_guru
        FROM 
            mapel_guru mg
        JOIN 
            kelas k ON mg.id_kelas = k.id_kelas
        JOIN 
            mapel m ON mg.id_mapel = m.id_mapel
        WHERE 
            mg.id_guru = :id_employe 
            AND mg.id_tahun_pelajaran = :tahun_pelajaran
    ");

        $stmt->execute([
            'id_employe'      => $id_employe,
            'tahun_pelajaran' => $id_tahun_pelajaran
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }




    public function insert($data)
    {
        $guru = is_array($data['id_employee'])
            ? implode(',', $data['id_employee'])
            : $data['id_employee'];
        $stmt = $this->db->prepare("INSERT INTO mapel (kode_mapel, nama_mapel, tingkat_mapel, guru) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $data['kode_mapel'],
            $data['mapel'],
            $data['tingkat'],
            $guru
        ]);
    }

    public function getGuruNames($idList)
    {
        $ids = array_filter(array_map('intval', explode(',', $idList)));
        if (empty($ids)) return [];

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT nama FROM employe WHERE id_employe IN ($placeholders)");
        $stmt->execute($ids);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function update($data)
    {
        $guru = is_array($data['id_employee'])
            ? implode(',', $data['id_employee'])
            : $data['id_employee'];
        $sql = "UPDATE mapel SET kode_mapel = ?, nama_mapel = ?, tingkat_mapel = ?, guru = ? WHERE id_mapel = ?";
        $params = [
            $data['kode_mapel'],
            $data['mapel'],
            $data['tingkat'],
            $guru,
            $data['id_mapel']
        ];

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }


    public function delete($id)
    {
        $stmt = $this->db->prepare("DELETE FROM mapel WHERE id_mapel = ?");
        $stmt->execute([$id]);
    }
}
