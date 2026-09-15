<?php

// ==================================================
// KATEGORI — HAPUS KATEGORI
// --------------------------------------------------
// Kategori yang masih dipakai pengaduan tidak boleh
// dihapus. File ini hanya memproses lalu kembali ke
// daftar (tanpa menampilkan HTML).
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// Ambil & validasi ID
$id_kategori = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_kategori <= 0) {
    header('Location: index.php?pesan=id_tidak_valid');
    exit;
}


// Pastikan kategorinya benar-benar ada
$stmt = sdb_prepare(
    $koneksi,
    "SELECT id_kategori
     FROM kategori
     WHERE id_kategori = ?
     LIMIT 1"
);

sdb_stmt_bind_param($stmt, 'i', $id_kategori);
sdb_stmt_execute($stmt);
sdb_stmt_store_result($stmt);

if (sdb_stmt_num_rows($stmt) === 0) {
    header('Location: index.php?pesan=data_tidak_ditemukan');
    exit;
}


// Hitung berapa pengaduan yang memakai kategori ini
$stmt_hitung = sdb_prepare(
    $koneksi,
    "SELECT COUNT(*) AS jumlah
     FROM pengaduan
     WHERE id_kategori = ?"
);

sdb_stmt_bind_param($stmt_hitung, 'i', $id_kategori);
sdb_stmt_execute($stmt_hitung);

$jumlah = sdb_fetch_assoc(sdb_stmt_get_result($stmt_hitung))['jumlah'] ?? 0;

if ((int) $jumlah > 0) {
    header('Location: index.php?pesan=kategori_digunakan');
    exit;
}


// Aman untuk dihapus
$stmt_hapus = sdb_prepare(
    $koneksi,
    "DELETE FROM kategori
     WHERE id_kategori = ?"
);

sdb_stmt_bind_param($stmt_hapus, 'i', $id_kategori);

if (sdb_stmt_execute($stmt_hapus)) {
    header('Location: index.php?pesan=hapus_berhasil');
} else {
    header('Location: index.php?pesan=hapus_gagal');
}

exit;
