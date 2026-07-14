<?php
echo "<h1>Pemeriksaan Konfigurasi Server untuk PHP Session</h1>";

// 1. Definisikan path folder session kustom kita
$custom_session_path = dirname(__DIR__) . '/sessions';

echo "<p>Mencoba menggunakan folder session kustom di: <strong>{$custom_session_path}</strong></p>";

// 2. Cek apakah folder itu ada
if (is_dir($custom_session_path)) {
    echo "<p style='color:green;'>✅ Folder 'sessions' ditemukan.</p>";
} else {
    echo "<p style-='color:red;'>❌ GAGAL: Folder 'sessions' tidak ditemukan. Harap buat folder tersebut di dalam direktori 'siber.ingintau.my.id'.</p>";
    die();
}

// 3. Cek apakah folder itu bisa ditulis (writable)
if (is_writable($custom_session_path)) {
    echo "<p style='color:green;'>✅ Folder 'sessions' BISA DITULIS (writable).</p>";
} else {
    echo "<p style='color:red;'>❌ GAGAL: Folder 'sessions' TIDAK BISA DITULIS. Ini adalah sumber masalahnya.</p>";
    echo "<p><b>Solusi:</b> Buka cPanel File Manager, klik kanan pada folder 'sessions', pilih 'Change Permissions', dan atur nilainya menjadi <strong>755</strong> atau <strong>775</strong>.</p>";
    die();
}

// 4. Jika semua pemeriksaan lolos
echo "<hr>";
echo "<h2 style='color:blue;'>🎉 Hasil: Konfigurasi Anda Seharusnya Sudah Benar.</h2>";
echo "<p>Jika Anda melihat pesan ini, berarti masalah session Anda telah teratasi. Silakan coba login kembali.</p>";
echo "<p>Pastikan baris kode untuk mengatur session path sudah ada di paling atas file `index.php` Anda.</p>";
