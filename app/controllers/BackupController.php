<?php

require_once 'BaseController.php';

class BackupController extends BaseController
{
    private $backupDir;

    public function __construct($pdo)
    {
        // Jika yang dipanggil adalah autoBackup (Cron), jangan cek login di parent
        $method = $_GET['method'] ?? '';
        if ($method === 'autoBackup') {
            $this->db = $pdo;
        } else {
            parent::__construct($pdo);
        }
        
        // Pastikan config sudah dimuat
        if (!defined('GOOGLE_DRIVE_WEBHOOK_URL')) {
            require_once __DIR__ . '/../config.php';
        }
        
        $this->backupDir = SIBER_ROOT . '/backups/';
        if (!is_dir($this->backupDir)) {
            @mkdir($this->backupDir, 0750, true);
        }
        if (!is_file($this->backupDir . '.htaccess')) {
            @file_put_contents($this->backupDir . '.htaccess', "Require all denied\nDeny from all\n");
        }
    }

    /** Cek berkas benar-benar berada di folder backup dan berakhiran .sql */
    private function resolveBackupPath(string $file): ?string
    {
        $name = basename($file);
        if ($name === '' || strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'sql') {
            return null;
        }
        $path = realpath($this->backupDir . $name);
        $base = realpath($this->backupDir);
        if ($path === false || $base === false || !str_starts_with($path, $base . DIRECTORY_SEPARATOR)) {
            return null;
        }
        return $path;
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
     * Membuat Backup secara manual:
     * Menyimpan di folder lokal & Upload ke Google Drive
     */
    public function createBackup()
    {
        sec_require_level(1, 'Hanya administrator yang dapat membuat backup.');
        $filename = "backup_siber_" . date("Ymd_His") . ".sql";
        $sqlScript = $this->exportDb();

        // 1. Simpan file secara lokal
        $filePath = $this->backupDir . $filename;
        file_put_contents($filePath, $sqlScript);

        // 2. Upload ke Google Drive
        $base64Data = base64_encode($sqlScript);
        $webhookUrl = GOOGLE_DRIVE_WEBHOOK_URL;
        $secretKey = GOOGLE_DRIVE_SECRET_KEY;
        
        // Ambil Folder ID langsung dari config.php untuk menghindari cache
        $folderId = '';
        $cfgPath = __DIR__ . '/../config.php';
        if (file_exists($cfgPath) && preg_match("/define\('GOOGLE_DRIVE_FOLDER_ID',\s*'(.*?)'\);/", file_get_contents($cfgPath), $m)) {
            $folderId = $m[1];
        }

        $postData = http_build_query([
            'secret' => $secretKey,
            'folder_id' => $folderId,
            'filename' => $filename,
            'file_base64' => $base64Data
        ]);

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // Timeout 60 detik
        
        $response = curl_exec($ch);
        curl_close($ch);

        $resData = json_decode($response, true);
        
        // 3. Redirect ke Halaman Setting
        if ($resData && isset($resData['status']) && $resData['status'] === 'success') {
            echo "<script>alert('Backup berhasil disimpan lokal dan diunggah ke Google Drive!'); window.location.href='?controller=setting&method=index';</script>";
        } else {
            $errorMsg = $resData['message'] ?? 'Unknown error';
            echo "<script>alert('Backup disimpan lokal, TETAPI gagal diunggah ke Google Drive: $errorMsg'); window.location.href='?controller=setting&method=index';</script>";
        }
        exit;
    }

    /**
     * Trigger auto-backup (Bisa dipanggil via Cron Job)
     */
    public function autoBackup()
    {
        // Token backup: pakai konstanta khusus bila tersedia.
        $expected = defined('BACKUP_CRON_TOKEN') ? (string) BACKUP_CRON_TOKEN : (string) GOOGLE_DRIVE_SECRET_KEY;
        $token = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';
        if ($expected === '' || !hash_equals($expected, $token)) {
            http_response_code(403);
            die(json_encode(['status' => 'error', 'message' => 'Token tidak valid']));
        }

        $filename = "backup_siber_auto_" . date("Ymd_His") . ".sql";
        $sqlScript = $this->exportDb();

        // 1. Simpan secara lokal
        $filePath = $this->backupDir . $filename;
        file_put_contents($filePath, $sqlScript);

        // 2. Upload ke Google Drive
        $base64Data = base64_encode($sqlScript);
        $webhookUrl = GOOGLE_DRIVE_WEBHOOK_URL;
        $secretKey = GOOGLE_DRIVE_SECRET_KEY;
        
        // Ambil Folder ID langsung dari config.php untuk menghindari cache
        $folderId = '';
        $cfgPath = __DIR__ . '/../config.php';
        if (file_exists($cfgPath) && preg_match("/define\('GOOGLE_DRIVE_FOLDER_ID',\s*'(.*?)'\);/", file_get_contents($cfgPath), $m)) {
            $folderId = $m[1];
        }

        $postData = http_build_query([
            'secret' => $secretKey,
            'folder_id' => $folderId,
            'filename' => $filename,
            'file_base64' => $base64Data
        ]);

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        
        $response = curl_exec($ch);
        curl_close($ch);

        header('Content-Type: application/json');
        echo $response;
        exit;
    }

    /**
     * Download Backup Lokal
     */
    public function downloadBackup()
    {
        sec_require_level(1, 'Hanya administrator yang dapat mengunduh backup.');
        $file = isset($_GET['file']) && is_string($_GET['file']) ? $_GET['file'] : '';
        $filePath = $this->resolveBackupPath($file);

        if ($filePath !== null) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/sql');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;
        } else {
            echo "<script>alert('File tidak ditemukan!'); window.location.href='?controller=setting&method=index';</script>";
            exit;
        }
    }

    /**
     * Hapus Backup Lokal
     */
    public function deleteBackup()
    {
        sec_require_level(1, 'Hanya administrator yang dapat menghapus backup.');
        $file = isset($_GET['file']) && is_string($_GET['file']) ? $_GET['file'] : '';
        $filePath = $this->resolveBackupPath($file);

        if ($filePath !== null) {
            unlink($filePath);
            echo "<script>alert('File backup berhasil dihapus.'); window.location.href='?controller=setting&method=index';</script>";
        } else {
            echo "<script>alert('File tidak ditemukan!'); window.location.href='?controller=setting&method=index';</script>";
        }
        exit;
    }

    /**
     * Restore database dari file .sql yang diunggah
     */
    public function restore()
    {
        sec_require_level(1, 'Hanya administrator yang dapat melakukan restore database.');
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
                error_log('[SIBER restore] ' . $e->getMessage());
                echo "<script>alert('Gagal restore database. Periksa format berkas lalu coba lagi.'); window.location.href='?controller=setting&method=index';</script>";
            }
        } else {
            header('Location: ?controller=setting&method=index');
        }
        exit;
    }
}
