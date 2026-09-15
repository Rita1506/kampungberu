<?php

// ==================================================
// KATEGORI — TAMBAH KATEGORI
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// Nilai awal formulir
$nama_kategori = '';
$deskripsi     = '';
$status        = 'aktif';
$error         = '';

$kelompok_ikon = 'kategori';
$nama_ikon   = '';
$objek_ikon  = '';
$ada_upload  = false;


// --------------------------------------------------
// Proses ketika formulir dikirim
// --------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $deskripsi     = trim($_POST['deskripsi'] ?? '');
    $status        = $_POST['status'] ?? 'aktif';


    // 1. Validasi data teks -----------------------------------

    if ($nama_kategori === '') {

        $error = 'Nama kategori wajib diisi.';

    } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {

        $error = 'Status kategori tidak valid.';

    } else {

        // 2. Cek nama kategori yang sudah ada ------------------

        $stmt_cek = sdb_prepare(
            $koneksi,
            "SELECT id_kategori FROM kategori
             WHERE nama_kategori = ?
             LIMIT 1"
        );

        sdb_stmt_bind_param($stmt_cek, 's', $nama_kategori);
        sdb_stmt_execute($stmt_cek);
        sdb_stmt_store_result($stmt_cek);

        if (sdb_stmt_num_rows($stmt_cek) > 0) {

            $error = 'Kategori dengan nama tersebut sudah ada.';

        }
    }


    // 3. Validasi & unggah ikon (opsional) --------------------

    if ($error === '' && isset($_FILES['ikon']) && ($_FILES['ikon']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {

        $berkas = $_FILES['ikon'];

        if ($berkas['error'] !== UPLOAD_ERR_OK) {

            $error = 'Terjadi kesalahan saat mengunggah ikon.';

        } elseif ($berkas['size'] > 2 * 1024 * 1024) {

            $error = 'Ukuran ikon maksimal 2 MB.';

        } else {

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $berkas['tmp_name']);
            finfo_close($finfo);

            $mime_diizinkan = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];

            if (!isset($mime_diizinkan[$mime])) {

                $error = 'Format ikon harus JPG, PNG, atau WEBP.';

            } else {

                $nama_ikon  = 'kategori_' . date('YmdHis') . '_' . uniqid() . '.' . $mime_diizinkan[$mime];
                $objek_ikon = $kelompok_ikon . '/' . $nama_ikon;

                if (storage_upload_file($berkas['tmp_name'], $objek_ikon, $mime)) {
                    $ada_upload = true;
                } else {
                    $error = 'Ikon gagal diunggah ke penyimpanan. ' . storage_error();
                }
            }
        }
    }


    // 4. Simpan ke database -----------------------------------

    if ($error === '') {

        $stmt = sdb_prepare(
            $koneksi,
            "INSERT INTO kategori (nama_kategori, deskripsi, ikon, status)
             VALUES (?, ?, ?, ?)"
        );

        sdb_stmt_bind_param(
            $stmt,
            'ssss',
            $nama_kategori,
            $deskripsi,
            $nama_ikon,
            $status
        );

        if (sdb_stmt_execute($stmt)) {

            header('Location: index.php?pesan=tambah_berhasil');
            exit;

        } else {

            if ($ada_upload) {
                storage_hapus($objek_ikon);
            }

            $error = 'Data kategori gagal disimpan.';
        }
    }
}


// Pengaturan tampilan
$judul_head = 'Tambah Kategori';
$menu_aktif  = 'kategori';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-plus-circle"></i> Tambah Kategori</h1>
        <p>Tambahkan kategori baru untuk pengelompokan pengaduan.</p>
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

            <!-- Nama kategori -->
            <div class="field span-2">

                <label for="nama_kategori">
                    Nama Kategori <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="nama_kategori"
                    name="nama_kategori"
                    class="input"
                    maxlength="100"
                    placeholder="Contoh: Kesehatan"
                    value="<?= e($nama_kategori) ?>"
                    required>

            </div>


            <!-- Deskripsi -->
            <div class="field span-2">

                <label for="deskripsi">Deskripsi</label>

                <textarea
                    id="deskripsi"
                    name="deskripsi"
                    class="textarea"
                    placeholder="Masukkan deskripsi singkat mengenai kategori..."><?= e($deskripsi) ?></textarea>

            </div>


            <!-- Ikon -->
            <div class="field span-2">

                <label for="ikon">Ikon Kategori</label>

                <input
                    type="file"
                    id="ikon"
                    name="ikon"
                    class="input-file"
                    accept=".jpg,.jpeg,.png,.webp"
                    data-preview="#preview-ikon">

                <span class="note">
                    <i class="bi bi-info-circle"></i>
                    <span>Opsional. Format JPG, PNG, atau WEBP, maksimal 2 MB. Bila kosong, dipakai ikon bawaan.</span>
                </span>

                <div class="foto-preview foto-kecil">
                    <img id="preview-ikon" src="" alt="Pratinjau ikon">
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
                    Kategori aktif dapat dipilih saat masyarakat mengirim pengaduan.
                </span>

            </div>

        </div>


        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Kategori
            </button>
            <a href="index.php" class="btn btn-light">Batal</a>
        </div>

    </form>

</div>


<?php require __DIR__ . '/../partials/footer.php'; ?>
