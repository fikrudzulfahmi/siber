<?php
/**
 * Sidebar desktop + pembuka <main>.
 *
 * Struktur menu TIDAK lagi ditulis di sini: semua item diambil dari
 * layouts/menu.php supaya sidebar desktop dan bottom navigation mobile
 * selalu identik. Sidebar ini hanya dirender pada layar >= 992px
 * (di bawah itu digantikan bottom menu, lihat layouts/mobile_nav.php).
 */
require_once __DIR__ . '/menu.php';

$siber_menu_desktop = siber_menu_for_level();
?>
<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-0 border-radius-xl my-3 fixed-start ms-3 bg-gradient-dark siber-sidenav d-none d-xl-block"
    id="sidenav-main">
    <div class="sidenav-header">
        <a class="navbar-brand m-0" href="?controller=dashboard&method=index">
            <img src="assets/img/logo-ct2.png" class="navbar-brand-img" style="height: 50px; object-fit: contain;" alt="main_logo">
            <span class="ms-1 font-weight-bold text-white">SiBer PPRM</span>
        </a>
    </div>
    <hr class="horizontal light mt-0 mb-2">

    <div class="collapse navbar-collapse w-auto h-auto" id="sidenav-collapse-main">
        <ul class="navbar-nav accordion" id="accordionSidebar">
            <?php foreach ($siber_menu_desktop as $entry): ?>
                <?php if ($entry['type'] === 'item'): ?>
                    <?php $aktif = siber_nav_item_active($entry); ?>
                    <li class="sidebar-item">
                        <a class="nav-link text-white <?= $aktif ? 'active bg-gradient-success' : '' ?>"
                            href="<?= sec_e(siber_item_href($entry)) ?>">
                            <i class="material-icons opacity-10 me-2"><?= sec_e($entry['icon']) ?></i>
                            <?= sec_e(siber_item_label($entry)) ?>
                        </a>
                    </li>
                <?php else: ?>
                    <?php
                    $anakAdaAktif = false;
                    foreach ($entry['items'] as $anak) {
                        if (siber_nav_item_active($anak, $entry)) {
                            $anakAdaAktif = true;
                            break;
                        }
                    }
                    ?>
                    <li class="sidebar-item">
                        <a class="nav-link text-white <?= $anakAdaAktif ? 'active' : '' ?>"
                            data-bs-toggle="collapse" href="#<?= sec_e($entry['id']) ?>" role="button"
                            aria-expanded="<?= $anakAdaAktif ? 'true' : 'false' ?>"
                            aria-controls="<?= sec_e($entry['id']) ?>">
                            <i class="material-icons opacity-10 me-2"><?= sec_e($entry['icon']) ?></i>
                            <span class="nav-link-text ms-1"><?= sec_e(siber_item_label($entry)) ?></span>
                        </a>
                        <div class="collapse <?= $anakAdaAktif ? 'show' : '' ?>" id="<?= sec_e($entry['id']) ?>"
                            data-bs-parent="#accordionSidebar">
                            <ul class="nav flex-column ms-3 ps-1">
                                <?php foreach ($entry['items'] as $anak): ?>
                                    <?php $anakAktif = siber_nav_item_active($anak, $entry); ?>
                                    <li class="sidebar-item my-1">
                                        <a class="nav-link text-white <?= $anakAktif ? 'active bg-gradient-success' : '' ?>"
                                            href="<?= sec_e($anak['href']) ?>">
                                            <i class="material-icons me-2 opacity-10"><?= sec_e($anak['icon']) ?></i>
                                            <?= sec_e($anak['label']) ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </div>
</aside>

<!-- Main Content Start -->
<main class="main-content position-relative h-100 border-radius-lg" id="main-content">
