<?php

// ==================================================
// PENGADUAN — DAFTAR, PENCARIAN, FILTER, PAGINATION
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';
require_once __DIR__ . '/../partials/pengaduan_helper.php';


// --------------------------------------------------
// Ambil parameter filter dari alamat
// --------------------------------------------------

$cari          = trim($_GET['cari'] ?? '');
$status        = $_GET['status'] ?? '';
$id_kategori   = $_GET['id_kategori'] ?? '';
$id_masyarakat = $_GET['masyarakat'] ?? '';


// Validasi nilai status
$status_pilihan = daftar_status_pengaduan();

if (!array_key_exists($status, $status_pilihan)) {
    $status = '';
}


// --------------------------------------------------
// Susun klausa WHERE secara dinamis (aman, pakai ?)
// --------------------------------------------------

$where  = [];
$params = [];
$tipe   = '';

if ($cari !== '') {
    $where[] = '(p.nomor_pengaduan LIKE ? OR p.judul_pengaduan LIKE ? OR m.nama_lengkap LIKE ?)';
    $kata = '%' . $cari . '%';
    array_push($params, $kata, $kata, $kata);
    $tipe .= 'sss';
}

if ($status !== '') {
    $where[] = 'p.status = ?';
    $params[] = $status;
    $tipe .= 's';
}

if ($id_kategori !== '' && is_numeric($id_kategori)) {
    $where[] = 'p.id_kategori = ?';
    $params[] = (int) $id_kategori;
    $tipe .= 'i';
}

if ($id_masyarakat !== '' && is_numeric($id_masyarakat)) {
    $where[] = 'p.id_masyarakat = ?';
    $params[] = (int) $id_masyarakat;
    $tipe .= 'i';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';


// Bagian FROM yang dipakai bersama
$from = "
    FROM pengaduan p
    INNER JOIN masyarakat m ON p.id_masyarakat = m.id_masyarakat
    INNER JOIN kategori  k ON p.id_kategori  = k.id_kategori
";


// --------------------------------------------------
// Hitung total data (untuk pagination)
// --------------------------------------------------

$sql_count = "SELECT COUNT(*) AS total $from $where_sql";
$stmt_count = sdb_prepare($koneksi, $sql_count);

if ($params) {
    sdb_stmt_bind_param($stmt_count, $tipe, ...$params);
}

sdb_stmt_execute($stmt_count);
$total_data = (int) sdb_fetch_assoc(sdb_stmt_get_result($stmt_count))['total'];


// --------------------------------------------------
// Pagination
// --------------------------------------------------

$batas = 10;

$halaman = isset($_GET['halaman']) && is_numeric($_GET['halaman'])
    ? (int) $_GET['halaman']
    : 1;

if ($halaman < 1) {
    $halaman = 1;
}

$total_halaman = $total_data > 0 ? (int) ceil($total_data / $batas) : 1;

if ($halaman > $total_halaman) {
    $halaman = $total_halaman;
}

$mulai = ($halaman - 1) * $batas;   // dijamin integer


// --------------------------------------------------
// Ambil data pengaduan per halaman
// --------------------------------------------------

$sql_data = "
    SELECT
        p.id_pengaduan,
        p.nomor_pengaduan,
        p.judul_pengaduan,
        p.status,
        p.tanggal_pengaduan,
        m.nama_lengkap,
        k.nama_kategori
    $from
    $where_sql
    ORDER BY p.tanggal_pengaduan DESC
    LIMIT $mulai, $batas
";

$stmt_data = sdb_prepare($koneksi, $sql_data);

if ($params) {
    sdb_stmt_bind_param($stmt_data, $tipe, ...$params);
}

sdb_stmt_execute($stmt_data);
$query_pengaduan = sdb_stmt_get_result($stmt_data);


// --------------------------------------------------
// Data kategori untuk dropdown filter
// --------------------------------------------------

$query_kategori = sdb_query(
    $koneksi,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);


// --------------------------------------------------
// Ringkasan jumlah per status (kartu statistik)
// --------------------------------------------------

$ringkasan = [
    'total'    => 0,
    'baru'     => 0,
    'diproses' => 0,
    'selesai'  => 0,
];

$query_status = sdb_query(
    $koneksi,
    "SELECT status, COUNT(*) AS jumlah
     FROM pengaduan
     GROUP BY status"
);

while ($s = sdb_fetch_assoc($query_status)) {
    $ringkasan['total'] += (int) $s['jumlah'];
    if (isset($ringkasan[$s['status']])) {
        $ringkasan[$s['status']] = (int) $s['jumlah'];
    }
}


// --------------------------------------------------
// Pembuat tautan pagination (melestarikan filter)
// --------------------------------------------------

$buat_url = function ($tuju) use ($cari, $status, $id_kategori, $id_masyarakat) {

    $q = array_filter([
        'cari'        => $cari,
        'status'      => $status,
        'id_kategori' => $id_kategori,
        'masyarakat'  => $id_masyarakat,
        'halaman'     => $tuju > 1 ? $tuju : '',
    ], fn ($nilai) => $nilai !== '' && $nilai !== null);

    return '?' . http_build_query($q);
};


// Pengaturan tampilan
$judul      = 'Data Pengaduan';
$menu_aktif = 'pengaduan';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-megaphone"></i> Data Pengaduan</h1>
        <p>Seluruh laporan masyarakat yang masuk. Gunakan pencarian dan filter untuk mempersempit data.</p>
    </div>

</div>


<!-- ============================== STATISTIK ============================== -->

<div class="stats-grid">

    <div class="stat-card">
        <div class="stat-icon tone-purple"><i class="bi bi-megaphone"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Total Pengaduan</span>
            <span class="stat-value"><?= $ringkasan['total'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-blue"><i class="bi bi-inbox"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Baru</span>
            <span class="stat-value"><?= $ringkasan['baru'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-amber"><i class="bi bi-arrow-repeat"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Diproses</span>
            <span class="stat-value"><?= $ringkasan['diproses'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-green"><i class="bi bi-check-circle"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Selesai</span>
            <span class="stat-value"><?= $ringkasan['selesai'] ?></span>
        </div>
    </div>

</div>


<!-- ============================== FILTER ============================== -->

<div class="card card-pad mb-3">

    <form method="GET" class="toolbar flush">

        <!-- Jika datang dari halaman masyarakat, ikut terbawa -->
        <?php if ($id_masyarakat !== ''): ?>
            <input type="hidden" name="masyarakat" value="<?= e($id_masyarakat) ?>">
        <?php endif; ?>

        <div class="search-box">
            <i class="bi bi-search"></i>
            <input
                class="input"
                type="text"
                name="cari"
                value="<?= e($cari) ?>"
                placeholder="Nomor, judul, atau nama pelapor...">
        </div>

        <select name="status" class="select">
            <option value="">Semua Status</option>
            <?php foreach ($status_pilihan as $kode => $label): ?>
                <option value="<?= $kode ?>" <?= $status === $kode ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>

        <select name="id_kategori" class="select">
            <option value="">Semua Kategori</option>
            <?php while ($kat = sdb_fetch_assoc($query_kategori)): ?>
                <option
                    value="<?= (int) $kat['id_kategori'] ?>"
                    <?= ((string) $id_kategori === (string) $kat['id_kategori']) ? 'selected' : '' ?>
                ><?= e($kat['nama_kategori']) ?></option>
            <?php endwhile; ?>
        </select>

        <button type="submit" class="btn btn-primary">
            <i class="bi bi-funnel"></i> Filter
        </button>

        <a href="index.php" class="btn btn-light">
            <i class="bi bi-arrow-counterclockwise"></i> Reset
        </a>

    </form>

</div>


<!-- ============================== TABEL ============================== -->

<?php if ($total_data > 0): ?>

    <div class="table-wrap">

        <table class="data-table">

            <thead>
                <tr>
                    <th width="55">No</th>
                    <th>Nomor</th>
                    <th>Pelapor</th>
                    <th>Kategori</th>
                    <th>Judul Pengaduan</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th width="140" class="text-center">Aksi</th>
                </tr>
            </thead>

            <tbody>

            <?php $no = $mulai + 1; ?>

            <?php while ($row = sdb_fetch_assoc($query_pengaduan)): ?>

                <tr>

                    <td><?= $no++ ?></td>

                    <td><span class="nomor-chip"><?= e($row['nomor_pengaduan']) ?></span></td>

                    <td class="fw-600"><?= e($row['nama_lengkap']) ?></td>

                    <td><?= e($row['nama_kategori']) ?></td>

                    <td><div class="judul-pengaduan"><?= e($row['judul_pengaduan']) ?></div></td>

                    <td>
                        <span class="badge <?= kelas_status($row['status']) ?>">
                            <i class="bi <?= ikon_status($row['status']) ?>"></i>
                            <?= nama_status($row['status']) ?>
                        </span>
                    </td>

                    <td class="small"><?= format_tanggal($row['tanggal_pengaduan']) ?></td>

                    <td>
                        <div class="col-actions-center">

                            <!-- Lihat detail -->
                            <a
                                href="detail.php?id=<?= (int) $row['id_pengaduan'] ?>"
                                class="btn-icon view"
                                title="Lihat detail">
                                <i class="bi bi-eye"></i>
                            </a>

                            <!-- Ubah status / beri tanggapan -->
                            <a
                                href="ubah_status.php?id=<?= (int) $row['id_pengaduan'] ?>"
                                class="btn-icon status-act"
                                title="Ubah status / tanggapi">
                                <i class="bi bi-arrow-repeat"></i>
                            </a>

                            <!-- Hapus (POST) -->
                            <form
                                action="hapus.php"
                                method="POST"
                                class="inline-form">
                                <input
                                    type="hidden"
                                    name="id_pengaduan"
                                    value="<?= (int) $row['id_pengaduan'] ?>">
                                <button
                                    type="submit"
                                    class="btn-icon delete btn-hapus"
                                    title="Hapus pengaduan"
                                    data-confirm="Yakin menghapus pengaduan &quot;<?= e($row['judul_pengaduan']) ?>&quot;? Tindakan ini tidak dapat dibatalkan.">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>

                        </div>
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
                <a href="<?= $buat_url($halaman - 1) ?>" aria-label="Halaman sebelumnya">
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
                <a href="<?= $buat_url($halaman + 1) ?>" aria-label="Halaman berikutnya">
                    <i class="bi bi-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="disabled"><i class="bi bi-chevron-right"></i></span>
            <?php endif; ?>

        </nav>

    <?php endif; ?>


    <p class="small text-muted mt-2">
        Menampilkan <?= (int) $mulai + 1 ?>–<?= min($mulai + $batas, $total_data) ?>
        dari <?= $total_data ?> pengaduan.
    </p>


<?php else: ?>

    <!-- Keadaan kosong -->
    <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-inbox"></i></div>
        <h3>Tidak Ada Pengaduan</h3>
        <p>
            <?= ($cari !== '' || $status !== '' || $id_kategori !== '')
                ? 'Tidak ada pengaduan yang cocok dengan filter yang dipilih.'
                : 'Belum ada pengaduan yang masuk ke sistem.'; ?>
        </p>
        <?php if ($cari !== '' || $status !== '' || $id_kategori !== ''): ?>
            <a href="index.php" class="btn btn-light">
                <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
            </a>
        <?php endif; ?>
    </div>

<?php endif; ?>


<?php require __DIR__ . '/../partials/footer.php'; ?>
