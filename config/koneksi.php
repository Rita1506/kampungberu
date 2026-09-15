<?php
// =====================================================
// KONEKSI DATABASE SI PA'MASE-MASE
// Sistem Pengelolaan Pengaduan Masyarakat
// Desa Kampung Beru
// Kecamatan Polombangkeng Timur
// Kabupaten Takalar
// =====================================================
//
// Sejak migrasi Supabase, database diakses lewat REST API
// (tanpa MySQL/XAMPP). Lihat config/db_supabase.php.
//
// Kredensial diambil dari:
//   1) Environment Variable (dipakai saat deploy di Vercel):
//        SUPABASE_URL, SUPABASE_SERVICE_KEY
//   2) File config/rahasia.php (untuk jalan lokal di XAMPP/PHP),
//      salin dari config/rahasia.contoh.php.
// =====================================================

require_once __DIR__ . '/db_supabase.php';
require_once __DIR__ . '/storage_supabase.php';
require_once __DIR__ . '/auth_admin.php';

// Membuat koneksi ke database Supabase
$koneksi = sdb_connect();

// Mengecek apakah koneksi berhasil
if (!$koneksi) {
    die("Koneksi database gagal: " . sdb_connect_error());
}

// Tampilan tanggal memakai waktu WITA (Sulawesi Selatan)
date_default_timezone_set('Asia/Makassar');
