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

        // Umur ping dihitung oleh MySQL (TIMESTAMPDIFF) supaya tidak bergantung
        // pada kesamaan zona waktu antara PHP dan server database. Sebelumnya
        // view menempelkan ' UTC' pada nilai last_ping; kalau zona waktu MySQL
        // bukan UTC, selisihnya jadi negatif dan status SELALU "Offline".
        $query = $this->db->query(
            "SELECT last_ping, mesin1_status, mesin2_status,
                    TIMESTAMPDIFF(SECOND, last_ping, NOW()) AS umur_detik
               FROM server_monitoring WHERE id = 1"
        );
        $data_server = $query ? $query->fetch(PDO::FETCH_ASSOC) : false;

        $data['last_ping']     = $data_server['last_ping']     ?? null;
        $data['mesin1_status'] = $data_server['mesin1_status'] ?? 'Offline';
        $data['mesin2_status'] = $data_server['mesin2_status'] ?? 'Offline';
        $data['umur_ping']     = isset($data_server['umur_detik']) ? (int) $data_server['umur_detik'] : null;
        
        // --- LOGIKA RIWAYAT BACKUP ---
        $backupDir = SIBER_ROOT . '/backups/';
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

        $key = isset($data['key']) && is_string($data['key']) ? $data['key'] : '';
        if ($key !== '' && preg_match('/^wa_[a-z0-9_]{1,40}$/', $key) && isset($data['status'])) {
            $model = new Setting($this->db);
            $status_str = ($data['status'] === true) ? 'true' : 'false';

            $result = $model->updateByKey($key, $status_str);

            echo json_encode([
                'success' => $result,
                'db_value' => $status_str,
                'key' => $key
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
        }
        exit;
    }

    public function updateFolderIdConfig()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $folder_id = isset($_POST['folder_id']) && is_string($_POST['folder_id']) ? trim($_POST['folder_id']) : '';

            // Hanya ID folder Google Drive yang aman (huruf, angka, -, _).
            if (!preg_match('/^[A-Za-z0-9_\-]{1,120}$/', $folder_id)) {
                setFlash('error', 'ID Folder tidak valid. Gunakan huruf, angka, tanda hubung, atau garis bawah.');
                header('Location: ?controller=setting&method=index');
                exit;
            }

            // Simpan ke config/secrets.php (di luar webroot, gitignored) bila ada.
            $secretsFile = dirname(__DIR__) . '/config/secrets.php';
            $configFile = is_file($secretsFile) ? $secretsFile : __DIR__ . '/../config.php';
            if (file_exists($configFile) && is_writable($configFile)) {
                $configContent = file_get_contents($configFile);

                // Jika konstanta GOOGLE_DRIVE_FOLDER_ID sudah ada, replace nilainya
                if (strpos($configContent, "define('GOOGLE_DRIVE_FOLDER_ID'") !== false) {
                    $configContent = preg_replace(
                        "/define\('GOOGLE_DRIVE_FOLDER_ID',\s*'.*?'\);/",
                        "define('GOOGLE_DRIVE_FOLDER_ID', '" . $folder_id . "');",
                        $configContent
                    );
                } else {
                    // Jika belum ada, tambahkan di baris baru setelah GOOGLE_DRIVE_SECRET_KEY
                    $configContent = preg_replace(
                        "/(define\('GOOGLE_DRIVE_SECRET_KEY',\s*'.*?'\);)/",
                        "$1\ndefine('GOOGLE_DRIVE_FOLDER_ID', '" . $folder_id . "');",
                        $configContent
                    );
                }

                file_put_contents($configFile, $configContent);

                setFlash('success', 'ID Folder Google Drive berhasil disimpan!');
                header('Location: ?controller=setting&method=index');
                exit;
            }

            setFlash('error', 'Gagal! File config tidak memiliki izin tulis (writeable).');
            header('Location: ?controller=setting&method=index');
            exit;
        } else {
            header('Location: ?controller=setting&method=index');
        }
        exit;
    }
}
