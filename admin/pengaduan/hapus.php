<?php

// ==================================================
// PENGADUAN — HAPUS LAPORAN
// --------------------------------------------------
// Menerima metode POST dari form pada daftar.
// Menghapus data laporan beserta foto bukti, lalu
// kembali ke daftar (tanpa menampilkan HTML).
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';

// Hanya boleh lewat POST
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php');
    exit;
}


// Ambil & validasi ID
$id_pengaduan = isset($_POST['id_pengaduan']) && is_numeric($_POST['id_pengaduan'])
    ? (int) $_POST['id_pengaduan']
    : 0;

if ($id_pengaduan <= 0) {
    header('Location: index.php?pesan=id_tidak_valid');
    exit;
}


// Pastikan laporan benar-benar ada
$stmt = sdb_prepare(
    $koneksi,
    "SELECT foto_bukti
     FROM pengaduan
     WHERE id_pengaduan = ?
     LIMIT 1"
);

sdb_stmt_bind_param($stmt, 'i', $id_pengaduan);
sdb_stmt_execute($stmt);

$data = sdb_fetch_assoc(sdb_stmt_get_result($stmt));

if (!$data) {
    header('Location: index.php?pesan=data_tidak_ditemukan');
    exit;
}


// Hapus dari database
$stmt_hapus = sdb_prepare(
    $koneksi,
    "DELETE FROM pengaduan WHERE id_pengaduan = ?"
);

sdb_stmt_bind_param($stmt_hapus, 'i', $id_pengaduan);

if (!sdb_stmt_execute($stmt_hapus)) {
    header('Location: index.php?pesan=gagal');
    exit;
}


// Hapus file foto bukti bila masih ada
$foto_bukti = $data['foto_bukti'] ?? '';

if ($foto_bukti !== '') {

    $path_foto = __DIR__ . '/../../uploads/pengaduan/' . basename($foto_bukti);

    if (file_exists($path_foto)) {
        unlink($path_foto);
    }
}


header('Location: index.php?pesan=hapus_berhasil');
exit;
