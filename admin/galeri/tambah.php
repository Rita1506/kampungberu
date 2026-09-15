<?php

// ==================================================
// GALERI — TAMBAH FOTO
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// Nilai awal formulir
$judul     = '';
$deskripsi = '';
$status    = 'aktif';
$error     = '';


// --------------------------------------------------
// Proses ketika formulir dikirim
// --------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    $judul     = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status    = $_POST['status'] ?? 'aktif';


    // 1. Validasi data teks -----------------------------------

    if ($judul === '') {

        $error = 'Judul foto wajib diisi.';

    } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {

        $error = 'Status tidak valid.';

    } elseif (!isset($_FILES['foto']) || ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {

        $error = 'Silakan pilih foto terlebih dahulu.';

    } elseif ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {

        $error = 'Terjadi kesalahan saat mengunggah foto.';

    } elseif ($_FILES['foto']['size'] > 2 * 1024 * 1024) {

        $error = 'Ukuran foto terlalu besar. Maksimal 2 MB.';

    } else {

        $foto = $_FILES['foto'];

        // 2. Validasi ekstensi --------------------------------

        $ekstensi = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));

        $ekstensi_diizinkan = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($ekstensi, $ekstensi_diizinkan, true)) {

            $error = 'Format foto tidak diperbolehkan. Gunakan JPG, JPEG, PNG, atau WEBP.';

        } else {

            // 3. Validasi jenis file asli (MIME) ---------------

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $foto['tmp_name']);
            finfo_close($finfo);

            $mime_diizinkan = ['image/jpeg', 'image/png', 'image/webp'];

            if (!in_array($mime, $mime_diizinkan, true)) {
                $error = 'File yang dipilih bukan gambar yang valid.';
            }
        }
    }


    // 4. Jika semua validasi lolos, unggah & simpan -----------

    if ($error === '') {

        // Nama objek di Storage dibuat unik agar tidak menimpa foto lain
        $nama_file  = 'galeri_' . date('YmdHis') . '_' . uniqid() . '.' . $ekstensi;
        $objek_file = 'galeri/' . $nama_file;

        if (!storage_upload_file($foto['tmp_name'], $objek_file, $mime)) {

            $error = 'Foto gagal diunggah ke penyimpanan. ' . storage_error();

        } else {

            // Simpan ke database (prepared statement anti SQL injection)
            $stmt = sdb_prepare(
                $koneksi,
                "INSERT INTO galeri (judul, deskripsi, foto, status)
                 VALUES (?, ?, ?, ?)"
            );

            sdb_stmt_bind_param($stmt, 'ssss', $judul, $deskripsi, $nama_file, $status);

            $berhasil = sdb_stmt_execute($stmt);

            if ($berhasil) {

                header('Location: index.php?pesan=tambah_berhasil');
                exit;

            } else {

                // Database gagal: batalkan upload agar tidak ada berkas yatim
                storage_hapus($objek_file);

                $error = 'Foto gagal disimpan ke database.';
            }
        }
    }
}


// Pengaturan tampilan
$judul_head = 'Tambah Foto';
$menu_aktif  = 'galeri';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-plus-circle"></i> Tambah Foto Galeri</h1>
        <p>Tambahkan foto kegiatan atau dokumentasi Desa Kampung Beru.</p>
    </div>

    <div class="head-actions">
        <a href="index.php" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
    </div>

</div>


<?php if ($error !== ''): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-octagon-fill"></i>
        <div><?= e($error) ?></div>
    </div>
<?php endif; ?>


<div class="form-card">

    <form method="POST" enctype="multipart/form-data">

        <div class="form-grid">

            <!-- Judul -->
            <div class="field span-2">

                <label for="judul">
                    Judul Foto <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="judul"
                    name="judul"
                    class="input"
                    maxlength="150"
                    placeholder="Contoh: Kegiatan Gotong Royong Desa"
                    value="<?= e($judul) ?>"
                    required>

            </div>


            <!-- Deskripsi -->
            <div class="field span-2">

                <label for="deskripsi">Deskripsi</label>

                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    class="textarea"
                    placeholder="Tambahkan keterangan mengenai foto..."><?= e($deskripsi) ?></textarea>

            </div>


            <!-- Status -->
            <div class="field">

                <label for="status">Status</label>

                <select id="status" name="status" class="select">
                    <option value="aktif"    <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="nonaktif" <?= $status === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>

                <span class="hint">
                    <i class="bi bi-info-circle"></i>
                    Foto aktif ditampilkan di halaman publik.
                </span>

            </div>

            <div class="field"></div>


            <!-- Foto -->
            <div class="field span-2">

                <label for="foto">
                    Foto <span class="required">*</span>
                </label>

                <input
                    type="file"
                    id="foto"
                    name="foto"
                    class="input-file"
                    accept=".jpg,.jpeg,.png,.webp"
                    data-preview="#preview-foto"
                    required>

                <span class="note">
                    <i class="bi bi-info-circle"></i>
                    <span>Format yang diperbolehkan: JPG, JPEG, PNG, WEBP. Ukuran maksimal 2 MB.</span>
                </span>

                <!-- Pratinjau (ditangani otomatis oleh assets/js/script.js) -->
                <div class="foto-preview">
                    <img id="preview-foto" src="" alt="Pratinjau foto">
                </div>

            </div>

        </div>


        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Foto
            </button>
            <a href="index.php" class="btn btn-light">Batal</a>
        </div>

    </form>

</div>


<?php require __DIR__ . '/../partials/footer.php'; ?>
