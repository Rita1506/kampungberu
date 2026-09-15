<?php

// ==================================================
// head.php — PEMBUKA SELURUH HALAMAN ADMIN
// --------------------------------------------------
// PASANGKAN DENGAN footer.php (penutupnya).
//
// Variabel yang disiapkan halaman sebelum memanggil:
//   $root        : '../../' (subfolder) atau '../' (folder admin)
//   $judul       : judul halaman (tampak di tab & topbar)
//   $menu_aktif  : penanda menu sidebar yang disorot
//
// Contoh pemakaian lengkap ada di halaman galeri.
//
// File ini membuka tag: <html>, <body>, <main>, <div class="page">
// yang nanti DITUTUP oleh footer.php.
// ==================================================

$judul      = $judul ?? 'Panel Admin';
$menu_aktif = $menu_aktif ?? '';

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($judul) ?> - SI PA'MASE-MASE</title>

    <!-- Ikon (berkas lokal, tetap tampil tanpa internet) -->
    <link rel="stylesheet" href="<?= $root ?>assets/fonts/bootstrap-icons.css">

    <!-- Satu design system untuk seluruh panel admin -->
    <link rel="stylesheet" href="<?= $root ?>assets/css/admin.css">

</head>

<body class="admin-app">

    <?php require __DIR__ . '/sidebar.php'; ?>

    <main class="app-main">

        <?php require __DIR__ . '/topbar.php'; ?>

        <div class="page">

            <?php require __DIR__ . '/flash.php'; ?>
