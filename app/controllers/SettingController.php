<?php
require_once __DIR__ . '/../models/Setting.php';

class SettingController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function index()
    {
        // Cek Login Admin


        $model = new Setting($this->db);
        $all_settings = $model->getAllWASettings();
        $query = $this->db->query("SELECT last_ping, mesin1_status, mesin2_status FROM server_monitoring WHERE id = 1");
        $data_server = $query->fetch(PDO::FETCH_ASSOC);

        // Masukkan ke dalam array $data agar bisa dibaca oleh View
        if ($data_server) {
            $data['last_ping']   = $data_server['last_ping'];
            $data['mesin1_status'] = $data_server['mesin1_status'];
            $data['mesin2_status'] = $data_server['mesin2_status'];
        } else {
            $data['last_ping']   = null;
            $data['mesin1_status'] = 'Offline';
            $data['mesin2_status'] = 'Offline';
        }

        // Data ini akan dikirim ke view
        $data['last_ping'] = $data_server['last_ping'];
        require __DIR__ . '/../views/admin/setting/setting.php';
    }

    public function updateAJAX()
    {
        ob_clean();
        header('Content-Type: application/json');

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (isset($data['key']) && isset($data['status'])) {
            $model = new Setting($this->db);
            $status_str = ($data['status'] === true) ? 'true' : 'false';

            $result = $model->updateByKey($data['key'], $status_str);

            echo json_encode([
                'success' => $result,
                'db_value' => $status_str,
                'key' => $data['key']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        }
        exit;
    }
}
