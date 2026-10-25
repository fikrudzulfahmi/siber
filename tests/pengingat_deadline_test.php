<?php
/**
 * Uji PengingatDeadline tanpa HTTP/login.
 * Jalankan: SIBER_DB_HOST=127.0.0.1 php tests/pengingat_deadline_test.php
 */
require_once __DIR__ . '/../app/helpers/LevelHelper.php';
require_once __DIR__ . '/../app/models/PengingatDeadline.php';

$host = getenv('SIBER_DB_HOST') ?: '127.0.0.1';
$port = getenv('SIBER_DB_PORT') ?: '3307';
$name = getenv('SIBER_DB_NAME') ?: 'siber_uji';
$user = getenv('SIBER_DB_USER') ?: 'root';
$pass = getenv('SIBER_DB_PASS') ?: '';

$db = new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$m = new PengingatDeadline($db);
$gagal = 0;

function cek($label, $harapan, $dapat)
{
    global $gagal;
    $ok = $harapan === $dapat;
    if (!$ok) $gagal++;
    printf("%s %-58s harap=%s dapat=%s\n", $ok ? 'OK  ' : 'GAGAL', $label, var_export($harapan, true), var_export($dapat, true));
}

// --- hak akses pengingat program struktural ---
cek('level 12 (tendik) session', true, $m->bolehProgramStruktural([12], 500));
cek('level 7 (kaponpes) session', true, $m->bolehProgramStruktural([7], 500));
cek('level 2 (guru) session', false, $m->bolehProgramStruktural([2], 503));
cek('level 1 (admin) session', false, $m->bolehProgramStruktural([1], 501));
cek('session kosong, jabatan employe 11', true, $m->bolehProgramStruktural([], 502));
cek('session kosong, jabatan employe 2 (guru)', false, $m->bolehProgramStruktural([], 503));
cek('id_employe null', false, $m->bolehProgramStruktural([], null));
cek('multi-level 3,12', true, $m->bolehProgramStruktural([3, 12], 504));

// --- daftar deadline program struktural tahun 3 ---
$rows = $m->programStruktural(500, 3);
cek('jumlah deadline program (tahun 3)', 2, count($rows));
cek('urutan tanggal naik', 'Laporan Evaluasi', $rows[0]['jenis_program'] ?? null);
cek('deadline lewat tanpa upload', 'lewat', $rows[0]['status'] ?? null);
cek('sudah upload -> selesai', 'selesai', $rows[1]['status'] ?? null);
cek('sisa hari null utk yg sudah selesai', null, $rows[1]['sisa_hari'] ?? null);
cek('tahun lain tidak ikut', false, (bool) array_filter($rows, fn($r) => $r['deadline'] === '2025-11-30'));

$kosong = $m->programStruktural(500, 99);
cek('tahun tanpa deadline -> array kosong', [], $kosong);

// --- perangkat pembelajaran ---
$p = $m->perangkat(510, 3);
cek('perangkat terisi utk guru ber-mapel', 3, count($p));
cek('perangkat tanpa mapel -> kosong', 0, count($m->perangkat(599, 3)));
cek('perangkat tanpa tahun -> kosong', [], $m->perangkat(510, null));
cek('perangkat status lewat', 'lewat', $p[0]['status'] ?? null);
cek('perangkat status aman (RPP, 16 hari)', 'aman', $p[1]['status'] ?? null);
cek('perangkat status aman (Modul Ajar)', 'aman', $p[2]['status'] ?? null);

echo $gagal === 0 ? "\nSEMUA CEK LULUS\n" : "\n$gagal CEK GAGAL\n";
exit($gagal === 0 ? 0 : 1);
