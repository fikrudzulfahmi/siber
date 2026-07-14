<?php
// File: api_wa.php (Di Server Hosting)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

// --- KONFIGURASI DATABASE HOSTING ---
$dbHost = 'localhost';
$dbName = 'u607305378_siber';
$dbUser = 'u607305378_siber';
$dbPass = 'root@P4ssw0rd';

try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['status' => false, 'message' => 'Koneksi database hosting gagal.']);
    exit;
}

// ==========================================
// 1. JIKA METODE POST: UPDATE STATUS DARI LOKAL (Konfirmasi)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputJSON = file_get_contents('php://input');
    $input = json_decode($inputJSON, TRUE);

    if (isset($input['action']) && $input['action'] == 'update_status') {
        $id = $input['id'];
        $status = $input['status']; // biasanya akan bernilai 'in_queue'

        // Update status agar tidak ditarik lagi pada polling berikutnya
        $stmt = $pdo->prepare("UPDATE outbox_wa SET status = ? WHERE id = ?");
        if ($stmt->execute([$status, $id])) {
            echo json_encode(['status' => true, 'message' => 'Status berhasil diupdate']);
        } else {
            echo json_encode(['status' => false, 'message' => 'Gagal mengupdate status']);
        }
        exit;
    }
}

// ==========================================
// 2. JIKA METODE GET: AMBIL PESAN PENDING (Polling)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Ambil maksimal 10 pesan sekali tarik agar Node.js dan jaringan tidak berat
    $stmt = $pdo->query("SELECT id, nomor, pesan FROM outbox_wa WHERE status = 'pending' LIMIT 10");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => true,
        'data' => $data
    ]);
    exit;
}
