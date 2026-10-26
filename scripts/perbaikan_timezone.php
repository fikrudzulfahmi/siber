<?php
/**
 * Perbaikan zona waktu data historis (UTC server → WIB / GMT+7).
 *
 * Aplikasi kini memaksa PHP & MySQL ke +07:00 (lihat config/database.php),
 * sehingga data BARU otomatis WIB. Data yang tersimpan sebelum perbaikan
 * masih memakai jam UTC, jadi tampil mundur 7 jam di dashboard.
 *
 * Pemakaian:
 *   php scripts/perbaikan_timezone.php                 # hanya memeriksa
 *   php scripts/perbaikan_timezone.php --apply         # geser +7 jam
 *   php scripts/perbaikan_timezone.php --apply --tabel=jurnal.created_at
 *
 * Idempoten: setiap tabel.kolom yang sudah digeser dicatat di tabel
 * `siber_migrasi`, sehingga skrip aman dijalankan berulang.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Skrip ini hanya untuk CLI.\n");
}

require_once __DIR__ . '/../config/database.php';

$apply = in_array('--apply', $argv, true);
$tabelArg = null;
foreach ($argv as $arg) {
    if (strpos($arg, '--tabel=') === 0) {
        $tabelArg = substr($arg, 8);
    }
}

$targets = [
    // Yang tampil di dashboard (jam isi jurnal & jam isi jurnal struktural).
    'jurnal.created_at',
    'jurnal_struktural.created_at',
];

if ($tabelArg) {
    $targets = array_values(array_filter(array_map('trim', explode(',', $tabelArg))));
}

$defaults = ['jurnal.created_at', 'jurnal_struktural.created_at'];

$database = new Database();
$db = $database->getConnection();

echo "=== ZONA WAKTU ===\n";
printf("PHP date.timezone (ini) : %s\n", ini_get('date.timezone') !== '' ? ini_get('date.timezone') : '(kosong)');
printf("PHP zona aktif          : %s\n", date_default_timezone_get());
printf("PHP sekarang            : %s\n", date('Y-m-d H:i:s'));
printf("MySQL system/global     : %s / %s\n", scalar($db, 'SELECT @@system_time_zone'), scalar($db, 'SELECT @@global.time_zone'));
printf("MySQL sesi              : %s\n", scalar($db, 'SELECT @@session.time_zone'));
printf("MySQL NOW() / UTC       : %s / %s\n", scalar($db, 'SELECT NOW()'), scalar($db, 'SELECT UTC_TIMESTAMP()'));
echo "\n";

echo "=== KOLOM WAKTU DI DATABASE INI ===\n";
$stmt = $db->query("
    SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND DATA_TYPE IN ('datetime', 'timestamp')
    ORDER BY TABLE_NAME, COLUMN_NAME
");
$kolom = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($kolom as $k) {
    $t = $k['TABLE_NAME'];
    $c = $k['COLUMN_NAME'];
    $ringkas = $db->query("SELECT COUNT({$c}) AS n, MIN({$c}) AS awal, MAX({$c}) AS akhir FROM `{$t}`")->fetch(PDO::FETCH_ASSOC);
    printf(
        "%-32s %-16s %-8s baris=%-6s %s .. %s\n",
        $t,
        $c,
        $k['DATA_TYPE'],
        (string) $ringkas['n'],
        (string) ($ringkas['awal'] ?? '-'),
        (string) ($ringkas['akhir'] ?? '-')
    );
}
echo "Catatan: attendance.scan_date berasal dari mesin finger dan sudah WIB — jangan digeser.\n";
echo "\n";

echo "=== RENCANA PERGESERAN (+7 JAM) ===\n";
foreach ($targets as $t) {
    [$table, $column] = array_pad(explode('.', $t, 2), 2, null);
    $ada = $table && $column && kolomAda($db, $table, $column);
    printf("%-32s %s\n", $t, $ada ? 'siap' : 'DILEWATI (kolom tidak ada)');
}
echo "\n";

if (!$apply) {
    echo "Mode periksa saja. Jalankan dengan --apply untuk menggeser +7 jam.\n";
    echo "Catatan: jalankan setelah backup database.\n";
    exit(0);
}

$db->exec("CREATE TABLE IF NOT EXISTS siber_migrasi (
    id_migrasi VARCHAR(100) NOT NULL PRIMARY KEY,
    dijalankan_pada DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$markerStmt = $db->prepare("SELECT COUNT(*) FROM siber_migrasi WHERE id_migrasi = ?");
$catatStmt = $db->prepare("INSERT INTO siber_migrasi (id_migrasi, dijalankan_pada) VALUES (?, NOW())");

$dikerjakan = 0;
foreach ($targets as $t) {
    [$table, $column] = array_pad(explode('.', $t, 2), 2, null);
    if (!$table || !$column || !kolomAda($db, $table, $column)) {
        echo "- {$t}: dilewati (kolom tidak ada)\n";
        continue;
    }

    $marker = 'tz_wib_' . $table . '_' . $column;
    $markerStmt->execute([$marker]);
    if ((int) $markerStmt->fetchColumn() > 0) {
        echo "- {$t}: sudah pernah digeser, dilewati\n";
        continue;
    }

    $before = $db->query("SELECT MIN({$column}) AS awal, MAX({$column}) AS akhir FROM `{$table}`")->fetch(PDO::FETCH_ASSOC);
    $db->exec("UPDATE `{$table}` SET `{$column}` = DATE_ADD(`{$column}`, INTERVAL 7 HOUR) WHERE `{$column}` IS NOT NULL");
    $after = $db->query("SELECT MIN({$column}) AS awal, MAX({$column}) AS akhir FROM `{$table}`")->fetch(PDO::FETCH_ASSOC);

    $catatStmt->execute([$marker]);
    $dikerjakan++;
    printf(
        "- %s: %s .. %s  ->  %s .. %s\n",
        $t,
        (string) ($before['awal'] ?? '-'),
        (string) ($before['akhir'] ?? '-'),
        (string) ($after['awal'] ?? '-'),
        (string) ($after['akhir'] ?? '-')
    );
}

echo $dikerjakan > 0 ? "\nSelesai. {$dikerjakan} kolom digeser +7 jam.\n" : "\nTidak ada yang digeser.\n";

function kolomAda(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function scalar(PDO $db, string $sql)
{
    return (string) $db->query($sql)->fetchColumn();
}
