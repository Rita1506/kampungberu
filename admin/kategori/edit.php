<?php

// ==================================================
// KATEGORI — EDIT KATEGORI
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// Ambil ID dari alamat (?id=...)
$id_kategori = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_kategori <= 0) {
    header('Location: index.php?pesan=id_tidak_valid');
    exit;
}


// Ambil data kategori
$stmt = sdb_prepare(
    $koneksi,
    "SELECT * FROM kategori
     WHERE id_kategori = ?
     LIMIT 1"
);

sdb_stmt_bind_param($stmt, 'i', $id_kategori);
sdb_stmt_execute($stmt);

$hasil = sdb_stmt_get_result($stmt);
$data  = sdb_fetch_assoc($hasil);

if (!$data) {
    header('Location: index.php?pesan=data_tidak_ditemukan');
    exit;
}


// Nilai awal formulir dari database
$nama_kategori = $data['nama_kategori'];
$deskripsi     = $data['deskripsi'];
$status        = $data['status'];
$ikon_lama     = $data['ikon'] ?? '';

$error = '';

// Kelompok ikon di dalam bucket Storage
$kelompok_ikon = 'kategori';


// --------------------------------------------------
// Proses ketika formulir dikirim
// --------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    $deskripsi     = trim($_POST['deskripsi'] ?? '');
    $status        = $_POST['status'] ?? 'aktif';

    $ikon_baru     = $ikon_lama;
    $ada_ikon_baru = false;
    $objek_ikon_baru = '';


    // 1. Validasi data teks -----------------------------------

    if ($nama_kategori === '') {

        $error = 'Nama kategori wajib diisi.';

    } elseif (!in_array($status, ['aktif', 'nonaktif'], true)) {

        $error = 'Status kategori tidak valid.';

    } else {

        // 2. Cek nama kembar (kecuali milik kategori ini sendiri)

        $stmt_cek = sdb_prepare(
            $koneksi,
            "SELECT id_kategori FROM kategori
             WHERE nama_kategori = ? AND id_kategori <> ?
             LIMIT 1"
        );

        sdb_stmt_bind_param($stmt_cek, 'si', $nama_kategori, $id_kategori);
        sdb_stmt_execute($stmt_cek);
        sdb_stmt_store_result($stmt_cek);

        if (sdb_stmt_num_rows($stmt_cek) > 0) {
            $error = 'Kategori dengan nama tersebut sudah ada.';
        }
    }


    // 3. Unggah ikon baru bila ada ----------------------------

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

                $ikon_baru = 'kategori_' . date('YmdHis') . '_' . uniqid() . '.' . $mime_diizinkan[$mime];
                $objek_ikon_baru = $kelompok_ikon . '/' . $ikon_baru;

                if (storage_upload_file($berkas['tmp_name'], $objek_ikon_baru, $mime)) {
                    $ada_ikon_baru = true;
                } else {
                    $error = 'Ikon gagal diunggah ke penyimpanan. ' . storage_error();
                }
            }
        }
    }


    // 4. Simpan perubahan -------------------------------------

    if ($error === '') {

        $stmt_update = sdb_prepare(
            $koneksi,
            "UPDATE kategori
             SET nama_kategori = ?, deskripsi = ?, ikon = ?, status = ?
             WHERE id_kategori = ?"
        );

        sdb_stmt_bind_param(
            $stmt_update,
            'ssssi',
            $nama_kategori,
            $deskripsi,
            $ikon_baru,
            $status,
            $id_kategori
        );

        if (sdb_stmt_execute($stmt_update)) {

            // Jika ikon diganti, hapus ikon lama
            if ($ada_ikon_baru && $ikon_lama !== '') {
                storage_hapus($kelompok_ikon . '/' . basename($ikon_lama));
            }

            header('Location: index.php?pesan=edit_berhasil');
            exit;

        } else {

            // Gagal update: buang ikon baru yang terlanjur diunggah
            if ($ada_ikon_baru) {
                storage_hapus($objek_ikon_baru);
            }

            $error = 'Kategori gagal diperbarui.';
        }
    }
}


// URL ikon lama dari Storage
$ikon_lama_url = storage_url_berkas($kelompok_ikon, $ikon_lama);
$ikon_lama_ada = ($ikon_lama_url !== '');


// Pengaturan tampilan
$judul_head = 'Edit Kategori';
$menu_aktif  = 'kategori';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-pencil-square"></i> Edit Kategori</h1>
        <p>Ubah data kategori pengaduan.</p>
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
                    placeholder="Contoh: Bidang Kesehatan"
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
                    placeholder="Masukkan deskripsi kategori..."><?= e($deskripsi) ?></textarea>

            </div>


            <!-- Ikon saat ini -->
            <div class="field span-2">

                <label>Ikon Saat Ini</label>

                <div class="foto-lama">
                    <?php if ($ikon_lama_ada): ?>
                        <img src="<?= e($ikon_lama_url) ?>" alt="<?= e($nama_kategori) ?>">
                    <?php else: ?>
                        <div class="hint">
                            <i class="bi bi-image"></i>
                            Belum ada ikon (akan memakai ikon bawaan).
                        </div>
                    <?php endif; ?>
                </div>

            </div>


            <!-- Ganti ikon -->
            <div class="field span-2">

                <label for="ikon">Ganti Ikon</label>

                <input
                    type="file"
                    id="ikon"
                    name="ikon"
                    class="input-file"
                    accept=".jpg,.jpeg,.png,.webp"
                    data-preview="#preview-ikon">

                <span class="note">
                    <i class="bi bi-info-circle"></i>
                    <span>Kosongkan jika tidak ingin mengganti ikon. JPG, PNG, atau WEBP, maksimal 2 MB.</span>
                </span>

                <div class="foto-preview foto-kecil">
                    <img id="preview-ikon" src="" alt="Pratinjau ikon baru">
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
                    Kategori nonaktif tidak dapat dipilih pada form pengaduan.
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
