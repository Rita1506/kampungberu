<?php

// ==================================================
// GALERI — HAPUS FOTO
// --------------------------------------------------
// File ini hanya memproses, lalu kembali ke daftar
// (pola POST-redirect-GET). Tidak menampilkan HTML.
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// Ambil & validasi ID dari alamat (?id=...)
$id_galeri = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_galeri <= 0) {
    header('Location: index.php?pesan=id_tidak_valid');
    exit;
}


// Ambil nama file fotonya dulu (untuk dihapus dari folder)
$stmt = sdb_prepare(
    $koneksi,
    "SELECT foto FROM galeri
     WHERE id_galeri = ?
     LIMIT 1"
);

sdb_stmt_bind_param($stmt, 'i', $id_galeri);
sdb_stmt_execute($stmt);

$hasil = sdb_stmt_get_result($stmt);
$data  = sdb_fetch_assoc($hasil);

if (!$data) {
    header('Location: index.php?pesan=data_tidak_ditemukan');
    exit;
}


// Hapus data dari database
$stmt_hapus = sdb_prepare(
    $koneksi,
    "DELETE FROM galeri WHERE id_galeri = ?"
);

sdb_stmt_bind_param($stmt_hapus, 'i', $id_galeri);
$berhasil = sdb_stmt_execute($stmt_hapus);


if ($berhasil) {

    // Hapus juga file fotonya bila masih ada
    $nama_foto = $data['foto'] ?? '';

    if ($nama_foto !== '') {
        storage_hapus('galeri/' . basename($nama_foto));
    }

    header('Location: index.php?pesan=hapus_berhasil');
    exit;

}

header('Location: index.php?pesan=hapus_gagal');
exit;
