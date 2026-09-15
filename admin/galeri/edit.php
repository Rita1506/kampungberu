<?php

// ==================================================
// GALERI — EDIT FOTO
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// --------------------------------------------------
// Ambil ID dari alamat (?id=...)
// --------------------------------------------------

$id_galeri = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_galeri <= 0) {
    header('Location: index.php?pesan=id_tidak_valid');
    exit;
}


// --------------------------------------------------
// Ambil data foto yang akan diedit
// --------------------------------------------------

$stmt = sdb_prepare(
    $koneksi,
    "SELECT * FROM galeri
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


// Nilai awal formulir diambil dari database
$judul     = $data['judul'];
$deskripsi = $data['deskripsi'];
$status    = $data['status'];
$foto_lama = $data['foto'];

$error = '';

// Kelompok folder di dalam bucket Storage
$kelompok_foto = 'galeri';


// --------------------------------------------------
// Proses ketika formulir dikirim
// --------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    $judul     = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $status    = $_POST['status'] ?? 'aktif';

    $nama_foto_baru = $foto_lama;
    $ada_foto_baru = false;
    $objek_foto_baru = '';
    $ekstensi = '';


    // 1. Validasi data teks -----------------------------------

    if ($judul === '') {

        $error = 'Judul foto wajib diisi.';

    } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {

        $error = 'Status yang dipilih tidak valid.';

    }


    // 2. Validasi foto baru (hanya jika pengguna memilih file) -

    if ($error === '' && isset($_FILES['foto']) && ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {

        $foto = $_FILES['foto'];

        if ($foto['error'] !== UPLOAD_ERR_OK) {

            $error = 'Foto gagal diunggah.';

        } elseif ($foto['size'] > 2 * 1024 * 1024) {

            $error = 'Ukuran foto maksimal 2 MB.';

        } else {

            $ekstensi = strtolower(pathinfo($foto['name'], PATHINFO_EXTENSION));

            if (!in_array($ekstensi, ['jpg', 'jpeg', 'png', 'webp'], true)) {

                $error = 'Format foto harus JPG, JPEG, PNG, atau WEBP.';

            } else {

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $foto['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    $error = 'File yang dipilih bukan gambar yang valid.';
                }
            }
        }


        // Unggah foto baru bila validasinya lolos --------------

        if ($error === '') {

            $nama_foto_baru  = 'galeri_' . date('YmdHis') . '_' . uniqid() . '.' . $ekstensi;
            $objek_foto_baru = $kelompok_foto . '/' . $nama_foto_baru;

            if (storage_upload_file($foto['tmp_name'], $objek_foto_baru, $mime)) {
                $ada_foto_baru = true;
            } else {
                $error = 'Foto gagal diunggah ke penyimpanan. ' . storage_error();
            }
        }
    }


    // 3. Simpan perubahan ke database --------------------------

    if ($error === '') {

        $stmt_update = sdb_prepare(
            $koneksi,
            "UPDATE galeri
             SET judul = ?, deskripsi = ?, foto = ?, status = ?
             WHERE id_galeri = ?"
        );

        sdb_stmt_bind_param(
            $stmt_update,
            'ssssi',
            $judul,
            $deskripsi,
            $nama_foto_baru,
            $status,
            $id_galeri
        );

        $berhasil = sdb_stmt_execute($stmt_update);

        if ($berhasil) {

            // Jika foto diganti, hapus foto lama dari Storage ----

            if ($ada_foto_baru && $foto_lama !== '') {
                storage_hapus($kelompok_foto . '/' . basename($foto_lama));
            }

            header('Location: index.php?pesan=edit_berhasil');
            exit;

        } else {

            // Database gagal: buang foto baru yang terlanjur diunggah
            if ($ada_foto_baru) {
                storage_hapus($objek_foto_baru);
            }

            $error = 'Data galeri gagal diperbarui.';
        }
    }
}


// URL foto lama dari Storage
$foto_lama_url = storage_url_berkas($kelompok_foto, $foto_lama);
$foto_lama_ada = ($foto_lama_url !== '');


// Pengaturan tampilan
$judul_head = 'Edit Galeri';
$menu_aktif  = 'galeri';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-pencil-square"></i> Edit Galeri</h1>
        <p>Ubah informasi foto galeri desa.</p>
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
                    placeholder="Masukkan deskripsi foto..."><?= e($deskripsi) ?></textarea>

            </div>


            <!-- Foto saat ini -->
            <div class="field span-2">

                <label>Foto Saat Ini</label>

                <div class="foto-lama">
                    <?php if ($foto_lama_ada): ?>
                        <img src="<?= e($foto_lama_url) ?>" alt="<?= e($judul) ?>">
                    <?php else: ?>
                        <div class="hint">
                            <i class="bi bi-image"></i>
                            Belum ada foto.
                        </div>
                    <?php endif; ?>
                </div>

            </div>


            <!-- Ganti foto -->
            <div class="field span-2">

                <label for="foto">Ganti Foto</label>

                <input
                    type="file"
                    id="foto"
                    name="foto"
                    class="input-file"
                    accept=".jpg,.jpeg,.png,.webp"
                    data-preview="#preview-baru">

                <span class="note">
                    <i class="bi bi-info-circle"></i>
                    <span>Kosongkan jika tidak ingin mengganti foto. Maksimal 2 MB, format JPG, JPEG, PNG, atau WEBP.</span>
                </span>

                <div class="foto-preview foto-kecil">
                    <img id="preview-baru" src="" alt="Pratinjau foto baru">
                </div>

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
                    Foto nonaktif tidak ditampilkan di galeri publik.
                </span>

            </div>

        </div>


        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Perubahan
            </button>
            <a href="index.php" class="btn btn-light">Batal</a>
        </div>

    </form>

</div>


<?php require __DIR__ . '/../partials/footer.php'; ?>
