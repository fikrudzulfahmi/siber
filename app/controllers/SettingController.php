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
        
        // --- LOGIKA RIWAYAT BACKUP ---
        $backupDir = __DIR__ . '/../../public/backups/';
        $riwayat_backup = [];
        if (is_dir($backupDir)) {
            $files = array_diff(scandir($backupDir), array('.', '..'));
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'sql') {
                    $filePath = $backupDir . $file;
                    $sizeBytes = filesize($filePath);
                    
                    // Format Size
                    if ($sizeBytes >= 1048576) {
                        $sizeStr = number_format($sizeBytes / 1048576, 2) . ' MB';
                    } elseif ($sizeBytes >= 1024) {
                        $sizeStr = number_format($sizeBytes / 1024, 2) . ' KB';
                    } else {
                        $sizeStr = $sizeBytes . ' Bytes';
                    }

                    $riwayat_backup[] = [
                        'nama_file' => $file,
                        'ukuran' => $sizeStr,
                        'tanggal' => date("Y-m-d H:i:s", filemtime($filePath)),
                        'timestamp' => filemtime($filePath)
                    ];
                }
            }
            // Sort by timestamp desc
            usort($riwayat_backup, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
        }
        
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

    public function updateGoogleDriveConfig()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $webhook_url = $_POST['webhook_url'] ?? '';
            $secret_key = $_POST['secret_key'] ?? '';

            $configFile = __DIR__ . '/../config.php';
            if (file_exists($configFile) && is_writable($configFile)) {
                $configContent = file_get_contents($configFile);

                // Regex untuk mengganti nilai define
                $configContent = preg_replace(
                    "/define\('GOOGLE_DRIVE_WEBHOOK_URL',\s*'.*?'\);/",
                    "define('GOOGLE_DRIVE_WEBHOOK_URL', '" . addslashes($webhook_url) . "');",
                    $configContent
                );

                $configContent = preg_replace(
                    "/define\('GOOGLE_DRIVE_SECRET_KEY',\s*'.*?'\);/",
                    "define('GOOGLE_DRIVE_SECRET_KEY', '" . addslashes($secret_key) . "');",
                    $configContent
                );

                file_put_contents($configFile, $configContent);

                echo "<script>alert('Konfigurasi Google Drive berhasil disimpan!'); window.location.href='?controller=setting&method=index';</script>";
            } else {
                echo "<script>alert('Gagal! File config.php tidak memiliki izin tulis (writeable).'); window.location.href='?controller=setting&method=index';</script>";
            }
        } else {
            header('Location: ?controller=setting&method=index');
        }
        exit;
    }
}
