<?php
class Kalender {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllHolidays() {
        $stmt = $this->pdo->query("SELECT * FROM hari_libur");
        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = [
                'id' => $row['id_libur'],
                'title' => $row['keterangan'],
                'start' => $row['tanggal_mulai'],
                'end' => date('Y-m-d', strtotime($row['tanggal_selesai'] . ' +1 day')),
                'color' => '#344767'
            ];
        }
        return $result;
    }

    public function simpan($mulai, $selesai, $keterangan) {
        $stmt = $this->pdo->prepare("INSERT INTO hari_libur (tanggal_mulai, tanggal_selesai, keterangan) VALUES (?, ?, ?)");
        $stmt->execute([$mulai, $selesai, $keterangan]);
        return $this->pdo->lastInsertId();
    }

    public function hapus($id) {
        $stmt = $this->pdo->prepare("DELETE FROM hari_libur WHERE id_libur = ?");
        $stmt->execute([$id]);
    }
}
