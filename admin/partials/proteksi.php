<?php

// ==================================================
// proteksi.php — GERBANG AWAL SEMUA HALAMAN ADMIN
// --------------------------------------------------
// Menggantikan baris yang dulu disalin di setiap file:
// require koneksi dan cek login. Login memakai cookie
// token (bukan session PHP) agar jalan di Vercel yang
// stateless; lihat config/auth_admin.php.
//
// CARA PAKAI di paling atas halaman:
//
//   // File di dalam subfolder (mis. admin/galeri/):
//   $root = '../../';
//   require_once __DIR__ . '/../partials/proteksi.php';
//
//   // File langsung di folder admin/ (mis. dashboard.php):
//   $root = '../';
//   require_once __DIR__ . '/partials/proteksi.php';
// ==================================================


// Variabel $root harus disiapkan sebelum file ini dipanggil.
if (!isset($root)) {
    $root = '../';
}


// 1) Koneksi database (sekaligus memuat helper auth token)
require_once __DIR__ . '/../../config/koneksi.php';


// 2) Verifikasi cookie token ke database; tendang ke login
//    bila tidak ada / tidak sah / sudah kedaluwarsa.
//    Tidak lagi memakai session PHP karena server Vercel
//    stateless (tidak ada file sesi yang bertahan).
auth_paksa_login($koneksi, $root);


// 4) Lokasi folder partial agar pemanggilan lebih ringkas
if (!defined('DIR_PARTIALS')) {
    define('DIR_PARTIALS', __DIR__);
}


// 5) Fungsi kecil bersama ------------------------------------------------

/**
 * Cetak teks dengan aman di dalam HTML (mencegah XSS).
 * Contoh: <h1><?= e($judul) ?></h1>
 */
if (!function_exists('e')) {
    function e($teks)
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Ambil nama admin yang sedang login dari sesi.
 */
if (!function_exists('admin_nama')) {
    function admin_nama()
    {
        return $_SESSION['nama_lengkap'] ?? 'Administrator';
    }
}


// 6) Cadangan bila ekstensi mbstring tidak aktif (jarang terjadi) ----
if (!function_exists('mb_strlen')) {
    function mb_strlen($teks, $encoding = null)
    {
        return strlen((string) $teks);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr($teks, $mulai, $panjang = null, $encoding = null)
    {
        return $panjang === null
            ? substr((string) $teks, $mulai)
            : substr((string) $teks, $mulai, $panjang);
    }
}
