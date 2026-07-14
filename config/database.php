<?php

class Database
{
    private $host = 'localhost';
    private $db_name = 'u607305378_siber';
    private $username = 'u607305378_siber';
    private $password = 'root@P4ssw0rd';
    private $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("DB error: " . $e->getMessage());
        }

        return $this->conn;
    }
}

// BASEURL bisa tetap di sini jika diperlukan di tempat lain
define('BASEURL', 'https://siber.pondokminggirsari.com'); // Ganti dengan BASEURL Anda jika berbeda
