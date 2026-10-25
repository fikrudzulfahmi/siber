<?php
/**
 * Navigasi mobile: bottom bar 5 slot + bottom sheet "Lainnya".
 *
 * Hanya tampil di layar < 992px (sidebar desktop disembunyikan).
 * Isi menu diambil dari layouts/menu.php, jadi tidak perlu dirawat terpisah.
 */
require_once __DIR__ . '/menu.php';

$siber_menu_mobile = siber_menu_for_level();
$siber_bottom      = siber_bottom_nav();

// Kalau halaman aktif tidak ada di bottom bar, sorot tombol "Lainnya".
$siber_ada_aktif = false;
foreach ($siber_bottom as $siber_item) {
    if (siber_nav_active($siber_item['controllers'], $siber_item['method'])) {
        $siber_ada_aktif = true;
        break;
    }
}

// Logout dipisah: tampil di kaki bottom sheet, bukan di daftar menu.
$siber_logout = null;
$siber_menu_mobile = array_values(array_filter($siber_menu_mobile, function ($entry) use (&$siber_logout) {
    if (($entry['type'] ?? '') === 'item' && ($entry['icon'] ?? '') === 'logout') {
        $siber_logout = $entry;
        return false;
    }
    return true;
}));
?>
<nav class="siber-bottomnav d-xl-none" aria-label="Navigasi utama">
    <?php foreach ($siber_bottom as $item): ?>
        <?php $aktif = siber_nav_active($item['controllers'], $item['method']); ?>
        <a class="siber-bottomnav__item <?= $aktif ? 'is-active' : '' ?>" href="<?= sec_e($item['href']) ?>"
            <?= $aktif ? 'aria-current="page"' : '' ?>>
            <i class="material-icons-round"><?= sec_e($item['icon']) ?></i>
            <span><?= sec_e($item['label']) ?></span>
        </a>
    <?php endforeach; ?>

    <button type="button" class="siber-bottomnav__item <?= $siber_ada_aktif ? '' : 'is-active' ?>" id="siber-sheet-toggle"
        aria-controls="siber-sheet" aria-expanded="false">
        <i class="material-icons-round">apps</i>
        <span>Lainnya</span>
    </button>
</nav>

<div class="siber-sheet-backdrop" id="siber-sheet-backdrop" hidden></div>

<div class="siber-sheet" id="siber-sheet" role="dialog" aria-modal="true" aria-labelledby="siber-sheet-title" hidden>
    <div class="siber-sheet__handle" aria-hidden="true"></div>
    <div class="siber-sheet__head">
        <div class="siber-sheet__brand">
            <img src="assets/img/logo-ct2.png" alt="">
            <div>
                <strong id="siber-sheet-title">Menu SiBer</strong>
                <small><?= sec_e($_SESSION['user']['nama'] ?? 'Pengguna') ?></small>
            </div>
        </div>
        <button type="button" class="siber-sheet__close" id="siber-sheet-close" aria-label="Tutup menu">
            <i class="material-icons-round">close</i>
        </button>
    </div>

    <div class="siber-sheet__body">
        <?php foreach ($siber_menu_mobile as $entry): ?>
            <?php if ($entry['type'] === 'item'): ?>
                <?php $aktif = siber_nav_active($entry['controllers'], $entry['method'] ?? 'index'); ?>
                <a class="siber-sheet__link <?= $aktif ? 'is-active' : '' ?>" href="<?= sec_e(siber_item_href($entry)) ?>">
                    <i class="material-icons-round"><?= sec_e($entry['icon']) ?></i>
                    <span><?= sec_e(siber_item_label($entry)) ?></span>
                </a>
            <?php else: ?>
                <?php $grupAktif = siber_nav_active($entry['controllers'], null); ?>
                <section class="siber-sheet__group <?= $grupAktif ? 'is-active' : '' ?>">
                    <h2 class="siber-sheet__group-title">
                        <i class="material-icons-round"><?= sec_e($entry['icon']) ?></i>
                        <?= sec_e(siber_item_label($entry)) ?>
                    </h2>
                    <div class="siber-sheet__grid">
                        <?php foreach ($entry['items'] as $anak): ?>
                            <?php $anakAktif = siber_nav_active($anak['controllers'], $anak['method'] ?? 'index'); ?>
                            <a class="siber-sheet__tile <?= $anakAktif ? 'is-active' : '' ?>"
                                href="<?= sec_e($anak['href']) ?>">
                                <i class="material-icons-round"><?= sec_e($anak['icon']) ?></i>
                                <span><?= sec_e($anak['label']) ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($siber_logout): ?>
        <div class="siber-sheet__foot">
            <a class="siber-sheet__logout" href="<?= sec_e($siber_logout['href']) ?>">
                <i class="material-icons-round">logout</i>
                Keluar
            </a>
        </div>
    <?php endif; ?>
</div>
