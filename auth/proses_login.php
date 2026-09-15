<?php

// Hubungkan ke database (sekaligus memuat helper auth token)
require_once __DIR__ . "/../config/koneksi.php";

// Pastikan data dikirim melalui POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: login.php");
    exit;
}

// Ambil data dari form
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Validasi input
if ($username === '' || $password === '') {
    header("Location: login.php?error=" . urlencode("Username dan password wajib diisi."));
    exit;
}

// Cari admin berdasarkan username (memakai parameter, jadi
// tanda baca seperti ' atau ? pada username aman)
$stmt = sdb_prepare(
    $koneksi,
    "SELECT * FROM admin
     WHERE username = ?
     LIMIT 1"
);
sdb_stmt_bind_param($stmt, 's', $username);

if (!sdb_stmt_execute($stmt)) {
    header("Location: login.php?error=" . urlencode("Terjadi kesalahan pada database."));
    exit;
}

$query = sdb_stmt_get_result($stmt);

// Cek apakah username ditemukan
if (sdb_num_rows($query) === 0) {
    header("Location: login.php?error=" . urlencode("Username atau password salah."));
    exit;
}

// Ambil data admin
$admin = sdb_fetch_assoc($query);

// Periksa status admin
if ($admin['status'] !== 'aktif') {
    header("Location: login.php?error=" . urlencode("Akun admin sedang nonaktif."));
    exit;
}

// Verifikasi password (pemeriksaan password belum di-hash;
// hashing menyusul pada fase 8F)
if ($password !== $admin['password']) {
    header("Location: login.php?error=" . urlencode("Username atau password salah."));
    exit;
}

// Buat token sesi baru, simpan hash-nya di database,
// dan kirim token aslinya lewat cookie HttpOnly.
if (!auth_buat_sesi($koneksi, $admin)) {
    header("Location: login.php?error=" . urlencode("Gagal membuat sesi login. Coba lagi."));
    exit;
}

// Arahkan ke dashboard
header("Location: ../admin/dashboard.php");
exit;
