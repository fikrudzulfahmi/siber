<?php
/**
 * Koneksi database.
 *
 * Kredensial diambil dari variabel lingkungan SIBER_DB_* bila ada, atau dari
 * config/secrets.php (di luar public/) yang tidak ikut Git. Tidak ada password
 * literal di dalam file ini.
 */

if (is_file(__DIR__ . '/secrets.php')) {
    require_once __DIR__ . '/secrets.php';
}

// Seluruh aplikasi memakai WIB (GMT+7). Server hosting berjalan di UTC, jadi
// tanpa baris ini date()/strtotime() menghasilkan waktu mundur 7 jam.
if (function_exists('date_default_timezone_set')) {
    date_default_timezone_set('Asia/Jakarta');
}

class Database
{
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        $this->host     = getenv('SIBER_DB_HOST') ?: (defined('SIBER_DB_HOST') ? SIBER_DB_HOST : 'localhost');
        $this->port     = getenv('SIBER_DB_PORT') ?: (defined('SIBER_DB_PORT') ? SIBER_DB_PORT : '');
        $this->db_name  = getenv('SIBER_DB_NAME') ?: (defined('SIBER_DB_NAME') ? SIBER_DB_NAME : '');
        $this->username = getenv('SIBER_DB_USER') ?: (defined('SIBER_DB_USERNAME') ? SIBER_DB_USERNAME : '');
        $this->password = getenv('SIBER_DB_PASS') !== false ? getenv('SIBER_DB_PASS') : (defined('SIBER_DB_PASSWORD') ? SIBER_DB_PASSWORD : '');
    }

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ($this->port !== '' ? ";port=" . $this->port : '') . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO(
                $dsn,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Samakan zona waktu MySQL dengan aplikasi supaya NOW(), CURDATE(),
            // dan DEFAULT CURRENT_TIMESTAMP menghasilkan WIB — bukan UTC server.
            try {
                $this->conn->exec("SET time_zone = '+07:00'");
            } catch (PDOException $e) {
                error_log('[SIBER db] gagal set time_zone: ' . $e->getMessage());
            }
        } catch (PDOException $e) {
            error_log('[SIBER db] koneksi gagal: ' . $e->getMessage());
            http_response_code(500);
            die('Koneksi database gagal. Hubungi administrator.');
        }

        return $this->conn;
    }
}

// BASEURL bisa tetap di sini jika diperlukan di tempat lain
if (!defined('BASEURL')) {
    define('BASEURL', 'https://siber.pondokminggirsari.com');
}
