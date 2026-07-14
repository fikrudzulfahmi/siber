<?php
// File: test_limit.php
// Skrip untuk menguji kapasitas pengiriman pesan server

// Atur batas waktu sesuai dengan pengaturan di cPanel Anda saat ini
// Ini PENTING agar pengujiannya akurat. Ubah jika perlu.
$max_execution_time = 60; // Ambil dari cPanel Anda (dalam detik)

// Atur jeda antar "pengiriman" pesan (sesuaikan dengan skrip asli Anda)
$jeda_per_pesan = 1; // Dalam detik

// ------------------------------------------------------------------
// Anda tidak perlu mengubah apapun di bawah ini
// ------------------------------------------------------------------

// Coba atasi batas waktu dari PHP, meskipun pengaturan server lebih kuat
set_time_limit($max_execution_time + 5); // Beri spare waktu 5 detik

// Tandai waktu mulai
$waktu_mulai = microtime(true);
$pesan_terkirim = 0;
$berhenti_karena_waktu = false;

echo "Memulai Uji Coba Server...\n";
echo "Batas Waktu Eksekusi: {$max_execution_time} detik\n";
echo "Jeda Antar Pesan: {$jeda_per_pesan} detik\n";
echo "----------------------------------------\n";

while (true) {
    // Cek apakah waktu eksekusi sudah mendekati limit
    $waktu_berjalan = microtime(true) - $waktu_mulai;
    if ($waktu_berjalan >= $max_execution_time) {
        $berhenti_karena_waktu = true;
        break;
    }

    // Simulasi pengiriman pesan
    $pesan_terkirim++;
    echo "Mengirim pesan ke-{$pesan_terkirim}... (Waktu berjalan: " . round($waktu_berjalan, 2) . " detik)\n";

    // Jeda
    sleep($jeda_per_pesan);
}

// Setelah loop selesai, tampilkan hasilnya
echo "----------------------------------------\n";
echo "UJI COBA SELESAI.\n\n";

if ($berhenti_karena_waktu) {
    echo "Hasil: Server dihentikan karena mencapai batas waktu.\n";
    echo "Kapasitas Maksimal Server Anda adalah sekitar: {$pesan_terkirim} pesan.\n";
} else {
    // Skenario ini seharusnya tidak terjadi jika loop-nya `while(true)`
    echo "Hasil: Proses selesai tanpa dihentikan oleh server.\n";
    echo "Total pesan yang disimulasikan: {$pesan_terkirim} pesan.\n";
}

$waktu_total = microtime(true) - $waktu_mulai;
echo "Total waktu eksekusi: " . round($waktu_total, 2) . " detik.\n";

?>