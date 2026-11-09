<?php
require_once 'Model.php'; // Pastikan path-nya sesuai, ini relatif terhadap BaseController
require_once __DIR__ . '/JenisPerangkatAlias.php';

class Perangkat extends Model
{
    public function getByUserAndTahun($id_user, $id_tahun)
    {
        $stmt = $this->db->prepare("SELECT * FROM perangkat_mengajar 
        WHERE id_employe = ? AND id_tahun_pelajaran = ?
        ORDER BY jenis_perangkat ASC");
        $stmt->execute([$id_user, $id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function getPerangkat($id_employe, $id_tahun, $jenis)
    {
        $stmt = $this->db->prepare("SELECT * FROM perangkat_mengajar WHERE id_employe = ? AND id_tahun_pelajaran = ? AND jenis_perangkat = ?");
        $stmt->execute([$id_employe, $id_tahun, $jenis]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

public function insertOrUpdate($data)
{
    // hapus data sebelumnya
    $this->db->prepare("DELETE FROM perangkat_mengajar 
        WHERE id_employe = ? AND id_tahun_pelajaran = ? AND id_mapel_guru = ? AND jenis_perangkat = ?")
        ->execute([$data['id_employe'], $data['id_tahun_pelajaran'], $data['id_mapel_guru'], $data['jenis_perangkat']]);

    // insert data baru
    $stmt = $this->db->prepare("INSERT INTO perangkat_mengajar 
        (id_employe, id_tahun_pelajaran, id_mapel_guru, tingkat, jenis_perangkat, file, status_approval, tanggal_upload) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");

    return $stmt->execute([
        $data['id_employe'],
        $data['id_tahun_pelajaran'],
        $data['id_mapel_guru'],
        $data['tingkat'],
        $data['jenis_perangkat'],
        $data['file']
    ]);
}



    public function getAllPerangkatGuru($id_tahun)
    {
        $stmt = $this->db->prepare("SELECT p.*, e.nama FROM perangkat_mengajar p JOIN employe e ON p.id_employe = e.id_employe WHERE p.id_tahun_pelajaran = ?");
        $stmt->execute([$id_tahun]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

/**
 * Ambil perangkat guru untuk satu mapel-guru, difilter tahun pelajaran + (opsional) tingkat.
 * $tingkat dibuat opsional supaya pemanggil yang tidak punya konteks tingkat
 * (mis. saat halaman dibuka lewat URL dengan id_mapel_guru) tidak fatal error.
 */
public function getWithDeadline($id_user, $id_tahun_pelajaran, $id_mapel_guru, $tingkat = null)
{
    // Acuan pencocokan = PASANGAN (mapel, tingkat) dari id_mapel_guru yang dipilih,
    // BUKAN id_mapel_guru itu sendiri. Satu guru sering punya beberapa baris mapel_guru
    // untuk mapel+tingkat yang sama (kelas paralel); unggahan bisa menempel pada baris
    // yang berbeda dari yang tampil di dropdown, sehingga pencocokan id persis membuat
    // file terbaca "belum upload" padahal ada dan sudah muncul di Verifikasi Admin.
    $stmt = $this->db->prepare("
        SELECT mg.id_mapel, m.nama_mapel, k.kelas, k.tingkat
        FROM mapel_guru mg
        JOIN mapel m ON m.id_mapel = mg.id_mapel
        JOIN kelas k ON k.id_kelas = mg.id_kelas
        WHERE mg.id_mapel_guru = ?
    ");
    $stmt->execute([$id_mapel_guru]);
    $acuan = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$acuan) return [];

    $tingkatAcuan = ($tingkat !== null && $tingkat !== '') ? $tingkat : $acuan['tingkat'];

    $stmt = $this->db->prepare("
        SELECT p.id, p.jenis_perangkat, p.file, p.status_approval, p.tanggal_upload, p.catatan
        FROM perangkat_mengajar p
        JOIN mapel_guru mg ON mg.id_mapel_guru = p.id_mapel_guru
        JOIN kelas k ON k.id_kelas = mg.id_kelas
        WHERE p.id_employe = ?
          AND p.id_tahun_pelajaran = ?
          AND mg.id_mapel = ?
          AND k.tingkat = ?
        ORDER BY p.id DESC
    ");
    $stmt->execute([$id_user, $id_tahun_pelajaran, $acuan['id_mapel'], $tingkatAcuan]);

    // Satu baris per jenis. Label jenis lama dinormalkan (JenisPerangkatAlias) supaya
    // cocok dengan nama master yang dipakai baris tabel di form, sama seperti Verifikasi.
    $perJenis = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $jenis = JenisPerangkatAlias::PETA[$row['jenis_perangkat']] ?? $row['jenis_perangkat'];
        if (isset($perJenis[$jenis])) continue; // ORDER BY p.id DESC -> unggahan terbaru menang
        $perJenis[$jenis] = $row;
    }

    $stmt = $this->db->prepare("SELECT jenis_perangkat, tanggal_deadline FROM deadline_perangkat WHERE id_tahun_pelajaran = ?");
    $stmt->execute([$id_tahun_pelajaran]);
    $deadline = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $d) {
        $deadline[$d['jenis_perangkat']] = $d['tanggal_deadline'];
    }

    $hasil = [];
    foreach ($perJenis as $jenis => $row) {
        $hasil[] = [
            'id_mapel_guru'    => $id_mapel_guru,
            'nama_mapel'       => $acuan['nama_mapel'],
            'kelas'            => $acuan['kelas'],
            'tingkat'          => $acuan['tingkat'],
            'jenis_perangkat'  => $jenis,
            'file'             => $row['file'],
            'status_approval'  => $row['status_approval'] ?: 'belum upload',
            'tanggal_upload'   => $row['tanggal_upload'],
            'tanggal_deadline' => $deadline[$jenis] ?? null,
            'catatan'          => $row['catatan'],
        ];
    }
    return $hasil;
}


public function getByGuruAndMapel($idGuru, $idTahun, $idMapelGuru)
{
    $stmt = $this->db->prepare("SELECT * FROM perangkat WHERE id_employe = ? AND id_tahun_pelajaran = ? AND id_mapel_guru = ?");
    $stmt->execute([$idGuru, $idTahun, $idMapelGuru]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getPerangkatByMapelKelas($id_mapel_kelas)
{
    $stmt = $this->db->prepare("SELECT p.*, jp.nama_jenis 
        FROM perangkat p 
        JOIN jenis_perangkat jp ON p.id_jenis_perangkat = jp.id_jenis_perangkat 
        WHERE p.id_mapel_kelas = ?
        ORDER BY jp.urutan ASC");
    $stmt->execute([$id_mapel_kelas]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}



    public function setStatusApproval($id, $status)
    {
        $stmt = $this->db->prepare("UPDATE perangkat_mengajar SET status_approval = ? WHERE id = ?");
        return $stmt->execute([$status, $id]);
    }

/**
 * Ambil kombinasi mapel + tingkat yang diampu guru PADA TAHUN PELAJARAN AKTIF.
 * Tanpa filter tahun, penugasan mengajar tahun ajaran lama ikut muncul di dropdown
 * (mis. tingkat 7 dari tahun lalu masih tampil bersama tingkat 8 yang berlaku).
 */
public function getByGuru($id_guru, $id_tahun = null)
{
    // Tahun aktif dibaca dari database (sumber kebenaran) — jangan dari sesi,
    // karena $_SESSION['user']['id_tahun'] hanya di-set saat login dan bisa basi.
    if ($id_tahun === null) {
        $id_tahun = $this->db->query("SELECT id_tahun_pelajaran FROM tahun_pelajaran WHERE status = 'Aktif' LIMIT 1")->fetchColumn();
    }

    $sql = "SELECT DISTINCT m.nama_mapel, k.tingkat, MIN(mg.id_mapel_guru) AS id_mapel_guru
            FROM mapel_guru mg
            JOIN mapel m ON mg.id_mapel = m.id_mapel
            JOIN kelas k ON mg.id_kelas = k.id_kelas
            WHERE mg.id_guru = ?";
    $params = [$id_guru];

    if ($id_tahun) {
        $sql .= " AND mg.id_tahun_pelajaran = ?";
        $params[] = $id_tahun;
    }

    $sql .= " GROUP BY m.nama_mapel, k.tingkat
              ORDER BY m.nama_mapel, k.tingkat";

    $stmt = $this->db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getByMapelGuru($id_mapel_guru)
{
    $stmt = $this->db->prepare("SELECT * FROM perangkat_mengajar WHERE id_mapel_guru = ?");
    $stmt->execute([$id_mapel_guru]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

public function updateStatus($id, $status)
{
    $stmt = $this->db->prepare("UPDATE perangkat_mengajar SET status_approval = ? WHERE id_perangkat = ?");
    return $stmt->execute([$status, $id]);
}

}