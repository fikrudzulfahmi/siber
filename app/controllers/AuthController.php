<?php
require_once __DIR__ . '/../models/User.php';

class AuthController
{
    private $db;

    public function __construct($pdo)
    {

        $this->db = $pdo;
    }

    // Di dalam file: AuthController.php

    // Di dalam AuthController.php

    public function login()
    {
        log_message("AuthController: Method login() dipanggil."); // ✅ JEJAK 3
        if (isset($_SESSION['user'])) {
            header('Location: index.php?controller=dashboard&method=index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            log_message("AuthController: Formulir login dikirim (POST)."); // ✅ JEJAK 4
            $username = $_POST['username'];
            $password = $_POST['password'];
            $model = new User($this->db);
            $user = $model->findByUsername($username);

            if ($user && password_verify($password, $user['password'])) {
                log_message("AuthController: Username dan password BENAR untuk user: " . $username); // ✅ JEJAK 5
                $levelStmt = $this->db->prepare("SELECT id_level FROM user_level WHERE id_employe = ?");
                $levelStmt->execute([$user['id_employe']]);
                $userLevels = $levelStmt->fetchAll(PDO::FETCH_COLUMN);

                if (empty($userLevels)) {
                    echo "<script>alert('Login gagal: Akun Anda tidak memiliki level akses. Hubungi admin.');window.location.href='index.php';</script>";
                    exit;
                }

                $tp = $model->getAktif();
                $stmt = $this->db->prepare("SELECT id_kelas FROM kelas WHERE wali_kelas = ?");
                $stmt->execute([$user['id_employe']]);
                $kelas = $stmt->fetch();

                $_SESSION['user'] = [
                    'id'       => $user['id_employe'],
                    'nama'     => $user['nama'],
                    'username' => $user['username'],
                    'level'    => base64_encode(implode(',', $userLevels)), // ✅ DI-IMPLODE LALU DI-ENCODE
                    'id_tahun' => $tp['id_tahun_pelajaran'],
                    'id_kelas' => $kelas['id_kelas'] ?? null,
                    'pin'      => $user['pin']
                ];
                // ✅ Versi Final
                if (in_array(8, $userLevels)) {
                    header('Location: ' . BASEURL . '/index.php?controller=dashboard&method=index2');
                } else {
                    header('Location: ' . BASEURL . '/index.php?controller=dashboard&method=index');
                }
                exit;
            } else {
                log_message("AuthController: Username atau password SALAH."); // ✅ JEJAK 7
                echo "<script>alert('Username atau password salah');window.location.href='index.php';</script>";
                exit;
            }
        } else {
            require __DIR__ . '/../views/login.php';
        }
    }

    public function logout()
    {
        logoutUser();
    }
}
