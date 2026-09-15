<?php

// ==================================================
// PENGADUAN — UBAH STATUS & BERI TANGGAPAN
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';
require_once __DIR__ . '/../partials/pengaduan_helper.php';


// Ambil ID dari alamat
$id_pengaduan = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id_pengaduan <= 0) {
    header('Location: index.php?pesan=id_tidak_valid');
    exit;
}

$id_admin = (int) $_SESSION['id_admin'];


// Ambil data pengaduan
$stmt = sdb_prepare(
    $koneksi,
    "SELECT
        p.*,
        m.nama_lengkap,
        m.no_telepon,
        m.email,
        k.nama_kategori
     FROM pengaduan p
     INNER JOIN masyarakat m ON p.id_masyarakat = m.id_masyarakat
     INNER JOIN kategori  k ON p.id_kategori  = k.id_kategori
     WHERE p.id_pengaduan = ?
     LIMIT 1"
);

sdb_stmt_bind_param($stmt, 'i', $id_pengaduan);
sdb_stmt_execute($stmt);

$data = sdb_fetch_assoc(sdb_stmt_get_result($stmt));

if (!$data) {
    header('Location: index.php?pesan=data_tidak_ditemukan');
    exit;
}


$status_pilihan = daftar_status_pengaduan();

$status_pilih = $data['status'];
$tanggapan    = '';
$error        = '';


// --------------------------------------------------
// Proses perubahan status
// --------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    $status_pilih = $_POST['status'] ?? '';
    $tanggapan    = trim($_POST['tanggapan'] ?? '');

    if (!array_key_exists($status_pilih, $status_pilihan)) {

        $error = 'Status pengaduan tidak valid.';

    } else {

        // 1. Perbarui status
        $stmt_update = sdb_prepare(
            $koneksi,
            "UPDATE pengaduan SET status = ? WHERE id_pengaduan = ?"
        );

        sdb_stmt_bind_param($stmt_update, 'si', $status_pilih, $id_pengaduan);
        $status_ok = sdb_stmt_execute($stmt_update);

        if (!$status_ok) {

            $error = 'Status gagal diperbarui.';

        } else {

            // 2. Simpan tanggapan bila diisi
            if ($tanggapan !== '') {

                $stmt_t = sdb_prepare(
                    $koneksi,
                    "INSERT INTO tanggapan (id_pengaduan, id_admin, tanggapan)
                     VALUES (?, ?, ?)"
                );

                sdb_stmt_bind_param($stmt_t, 'iis', $id_pengaduan, $id_admin, $tanggapan);
                $t_ok = sdb_stmt_execute($stmt_t);

                if (!$t_ok) {
                    $error = 'Status berhasil diperbarui, tetapi tanggapan gagal disimpan.';
                }
            }

            if ($error === '') {
                header('Location: detail.php?id=' . $id_pengaduan . '&pesan=status_berhasil');
                exit;
            }
        }
    }
}


// Pengaturan tampilan
$judul      = 'Ubah Status Pengaduan';
$menu_aktif = 'pengaduan';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-arrow-repeat"></i> Ubah Status Pengaduan</h1>
        <p>Tetapkan status terbaru dan berikan tanggapan yang dapat dilihat pelapor.</p>
    </div>

    <div class="head-actions">
        <a href="detail.php?id=<?= (int) $data['id_pengaduan'] ?>" class="btn btn-light">
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


<!-- ================= RINGKASAN ================= -->
<div class="card card-pad mb-3">

    <div class="card-title"><i class="bi bi-info-circle"></i> Ringkasan Pengaduan</div>

    <div class="ringkasan-grid">

        <div class="desc-item">
            <div class="desc-label">Nomor Pengaduan</div>
            <div class="desc-value"><span class="nomor-chip"><?= e($data['nomor_pengaduan']) ?></span></div>
        </div>

        <div class="desc-item">
            <div class="desc-label">Status Saat Ini</div>
            <div class="desc-value">
                <span class="badge <?= kelas_status($data['status']) ?>">
                    <i class="bi <?= ikon_status($data['status']) ?>"></i>
                    <?= nama_status($data['status']) ?>
                </span>
            </div>
        </div>

        <div class="desc-item">
            <div class="desc-label">Pelapor</div>
            <div class="desc-value"><?= e($data['nama_lengkap']) ?></div>
        </div>

        <div class="desc-item">
            <div class="desc-label">Kategori</div>
            <div class="desc-value"><?= e($data['nama_kategori']) ?></div>
        </div>

        <div class="desc-item col-span-full">
            <div class="desc-label">Judul Pengaduan</div>
            <div class="desc-value"><?= e($data['judul_pengaduan']) ?></div>
        </div>

    </div>

</div>


<!-- ================= FORM ================= -->
<div class="form-card">

    <form method="POST">

        <div class="form-grid">

            <!-- Status baru -->
            <div class="field">

                <label for="status">
                    Status Baru <span class="required">*</span>
                </label>

                <select id="status" name="status" class="select" required>
                    <?php foreach ($status_pilihan as $kode => $label): ?>
                        <option
                            value="<?= $kode ?>"
                            <?= $status_pilih === $kode ? 'selected' : '' ?>
                        ><?= $label ?></option>
                    <?php endforeach; ?>
                </select>

            </div>

            <div class="field"></div>

            <!-- Tanggapan -->
            <div class="field span-2">

                <label for="tanggapan">Tanggapan Admin</label>

                <textarea
                    id="tanggapan"
                    name="tanggapan"
                    class="textarea"
                    placeholder="Tulis tanggapan atau keterangan penanganan untuk pelapor..."><?= e($tanggapan) ?></textarea>

                <span class="note">
                    <i class="bi bi-info-circle"></i>
                    <span>Opsional, tetapi sangat disarankan diisi saat status diubah menjadi <strong>Selesai</strong> atau <strong>Ditolak</strong>.</span>
                </span>

            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Perubahan
            </button>
            <a href="detail.php?id=<?= (int) $data['id_pengaduan'] ?>" class="btn btn-light">Batal</a>
        </div>

    </form>

</div>


<?php require __DIR__ . '/../partials/footer.php'; ?>
