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
     * Kirim isi backup ke Apps Script (Google Drive).
     *
     * Backup database produksi ~50 MB; setelah base64 jadi ~66 MB dan Google
     * membalas HTTP 400 (payload terlalu besar). Karena itu file besar dikirim
     * dalam bentuk gzip -- hasilnya beberapa MB saja. File lokal tetap .sql
     * supaya restore dari panel tidak berubah.
     *
     * @return array{ok:bool,http:int,errno:int,pesan:string,nama:string,gzip:bool}
     */
    private function uploadToDrive(string $payload, string $filename): array
    {
        $webhookUrl = defined('GOOGLE_DRIVE_WEBHOOK_URL') ? (string) GOOGLE_DRIVE_WEBHOOK_URL : '';
        $secretKey  = defined('GOOGLE_DRIVE_SECRET_KEY') ? (string) GOOGLE_DRIVE_SECRET_KEY : '';
        $folderId   = defined('GOOGLE_DRIVE_FOLDER_ID') ? (string) GOOGLE_DRIVE_FOLDER_ID : '';
        $kosong     = ['ok' => false, 'http' => 0, 'errno' => 0];

        if ($webhookUrl === '') {
            return $kosong + ['pesan' => 'GOOGLE_DRIVE_WEBHOOK_URL belum diisi di config/secrets.php', 'nama' => $filename, 'gzip' => false];
        }

        $nama = $filename;
        $gzip = false;
        // Di atas 4 MB, POST base64 melewati batas Apps Script (HTTP 400).
        if (strlen($payload) > 4 * 1024 * 1024 && function_exists('gzencode')) {
            $terkompres = gzencode($payload, 6);
            if ($terkompres !== false && strlen($terkompres) < strlen($payload)) {
                $payload = $terkompres;
                $nama   .= '.gz';
                $gzip    = true;
            }
        }

        $postData = http_build_query([
            'secret'      => $secretKey,
            'folder_id'   => $folderId,
            'filename'    => $nama,
            'file_base64' => base64_encode($payload),
        ]);

        $ch = curl_init($webhookUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $http     = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $galat    = curl_error($ch);
        curl_close($ch);

        if ($errno !== 0) {
            return $kosong + ['http' => $http, 'errno' => $errno, 'pesan' => 'curl error ' . $errno . ': ' . $galat, 'nama' => $nama, 'gzip' => $gzip];
        }
        if ($http < 200 || $http >= 300) {
            return $kosong + ['http' => $http, 'pesan' => 'Drive membalas HTTP ' . $http . '. ' . substr(trim(strip_tags((string) $response)), 0, 160), 'nama' => $nama, 'gzip' => $gzip];
        }

        $resData = json_decode((string) $response, true);
        $ok      = is_array($resData) && isset($resData['status']) && $resData['status'] === 'success';
        $pesan   = $ok
            ? 'terunggah sebagai ' . $nama
            : (string) ($resData['message'] ?? substr(trim((string) $response), 0, 160));

        return ['ok' => $ok, 'http' => $http, 'errno' => 0, 'pesan' => $pesan, 'nama' => $nama, 'gzip' => $gzip];
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
        $hasil = $this->uploadToDrive($sqlScript, $filename);

        // 3. Redirect ke Halaman Setting
        if ($hasil['ok']) {
            $info = $hasil['gzip'] ? ' (dikompres: ' . $hasil['nama'] . ')' : '';
            setFlash('success', 'Backup berhasil disimpan lokal dan diunggah ke Google Drive' . $info . '!');
            header('Location: ?controller=setting&method=index');
        } else {
            $errorMsg = (string) $hasil['pesan'];
            setFlash('error', 'Backup disimpan lokal, TETAPI gagal diunggah ke Google Drive: ' . $errorMsg);
            header('Location: ?controller=setting&method=index');
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
        $hasil = $this->uploadToDrive($sqlScript, $filename);

        if (!$hasil['ok']) {
            error_log('[BACKUP] upload Drive gagal (' . $hasil['pesan'] . '), file lokal sudah tersimpan: ' . $filePath);
            http_response_code(502);
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Upload ke Google Drive gagal, backup lokal sudah tersimpan.',
                'detail'  => $hasil['pesan'],
                'file'    => $filename,
            ]);
            exit;
        }

        header('Content-Type: application/json');
        echo json_encode([
            'status'  => 'success',
            'message' => 'Backup tersimpan lokal dan terunggah ke Google Drive',
            'file'    => $filename,
            'drive'   => $hasil['nama'],
            'gzip'    => $hasil['gzip'],
        ]);
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
            setFlash('error', 'File tidak ditemukan!');
            header('Location: ?controller=setting&method=index');
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
            setFlash('success', 'File backup berhasil dihapus.');
            header('Location: ?controller=setting&method=index');
        } else {
            setFlash('error', 'File tidak ditemukan!');
            header('Location: ?controller=setting&method=index');
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
                setFlash('error', 'Gagal mengunggah file.');
            header('Location: ?controller=setting&method=index');
                exit;
            }

            // Validasi ekstensi
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (strtolower($ext) !== 'sql') {
                setFlash('error', 'File harus berformat .sql');
            header('Location: ?controller=setting&method=index');
                exit;
            }

            $sqlContent = file_get_contents($file['tmp_name']);
            if (empty(trim($sqlContent))) {
                setFlash('error', 'File SQL kosong.');
            header('Location: ?controller=setting&method=index');
                exit;
            }

            try {
                // Eksekusi skrip SQL
                $this->db->exec($sqlContent);
                setFlash('success', 'Database berhasil direstore!');
            header('Location: ?controller=setting&method=index');
            } catch (PDOException $e) {
                error_log('[SIBER restore] ' . $e->getMessage());
                setFlash('error', 'Gagal restore database. Periksa format berkas lalu coba lagi.');
            header('Location: ?controller=setting&method=index');
            }
        } else {
            header('Location: ?controller=setting&method=index');
        }
        exit;
    }
}
