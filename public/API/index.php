<?php

// Konfigurasi database hosting
$host = 'localhost'; // atau gunakan IP hosting
$user = 'u607305378_siber'; // ganti dengan username database hosting
$password = 'root@P4ssw0rd'; // ganti dengan password database hosting
$db = 'u607305378_siber'; // nama database pada server hosting

$conn = new mysqli($host, $user, $password, $db);

// Periksa koneksi
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed'])); // Koneksi gagal
}

// Ambil data JSON dari request
$data = json_decode(file_get_contents("php://input"), true);

// 1. UPDATE LAST PING SERVER LOKAL SECARA UMUM
$conn->query("UPDATE server_monitoring SET last_ping = NOW() WHERE id = 1");

// 2. CEK APAKAH INI KIRIMAN HEARTBEAT YANG MEMBAWA STATUS MESIN
if (isset($data['tipe']) && $data['tipe'] === 'heartbeat') {
    $status_m1 = $data['mesin_1'];
    $status_m2 = $data['mesin_2'];

    // Update status mesin ke database
    $stmt = $conn->prepare("UPDATE server_monitoring SET mesin1_status = ?, mesin2_status = ? WHERE id = 1");
    $stmt->bind_param("ss", $status_m1, $status_m2);
    $stmt->execute();

    echo json_encode(['status' => 'success', 'message' => 'Heartbeat and machine status received']);
    exit(); // Hentikan script karena ini bukan data presensi
}

// Pastikan data diterima
if (!empty($data)) {
    // Loop untuk memproses setiap entri data
    foreach ($data as $entry) {
        $sn = $entry['sn'];
        $scan_date = $entry['scan_date'];
        $pin = $entry['pin'];
        $verify_mode = $entry['verify_mode'];
        $io_mode = $entry['io_mode'];

        // Cek jumlah scan untuk PIN ini pada tanggal yang sama
        $query_check = "SELECT COUNT(*) AS scan_count FROM attendance WHERE pin = '$pin' AND DATE(scan_date) = DATE('$scan_date')";
        $result_check = $conn->query($query_check);
        $row_check = $result_check->fetch_assoc();
        $scan_count = $row_check['scan_count'];

        // Tentukan status berdasarkan jumlah scan
        if ($scan_count == 0) {
            $status = 'datang';  // Scan pertama dianggap datang
        } else {
            $status = 'pulang';  // Scan kedua dan seterusnya dianggap pulang
        }

        // Persiapkan query untuk memasukkan data
        $query = "INSERT INTO attendance (sn, scan_date, pin, verify_mode, io_mode, status) 
                  VALUES (?, ?, ?, ?, ?, ?) 
                  ON DUPLICATE KEY UPDATE scan_date = ?, verify_mode = ?, io_mode = ?, status = ?";

        // Gunakan prepared statement untuk mencegah SQL injection
        if ($stmt = $conn->prepare($query)) {
            // Bind parameters untuk prepared statement
            $stmt->bind_param('ssssssssss', $sn, $scan_date, $pin, $verify_mode, $io_mode, $status, $scan_date, $verify_mode, $io_mode, $status);

            // Eksekusi query
            if (!$stmt->execute()) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to insert data']);
                $conn->close();
                exit();
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to prepare SQL statement']);
            $conn->close();
            exit();
        }
    }

    // Beri respons berhasil setelah semua data dimasukkan
    echo json_encode(['status' => 'success', 'message' => 'Data successfully saved']);

    // Tutup prepared statement
    $stmt->close();
} else {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
}

$conn->close();
