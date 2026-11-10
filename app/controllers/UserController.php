<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/LevelHelper.php';
require_once 'BaseController.php';

class UserController extends BaseController
{
    private $model;

    public function __construct($pdo)
    {
        parent::__construct($pdo);
        $this->model = new User($this->db);
    }

    // =================== USER ===================
    public function index()
    {
        $users = $this->model->getAll();
        require __DIR__ . '/../views/admin/user/index.php';
    }

    public function create()
    {
        $jabatans  = $this->model->getAllJabatan();
        $allLevels = $this->db->query("SELECT * FROM level ORDER BY nama_level ASC")->fetchAll(PDO::FETCH_ASSOC);
        require __DIR__ . '/../views/admin/user/create.php';
    }

    public function store()
    {
        $nama     = trim((string) ($_POST['nama'] ?? ''));
        $pin      = trim((string) ($_POST['pin'] ?? ''));
        $jabatan  = trim((string) ($_POST['jabatan'] ?? ''));
        $idJab    = (int) ($_POST['id_jabatan'] ?? 0);
        $noWa     = trim((string) ($_POST['no_wa'] ?? ''));
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $levels   = is_array($_POST['levels'] ?? null) ? $_POST['levels'] : [];

        $kembali = 'Location: index.php?controller=user&method=create';
        $tolak = function (string $pesan) use ($kembali) {
            setFlash('danger', $pesan);
            header($kembali);
            exit;
        };

        if ($nama === '' || $username === '' || $password === '' || $pin === '' || $idJab <= 0) {
            $tolak('Nama, PIN, kategori jabatan, username, dan password wajib diisi.');
        }
        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            $tolak('Username 3–50 karakter, hanya huruf/angka/titik/garis bawah/strip.');
        }
        if (strlen($password) < 6) {
            $tolak('Password minimal 6 karakter.');
        }
        if (!preg_match('/^[0-9]{1,20}$/', $pin)) {
            $tolak('PIN harus angka (1–20 digit) — ini yang dipakai mesin fingerprint.');
        }
        if ($this->model->usernameExists($username)) {
            $tolak('Username "' . $username . '" sudah dipakai pegawai lain.');
        }
        if ($this->model->pinExists($pin)) {
            $tolak('PIN ' . $pin . ' sudah dipakai pegawai lain.');
        }

        // Kolom 'jabatan' (teks lama) diisi otomatis dari kategori bila kosong.
        if ($jabatan === '') {
            foreach ($this->model->getAllJabatan() as $j) {
                if ((int) $j['id_jabatan'] === $idJab) {
                    $jabatan = (string) $j['jabatan'];
                    break;
                }
            }
        }

        try {
            $idBaru = $this->model->insert([
                'nama'       => $nama,
                'pin'        => $pin,
                'jabatan'    => $jabatan,
                'id_jabatan' => $idJab,
                'no_wa'      => $noWa,
                'username'   => $username,
                'password'   => $password,
            ]);
            $this->model->setLevels($idBaru, $levels);
        } catch (Throwable $e) {
            error_log('[SIBER] tambah pegawai gagal: ' . $e->getMessage());
            $tolak('Gagal menyimpan pegawai. Periksa kembali data (username/PIN mungkin sudah dipakai).');
        }

        setFlash('success', 'Pegawai "' . $nama . '" berhasil ditambahkan.');
        header('Location: index.php?controller=user&method=index');
        exit;
    }

    public function edit() // <-- Hapus parameter $id dari sini
    {
        // Ambil ID dari URL menggunakan $_GET
        $id = $_GET['id'] ?? null;
        if (!$id) {
            die('Error: ID User tidak ditemukan di URL.');
        }

        // 1. Ambil data user, jadwal, dan jabatan (seperti kode Anda sebelumnya)
        $user = $this->model->find($id);
        $jadwal = $this->model->getJadwal($user['pin']);
        $jabatans = $this->model->getAllJabatan();

        // 2. Ambil SEMUA level yang tersedia di sistem
        $levelStmt = $this->db->query("SELECT * FROM level ORDER BY nama_level ASC");
        $allLevels = $levelStmt->fetchAll(PDO::FETCH_ASSOC);

        // 3. Ambil level yang saat ini DIMILIKI oleh user tersebut
        $userLevelStmt = $this->db->prepare("SELECT id_level FROM user_level WHERE id_employe = ?");
        $userLevelStmt->execute([$id]);
        $userOwnedLevels = $userLevelStmt->fetchAll(PDO::FETCH_COLUMN);

        // 4. Load view (menggunakan 'require' seperti cara Anda)
        require __DIR__ . '/../views/admin/user/edit.php';
    }

    public function update()
    {
        $id_employe = $_POST['id'];
        $selectedLevels = $_POST['levels'] ?? []; // Ambil level dari form, default array kosong

        // 1. Update data dasar di tabel 'employe'
        $this->model->update($_POST);

        // 2. Kelola level hanya bila pengguna berhak (pertahanan berlapis:
        //    mencegah eskalasi hak akses lewat parameter levels[]).
        $bolehKelolaLevel = !function_exists('sec_has_level') || sec_has_level(1);
        if ($bolehKelolaLevel) {
            $deleteStmt = $this->db->prepare("DELETE FROM user_level WHERE id_employe = ?");
            $deleteStmt->execute([$id_employe]);

            if (!empty($selectedLevels) && is_array($selectedLevels)) {
                $insertStmt = $this->db->prepare("INSERT INTO user_level (id_employe, id_level) VALUES (?, ?)");
                foreach ($selectedLevels as $id_level) {
                    if (ctype_digit((string) $id_level)) {
                        $insertStmt->execute([$id_employe, (int) $id_level]);
                    }
                }
            }
        }

        setFlash('success', 'User berhasil diperbarui.');
        header('Location: index.php?controller=user&method=edit&id=' . $id_employe);
        exit;
    }

    public function delete()
    {
        $kembali = 'Location: index.php?controller=user&method=index';

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            setFlash('danger', 'Penghapusan hanya bisa lewat tombol Hapus di daftar pegawai.');
            header($kembali);
            exit;
        }

        $id   = (int) ($_POST['id'] ?? 0);
        $saya = (int) ($_SESSION['user']['id'] ?? 0);

        if ($id <= 0) {
            setFlash('danger', 'Pegawai tidak ditemukan.');
            header($kembali);
            exit;
        }
        if ($id === $saya) {
            setFlash('danger', 'Tidak bisa menghapus akun yang sedang Anda pakai.');
            header($kembali);
            exit;
        }

        $target = $this->model->find($id);
        if (!$target) {
            setFlash('danger', 'Pegawai tidak ditemukan.');
            header($kembali);
            exit;
        }

        // Jangan sampai Administrator terakhir ikut terhapus (tidak ada yang bisa masuk lagi).
        $cekAdmin = $this->db->prepare("SELECT COUNT(*) FROM user_level WHERE id_employe = ? AND id_level = 1");
        $cekAdmin->execute([$id]);
        if ((int) $cekAdmin->fetchColumn() > 0 && $this->model->hitungLevel(1) <= 1) {
            setFlash('danger', 'Pegawai ini satu-satunya Administrator. Angkat administrator lain dulu sebelum menghapus.');
            header($kembali);
            exit;
        }

        try {
            $this->model->delete($id);
            setFlash('success', 'Pegawai "' . $target['nama'] . '" berhasil dihapus.');
        } catch (Throwable $e) {
            error_log('[SIBER] hapus pegawai gagal: ' . $e->getMessage());
            setFlash('danger', 'Gagal menghapus pegawai. Data mungkin masih dipakai di halaman lain.');
        }

        header($kembali);
        exit;
    }

    // =================== JADWAL ===================
    public function jadwal()
    {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            die('Error: ID User tidak ditemukan di URL.');
        }

        $user = $this->model->find($id);
        $jadwal = $this->model->getJadwal($user['pin']);

        require __DIR__ . '/../views/admin/user/jadwal.php';
    }

    public function addJadwal()
    {
        if ($this->model->addJadwal($_POST)) {
            setFlash('success', 'Jadwal berhasil ditambahkan.');
        } else {
            setFlash('danger', 'Gagal menambahkan jadwal.');
        }
        header('Location: index.php?controller=user&method=jadwal&id=' . $_POST['id_employe']);
        exit; // Selalu gunakan exit setelah header location
    }

    public function updateJadwal()
    {
        $this->model->updateJadwal($_POST);
        setFlash('success', 'Jadwal berhasil diperbarui.');
        header('Location: index.php?controller=user&method=jadwal&id=' . $_POST['id_employe']);
    }

    public function deleteJadwal()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->model->deleteJadwal($_POST['id']);
            setFlash('success', 'Jadwal berhasil dihapus.');
            header('Location: index.php?controller=user&method=jadwal&id=' . $_POST['id_user']);
        } else {
            // Jika bukan POST, redirect atau tampilkan error
            header('Location: index.php?controller=user&method=index');
        }
    }
}
