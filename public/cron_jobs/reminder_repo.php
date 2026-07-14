<?php
if (!isset($_GET['key']) || $_GET['key'] !== 'SiberRM_svbndks987839432') {
    die('Unauthorized');
}
// File: reminder.php
// Versi LENGKAP dengan logika pengiriman pesan

// ------------------- KONFIGURASI -------------------
$dbHost = 'localhost';
$dbName = 'u607305378_siber';
$dbUser = 'u607305378_siber';
$dbPass = 'root@P4ssw0rd';
$fonnteToken = 'ZFUkS3d6pgPT1vRgfuLV';
// ---------------------------------------------------

header('Content-Type: text/plain');

// 1. KONEKSI KE DATABASE
try {
    $pdo = new PDO("mysql:host={$dbHost};dbname={$dbName}", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("ERROR: Koneksi database gagal: " . $e->getMessage());
}

// FUNGSI LOKAL UNTUK CEK HARI LIBUR
function isTanggalLibur($pdo_conn, $tanggal)
{
    $sql = "SELECT COUNT(*) FROM hari_libur WHERE ? BETWEEN tanggal_mulai AND tanggal_selesai";
    $stmt = $pdo_conn->prepare($sql);
    $stmt->execute([$tanggal]);
    return $stmt->fetchColumn() > 0;
}

// 2. LOGIKA PENGECEKAN HARI LIBUR
$today = date('Y-m-d');
$dayNameMapping = ['Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'];
$todayName = $dayNameMapping[date('l')];

if (strtolower($todayName) == 'jumat' || strtolower($todayName) == 'minggu') {
    echo "Hari libur rutin ({$todayName}). Tidak ada pengingat yang dikirim.\n";
    exit;
}

if (isTanggalLibur($pdo, $today)) {
    echo "Hari libur berdasarkan database. Tidak ada pengingat yang dikirim.\n";
    exit;
}

// 3. JIKA BUKAN HARI LIBUR, LANJUTKAN PROSES...
echo "Hari ini adalah hari aktif. Memeriksa jurnal guru...\n\n";

$sql = "
    SELECT e.id_employe, e.nama, e.no_wa, m.nama_mapel, jp.jam_mulai, jp.jam_selesai
    FROM jadwal_pelajaran jp
    LEFT JOIN mapel_guru mg ON jp.id_mapel_guru = mg.id_mapel_guru
    LEFT JOIN employe e ON mg.id_guru = e.id_employe
    LEFT JOIN mapel m ON mg.id_mapel = m.id_mapel
    LEFT JOIN jurnal j ON j.id_mapel_guru = mg.id_mapel_guru AND DATE(j.created_at) = :today
    WHERE
        LOWER(jp.hari) = LOWER(:todayName)
        AND j.id_jurnal IS NULL
        AND e.no_wa IS NOT NULL AND e.no_wa != ''
    ORDER BY e.nama, jp.jam_mulai
";

$stmt = $pdo->prepare($sql);
$stmt->execute([':today' => $today, ':todayName' => $todayName]);
$jadwalBelumDiisi = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($jadwalBelumDiisi)) {
    echo "Luar biasa! Semua guru sudah mengisi jurnal untuk hari ini.\n";
    exit;
}

// ✅ ===================================================================
// ✅ ============== BAGIAN YANG DILENGKAPI DIMULAI DARI SINI ==============
// ✅ ===================================================================

// 4. KELOMPOKKAN JADWAL BERDASARKAN GURU
// Agar satu guru yang lalai di banyak jam pelajaran hanya menerima satu pesan.
$guruUntukDiingatkan = [];
foreach ($jadwalBelumDiisi as $jadwal) {
    $guruUntukDiingatkan[$jadwal['id_employe']]['nama'] = $jadwal['nama'];
    $guruUntukDiingatkan[$jadwal['id_employe']]['no_wa'] = $jadwal['no_wa'];
    // Kumpulkan semua jadwal yang terlewat dalam satu array
    $guruUntukDiingatkan[$jadwal['id_employe']]['jadwal_terlewat'][] = "- {$jadwal['nama_mapel']} (Jam ke-{$jadwal['jam_mulai']}-{$jadwal['jam_selesai']})";
}

// 5. KIRIM PESAN KE SETIAP GURU YANG SUDAH DIKELOMPOKKAN
echo "Memulai proses pengiriman pengingat melalui Fonnte...\n\n";

foreach ($guruUntukDiingatkan as $id => $guru) {
    $nomorTujuan = $guru['no_wa'];
    $namaGuru = $guru['nama'];
    // Gabungkan daftar jadwal yang terlewat menjadi satu string dengan pemisah baris baru
    $daftarJadwal = implode("\n", $guru['jadwal_terlewat']);

    // Susun pesan yang akan dikirim
    $pesan = "*Pengingat Otomatis* ⏰\n\n" .
        "Yth. Bapak/Ibu {$namaGuru},\n\n" .
        "Anda terpantau belum mengisi jurnal pembelajaran untuk jadwal berikut pada hari ini, " . date('d F Y') . ":\n" .
        "{$daftarJadwal}\n\n" .
        "Mohon untuk segera melengkapinya pada laman https://siber.pondokminggirsari.com .\n\n" .
        "Terima kasih,\n" .
        "*SIBER PPRM*";

    // Kirim permintaan ke API Fonnte menggunakan cURL
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://api.fonnte.com/send',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => [
            'target' => $nomorTujuan,
            'message' => $pesan,
            'delay' => '2'
        ],
        CURLOPT_HTTPHEADER => array(
            "Authorization: {$fonnteToken}"
        ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);

    echo "-> Mengirim ke: {$namaGuru} ({$nomorTujuan})\n";
    echo "   Response API Fonnte: {$response}\n\n";

    sleep(1); // Jeda 1 detik agar tidak dianggap spam oleh WhatsApp
}

echo "Proses pengiriman selesai.\n";
