<?php

// ==================================================
// PROSES SIMPAN PENGADUAN DARI MASYARAKAT
// Hanya boleh diakses lewat method POST.
// ==================================================

require_once __DIR__ . '/../config/koneksi.php';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Location: ../pengaduan.php');
    exit;
}


// --------------------------------------------------
// Ambil data dari form
// --------------------------------------------------

$nama_lengkap    = trim($_POST['nama_lengkap']    ?? '');
$nik             = trim($_POST['nik']             ?? '');
$no_telepon      = trim($_POST['no_telepon']      ?? '');
$email           = trim($_POST['email']           ?? '');
$alamat          = trim($_POST['alamat']          ?? '');

$id_kategori     = isset($_POST['id_kategori']) ? (int) $_POST['id_kategori'] : 0;

$judul_pengaduan = trim($_POST['judul_pengaduan'] ?? '');
$isi_pengaduan   = trim($_POST['isi_pengaduan']   ?? '');
$alamat_lokasi   = trim($_POST['alamat_lokasi']   ?? '');

$persetujuan     = $_POST['persetujuan'] ?? '';


// --------------------------------------------------
// Validasi data wajib
// --------------------------------------------------

if (
    $nama_lengkap === '' ||
    $no_telepon === '' ||
    $alamat === '' ||
    $id_kategori <= 0 ||
    $judul_pengaduan === '' ||
    $isi_pengaduan === ''
) {
    header('Location: ../pengaduan.php?error=data_belum_lengkap');
    exit;
}

// Persetujuan wajib dicentang
if ($persetujuan !== '1') {
    header('Location: ../pengaduan.php?error=belum_menyetujui');
    exit;
}

// Email opsional, tapi jika diisi harus valid
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../pengaduan.php?error=email_tidak_valid');
    exit;
}


// --------------------------------------------------
// Validasi kategori (harus kategori yang aktif)
// --------------------------------------------------

$stmt_kategori = sdb_prepare(
    $koneksi,
    "SELECT id_kategori
     FROM kategori
     WHERE id_kategori = ?
       AND status = 'aktif'
     LIMIT 1"
);

sdb_stmt_bind_param($stmt_kategori, 'i', $id_kategori);
sdb_stmt_execute($stmt_kategori);
$res_kategori = sdb_stmt_get_result($stmt_kategori);

if (sdb_num_rows($res_kategori) === 0) {
    header('Location: ../pengaduan.php?error=kategori_tidak_valid');
    exit;
}


// --------------------------------------------------
// Cari masyarakat berdasarkan nomor HP.
// Sudah ada -> pakai ID yang ada (sekalian perbarui data).
// Belum ada -> buat masyarakat baru.
// --------------------------------------------------

$stmt_cari_m = sdb_prepare(
    $koneksi,
    "SELECT id_masyarakat
     FROM masyarakat
     WHERE no_telepon = ?
     LIMIT 1"
);

sdb_stmt_bind_param($stmt_cari_m, 's', $no_telepon);
sdb_stmt_execute($stmt_cari_m);
$res_cari_m = sdb_stmt_get_result($stmt_cari_m);

if (sdb_num_rows($res_cari_m) > 0) {

    // Masyarakat sudah ada
    $data_masyarakat = sdb_fetch_assoc($res_cari_m);
    $id_masyarakat   = (int) $data_masyarakat['id_masyarakat'];

    $stmt_upd_m = sdb_prepare(
        $koneksi,
        "UPDATE masyarakat SET
            nama_lengkap = ?,
            nik = ?,
            email = ?,
            alamat = ?
         WHERE id_masyarakat = ?"
    );

    sdb_stmt_bind_param(
        $stmt_upd_m,
        'ssssi',
        $nama_lengkap,
        $nik,
        $email,
        $alamat,
        $id_masyarakat
    );

    sdb_stmt_execute($stmt_upd_m);

} else {

    // Masyarakat baru
    $stmt_ins_m = sdb_prepare(
        $koneksi,
        "INSERT INTO masyarakat (
            nama_lengkap, nik, no_telepon, email, alamat
         ) VALUES (?, ?, ?, ?, ?)"
    );

    sdb_stmt_bind_param(
        $stmt_ins_m,
        'sssss',
        $nama_lengkap,
        $nik,
        $no_telepon,
        $email,
        $alamat
    );

    if (!sdb_stmt_execute($stmt_ins_m)) {
        die('Data masyarakat gagal disimpan: ' . sdb_error($koneksi));
    }

    $id_masyarakat = (int) sdb_insert_id($koneksi);
}


// --------------------------------------------------
// Buat nomor pengaduan: ADU-YYYYMMDD-0001
// --------------------------------------------------

$tanggal_sekarang = date('Ymd');
$awalan           = 'ADU-' . $tanggal_sekarang . '-';

$stmt_nomor = sdb_prepare(
    $koneksi,
    "SELECT nomor_pengaduan
     FROM pengaduan
     WHERE nomor_pengaduan LIKE ?
     ORDER BY id_pengaduan DESC
     LIMIT 1"
);

$pola_cari = $awalan . '%';
sdb_stmt_bind_param($stmt_nomor, 's', $pola_cari);
sdb_stmt_execute($stmt_nomor);
$res_nomor = sdb_stmt_get_result($stmt_nomor);

$urutan = 1;

if (sdb_num_rows($res_nomor) > 0) {

    $data_nomor    = sdb_fetch_assoc($res_nomor);
    $nomor_terakhir = $data_nomor['nomor_pengaduan'];

    // Ambil empat angka terakhir lalu tambah satu
    $urutan = (int) substr($nomor_terakhir, -4) + 1;
}

$nomor_pengaduan = $awalan . str_pad($urutan, 4, '0', STR_PAD_LEFT);


// --------------------------------------------------
// Upload foto bukti (opsional)
// --------------------------------------------------

$foto_bukti = '';

if (
    isset($_FILES['foto_bukti']) &&
    $_FILES['foto_bukti']['error'] !== UPLOAD_ERR_NO_FILE
) {

    if ($_FILES['foto_bukti']['error'] !== UPLOAD_ERR_OK) {
        die('Foto gagal diupload.');
    }

    $file_tmp  = $_FILES['foto_bukti']['tmp_name'];
    $file_size = $_FILES['foto_bukti']['size'];

    // Maksimal 2 MB
    if ($file_size > 2 * 1024 * 1024) {
        die('Ukuran foto maksimal 2 MB.');
    }

    // Cek tipe MIME sebenarnya
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file_tmp);
    finfo_close($finfo);

    $format_diizinkan = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    if (!isset($format_diizinkan[$mime])) {
        die('Format foto harus JPG, JPEG, PNG, atau WEBP.');
    }

    // Nama objek di Supabase Storage
    $nama_file = 'pengaduan_' . date('YmdHis') . '_' . uniqid()
               . '.' . $format_diizinkan[$mime];

    $objek_file = 'pengaduan/' . $nama_file;

    if (storage_upload_file($file_tmp, $objek_file, $mime)) {
        $foto_bukti = $nama_file;
    } else {
        die('Foto gagal diunggah ke penyimpanan. ' . storage_error());
    }
}


// --------------------------------------------------
// Simpan pengaduan
// --------------------------------------------------

$status = 'baru';

$stmt_pengaduan = sdb_prepare(
    $koneksi,
    "INSERT INTO pengaduan (
        nomor_pengaduan, id_masyarakat, id_kategori,
        judul_pengaduan, isi_pengaduan, alamat_lokasi,
        foto_bukti, status
     ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

sdb_stmt_bind_param(
    $stmt_pengaduan,
    'siisssss',
    $nomor_pengaduan,
    $id_masyarakat,
    $id_kategori,
    $judul_pengaduan,
    $isi_pengaduan,
    $alamat_lokasi,
    $foto_bukti,
    $status
);

$berhasil = sdb_stmt_execute($stmt_pengaduan);


// --------------------------------------------------
// Tindak lanjut hasil simpan
// --------------------------------------------------

if ($berhasil) {

    // Arahkan ke halaman cek status dengan membawa nomor pengaduan
    header(
        'Location: ../cek_status.php?nomor='
        . urlencode($nomor_pengaduan)
        . '&berhasil=1'
    );
    exit;

} else {

    // Database gagal: hapus kembali foto yang sempat terunggah
    if ($foto_bukti !== '') {
        storage_hapus('pengaduan/' . $foto_bukti);
    }

    die('Pengaduan gagal disimpan: ' . sdb_error($koneksi));
}
