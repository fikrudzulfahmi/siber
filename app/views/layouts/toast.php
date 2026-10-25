<?php
/**
 * Notifikasi global.
 *
 * header.php sudah "menyedot" seluruh flash session ke $GLOBALS['siber_flashes'],
 * jadi di sini cukup memetakan tipenya:
 *   - berhasil simpan/ubah/hapus  -> toast pojok kanan atas
 *   - gagal / validasi / error    -> SweetAlert (modal)
 * View tidak perlu lagi mencetak alert sendiri.
 */
$siber_flash = $GLOBALS['siber_flashes'] ?? [];

$petaTipe = [
    'success'    => 'success',
    'sukses'     => 'success',
    'berhasil'   => 'success',
    'error'      => 'error',
    'gagal'      => 'error',
    'danger'     => 'error',
    'no_student' => 'error',
    'warning'    => 'warning',
    'peringatan' => 'warning',
    'info'       => 'info',
];

$toast  = [];
$dialog = [];

foreach ($siber_flash as $kunci => $pesan) {
    $pesan = trim((string) $pesan);
    if ($pesan === '') {
        continue;
    }

    $tipe = $petaTipe[strtolower((string) $kunci)] ?? 'info';

    if ($tipe === 'error') {
        $dialog[] = ['tipe' => 'error', 'pesan' => $pesan];
    } else {
        $toast[] = ['tipe' => $tipe, 'pesan' => $pesan];
    }
}

$GLOBALS['siber_flashes'] = [];
?>
<script>
    window.__SIBER_FLASH = <?= json_encode(
        ['toast' => $toast, 'dialog' => $dialog],
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) ?>;
</script>
<script src="assets/js/siber-notify.js?v=1"></script>
