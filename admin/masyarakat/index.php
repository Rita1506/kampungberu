<?php

// ==================================================
// MASYARAKAT — DATA PELAPOR TERDAFTAR
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// --------------------------------------------------
// Pencarian & pagination
// --------------------------------------------------

$cari = trim($_GET['cari'] ?? '');

$batas = 10;

$halaman = isset($_GET['halaman']) && is_numeric($_GET['halaman'])
    ? (int) $_GET['halaman']
    : 1;

if ($halaman < 1) {
    $halaman = 1;
}

$mulai = ($halaman - 1) * $batas;


// Klausa pencarian (memakai prepared statement)
$where_sql = '';
$params    = [];
$tipe      = '';

if ($cari !== '') {
    $where_sql = "WHERE (
        m.nama_lengkap LIKE ?
        OR m.nik LIKE ?
        OR m.no_telepon LIKE ?
        OR m.email LIKE ?
    )";
    $kata = '%' . $cari . '%';
    $params = [$kata, $kata, $kata, $kata];
    $tipe   = 'ssss';
}


// --------------------------------------------------
// Hitung total warga
// --------------------------------------------------

$stmt_count = sdb_prepare(
    $koneksi,
    "SELECT COUNT(DISTINCT m.id_masyarakat) AS total
     FROM masyarakat m
     $where_sql"
);

if ($params) {
    sdb_stmt_bind_param($stmt_count, $tipe, ...$params);
}

sdb_stmt_execute($stmt_count);
$total_data = (int) sdb_fetch_assoc(sdb_stmt_get_result($stmt_count))['total'];

$total_halaman = $total_data > 0 ? (int) ceil($total_data / $batas) : 1;

if ($halaman > $total_halaman) {
    $halaman = $total_halaman;
    $mulai = ($halaman - 1) * $batas;
}


// --------------------------------------------------
// Ambil data warga + jumlah pengaduan masing-masing
// --------------------------------------------------

$stmt_data = sdb_prepare(
    $koneksi,
    "SELECT
        m.id_masyarakat,
        m.nama_lengkap,
        m.nik,
        m.no_telepon,
        m.email,
        m.alamat,
        m.created_at,
        COUNT(p.id_pengaduan) AS jumlah_pengaduan
     FROM masyarakat m
     LEFT JOIN pengaduan p ON m.id_masyarakat = p.id_masyarakat
     $where_sql
     GROUP BY
        m.id_masyarakat, m.nama_lengkap, m.nik, m.no_telepon,
        m.email, m.alamat, m.created_at
     ORDER BY m.id_masyarakat DESC
     LIMIT $mulai, $batas"
);

if ($params) {
    sdb_stmt_bind_param($stmt_data, $tipe, ...$params);
}

sdb_stmt_execute($stmt_data);
$query_masyarakat = sdb_stmt_get_result($stmt_data);


// --------------------------------------------------
// Total seluruh pengaduan (untuk kartu statistik)
// --------------------------------------------------

$total_pengaduan = (int) sdb_fetch_assoc(
    sdb_query($koneksi, "SELECT COUNT(*) AS total FROM pengaduan")
)['total'];


// Pembuat tautan pagination (melestarikan kata kunci)
$buat_url = function ($tuju) use ($cari) {

    $q = array_filter([
        'cari'    => $cari,
        'halaman' => $tuju > 1 ? $tuju : '',
    ], fn ($nilai) => $nilai !== '');

    return '?' . http_build_query($q);
};


// Pengaturan tampilan
$judul      = 'Data Masyarakat';
$menu_aktif = 'masyarakat';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-people"></i> Data Masyarakat</h1>
        <p>Daftar masyarakat yang terdaftar beserta jumlah pengaduan yang pernah mereka ajukan.</p>
    </div>

</div>


<!-- Statistik -->
<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon tone-purple"><i class="bi bi-people"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Masyarakat Terdaftar</span>
            <span class="stat-value"><?= $total_data ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-blue"><i class="bi bi-megaphone"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Total Pengaduan</span>
            <span class="stat-value"><?= $total_pengaduan ?></span>
        </div>
    </div>

</div>


<!-- Pencarian -->
<div class="card card-pad mb-3 no-print">

    <form method="GET" class="toolbar flush">

        <div class="search-box">
            <i class="bi bi-search"></i>
            <input
                class="input"
                type="text"
                name="cari"
                value="<?= e($cari) ?>"
                placeholder="Cari nama, NIK, telepon, atau email...">
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="bi bi-search"></i> Cari
        </button>

        <?php if ($cari !== ''): ?>
            <a href="index.php" class="btn btn-light">
                <i class="bi bi-arrow-counterclockwise"></i> Reset
            </a>
        <?php endif; ?>

    </form>

</div>


<!-- Tabel -->
<?php if ($total_data > 0): ?>

    <div class="table-wrap">

        <table class="data-table">

            <thead>
                <tr>
                    <th width="55">No</th>
                    <th>Masyarakat</th>
                    <th>NIK</th>
                    <th>No. Telepon</th>
                    <th>Alamat</th>
                    <th>Pengaduan</th>
                    <th>Terdaftar</th>
                </tr>
            </thead>

            <tbody>

            <?php $no = $mulai + 1; ?>

            <?php while ($row = sdb_fetch_assoc($query_masyarakat)):

                $nama    = $row['nama_lengkap'] ?? '';
                $email   = $row['email'] ?? '';
                $nik     = $row['nik'] ?? '';
                $telepon = $row['no_telepon'] ?? '';
                $alamat  = $row['alamat'] ?? '';
                $jumlah  = (int) ($row['jumlah_pengaduan'] ?? 0);

                if (mb_strlen($alamat) > 45) {
                    $alamat_tampil = mb_substr($alamat, 0, 45) . '...';
                } else {
                    $alamat_tampil = $alamat;
                }

            ?>

                <tr>

                    <td><?= $no++ ?></td>

                    <td>
                        <div class="user-cell">
                            <div class="avatar"><i class="bi bi-person-fill"></i></div>
                            <div>
                                <div class="user-nama"><?= e($nama) ?></div>
                                <div class="user-email">
                                    <?= $email !== '' ? e($email) : '<span class="text-muted">tanpa email</span>' ?>
                                </div>
                            </div>
                        </div>
                    </td>

                    <td><span class="small"><?= $nik !== '' ? e($nik) : '-' ?></span></td>

                    <td>
                        <?php if ($telepon !== ''): ?>
                            <a href="tel:<?= e($telepon) ?>">
                                <i class="bi bi-telephone"></i>
                                <span class="small"><?= e($telepon) ?></span>
                            </a>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>

                    <td class="small" title="<?= e($alamat) ?>">
                        <?= $alamat_tampil !== '' ? e($alamat_tampil) : '<span class="text-muted">-</span>' ?>
                    </td>

                    <td>
                        <?php if ($jumlah > 0): ?>
                            <a
                                href="../pengaduan/index.php?masyarakat=<?= (int) $row['id_masyarakat'] ?>"
                                class="complaint-count"
                                title="Lihat pengaduan warga ini">
                                <i class="bi bi-megaphone"></i>
                                <?= $jumlah ?> laporan
                            </a>
                        <?php else: ?>
                            <span class="complaint-count zero">
                                <i class="bi bi-megaphone"></i> 0
                            </span>
                        <?php endif; ?>
                    </td>

                    <td class="small text-muted">
                        <?= !empty($row['created_at']) ? date('d/m/Y', strtotime($row['created_at'])) : '-' ?>
                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>


    <!-- Pagination -->
    <?php if ($total_halaman > 1): ?>

        <nav class="pagination" aria-label="Navigasi halaman">

            <?php if ($halaman > 1): ?>
                <a href="<?= $buat_url($halaman - 1) ?>" aria-label="Sebelumnya">
                    <i class="bi bi-chevron-left"></i>
                </a>
            <?php else: ?>
                <span class="disabled"><i class="bi bi-chevron-left"></i></span>
            <?php endif; ?>

            <?php
            $dari   = max(1, $halaman - 2);
            $sampai = min($total_halaman, $halaman + 2);
            ?>

            <?php for ($i = $dari; $i <= $sampai; $i++): ?>
                <?php if ($i == $halaman): ?>
                    <span class="active"><?= $i ?></span>
                <?php else: ?>
                    <a href="<?= $buat_url($i) ?>"><?= $i ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($halaman < $total_halaman): ?>
                <a href="<?= $buat_url($halaman + 1) ?>" aria-label="Berikutnya">
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="disabled"><i class="bi bi-chevron-right"></i></span>
            <?php endif; ?>

        </nav>

    <?php endif; ?>


    <p class="small text-muted mt-2">
        Menampilkan <?= (int) $mulai + 1 ?>–<?= min($mulai + $batas, $total_data) ?>
        dari <?= $total_data ?> masyarakat.
    </p>


<?php else: ?>

    <!-- Keadaan kosong -->
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-people"></i></div>
        <h3><?= $cari !== '' ? 'Data Tidak Ditemukan' : 'Belum Ada Masyarakat' ?></h3>
        <p>
            <?= $cari !== ''
                ? 'Tidak ada masyarakat yang cocok dengan kata kunci pencarian.'
                : 'Belum ada masyarakat yang terdaftar di sistem.'; ?>
        </p>
        <?php if ($cari !== ''): ?>
            <a href="index.php" class="btn btn-light">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Pencarian
            </a>
        <?php endif; ?>
    </div>

<?php endif; ?>


<?php require __DIR__ . '/../partials/footer.php'; ?>
