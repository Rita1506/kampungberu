<?php

// ==================================================
// LAPORAN — REKAP & CETAK PENGADUAN
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';
require_once __DIR__ . '/../partials/pengaduan_helper.php';


// --------------------------------------------------
// Ambil filter
// --------------------------------------------------

$status        = $_GET['status'] ?? '';
$id_kategori   = $_GET['id_kategori'] ?? '';
$tanggal_dari  = $_GET['tanggal_dari'] ?? '';
$tanggal_sampai = $_GET['tanggal_sampai'] ?? '';

if (!array_key_exists($status, daftar_status_pengaduan())) {
    $status = '';
}

if ($id_kategori !== '' && !is_numeric($id_kategori)) {
    $id_kategori = '';
}

// Validasi sederhana format tanggal (YYYY-MM-DD)
if ($tanggal_dari !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_dari)) {
    $tanggal_dari = '';
}
if ($tanggal_sampai !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_sampai)) {
    $tanggal_sampai = '';
}


// --------------------------------------------------
// Daftar kategori untuk dropdown
// --------------------------------------------------

$query_kategori = sdb_query(
    $koneksi,
    "SELECT id_kategori, nama_kategori
     FROM kategori
     ORDER BY nama_kategori ASC"
);


// --------------------------------------------------
// Susun filter secara dinamis (prepared statement)
// --------------------------------------------------

$where  = [];
$params = [];
$tipe   = '';

if ($status !== '') {
    $where[]  = 'p.status = ?';
    $params[] = $status;
    $tipe    .= 's';
}

if ($id_kategori !== '') {
    $where[]  = 'p.id_kategori = ?';
    $params[] = (int) $id_kategori;
    $tipe    .= 'i';
}

if ($tanggal_dari !== '') {
    $where[]  = 'p.tanggal_pengaduan >= ?';
    $params[] = $tanggal_dari . ' 00:00:00';
    $tipe    .= 's';
}

if ($tanggal_sampai !== '') {
    $where[]  = 'p.tanggal_pengaduan <= ?';
    $params[] = $tanggal_sampai . ' 23:59:59';
    $tipe    .= 's';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';


// --------------------------------------------------
// Ambil data laporan
// --------------------------------------------------

$sql = "
    SELECT
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
    $where_sql
    ORDER BY p.tanggal_pengaduan DESC
";

$stmt = sdb_prepare($koneksi, $sql);

if ($params) {
    sdb_stmt_bind_param($stmt, $tipe, ...$params);
}

sdb_stmt_execute($stmt);
$hasil = sdb_stmt_get_result($stmt);

$daftar_laporan = sdb_fetch_all($hasil, MYSQLI_ASSOC);
$total_laporan  = count($daftar_laporan);


// --------------------------------------------------
// Rekap jumlah per status (dari hasil filter)
// --------------------------------------------------

$rekap = array_fill_keys(array_keys(daftar_status_pengaduan()), 0);

foreach ($daftar_laporan as $baris) {
    if (isset($rekap[$baris['status']])) {
        $rekap[$baris['status']]++;
    }
}


// Apakah sedang dalam keadaan terfilter (untuk keterangan)
$ada_filter = ($status !== '' || $id_kategori !== '' || $tanggal_dari !== '' || $tanggal_sampai !== '');


// Pengaturan tampilan
$judul      = 'Laporan Pengaduan';
$menu_aktif = 'laporan';

require __DIR__ . '/../partials/head.php';

?>

<!-- ================= KEPALA CETAK (hanya tampil saat dicetak) ================= -->
<div class="print-header">
    <h2>SI PA'MASE-MASE</h2>
    <p>Sistem Pengelolaan Pengaduan Masyarakat</p>
    <p>Desa Kampung Beru, Kecamatan Polombangkeng Timur, Kabupaten Takalar</p>
    <hr>
    <h4>Laporan Pengaduan Masyarakat</h4>
    <p>Dicetak pada: <?= date('d/m/Y H:i'); ?></p>
</div>


<div class="page-head no-print">

    <div>
        <h1><i class="bi bi-file-earmark-bar-graph"></i> Laporan Pengaduan</h1>
        <p>Saring data berdasarkan status, kategori, dan rentang waktu, lalu cetak sebagai laporan resmi.</p>
    </div>

</div>


<!-- ================= FILTER ================= -->
<div class="card card-pad mb-3 no-print">

    <form method="GET" class="form-grid mb-0">

        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status" class="select">
                <option value="">Semua Status</option>
                <?php foreach (daftar_status_pengaduan() as $kode => $label): ?>
                    <option value="<?= $kode ?>" <?= $status === $kode ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label for="id_kategori">Kategori</label>
            <select id="id_kategori" name="id_kategori" class="select">
                <option value="">Semua Kategori</option>
                <?php while ($kat = sdb_fetch_assoc($query_kategori)): ?>
                    <option
                        value="<?= (int) $kat['id_kategori'] ?>"
                        <?= ((string) $id_kategori === (string) $kat['id_kategori']) ? 'selected' : '' ?>
                    ><?= e($kat['nama_kategori']) ?></option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="field">
            <label for="tanggal_dari">Dari Tanggal</label>
            <input
                type="date"
                id="tanggal_dari"
                name="tanggal_dari"
                class="input"
                value="<?= e($tanggal_dari) ?>">
        </div>

        <div class="field">
            <label for="tanggal_sampai">Sampai Tanggal</label>
            <input
                type="date"
                id="tanggal_sampai"
                name="tanggal_sampai"
                class="input"
                value="<?= e($tanggal_sampai) ?>">
        </div>

        <div class="field span-2">
            <div class="form-actions mt-1">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-funnel"></i> Terapkan Filter
                </button>
                <?php if ($ada_filter): ?>
                    <a href="index.php" class="btn btn-light">
                        <i class="bi bi-arrow-counterclockwise"></i> Reset
                    </a>
                <?php endif; ?>
                <button type="button" class="btn btn-light ml-auto" onclick="window.print();">
                    <i class="bi bi-printer"></i> Cetak Laporan
                </button>
            </div>
        </div>

    </form>

</div>


<!-- ================= RINGKASAN ================= -->
<div class="stats-grid no-print">

    <div class="stat-card">
        <div class="stat-icon tone-purple"><i class="bi bi-list-ul"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Total Ditampilkan</span>
            <span class="stat-value"><?= $total_laporan ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-blue"><i class="bi bi-inbox"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Baru</span>
            <span class="stat-value"><?= $rekap['baru'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-amber"><i class="bi bi-arrow-repeat"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Diproses</span>
            <span class="stat-value"><?= $rekap['diproses'] + $rekap['diverifikasi'] ?></span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon tone-green"><i class="bi bi-check-circle"></i></div>
        <div class="stat-meta">
            <span class="stat-label">Selesai</span>
            <span class="stat-value"><?= $rekap['selesai'] ?></span>
        </div>
    </div>

</div>


<!-- ================= TABEL LAPORAN ================= -->
<div class="table-wrap">

    <div class="d-flex items-center justify-between card-pad mb-1">
        <div class="card-title mb-0">
            <i class="bi bi-table"></i> Data Laporan
        </div>
        <span class="small text-muted no-print">
            Menampilkan <strong class="fw-700"><?= $total_laporan ?></strong> pengaduan
        </span>
    </div>

    <?php if ($total_laporan > 0): ?>

        <table class="data-table">

            <thead>
                <tr>
                    <th width="50">No</th>
                    <th>Nomor</th>
                    <th>Pelapor</th>
                    <th>Kategori</th>
                    <th>Judul Pengaduan</th>
                    <th>Status</th>
                    <th>Tanggal</th>
                    <th width="90" class="text-center no-print">Aksi</th>
                </tr>
            </thead>

            <tbody>

            <?php $no = 1; ?>

            <?php foreach ($daftar_laporan as $row): ?>

                <tr>

                    <td><?= $no++ ?></td>

                    <td><span class="nomor-chip"><?= e($row['nomor_pengaduan']) ?></span></td>

                    <td class="fw-600"><?= e($row['nama_lengkap']) ?></td>

                    <td><?= e($row['nama_kategori']) ?></td>

                    <td><div class="judul-pengaduan"><?= e($row['judul_pengaduan']) ?></div></td>

                    <td>
                        <span class="badge <?= kelas_status($row['status']) ?>">
                            <?= nama_status($row['status']) ?>
                        </span>
                    </td>

                    <td class="small"><?= format_tanggal($row['tanggal_pengaduan']) ?></td>

                    <td class="no-print">
                        <div class="col-actions-center">
                            <a
                                href="../pengaduan/detail.php?id=<?= (int) $row['id_pengaduan'] ?>"
                                class="btn-icon view"
                                title="Lihat detail">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    <?php else: ?>

        <div class="empty-state">
            <div class="empty-icon"><i class="bi bi-file-earmark-bar-graph"></i></div>
            <h3>Tidak Ada Data</h3>
            <p>
                <?= $ada_filter
                    ? 'Tidak ada pengaduan yang cocok dengan filter yang dipilih.'
                    : 'Belum ada pengaduan yang masuk.'; ?>
            </p>
            <?php if ($ada_filter): ?>
                <a href="index.php" class="btn btn-light">
                    <i class="bi bi-arrow-counterclockwise"></i> Reset Filter
                </a>
            <?php endif; ?>
        </div>

    <?php endif; ?>

</div>


<?php require __DIR__ . '/../partials/footer.php'; ?>
