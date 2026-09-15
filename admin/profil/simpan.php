<?php

// ==================================================
// PROFIL DESA — PROSES SIMPAN DATA & FOTO
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// Hanya boleh diakses lewat form (POST)
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: index.php');
    exit;
}


// --------------------------------------------------
// Ambil data dari form
// --------------------------------------------------

$id_profil = (int) ($_POST['id_profil'] ?? 0);

$nama_desa  = trim($_POST['nama_desa']  ?? '');
$kecamatan  = trim($_POST['kecamatan']  ?? '');
$kabupaten  = trim($_POST['kabupaten']  ?? '');
$provinsi   = trim($_POST['provinsi']   ?? '');
$alamat     = trim($_POST['alamat']     ?? '');
$email      = trim($_POST['email']      ?? '');
$no_telepon = trim($_POST['no_telepon'] ?? '');
$kepala_desa = trim($_POST['kepala_desa'] ?? '');
$ibu_desa    = trim($_POST['ibu_desa']    ?? '');
$sejarah    = trim($_POST['sejarah']    ?? '');
$visi       = trim($_POST['visi']       ?? '');
$misi       = trim($_POST['misi']       ?? '');
$deskripsi  = trim($_POST['deskripsi']  ?? '');


// --------------------------------------------------
// Validasi data wajib
// --------------------------------------------------

if ($nama_desa === '' || $kecamatan === '' || $kabupaten === '' || $provinsi === '') {
    header('Location: index.php?pesan=profil_validasi');
    exit;
}


// --------------------------------------------------
// Fungsi upload foto petugas desa
//
// Mengembalikan:
//   ['uploaded' => false]                 bila tidak ada file dikirim
//   ['uploaded' => true, 'nama' => ...]   bila berhasil
//   null                                  bila ada kesalahan validasi/upload
// --------------------------------------------------

function upload_foto_petugas($field, $prefix, $kelompok)
{
    // Tidak ada file yang dipilih
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return ['uploaded' => false];
    }

    $file      = $_FILES[$field];
    $nama_asli = $file['name'];
    $tmp       = $file['tmp_name'];
    $ukuran    = $file['size'];

    // Error saat upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    // Maksimal 2 MB
    if ($ukuran > 2 * 1024 * 1024) {
        return null;
    }

    // Cek ekstensi
    $ekstensi = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

    if (!in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        return null;
    }

    // Cek tipe MIME sebenarnya
    $mime = mime_content_type($tmp);

    if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
        return null;
    }

    // Nama objek baru di Storage
    $nama_baru = $prefix . time() . '-' . uniqid() . '.' . $ekstensi;
    $objek     = $kelompok . '/' . $nama_baru;

    if (!storage_upload_file($tmp, $objek, $mime)) {
        return null;
    }

    return ['uploaded' => true, 'nama' => $nama_baru, 'objek' => $objek];
}


// --------------------------------------------------
// Ambil foto lama dari database (untuk dipertahankan/dihapus)
// --------------------------------------------------

$foto_kepala_lama = '';
$foto_ibu_lama    = '';

if ($id_profil > 0) {

    $stmt_lama = sdb_prepare(
        $koneksi,
        "SELECT foto_desa, foto_ibu_desa
         FROM profil_desa
         WHERE id_profil = ?
         LIMIT 1"
    );

    sdb_stmt_bind_param($stmt_lama, 'i', $id_profil);
    sdb_stmt_execute($stmt_lama);

    $data_lama = sdb_fetch_assoc(sdb_stmt_get_result($stmt_lama));

    if ($data_lama) {
        $foto_kepala_lama = $data_lama['foto_desa'] ?? '';
        $foto_ibu_lama    = $data_lama['foto_ibu_desa'] ?? '';
    }
}


// Nama foto yang akan tersimpan: default memakai foto lama
$nama_foto_kepala = $foto_kepala_lama;
$nama_foto_ibu    = $foto_ibu_lama;


// --------------------------------------------------
// Kelompok folder foto di Storage
// --------------------------------------------------

$kelompok_upload = 'profil';


// --------------------------------------------------
// Upload foto kepala desa
// --------------------------------------------------

$file_kepala_baru = '';

$hasil_kepala = upload_foto_petugas('foto_desa', 'kepala-desa-', $kelompok_upload);

if ($hasil_kepala === null) {
    header('Location: index.php?pesan=profil_gagal');
    exit;
}

if ($hasil_kepala['uploaded']) {
    $nama_foto_kepala   = $hasil_kepala['nama'];
    $file_kepala_baru   = $hasil_kepala['nama'];
}


// --------------------------------------------------
// Upload foto ibu desa
// --------------------------------------------------

$file_ibu_baru = '';

$hasil_ibu = upload_foto_petugas('foto_ibu_desa', 'ibu-desa-', $kelompok_upload);

if ($hasil_ibu === null) {

    // Batalkan foto kepala yang barusan terunggah
    if ($file_kepala_baru !== '') {
        storage_hapus($kelompok_upload . '/' . $file_kepala_baru);
    }

    header('Location: index.php?pesan=profil_gagal');
    exit;
}

if ($hasil_ibu['uploaded']) {
    $nama_foto_ibu = $hasil_ibu['nama'];
    $file_ibu_baru = $hasil_ibu['nama'];
}


// --------------------------------------------------
// Simpan ke database: UPDATE bila sudah ada, INSERT bila baru
// --------------------------------------------------

if ($id_profil > 0) {

    $stmt = sdb_prepare(
        $koneksi,
        "UPDATE profil_desa SET
            nama_desa = ?,
            kecamatan = ?,
            kabupaten = ?,
            provinsi = ?,
            alamat = ?,
            email = ?,
            no_telepon = ?,
            kepala_desa = ?,
            ibu_desa = ?,
            sejarah = ?,
            visi = ?,
            misi = ?,
            deskripsi = ?,
            foto_desa = ?,
            foto_ibu_desa = ?
         WHERE id_profil = ?"
    );

    sdb_stmt_bind_param(
        $stmt,
        'sssssssssssssssi',
        $nama_desa,
        $kecamatan,
        $kabupaten,
        $provinsi,
        $alamat,
        $email,
        $no_telepon,
        $kepala_desa,
        $ibu_desa,
        $sejarah,
        $visi,
        $misi,
        $deskripsi,
        $nama_foto_kepala,
        $nama_foto_ibu,
        $id_profil
    );

} else {

    $stmt = sdb_prepare(
        $koneksi,
        "INSERT INTO profil_desa (
            nama_desa, kecamatan, kabupaten, provinsi, alamat, email,
            no_telepon, kepala_desa, ibu_desa, sejarah, visi, misi,
            deskripsi, foto_desa, foto_ibu_desa
         ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    sdb_stmt_bind_param(
        $stmt,
        'sssssssssssssss',
        $nama_desa,
        $kecamatan,
        $kabupaten,
        $provinsi,
        $alamat,
        $email,
        $no_telepon,
        $kepala_desa,
        $ibu_desa,
        $sejarah,
        $visi,
        $misi,
        $deskripsi,
        $nama_foto_kepala,
        $nama_foto_ibu
    );
}

$berhasil = sdb_stmt_execute($stmt);


// --------------------------------------------------
// Tindakan setelah penyimpanan
// --------------------------------------------------

if ($berhasil) {

    // Hapus foto kepala desa lama bila diganti
    if ($file_kepala_baru !== '' && $foto_kepala_lama !== '') {
        storage_hapus($kelompok_upload . '/' . basename($foto_kepala_lama));
    }

    // Hapus foto ibu desa lama bila diganti
    if ($file_ibu_baru !== '' && $foto_ibu_lama !== '') {
        storage_hapus($kelompok_upload . '/' . basename($foto_ibu_lama));
    }

    header('Location: index.php?pesan=profil_berhasil');
    exit;

} else {

    // Database gagal: kembalikan foto baru yang sudah terlanjur diunggah
    foreach ([$file_kepala_baru, $file_ibu_baru] as $foto_baru) {

        if ($foto_baru !== '') {
            storage_hapus($kelompok_upload . '/' . $foto_baru);
        }
    }

    header('Location: index.php?pesan=profil_gagal');
    exit;
}
