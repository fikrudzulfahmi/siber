<?php

require_once 'BaseController.php';

class BackupController extends BaseController
{
    public function __construct($pdo)
    {
        parent::__construct($pdo);
        // Pastikan config sudah dimuat
        if (!defined('GOOGLE_DRIVE_WEBHOOK_URL')) {
            require_once __DIR__ . '/../config.php';
        }
    }

    /**
     * Helper: Menghasilkan string SQL dump dari database saat ini
     */
    private function exportDb()
    {
        $sqlScript = "";
        
        // Nonaktifkan foreign key checks saat restore
        $sqlScript .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        // Dapatkan semua tabel
        $tables = [];
        $stmt = $this->db->query("SHOW TABLES");
        while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        foreach ($tables as $table) {
            // Struktur Tabel
            $stmt = $this->db->query("SHOW CREATE TABLE `$table`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            
            $sqlScript .= "DROP TABLE IF EXISTS `$table`;\n";
            $sqlScript .= $row[1] . ";\n\n";

            // Data Tabel
            $stmt = $this->db->query("SELECT * FROM `$table`");
            $rowCount = $stmt->rowCount();
            
            if ($rowCount > 0) {
                $sqlScript .= "INSERT INTO `$table` VALUES ";
                $rows = [];
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $vals = [];
                    foreach ($row as $val) {
                        if (is_null($val)) {
                            $vals[] = "NULL";
                        } else {
                            $vals[] = $this->db->quote($val);
                        }
                    }
                    $rows[] = "(" . implode(", ", $vals) . ")";
                }
                $sqlScript .= implode(",\n", $rows) . ";\n\n";
            }
        }
        
        $sqlScript .= "SET FOREIGN_KEY_CHECKS = 1;\n";

        return $sqlScript;
    }

    /**
     * Download Backup secara manual
     */
    public function manualBackup()
    {
        $databaseName = "backup_siber_" . date("Ymd_His") . ".sql";
        $sqlScript = $this->exportDb();

        header('Content-Type: application/sql');
        header('Content-Disposition: attachment; filename="' . $databaseName . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        echo $sqlScript;
        exit;
    }

    /**
     * Trigger auto-backup (Bisa dipanggil via Cron Job)
     */
    public function autoBackup()
    {
        // Pengecekan token keamanan sederhana
        $token = $_GET['token'] ?? '';
        if ($token !== GOOGLE_DRIVE_SECRET_KEY) {
            die(json_encode(['status' => 'error', 'message' => 'Token tidak valid']));
        }

        $this->uploadToGoogleDrive(true);
    }

    /**
     * Upload hasil backup ke Google Drive via Webhook (Google Apps Script)
     */
    public function uploadToGoogleDrive($isCron = false)
    {
        $filename = "backup_siber_" . date("Ymd_His") . ".sql";
        $sqlScript = $this->exportDb();
        
        $base64Data = base64_encode($sqlScript);

        $webhookUrl = GOOGLE_DRIVE_WEBHOOK_URL;
        $secretKey = GOOGLE_DRIVE_SECRET_KEY;

        $postData = http_build_query([
            'secret' => $secretKey,
            'filename' => $filename,
            'file_base64' => $base64Data
        ]);

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($isCron) {
            header('Content-Type: application/json');
            echo $response;
            exit;
        } else {
            // Jika dipanggil dari UI manual
            $resData = json_decode($response, true);
            if ($resData && isset($resData['status']) && $resData['status'] === 'success') {
                echo "<script>alert('Backup berhasil diunggah ke Google Drive!'); window.location.href='?controller=setting&method=index';</script>";
            } else {
                $errorMsg = $resData['message'] ?? 'Unknown error';
                echo "<script>alert('Gagal mengunggah backup: $errorMsg'); window.location.href='?controller=setting&method=index';</script>";
            }
        }
    }

    /**
     * Restore database dari file .sql yang diunggah
     */
    public function restore()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['backup_file'])) {
            $file = $_FILES['backup_file'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo "<script>alert('Gagal mengunggah file.'); window.location.href='?controller=setting&method=index';</script>";
                exit;
            }

            // Validasi ekstensi
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) !== 'sql') {
                echo "<script>alert('File harus berformat .sql'); window.location.href='?controller=setting&method=index';</script>";
                exit;
            }

            $sqlContent = file_get_contents($file['tmp_name']);
            if (empty(trim($sqlContent))) {
                echo "<script>alert('File SQL kosong.'); window.location.href='?controller=setting&method=index';</script>";
                exit;
            }

            try {
                // Eksekusi skrip SQL
                $this->db->exec($sqlContent);
                echo "<script>alert('Database berhasil direstore!'); window.location.href='?controller=setting&method=index';</script>";
            } catch (PDOException $e) {
                $errorMsg = addslashes($e->getMessage());
                echo "<script>alert('Gagal restore database: $errorMsg'); window.location.href='?controller=setting&method=index';</script>";
            }
        } else {
            header('Location: ?controller=setting&method=index');
        }
        exit;
    }
}
