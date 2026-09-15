<?php

// ==================================================
// GALERI — DAFTAR FOTO
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// --------------------------------------------------
// Ambil seluruh data galeri (terbaru di atas)
// --------------------------------------------------

$query_galeri = sdb_query(
    $koneksi,
    "SELECT * FROM galeri
     ORDER BY id_galeri DESC"
);


// --------------------------------------------------
// Hitung statistik galeri
// --------------------------------------------------

$total_galeri = (int) (sdb_fetch_assoc(
    sdb_query($koneksi, "SELECT COUNT(*) AS total FROM galeri")
)['total'] ?? 0);

$total_aktif = (int) (sdb_fetch_assoc(
    sdb_query($koneksi, "SELECT COUNT(*) AS total FROM galeri WHERE status = 'aktif'")
)['total'] ?? 0);

$total_nonaktif = $total_galeri - $total_aktif;


// --------------------------------------------------
// Kelompok foto di Storage (galeri/<nama file>)
// --------------------------------------------------

$kelompok_foto = 'galeri';


// Judul halaman & penanda menu aktif
$judul      = 'Galeri Desa';
$menu_aktif = 'galeri';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-images"></i> Galeri Desa</h1>
        <p>Kelola dokumentasi kegiatan Desa Kampung Beru. Foto berstatus aktif tampil di halaman depan.</p>
    </div>

    <div class="head-actions">
        <a href="tambah.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Foto
        </a>
    </div>

</div>


<!-- ============================== STATISTIK ============================== -->

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon tone-purple"><i class="bi bi-images"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Total Foto</span>
            <span class="stat-value"><?= $total_galeri ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-green"><i class="bi bi-check-circle"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Foto Aktif</span>
            <span class="stat-value"><?= $total_aktif ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-gray"><i class="bi bi-eye-slash"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Foto Nonaktif</span>
            <span class="stat-value"><?= $total_nonaktif ?></span>
        </div>
    </div>

</div>


<!-- ============================== DAFTAR FOTO ============================== -->

<?php if ($query_galeri && sdb_num_rows($query_galeri) > 0): ?>

    <div class="gallery-grid">

        <?php while ($row = sdb_fetch_assoc($query_galeri)):

            $nama_file = $row['foto'] ?? '';
            $judul_foto = $row['judul'] ?? 'Tanpa judul';
            $deskripsi  = $row['deskripsi'] ?? '';
            $status     = $row['status'] ?? 'nonaktif';
            $id         = (int) $row['id_galeri'];

            $url_foto_objek = storage_url_berkas($kelompok_foto, $nama_file);
            $ada_foto = ($url_foto_objek !== '');

            // Potong deskripsi yang terlalu panjang
            if (mb_strlen($deskripsi) > 100) {
                $deskripsi_tampil = mb_substr($deskripsi, 0, 100) . '...';
            } else {
                $deskripsi_tampil = $deskripsi;
            }

        ?>

            <article class="gallery-card">

                <div class="gallery-media">

                    <?php if ($ada_foto): ?>
                        <img
                            src="<?= e($url_foto_objek) ?>"
                            alt="<?= e($judul_foto) ?>">
                    <?php else: ?>
                        <div class="gallery-placeholder">
                            <i class="bi bi-image"></i>
                            <small>Foto tidak ditemukan</small>
                        </div>
                    <?php endif; ?>

                    <?php if ($status === 'aktif'): ?>
                        <span class="gallery-badge badge badge-success">Aktif</span>
                    <?php else: ?>
                        <span class="gallery-badge badge badge-gray">Nonaktif</span>
                    <?php endif; ?>

                </div>

                <div class="gallery-body">

                    <div class="gallery-title"><?= e($judul_foto) ?></div>

                    <div class="gallery-desc">
                        <?php if ($deskripsi_tampil !== ''): ?>
                            <?= e($deskripsi_tampil) ?>
                        <?php else: ?>
                            <span class="text-muted fst-italic">Tidak ada deskripsi.</span>
                        <?php endif; ?>
                    </div>

                    <div class="gallery-foot">

                        <?php if ($status === 'aktif'): ?>
                            <span class="small text-muted">
                                <i class="bi bi-eye"></i> Tampil di publik
                            </span>
                        <?php else: ?>
                            <span class="small text-muted">
                                <i class="bi bi-eye-slash"></i> Disembunyikan
                            </span>
                        <?php endif; ?>

                        <div class="gallery-actions">

                            <a
                                href="edit.php?id=<?= $id ?>"
                                class="btn-icon edit"
                                title="Edit foto">
                                <i class="bi bi-pencil-square"></i>
                            </a>

                            <a
                                href="hapus.php?id=<?= $id ?>"
                                class="btn-icon delete btn-hapus"
                                title="Hapus foto"
                                data-confirm="Apakah Anda yakin ingin menghapus foto &quot;<?= e($judul_foto) ?>&quot;?">
                                <i class="bi bi-trash"></i>
                            </a>

                        </div>

                    </div>

                </div>

            </article>

        <?php endwhile; ?>

    </div>

<?php else: ?>

    <!-- Keadaan kosong: belum ada foto sama sekali -->
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-images"></i></div>
        <h3>Belum Ada Foto</h3>
        <p>Belum ada foto yang ditambahkan ke galeri desa.</p>
        <a href="tambah.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Foto Pertama
        </a>
    </div>

<?php endif; ?>


<?php require __DIR__ . '/../partials/footer.php'; ?>
