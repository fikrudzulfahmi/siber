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
        if (isset($_SESSION['user'])) {
            header('Location: index.php?controller=dashboard&method=index');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = isset($_POST['username']) && is_string($_POST['username']) ? trim($_POST['username']) : '';
            $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

            $throttleKey = 'login:' . sec_client_ip() . ':' . strtolower($username);
            $wait = sec_throttle_wait($throttleKey, 5, 900);
            if ($wait > 0) {
                $menit = (int) ceil($wait / 60);
                header('Location: index.php?error=limit&m=' . $menit);
                exit;
            }

            if ($username === '' || $password === '') {
                sec_throttle_fail($throttleKey);
                header('Location: index.php?error=1');
                exit;
            }

            $model = new User($this->db);
            $user = $model->findByUsername($username);

            if ($user && password_verify($password, $user['password'])) {
                sec_throttle_reset($throttleKey);
                $levelStmt = $this->db->prepare("SELECT id_level FROM user_level WHERE id_employe = ?");
                $levelStmt->execute([$user['id_employe']]);
                $userLevels = $levelStmt->fetchAll(PDO::FETCH_COLUMN);

                if (empty($userLevels)) {
                    header('Location: index.php?error=level');
                    exit;
                }

                $tp = $model->getAktif();
                $stmt = $this->db->prepare("SELECT id_kelas FROM kelas WHERE wali_kelas = ?");
                $stmt->execute([$user['id_employe']]);
                $kelas = $stmt->fetch();

                // Cegah session fixation: ID sesi baru setelah hak akses berubah.
                sec_session_regenerate();

                $_SESSION['user'] = [
                    'id'       => $user['id_employe'],
                    'nama'     => $user['nama'],
                    'username' => $user['username'],
                    'level'    => base64_encode(implode(',', $userLevels)),
                    'id_tahun' => $tp['id_tahun_pelajaran'] ?? null,
                    'id_kelas' => $kelas['id_kelas'] ?? null,
                    'pin'      => $user['pin'],
                    'id_jabatan' => (int) ($user['id_jabatan'] ?? 0)
                ];

                if (in_array(8, array_map('intval', $userLevels), true)) {
                    header('Location: ' . BASEURL . '/index.php?controller=dashboard&method=index2');
                } else {
                    header('Location: ' . BASEURL . '/index.php?controller=dashboard&method=index');
                }
                exit;
            }

            sec_throttle_fail($throttleKey);
            header('Location: index.php?error=1');
            exit;
        }

        require __DIR__ . '/../views/login.php';
    }

    public function logout()
    {
        logoutUser();
    }
}
