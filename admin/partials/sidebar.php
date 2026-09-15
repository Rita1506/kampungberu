<?php

// ==================================================
// sidebar.php — MENU SAMPING (satu-satunya salinan)
// --------------------------------------------------
// Menu aktif ditentukan dari variabel $menu_aktif
// yang disiapkan halaman, dengan nilai:
// dashboard | pengaduan | kategori | galeri |
// masyarakat | laporan | pengaturan | profil
// ==================================================

$daftar_menu = [

    'MENU UTAMA' => [
        ['key' => 'dashboard',  'label' => 'Dashboard',   'ikon' => 'bi-speedometer2',           'url' => 'admin/dashboard.php'],
        ['key' => 'pengaduan',  'label' => 'Pengaduan',   'ikon' => 'bi-megaphone',              'url' => 'admin/pengaduan/index.php'],
        ['key' => 'kategori',   'label' => 'Kategori',    'ikon' => 'bi-grid',                   'url' => 'admin/kategori/index.php'],
        ['key' => 'galeri',     'label' => 'Galeri',      'ikon' => 'bi-images',                 'url' => 'admin/galeri/index.php'],
        ['key' => 'masyarakat', 'label' => 'Masyarakat',  'ikon' => 'bi-people',                 'url' => 'admin/masyarakat/index.php'],
        ['key' => 'laporan',    'label' => 'Laporan',     'ikon' => 'bi-file-earmark-bar-graph', 'url' => 'admin/laporan/index.php'],
    ],

    'PENGATURAN' => [
        ['key' => 'pengaturan', 'label' => 'Pengaturan',  'ikon' => 'bi-gear',      'url' => 'admin/pengaturan.php'],
        ['key' => 'profil',     'label' => 'Profil Desa', 'ikon' => 'bi-building',  'url' => 'admin/profil/index.php'],
    ],
];

?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <span class="brand-icon"><i class="bi bi-megaphone-fill"></i></span>
        <span>
            <strong>SI PA'MASE-MASE</strong>
            <small>Panel Admin Desa</small>
        </span>
    </div>

    <nav class="sidebar-nav">

        <?php foreach ($daftar_menu as $judul_grup => $menu_grup): ?>

            <div class="sidebar-section"><?= $judul_grup ?></div>

            <?php foreach ($menu_grup as $menu): ?>

                <a
                    href="<?= $root . $menu['url'] ?>"
                    class="sidebar-link <?= ($menu_aktif ?? '') === $menu['key'] ? 'active' : '' ?>"
                >
                    <i class="bi <?= $menu['ikon'] ?>"></i>
                    <span><?= $menu['label'] ?></span>
                </a>

            <?php endforeach; ?>

        <?php endforeach; ?>

    </nav>

    <nav class="sidebar-nav" style="padding-top:0;">
        <a href="<?= $root ?>auth/logout.php" class="sidebar-link logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </nav>

</aside>

<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
