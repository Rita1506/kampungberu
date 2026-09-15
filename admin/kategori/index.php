<?php

// ==================================================
// KATEGORI — DAFTAR KATEGORI PENGADUAN
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// Ambil seluruh kategori (terlama di atas)
$query_kategori = sdb_query(
    $koneksi,
    "SELECT * FROM kategori
     ORDER BY id_kategori ASC"
);

if (!$query_kategori) {
    die('Query kategori gagal: ' . sdb_error($koneksi));
}

$total_kategori = sdb_num_rows($query_kategori);

// Kelompok ikon di Storage (kategori/<nama file>)
$kelompok_ikon = 'kategori';


// Pengaturan tampilan
$judul      = 'Kategori Pengaduan';
$menu_aktif = 'kategori';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-grid"></i> Kategori Pengaduan</h1>
        <p>Kelola kategori untuk mengelompokkan pengaduan berdasarkan jenis permasalahan.</p>
    </div>

    <div class="head-actions">
        <a href="tambah.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Kategori
        </a>
    </div>

</div>


<?php if ($query_kategori && $total_kategori > 0): ?>

    <div class="table-wrap">

        <table class="data-table">

            <thead>
                <tr>
                    <th width="60">No.</th>
                    <th width="70">Ikon</th>
                    <th>Nama Kategori</th>
                    <th>Deskripsi</th>
                    <th width="130">Status</th>
                    <th width="110" class="text-center">Aksi</th>
                </tr>
            </thead>

            <tbody>

            <?php $no = 1; ?>

            <?php while ($row = sdb_fetch_assoc($query_kategori)):

                $id        = (int) $row['id_kategori'];
                $nama      = $row['nama_kategori'] ?? '';
                $deskripsi = $row['deskripsi'] ?? '';
                $ikon      = $row['ikon'] ?? '';
                $status    = $row['status'] ?? 'nonaktif';

                $url_ikon_objek = storage_url_berkas($kelompok_ikon, $ikon);
                $ada_ikon = ($url_ikon_objek !== '');

                if (mb_strlen($deskripsi) > 60) {
                    $deskripsi_tampil = mb_substr($deskripsi, 0, 60) . '...';
                } else {
                    $deskripsi_tampil = $deskripsi;
                }

            ?>

                <tr>

                    <td><?= $no++ ?></td>

                    <td>
                        <div class="icon-thumb">
                            <?php if ($ada_ikon): ?>
                                <img src="<?= e($url_ikon_objek) ?>" alt="<?= e($nama) ?>">
                            <?php else: ?>
                                <i class="bi bi-grid"></i>
                            <?php endif; ?>
                        </div>
                    </td>

                    <td><strong class="fw-700"><?= e($nama) ?></strong></td>

                    <td>
                        <?php if ($deskripsi_tampil !== ''): ?>
                            <?= e($deskripsi_tampil) ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <?php if ($status === 'aktif'): ?>
                            <span class="badge badge-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-gray">Nonaktif</span>
                        <?php endif; ?>
                    </td>

                    <td>
                        <div class="col-actions-center">
                            <a
                                href="edit.php?id=<?= $id ?>"
                                class="btn-icon edit"
                                title="Edit kategori">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a
                                href="hapus.php?id=<?= $id ?>"
                                class="btn-icon delete btn-hapus"
                                title="Hapus kategori"
                                data-confirm="Apakah Anda yakin ingin menghapus kategori &quot;<?= e($nama) ?>&quot;?">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

<?php else: ?>

    <!-- Keadaan kosong -->
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-grid"></i></div>
        <h3>Belum Ada Kategori</h3>
        <p>Belum terdapat kategori pengaduan di dalam database.</p>
        <a href="tambah.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Tambah Kategori Pertama
        </a>
    </div>

<?php endif; ?>


<?php require __DIR__ . '/../partials/footer.php'; ?>
