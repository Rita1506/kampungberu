<?php

// ==================================================
// PENGADUAN — DETAIL LAPORAN & RIWAYAT TANGGAPAN
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


// --------------------------------------------------
// Ambil data pengaduan lengkap
// --------------------------------------------------

$stmt = sdb_prepare(
    $koneksi,
    "SELECT
        p.*,
        m.nama_lengkap,
        m.nik,
        m.no_telepon,
        m.email,
        m.alamat,
        k.nama_kategori,
        k.ikon
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


// --------------------------------------------------
// Ambil riwayat tanggapan
// --------------------------------------------------

$stmt_t = sdb_prepare(
    $koneksi,
    "SELECT t.*, a.nama_lengkap AS nama_admin
     FROM tanggapan t
     INNER JOIN admin a ON t.id_admin = a.id_admin
     WHERE t.id_pengaduan = ?
     ORDER BY t.tanggal_tanggapan DESC"
);

sdb_stmt_bind_param($stmt_t, 'i', $id_pengaduan);
sdb_stmt_execute($stmt_t);
$query_tanggapan = sdb_stmt_get_result($stmt_t);


// --------------------------------------------------
// URL berkas dari Supabase Storage
// --------------------------------------------------

$foto_bukti = $data['foto_bukti'] ?? '';
$url_bukti_objek = storage_url_berkas('pengaduan', $foto_bukti);
$ada_foto   = ($url_bukti_objek !== '');

$ikon_kategori = $data['ikon'] ?? '';
$url_ikon_objek = storage_url_berkas('kategori', $ikon_kategori);
$ada_ikon      = ($url_ikon_objek !== '');


// --------------------------------------------------
// Nilai yang sering dipakai
// --------------------------------------------------

$kosong = '<span class="text-muted">-</span>';


// Pengaturan tampilan
$judul      = 'Detail Pengaduan';
$menu_aktif = 'pengaduan';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-file-earmark-text"></i> Detail Pengaduan</h1>
        <p>Informasi lengkap laporan masyarakat beserta riwayat penanganannya.</p>
    </div>

    <div class="head-actions">
        <a href="index.php" class="btn btn-light">
            <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <a href="ubah_status.php?id=<?= (int) $data['id_pengaduan'] ?>" class="btn btn-primary">
            <i class="bi bi-arrow-repeat"></i> Ubah Status
        </a>
    </div>

</div>


<!-- Kepala: nomor + status -->
<div class="card card-pad mb-3">

    <div class="detail-head">

        <div>
            <div class="d-flex items-center gap-2 mb-1">
                <i class="bi bi-hash text-muted"></i>
                <span class="nomor-besar"><?= e($data['nomor_pengaduan']) ?></span>
            </div>
            <span class="small text-muted">
                <i class="bi bi-calendar-event"></i>
                Dikirim <?= format_tanggal($data['tanggal_pengaduan']) ?>
            </span>
        </div>

        <span class="badge badge-lg <?= kelas_status($data['status']) ?>">
            <i class="bi <?= ikon_status($data['status']) ?>"></i>
            <?= nama_status($data['status']) ?>
        </span>

    </div>

</div>


<div class="detail-grid">

    <!-- ================= DATA PELAPOR ================= -->
    <div class="card card-pad">

        <div class="card-title"><i class="bi bi-person-circle"></i> Data Pelapor</div>

        <div class="desc-list">

            <div class="desc-item">
                <div class="desc-label"><i class="bi bi-person"></i> Nama Lengkap</div>
                <div class="desc-value"><?= e($data['nama_lengkap']) ?></div>
            </div>

            <div class="desc-item">
                <div class="desc-label"><i class="bi bi-card-heading"></i> NIK</div>
                <div class="desc-value"><?= !empty($data['nik']) ? e($data['nik']) : $kosong ?></div>
            </div>

            <div class="desc-item">
                <div class="desc-label"><i class="bi bi-telephone"></i> Nomor Telepon</div>
                <div class="desc-value">
                    <?php if (!empty($data['no_telepon'])): ?>
                        <a href="tel:<?= e($data['no_telepon']) ?>">
                            <i class="bi bi-telephone"></i> <?= e($data['no_telepon']) ?>
                        </a>
                    <?php else: ?>
                        <?= $kosong ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="desc-item">
                <div class="desc-label"><i class="bi bi-envelope"></i> Email</div>
                <div class="desc-value">
                    <?php if (!empty($data['email'])): ?>
                        <a href="mailto:<?= e($data['email']) ?>"><?= e($data['email']) ?></a>
                    <?php else: ?>
                        <?= $kosong ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="desc-item">
                <div class="desc-label"><i class="bi bi-geo-alt"></i> Alamat</div>
                <div class="desc-value">
                    <?= !empty($data['alamat']) ? nl2br(e($data['alamat'])) : $kosong ?>
                </div>
            </div>

        </div>

    </div>


    <!-- ================= DATA PENGADUAN ================= -->
    <div class="card card-pad">

        <div class="card-title"><i class="bi bi-megaphone"></i> Data Pengaduan</div>

        <div class="desc-list">

            <div class="desc-item">
                <div class="desc-label"><i class="bi bi-grid"></i> Kategori</div>
                <div class="desc-value">
                    <span class="d-flex items-center gap-2">
                        <span class="icon-thumb sm">
                            <?php if ($ada_ikon): ?>
                                <img src="<?= e($url_ikon_objek) ?>" alt="">
                            <?php else: ?>
                                <i class="bi bi-grid"></i>
                            <?php endif; ?>
                        </span>
                        <?= e($data['nama_kategori']) ?>
                    </span>
                </div>
            </div>

            <div class="desc-item">
                <div class="desc-label"><i class="bi bi-chat-left-text"></i> Judul</div>
                <div class="desc-value"><?= e($data['judul_pengaduan']) ?></div>
            </div>

            <div class="desc-item">
                <div class="desc-label"><i class="bi bi-geo"></i> Lokasi Kejadian</div>
                <div class="desc-value">
                    <?= !empty($data['alamat_lokasi']) ? nl2br(e($data['alamat_lokasi'])) : $kosong ?>
                </div>
            </div>

            <div class="desc-item">
                <div class="desc-label"><i class="bi bi-calendar-event"></i> Tanggal Kirim</div>
                <div class="desc-value"><?= format_tanggal($data['tanggal_pengaduan']) ?></div>
            </div>

            <div class="desc-item">
                <div class="desc-label"><i class="bi bi-clock-history"></i> Terakhir Diperbarui</div>
                <div class="desc-value"><?= format_tanggal($data['tanggal_update']) ?></div>
            </div>

        </div>

    </div>

</div>


<!-- ================= ISI PENGADUAN ================= -->
<div class="card card-pad mb-3">

    <div class="card-title"><i class="bi bi-chat-left-text"></i> Isi Pengaduan</div>

    <div class="isi-laporan"><?= e($data['isi_pengaduan']) ?></div>

</div>


<!-- ================= FOTO BUKTI ================= -->
<div class="card card-pad mb-3">

    <div class="card-title"><i class="bi bi-image"></i> Foto Bukti</div>

    <?php if ($ada_foto): ?>
        <a href="<?= e($url_bukti_objek) ?>" target="_blank" rel="noopener">
            <img
                src="<?= e($url_bukti_objek) ?>"
                alt="Foto bukti pengaduan"
                class="foto-bukti">
        </a>
        <p class="hint mt-2">
            <i class="bi bi-box-arrow-up-right"></i>
            Klik foto untuk membuka ukuran penuh.
        </p>
    <?php else: ?>
        <div class="empty-state compact">
            <div class="empty-icon">
                <i class="bi bi-image"></i>
            </div>
            <p class="mb-0">Tidak ada foto bukti yang dilampirkan.</p>
        </div>
    <?php endif; ?>

</div>


<!-- ================= RIWAYAT TANGGAPAN ================= -->
<div class="card card-pad mb-3">

    <div class="card-title">
        <i class="bi bi-reply"></i> Riwayat Tanggapan
        <span class="badge badge-purple no-dot ml-auto">
            <?= sdb_num_rows($query_tanggapan) ?> tanggapan
        </span>
    </div>

    <?php if (sdb_num_rows($query_tanggapan) > 0): ?>

        <ul class="timeline">

            <?php while ($t = sdb_fetch_assoc($query_tanggapan)): ?>

                <li class="timeline-item">

                    <div class="timeline-dot">
                        <i class="bi bi-person-fill"></i>
                    </div>

                    <div class="timeline-head">
                        <span class="timeline-name"><?= e($t['nama_admin']) ?></span>
                        <span class="timeline-date">
                            <i class="bi bi-clock"></i>
                            <?= format_tanggal($t['tanggal_tanggapan']) ?>
                        </span>
                    </div>

                    <div class="timeline-text"><?= e($t['tanggapan']) ?></div>

                </li>

            <?php endwhile; ?>

        </ul>

    <?php else: ?>

        <div class="d-flex items-center gap-2 text-muted">
            <i class="bi bi-chat-square-text"></i>
            Belum ada tanggapan dari admin.
        </div>

    <?php endif; ?>

</div>


<div class="form-actions">
    <a href="ubah_status.php?id=<?= (int) $data['id_pengaduan'] ?>" class="btn btn-primary">
        <i class="bi bi-arrow-repeat"></i> Ubah Status / Beri Tanggapan
    </a>
    <a href="index.php" class="btn btn-light">Kembali ke Daftar</a>
</div>


<?php require __DIR__ . '/../partials/footer.php'; ?>
