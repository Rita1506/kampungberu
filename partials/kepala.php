<?php

// ==================================================
// kepala.php — PEMBUKA HALAMAN PUBLIK
// --------------------------------------------------
// Pasangkan dengan kaki.php.
//
// Variabel sebelum memanggil:
//   $judul       : judul halaman (tab browser)
//   $menu_aktif  : beranda | profil | pengaduan | cek-status
//   $root        : '' (halaman di root) atau '../' (folder auth)
// ==================================================

$judul      = $judul ?? 'Beranda';
$menu_aktif = $menu_aktif ?? '';

?>
<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= e($judul) ?> - SI PA'MASE-MASE</title>

    <!-- Ikon (berkas lokal, tampil tanpa internet) -->
    <link rel="stylesheet" href="<?= e($root) ?>assets/fonts/bootstrap-icons.css">

    <!-- Tema utama publik -->
    <link rel="stylesheet" href="<?= e($root) ?>assets/css/style.css">

    <!-- Lapisan pengganti Bootstrap + komponen tambahan -->
    <link rel="stylesheet" href="<?= e($root) ?>assets/css/publik.css">

</head>

<?php
// Kelas tambahan pada <body>, mis. "halaman-login".
$kelas_body = trim($kelas_body_tambahan ?? '');
?>

<body class="<?= e($kelas_body) ?>">
