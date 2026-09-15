<?php

// ==================================================
// CEK STATUS PENGADUAN (PUBLIK)
// Warga memasukkan nomor pengaduan untuk memantau
// perkembangan laporannya.
// ==================================================

$root = '';
require_once __DIR__ . '/partials/init.php';

$hasil            = null;
$pesan            = '';
$nomor_dicari     = '';
$tanggapan_admin  = [];


// --------------------------------------------------
// Nomor yang baru saja berhasil dikirim
// (diarahkan dari proses/simpan_pengaduan.php)
// --------------------------------------------------

$nomor_berhasil = trim($_GET['nomor'] ?? '');
$berhasil       = ($nomor_berhasil !== '');

if ($berhasil) {
    $nomor_dicari = $nomor_berhasil;
}


// --------------------------------------------------
// Pencarian saat form dikirim
// --------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    $nomor_dicari = trim($_POST['nomor_pengaduan'] ?? '');

    if ($nomor_dicari === '') {

        $pesan = 'Silakan masukkan nomor pengaduan.';

    } else {

        $stmt = sdb_prepare(
            $koneksi,
            "SELECT
                p.*,
                m.nama_lengkap,
                k.nama_kategori
             FROM pengaduan p
             INNER JOIN masyarakat m ON p.id_masyarakat = m.id_masyarakat
             INNER JOIN kategori   k ON p.id_kategori   = k.id_kategori
             WHERE p.nomor_pengaduan = ?
             LIMIT 1"
        );

        sdb_stmt_bind_param($stmt, 's', $nomor_dicari);
        sdb_stmt_execute($stmt);
        $result = sdb_stmt_get_result($stmt);

        if (sdb_num_rows($result) > 0) {

            $hasil = sdb_fetch_assoc($result);

            // Semua tanggapan admin untuk pengaduan ini
            $id_pengaduan_hasil = (int) $hasil['id_pengaduan'];

            $stmt_t = sdb_prepare(
                $koneksi,
                "SELECT tanggapan, id_admin
                 FROM tanggapan
                 WHERE id_pengaduan = ?
                 ORDER BY id_admin DESC"
            );

            sdb_stmt_bind_param($stmt_t, 'i', $id_pengaduan_hasil);
            sdb_stmt_execute($stmt_t);
            $res_t = sdb_stmt_get_result($stmt_t);

            while ($row_t = sdb_fetch_assoc($res_t)) {
                $tanggapan_admin[] = $row_t;
            }

        } else {

            $pesan = 'Nomor pengaduan tidak ditemukan. Silakan periksa kembali nomor pengaduan Anda.';
        }

        sdb_stmt_close($stmt);
    }
}


// Pengaturan tampilan
$judul      = 'Cek Status Pengaduan';
$menu_aktif = 'cek-status';

require __DIR__ . '/partials/kepala.php';
require __DIR__ . '/partials/navbar.php';

?>


<!-- =====================================================
     KEPALA HALAMAN
===================================================== -->

<section class="py-5 cek-status-header">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-lg-8 text-center">

                <span class="badge rounded-pill pill-ungu mb-3">CEK STATUS PENGADUAN</span>

                <h1 class="fw-bold mb-3">Pantau Pengaduan Anda</h1>

                <p class="lead text-muted">
                    Masukkan nomor pengaduan untuk mengetahui
                    perkembangan laporan yang telah Anda sampaikan
                    kepada Pemerintah Desa Kampung Beru.
                </p>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     PESAN PENGADUAN BERHASIL DIKIRIM
===================================================== -->

<?php if ($berhasil && $nomor_berhasil !== ''): ?>

<section class="py-4">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-lg-7">

                <div class="card border-0 shadow-sm sukses-kirim-card">

                    <div class="card-body text-center p-4">

                        <div class="mb-3">
                            <div class="lingkaran-ungu mx-auto">
                                <i class="bi bi-check-lg fs-2"></i>
                            </div>
                        </div>

                        <h4 class="fw-bold mb-2 ungu-judul">Pengaduan Berhasil Dikirim</h4>

                        <p class="text-muted mb-3">Pengaduan Anda telah berhasil dikirim ke sistem.</p>

                        <p class="fw-semibold mb-2">
                            Salin kode berikut, penting untuk mengecek status pengaduan Anda:
                        </p>

                        <div class="mb-3">
                            <div id="nomorBerhasil" class="fw-bold kode-nomor-box">
                                <?= e($nomor_berhasil) ?>
                            </div>
                        </div>

                        <button type="button" id="btnSalinKode" class="btn btn-salin"
                                data-salin="#nomorBerhasil">
                            <i class="bi bi-clipboard me-1"></i> Salin Kode
                        </button>

                        <p class="text-muted small mt-3 mb-0">
                            Simpan kode ini dengan baik. Gunakan kode tersebut
                            pada halaman <strong>Cek Status Pengaduan</strong>.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<?php endif; ?>


<!-- =====================================================
     FORM PENCARIAN
===================================================== -->

<section class="py-5 cek-status-form-section">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-lg-7 cek-status-form-card">

                <div class="card border-0 shadow-sm">

                    <div class="card-body p-4 p-lg-5">

                        <div class="text-center mb-4">

                            <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center cek-status-search-icon">
                                <i class="bi bi-search fs-2"></i>
                            </div>

                            <h4 class="fw-bold cek-status-form-title">Cek Status Pengaduan</h4>

                            <p class="text-muted mb-0 cek-status-form-description">
                                Masukkan nomor pengaduan yang Anda terima setelah mengirim laporan.
                            </p>

                        </div>

                        <?php if ($pesan !== ''): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-circle-fill me-2"></i>
                                <div><?= e($pesan) ?></div>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="">

                            <div class="mb-3">

                                <label for="nomor_pengaduan" class="form-label fw-semibold">
                                    Nomor Pengaduan
                                </label>

                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-hash"></i></span>
                                    <input type="text"
                                           name="nomor_pengaduan"
                                           id="nomor_pengaduan"
                                           class="form-control"
                                           placeholder="Contoh: ADU-20260909-0001"
                                           value="<?= e($nomor_dicari) ?>"
                                           required>
                                </div>

                                <small class="text-muted d-block mt-1">
                                    Masukkan nomor pengaduan sesuai dengan nomor yang Anda terima.
                                </small>

                            </div>

                            <button type="submit" class="btn btn-cek-status-soft w-100 py-2">
                                <i class="bi bi-search me-1"></i> Cek Status Pengaduan
                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     HASIL PENGADUAN
===================================================== -->

<?php if ($hasil): ?>

<section class="pb-5 cek-status-result-section">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-lg-9 cek-status-result-card">

                <div class="card border-0 shadow-sm">

                    <!-- Kepala hasil -->
                    <div class="cek-status-result-header">

                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">

                            <div>
                                <small class="opacity-75 d-block">NOMOR PENGADUAN</small>
                                <h4 class="fw-bold mb-0"><?= e($hasil['nomor_pengaduan']) ?></h4>
                            </div>

                            <div>
                                <span class="badge bg-<?= status_warna_publik($hasil['status']) ?> fs-6 px-3 py-2">
                                    <i class="bi <?= status_ikon_publik($hasil['status']) ?>"></i>
                                    <?= status_nama_publik($hasil['status']) ?>
                                </span>
                            </div>

                        </div>

                    </div>

                    <div class="card-body cek-status-result-body">

                        <!-- Informasi pengadu -->
                        <div class="mb-4">

                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-person me-2"></i> Informasi Pengadu
                            </h5>

                            <div class="row g-3">

                                <div class="col-md-6">
                                    <div class="p-3 rounded cek-status-info-box">
                                        <small class="text-muted d-block">Nama</small>
                                        <strong><?= e($hasil['nama_lengkap']) ?></strong>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 rounded cek-status-info-box">
                                        <small class="text-muted d-block">Kategori</small>
                                        <strong><?= e($hasil['nama_kategori']) ?></strong>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <hr>

                        <!-- Detail -->
                        <div class="mb-4">

                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-file-text me-2"></i> Detail Pengaduan
                            </h5>

                            <div class="mb-3">
                                <small class="text-muted d-block">Judul Pengaduan</small>
                                <h5 class="fw-semibold"><?= e($hasil['judul_pengaduan']) ?></h5>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted d-block mb-1">Isi Pengaduan</small>
                                <div class="p-3 rounded cek-status-info-box">
                                    <?= nl2br(e($hasil['isi_pengaduan'])) ?>
                                </div>
                            </div>

                            <?php if (!empty($hasil['alamat_lokasi'])): ?>
                                <div class="mb-3">
                                    <small class="text-muted d-block">Lokasi Permasalahan</small>
                                    <p class="mb-0">
                                        <i class="bi bi-geo-alt me-1"></i>
                                        <?= nl2br(e($hasil['alamat_lokasi'])) ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Tanggapan admin -->
                        <?php if (!empty($tanggapan_admin)): ?>

                        <div class="mb-4">

                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-chat-left-text me-2"></i> Tanggapan Admin
                            </h5>

                            <?php foreach ($tanggapan_admin as $tanggapan): ?>

                                <div class="p-3 rounded mb-3 tanggapan-box">

                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                        <strong class="tanggapan-admin">
                                            <i class="bi bi-person-circle me-1"></i> Admin
                                        </strong>
                                    </div>

                                    <div class="tanggapan-isi">
                                        <?= nl2br(e($tanggapan['tanggapan'])) ?>
                                    </div>

                                </div>

                            <?php endforeach; ?>

                        </div>

                        <hr>

                        <?php endif; ?>

                        <!-- Perkembangan -->
                        <div class="mb-4">

                            <h5 class="fw-bold mb-4">
                                <i class="bi bi-activity me-2"></i> Perkembangan Pengaduan
                            </h5>

                            <div class="status-timeline">

                                <div class="d-flex mb-4">
                                    <div class="me-3 text-center">
                                        <div class="cek-status-timeline-icon tl-biru">
                                            <i class="bi bi-file-earmark-text"></i>
                                        </div>
                                    </div>
                                    <div class="cek-status-timeline-text">
                                        <h6 class="fw-bold mb-1">Pengaduan Diterima</h6>
                                        <p class="text-muted mb-0">Pengaduan telah berhasil diterima oleh sistem.</p>
                                    </div>
                                </div>

                                <div class="d-flex mb-4">
                                    <div class="me-3 text-center">
                                        <div class="cek-status-timeline-icon tl-cyan">
                                            <i class="bi bi-check-circle"></i>
                                        </div>
                                    </div>
                                    <div class="cek-status-timeline-text">
                                        <h6 class="fw-bold mb-1">Diverifikasi</h6>
                                        <p class="text-muted mb-0">Pengaduan telah diperiksa oleh petugas desa.</p>
                                    </div>
                                </div>

                                <div class="d-flex mb-4">
                                    <div class="me-3 text-center">
                                        <div class="cek-status-timeline-icon tl-kuning">
                                            <i class="bi bi-hourglass-split"></i>
                                        </div>
                                    </div>
                                    <div class="cek-status-timeline-text">
                                        <h6 class="fw-bold mb-1">Sedang Diproses</h6>
                                        <p class="text-muted mb-0">Pengaduan sedang ditindaklanjuti oleh pihak terkait.</p>
                                    </div>
                                </div>

                                <div class="d-flex">
                                    <div class="me-3 text-center">
                                        <div class="cek-status-timeline-icon tl-hijau">
                                            <i class="bi bi-check-circle-fill"></i>
                                        </div>
                                    </div>
                                    <div class="cek-status-timeline-text">
                                        <h6 class="fw-bold mb-1">Selesai</h6>
                                        <p class="text-muted mb-0">Pengaduan telah selesai ditindaklanjuti.</p>
                                    </div>
                                </div>

                            </div>

                        </div>

                        <hr>

                        <!-- Tanggal -->
                        <div class="row g-3">

                            <div class="col-md-6">
                                <div class="p-3 rounded cek-status-info-box">
                                    <small class="text-muted d-block">Tanggal Pengaduan</small>
                                    <strong><?= date('d-m-Y H:i', strtotime($hasil['tanggal_pengaduan'])) ?></strong>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="p-3 rounded cek-status-info-box">
                                    <small class="text-muted d-block">Terakhir Diperbarui</small>
                                    <strong>
                                        <?= !empty($hasil['tanggal_update'])
                                            ? date('d-m-Y H:i', strtotime($hasil['tanggal_update']))
                                            : '-' ?>
                                    </strong>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>

<?php endif; ?>


<!-- =====================================================
     BANTUAN
===================================================== -->

<section class="py-5 cek-status-help-section">

    <div class="container">

        <div class="row justify-content-center">

            <div class="col-lg-8 text-center">

                <i class="bi bi-question-circle fs-1"></i>

                <h4 class="fw-bold mt-3">Belum memiliki nomor pengaduan?</h4>

                <p class="text-muted">
                    Silakan buat pengaduan terlebih dahulu melalui tombol berikut.
                </p>

                <a href="pengaduan.php" class="btn btn-buat-pengaduan-soft px-4">
                    <i class="bi bi-megaphone me-1"></i> Buat Pengaduan
                </a>

            </div>

        </div>

    </div>

</section>


<?php require __DIR__ . '/partials/footer.php'; ?>
<?php require __DIR__ . '/partials/kaki.php'; ?>
