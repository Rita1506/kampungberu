<?php

// ==================================================
// DASHBOARD ADMIN
// ==================================================

$root = '../';
require_once __DIR__ . '/partials/proteksi.php';
require_once __DIR__ . '/partials/pengaduan_helper.php';


// --------------------------------------------------
// Ringkasan jumlah per status (satu query)
// --------------------------------------------------

$jumlah = array_fill_keys(array_keys(daftar_status_pengaduan()), 0);
$total_pengaduan = 0;

$query_status = sdb_query(
    $koneksi,
    "SELECT status, COUNT(*) AS jumlah
     FROM pengaduan
     GROUP BY status"
);

while ($s = sdb_fetch_assoc($query_status)) {
    if (isset($jumlah[$s['status']])) {
        $jumlah[$s['status']] = (int) $s['jumlah'];
    }
    $total_pengaduan += (int) $s['jumlah'];
}


// Jumlah masyarakat terdaftar
$total_masyarakat = (int) sdb_fetch_assoc(
    sdb_query($koneksi, "SELECT COUNT(*) AS total FROM masyarakat")
)['total'];


// --------------------------------------------------
// Lima pengaduan terbaru
// --------------------------------------------------

$query_terbaru = sdb_query(
    $koneksi,
    "SELECT
        p.id_pengaduan,
        p.nomor_pengaduan,
        p.judul_pengaduan,
        p.status,
        p.tanggal_pengaduan,
        m.nama_lengkap,
        k.nama_kategori
     FROM pengaduan p
     INNER JOIN masyarakat m ON p.id_masyarakat = m.id_masyarakat
     INNER JOIN kategori  k ON p.id_kategori  = k.id_kategori
     ORDER BY p.id_pengaduan DESC
     LIMIT 5"
);


// Sapaan sesuai waktu
$jam = (int) date('H');

if ($jam < 11) {
    $salam = 'Selamat pagi';
} elseif ($jam < 15) {
    $salam = 'Selamat siang';
} elseif ($jam < 19) {
    $salam = 'Selamat sore';
} else {
    $salam = 'Selamat malam';
}


// Pengaturan tampilan
$judul      = 'Dashboard';
$menu_aktif = 'dashboard';

require __DIR__ . '/partials/head.php';

?>

<!-- Sapaan -->
<div class="sapaan">
    <div>
        <h2><?= $salam ?>, <?= e(admin_nama()) ?> 👋</h2>
        <p>Berikut ringkasan pengelolaan pengaduan masyarakat Desa Kampung Beru.</p>
    </div>
    <i class="bi bi-megaphone-fill sapaan-icon"></i>
</div>


<!-- ================= STATISTIK ================= -->
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon tone-purple"><i class="bi bi-megaphone"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Total Pengaduan</span>
            <span class="stat-value"><?= $total_pengaduan ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-blue"><i class="bi bi-inbox"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Baru</span>
            <span class="stat-value"><?= $jumlah['baru'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-purple"><i class="bi bi-shield-check"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Diverifikasi</span>
            <span class="stat-value"><?= $jumlah['diverifikasi'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-amber"><i class="bi bi-arrow-repeat"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Diproses</span>
            <span class="stat-value"><?= $jumlah['diproses'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-green"><i class="bi bi-check-circle"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Selesai</span>
            <span class="stat-value"><?= $jumlah['selesai'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-red"><i class="bi bi-x-circle"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Ditolak</span>
            <span class="stat-value"><?= $jumlah['ditolak'] ?></span>
        </div>
    </div>

</div>


<!-- ================= AKSES CEPAT ================= -->
<div class="stats-grid quick">

    <a href="pengaduan/index.php" class="stat-card stat-link">
        <div class="stat-icon tone-blue"><i class="bi bi-megaphone"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Kelola Pengaduan</span>
            <span class="stat-value sm">Lihat semua <i class="bi bi-arrow-right"></i></span>
        </div>
    </a>

    <a href="masyarakat/index.php" class="stat-card stat-link">
        <div class="stat-icon tone-green"><i class="bi bi-people"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Masyarakat Terdaftar</span>
            <span class="stat-value"><?= $total_masyarakat ?></span>
        </div>
    </a>

    <a href="laporan/index.php" class="stat-card stat-link">
        <div class="stat-icon tone-purple"><i class="bi bi-file-earmark-bar-graph"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Laporan</span>
            <span class="stat-value sm">Cetak rekap <i class="bi bi-arrow-right"></i></span>
        </div>
    </a>

    <a href="galeri/index.php" class="stat-card stat-link">
        <div class="stat-icon tone-amber"><i class="bi bi-images"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Galeri Desa</span>
            <span class="stat-value sm">Kelola foto <i class="bi bi-arrow-right"></i></span>
        </div>
    </a>

</div>


<!-- ================= PENGADUAN TERBARU ================= -->
<div class="card">

    <div class="card-head">
        <div class="card-title">
            <i class="bi bi-clock-history"></i> Pengaduan Terbaru
        </div>
        <a href="pengaduan/index.php" class="btn btn-light btn-sm">
            Lihat Semua <i class="bi bi-arrow-right"></i>
        </a>
    </div>

    <?php if ($query_terbaru && sdb_num_rows($query_terbaru) > 0): ?>

        <div class="table-wrap flush">

            <table class="data-table">

                <thead>
                    <tr>
                        <th>Nomor</th>
                        <th>Pelapor</th>
                        <th>Kategori</th>
                        <th>Judul</th>
                        <th>Status</th>
                        <th>Tanggal</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($row = sdb_fetch_assoc($query_terbaru)):

                    $judul = $row['judul_pengaduan'];

                    if (mb_strlen($judul) > 35) {
                        $judul = mb_substr($judul, 0, 35) . '...';
                    }

                ?>

                    <tr>
                        <td>
                            <a href="pengaduan/detail.php?id=<?= (int) $row['id_pengaduan'] ?>">
                                <span class="nomor-chip"><?= e($row['nomor_pengaduan']) ?></span>
                            </a>
                        </td>
                        <td class="fw-600"><?= e($row['nama_lengkap']) ?></td>
                        <td><?= e($row['nama_kategori']) ?></td>
                        <td title="<?= e($row['judul_pengaduan']) ?>"><?= e($judul) ?></td>
                        <td>
                            <span class="badge <?= kelas_status($row['status']) ?>">
                                <?= nama_status($row['status']) ?>
                            </span>
                        </td>
                        <td class="small"><?= format_tanggal($row['tanggal_pengaduan']) ?></td>
                    </tr>

                <?php endwhile; ?>

                </tbody>

            </table>

        </div>

    <?php else: ?>

        <div class="empty-state flush">
            <div class="empty-icon"><i class="bi bi-inbox"></i></div>
            <h3>Belum Ada Pengaduan</h3>
            <p>Data pengaduan terbaru akan tampil di sini setelah masyarakat mengirim laporan.</p>
        </div>

    <?php endif; ?>

</div>


<?php require __DIR__ . '/partials/footer.php'; ?>
