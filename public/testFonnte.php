<?php
// File: test_fonnte.php
// Skrip untuk menguji pengiriman satu pesan WhatsApp via Fonnte

// ------------------- KONFIGURASI UNTUK TES -------------------
// 1. Ganti dengan API Token yang Anda salin dari dasbor Fonnte
$fonnteToken = 'ZFUkS3d6pgPT1vRgfuLV';

// 2. Ganti dengan NOMOR WHATSAPP ANDA SENDIRI untuk menerima pesan tes
//    Gunakan format 62, contoh: 6281234567890
$nomorTujuan = '081216898874';
// -------------------------------------------------------------


// Siapkan pesan tes
$pesan = "Pengingat Otomatis ⏰\n\n" .
             "Yth. Bapak/Ibu *{$namaGuru}*,\n\n" .
             "Anda terpantau belum mengisi jurnal pembelajaran untuk jadwal berikut pada hari ini, " . date('d F Y') . ":\n" .
             "{$daftarJadwal}\n\n" .
             "Mohon untuk segera melengkapinya.\n\n" .
             "Terima kasih,\n" .
             "*SiBerkah PPRM*";
echo "<h1>Tes Pengiriman Pesan Fonnte</h1>";
echo "<hr>";
echo "<b>Token yang digunakan:</b> " . htmlspecialchars($fonnteToken) . "<br>";
echo "<b>Nomor Tujuan:</b> " . htmlspecialchars($nomorTujuan) . "<br>";
echo "<hr>";
echo "<i>Mencoba mengirim pesan...</i><br><br>";

// Proses pengiriman menggunakan cURL
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
        'message' => $pesan
    ],
    CURLOPT_HTTPHEADER => array(
        "Authorization: {$fonnteToken}"
    ),
));

$response = curl_exec($curl);

if (curl_errno($curl)) {
    $error_msg = curl_error($curl);
    echo "<b>Error cURL:</b> " . htmlspecialchars($error_msg);
}

curl_close($curl);

// Tampilkan hasil respons mentah dari Fonnte untuk debugging
echo "<b>Hasil Respons dari Fonnte:</b><br>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";
echo "<hr>";

// Analisis hasil
$responseData = json_decode($response, true);
if (isset($responseData['status']) && $responseData['status'] == true) {
    echo "<h2 style='color:green;'>✅ TES BERHASIL!</h2>";
    echo "Pesan berhasil dikirim ke antrian Fonnte. Silakan periksa WhatsApp Anda dalam beberapa detik.";
} else {
    echo "<h2 style='color:red;'>❌ TES GAGAL!</h2>";
    echo "Pesan gagal dikirim. Periksa pesan error di atas. Kemungkinan penyebabnya adalah Token salah atau status device di Fonnte 'Disconnected'.";
}
