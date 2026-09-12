<?php

/**
 * Model Activity Log
 * Mencatat seluruh aktivitas pengguna aplikasi (siapa, kapan, aktivitas, perangkat).
 */
class ActivityLog
{
    private $db;
    private $tabelDicek = false;
    private $tabelAda = false;

    public function __construct($pdo)
    {
        $this->db = $pdo;
    }

    /**
     * Cek sekali per-request apakah tabel activity_log sudah dibuat.
     * Dipakai agar aplikasi tetap berjalan normal sebelum tabel di-import.
     */
    public function tabelAda()
    {
        if ($this->tabelDicek) {
            return $this->tabelAda;
        }

        $this->tabelDicek = true;
        try {
            $this->db->query("SELECT 1 FROM activity_log LIMIT 1");
            $this->tabelAda = true;
        } catch (Throwable $e) {
            $this->tabelAda = false;
        }

        return $this->tabelAda;
    }

    /**
     * Simpan satu baris aktivitas.
     */
    public function insert($data)
    {
        $stmt = $this->db->prepare("
            INSERT INTO activity_log
                (id_employe, nama_user, level_user, aktivitas, modul, aksi,
                 controller, method, http_method, url,
                 ip_address, user_agent, browser, os, device, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $data['id_employe'] ?? null,
            $data['nama_user'] ?? null,
            $data['level_user'] ?? null,
            $data['aktivitas'] ?? '-',
            $data['modul'] ?? null,
            $data['aksi'] ?? null,
            $data['controller'] ?? null,
            $data['method'] ?? null,
            $data['http_method'] ?? null,
            $data['url'] ?? null,
            $data['ip_address'] ?? null,
            $data['user_agent'] ?? null,
            $data['browser'] ?? null,
            $data['os'] ?? null,
            $data['device'] ?? null,
            $data['created_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Susun klausa WHERE dari filter (dipakai getPaginated & countTotal).
     */
    private function buildWhere($filters)
    {
        $where  = [];
        $params = [];

        if (!empty($filters['id_employe'])) {
            $where[]  = "id_employe = ?";
            $params[] = $filters['id_employe'];
        }

        if (!empty($filters['aksi'])) {
            $where[]  = "aksi = ?";
            $params[] = $filters['aksi'];
        }

        if (!empty($filters['modul'])) {
            $where[]  = "modul = ?";
            $params[] = $filters['modul'];
        }

        if (!empty($filters['tanggal_awal'])) {
            $where[]  = "DATE(created_at) >= ?";
            $params[] = $filters['tanggal_awal'];
        }

        if (!empty($filters['tanggal_akhir'])) {
            $where[]  = "DATE(created_at) <= ?";
            $params[] = $filters['tanggal_akhir'];
        }

        if (!empty($filters['q'])) {
            $where[] = "(aktivitas LIKE ? OR nama_user LIKE ? OR ip_address LIKE ? OR modul LIKE ?)";
            $kw = '%' . $filters['q'] . '%';
            array_push($params, $kw, $kw, $kw, $kw);
        }

        return [
            $where ? (' WHERE ' . implode(' AND ', $where)) : '',
            $params,
        ];
    }

    /**
     * Ambil data log terpaginasi.
     */
    public function getPaginated($filters = [], $limit = 25, $offset = 0)
    {
        if (!$this->tabelAda()) {
            return [];
        }

        list($where, $params) = $this->buildWhere($filters);

        $sql = "SELECT * FROM activity_log" . $where . " ORDER BY created_at DESC, id_log DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Hitung total baris sesuai filter (untuk pagination).
     */
    public function countTotal($filters = [])
    {
        if (!$this->tabelAda()) {
            return 0;
        }

        list($where, $params) = $this->buildWhere($filters);

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM activity_log" . $where);
        $stmt->execute($params);

        return (int)$stmt->fetchColumn();
    }

    /**
     * Daftar user yang pernah tercatat (untuk dropdown filter).
     */
    public function getDaftarUser()
    {
        if (!$this->tabelAda()) {
            return [];
        }

        return $this->db->query("
            SELECT id_employe, MAX(nama_user) AS nama_user, COUNT(*) AS jumlah
            FROM activity_log
            WHERE id_employe IS NOT NULL
            GROUP BY id_employe
            ORDER BY nama_user ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Daftar modul yang pernah tercatat (untuk dropdown filter).
     */
    public function getDaftarModul()
    {
        if (!$this->tabelAda()) {
            return [];
        }

        return $this->db->query("
            SELECT modul, COUNT(*) AS jumlah
            FROM activity_log
            WHERE modul IS NOT NULL AND modul != ''
            GROUP BY modul
            ORDER BY modul ASC
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Hapus log yang lebih lama dari N hari.
     */
    public function hapusLogLama($hari)
    {
        if (!$this->tabelAda()) {
            return 0;
        }

        $stmt = $this->db->prepare("DELETE FROM activity_log WHERE created_at < (NOW() - INTERVAL ? DAY)");
        $stmt->execute([(int)$hari]);

        return $stmt->rowCount();
    }

    /**
     * Hapus seluruh log.
     */
    public function hapusSemua()
    {
        if (!$this->tabelAda()) {
            return 0;
        }

        $jumlah = (int)$this->db->query("SELECT COUNT(*) FROM activity_log")->fetchColumn();
        $this->db->exec("DELETE FROM activity_log");

        return $jumlah;
    }
}
