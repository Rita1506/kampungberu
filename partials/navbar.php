<?php

// ==================================================
// navbar.php — MENU ATAS SETIAP HALAMAN PUBLIK
// --------------------------------------------------
// Satu menu yang SAMA di semua halaman.
// Menu aktif disorot lewat variabel $menu_aktif.
// Toggle mobile ditangani assets/js/publik.js
// (tanpa Bootstrap JS).
// ==================================================

$logo_navbar = is_file(DIR_IMG . 'logotakalar.jpe')
    ? $root . 'assets/img/logotakalar.jpe'
    : '';

// Di luar beranda, tautan ke seksi halaman diawali index.php
$anchor = static function ($sek) use ($menu_aktif) {
    return ($menu_aktif === 'beranda' ? '' : 'index.php') . '#' . $sek;
};

// Menu yang tampil identik di setiap halaman
$daftar_menu = [
    ['key' => 'beranda',    'label' => 'Beranda',        'url' => 'index.php',                'ikon' => 'bi-house-door'],
    ['key' => 'profil',     'label' => 'Profil Desa',    'url' => 'tentang.php',              'ikon' => 'bi-info-circle'],
    ['key' => 'pemerintah', 'label' => 'Pemerintah Desa', 'url' => $anchor('pemerintah-desa'), 'ikon' => 'bi-people'],
    ['key' => 'kategori',   'label' => 'Kategori',       'url' => $anchor('kategori'),        'ikon' => 'bi-grid'],
    ['key' => 'pengaduan',  'label' => 'Buat Pengaduan', 'url' => 'pengaduan.php',            'ikon' => 'bi-megaphone'],
    ['key' => 'cek-status', 'label' => 'Cek Status',     'url' => 'cek_status.php',           'ikon' => 'bi-search'],
];

?>

<nav class="navbar sticky-top">

    <div class="container navbar-dalam">

        <!-- Logo -->
        <a class="navbar-brand" href="index.php">

            <?php if ($logo_navbar !== ''): ?>
                <img src="<?= e($logo_navbar) ?>" alt="Logo Kabupaten Takalar" class="logotakalar">
            <?php else: ?>
                <span class="logo-pengganti"><i class="bi bi-megaphone-fill"></i></span>
            <?php endif; ?>

            <span>
                <span class="brand-name">SI PA'MASE-MASE</span>
                <span class="brand-subtitle d-block">Desa Kampung Beru</span>
            </span>

        </a>


        <!-- Tombol menu mobile -->
        <button class="navbar-toggler" type="button"
            id="navbarToggler"
            aria-controls="navbarMenu"
            aria-expanded="false"
            aria-label="Buka menu navigasi">
            <span class="navbar-toggler-icon"><i class="bi bi-list"></i></span>
        </button>


        <!-- Daftar menu -->
        <div class="navbar-collapse" id="navbarMenu">

            <ul class="navbar-nav">

                <?php foreach ($daftar_menu as $menu): ?>

                    <li class="nav-item">
                        <a class="nav-link <?= $menu_aktif === $menu['key'] ? 'active' : '' ?>"
                           href="<?= e($menu['url']) ?>">
                            <i class="bi <?= e($menu['ikon']) ?> ikon-menu"></i>
                            <?= e($menu['label']) ?>
                        </a>
                    </li>

                <?php endforeach; ?>

                <li class="nav-item nav-login">
                    <a href="<?= e($root) ?>auth/login.php" class="btn btn-login">
                        <i class="bi bi-person-lock"></i> Login Admin
                    </a>
                </li>

            </ul>

        </div>

    </div>

</nav>
