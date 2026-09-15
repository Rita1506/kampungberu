<?php

// ==================================================
// BERANDA PUBLIK — SI PA'MASE-MASE
// ==================================================

$root = '';
require_once __DIR__ . '/partials/init.php';


// --------------------------------------------------
// Data profil desa
// --------------------------------------------------

$profil = ambil_profil_desa($koneksi);

$nama_desa   = $profil['nama_desa'];
$kecamatan   = $profil['kecamatan'];
$kabupaten   = $profil['kabupaten'];
$kepala_desa = $profil['kepala_desa'];
$ibu_desa    = $profil['ibu_desa'];

// Lokasi foto kepala & ibu desa (profil/ lalu folder img utama)
$foto_kepala = cari_foto_publik($profil['foto_desa'], ['profil/', '']);
$foto_ibu    = cari_foto_publik($profil['foto_ibu_desa'], ['profil/', '']);


// --------------------------------------------------
// Kategori pengaduan aktif
// --------------------------------------------------

$query_kategori = sdb_query(
    $koneksi,
    "SELECT * FROM kategori
     WHERE status = 'aktif'
     ORDER BY id_kategori ASC"
);


// --------------------------------------------------
// Enam dokumentasi galeri terbaru
// --------------------------------------------------

$query_galeri = sdb_query(
    $koneksi,
    "SELECT * FROM galeri
     WHERE status = 'aktif'
     ORDER BY id_galeri DESC
     LIMIT 6"
);


// Pengaturan tampilan
$judul      = 'Beranda';
$menu_aktif = 'beranda';

require __DIR__ . '/partials/kepala.php';
require __DIR__ . '/partials/navbar.php';

?>


<!-- =====================================================
     HERO
===================================================== -->

<section class="hero-section">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-7">

                <div class="hero-content">

                    <span class="hero-badge">
                        <i class="bi bi-megaphone-fill"></i>
                        Pelayanan Pengaduan Masyarakat
                    </span>

                    <h1>SI PA'MASE-MASE</h1>
                    <h2>Sistem Pengelolaan Pengaduan Masyarakat</h2>

                    <p>
                        Sampaikan pengaduan, aspirasi, dan permasalahan
                        yang terjadi di lingkungan <?= e($nama_desa) ?>
                        untuk mendukung pelayanan dan pembangunan desa
                        yang lebih baik.
                    </p>

                    <div class="hero-buttons">
                        <a href="pengaduan.php" class="btn btn-primary-custom">
                            <i class="bi bi-send-fill"></i> Buat Pengaduan
                        </a>
                        <a href="cek_status.php" class="btn btn-outline-custom">
                            <i class="bi bi-search"></i> Cek Status
                        </a>
                    </div>

                </div>

            </div>

            <div class="col-lg-5 text-center mt-5 mt-lg-0">

                <div class="hero-card">
                    <div class="hero-icon">
                        <i class="bi bi-chat-square-text-fill"></i>
                    </div>
                    <h4>Suara Masyarakat</h4>
                    <p>
                        Setiap pengaduan merupakan bagian
                        dari upaya bersama untuk membangun
                        <?= e($nama_desa) ?>.
                    </p>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     TIGA LANGKAH INFORMASI
===================================================== -->

<section class="info-section">

    <div class="container">

        <div class="row g-4">

            <div class="col-md-4">
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-megaphone-fill"></i></div>
                    <h5>Sampaikan Pengaduan</h5>
                    <p>Laporkan permasalahan yang terjadi di lingkungan <?= e($nama_desa) ?>.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-clock-history"></i></div>
                    <h5>Pantau Pengaduan</h5>
                    <p>Gunakan nomor pengaduan untuk mengetahui perkembangan laporan.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="info-card">
                    <div class="info-icon"><i class="bi bi-people-fill"></i></div>
                    <h5>Bersama Membangun Desa</h5>
                    <p>Partisipasi masyarakat membantu mewujudkan pelayanan desa yang lebih baik.</p>
                </div>
            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     KATEGORI PENGADUAN
===================================================== -->

<section class="category-section" id="kategori">

    <div class="container">

        <div class="section-heading text-center">
            <span class="section-label">JENIS PENGADUAN</span>
            <h2>Kategori Pengaduan</h2>
            <p>Pilih bidang yang sesuai dengan pengaduan atau permasalahan yang ingin disampaikan.</p>
        </div>

        <div class="row g-4">

            <?php if ($query_kategori && sdb_num_rows($query_kategori) > 0): ?>

                <?php while ($kategori = sdb_fetch_assoc($query_kategori)):

                    $path_ikon = cari_foto_publik($kategori['ikon'] ?? '', ['kategori/']);

                ?>

                    <div class="col-12 col-sm-6 col-lg-4">

                        <div class="category-card">

                            <div class="category-icon">

                                <?php if ($path_ikon !== ''): ?>
                                    <img src="<?= e($root . $path_ikon) ?>"
                                         alt="<?= e($kategori['nama_kategori']) ?>"
                                         class="kategori-logo">
                                <?php else: ?>
                                    <i class="bi bi-folder-fill"></i>
                                <?php endif; ?>

                            </div>

                            <h4><?= e($kategori['nama_kategori']) ?></h4>

                            <p><?= !empty($kategori['deskripsi'])
                                    ? e($kategori['deskripsi'])
                                    : 'Kategori pengaduan masyarakat ' . e($nama_desa) . '.' ?></p>

                            <a href="pengaduan.php?kategori=<?= (int) $kategori['id_kategori'] ?>"
                               class="category-link">
                                Buat Pengaduan <i class="bi bi-arrow-right"></i>
                            </a>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="col-12 text-center">
                    <p class="text-muted">Belum ada kategori pengaduan.</p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =====================================================
     PEMERINTAH DESA
===================================================== -->

<section class="kepala-desa-section" id="pemerintah-desa">

    <div class="container">

        <div class="section-heading text-center mb-5">
            <span class="section-label">PEMERINTAH DESA</span>
            <h2>Pemerintah Desa</h2>
            <p>Kepala Desa dan Ibu Desa <?= e($nama_desa) ?>.</p>
        </div>

        <div class="row g-4 justify-content-center">

            <!-- Kepala desa -->
            <div class="col-md-6 col-lg-5">

                <div class="pemerintah-desa-card">

                    <div class="pemerintah-desa-photo">
                        <?php if ($foto_kepala !== ''): ?>
                            <img src="<?= e($root . $foto_kepala) ?>"
                                 alt="Foto <?= e($kepala_desa) ?>">
                        <?php else: ?>
                            <div class="pemerintah-desa-empty">
                                <i class="bi bi-person-fill"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pemerintah-desa-info">
                        <span class="label-pemerintah">KEPALA DESA</span>
                        <h3><?= $kepala_desa !== '' ? e($kepala_desa) : 'Nama Kepala Desa' ?></h3>
                        <p>Kepala Desa <?= e($nama_desa) ?></p>
                        <div class="pemerintah-desa-location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span><?= e($kecamatan) ?>, <?= e($kabupaten) ?></span>
                        </div>
                    </div>

                </div>

            </div>

            <!-- Ibu desa -->
            <div class="col-md-6 col-lg-5">

                <div class="pemerintah-desa-card">

                    <div class="pemerintah-desa-photo">
                        <?php if ($foto_ibu !== ''): ?>
                            <img src="<?= e($root . $foto_ibu) ?>"
                                 alt="Foto <?= e($ibu_desa) ?>">
                        <?php else: ?>
                            <div class="pemerintah-desa-empty">
                                <i class="bi bi-person-heart"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="pemerintah-desa-info">
                        <span class="label-pemerintah">IBU DESA</span>
                        <h3><?= $ibu_desa !== '' ? e($ibu_desa) : 'Nama Ibu Desa' ?></h3>
                        <p>Ibu Desa <?= e($nama_desa) ?></p>
                        <div class="pemerintah-desa-location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span><?= e($kecamatan) ?>, <?= e($kabupaten) ?></span>
                        </div>
                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     TENTANG SISTEM
===================================================== -->

<section class="about-section">

    <div class="container">

        <div class="row align-items-center g-5">

            <div class="col-lg-6">
                <div class="about-image">
                    <div class="about-logo">
                        <?php if (is_file(DIR_IMG . 'logotakalar.jpe')): ?>
                            <img src="assets/img/logotakalar.jpe" alt="Logo Kabupaten Takalar">
                        <?php else: ?>
                            <span class="logo-pengganti logo-pengganti-besar">
                                <i class="bi bi-megaphone-fill"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">

                <span class="section-label">TENTANG SISTEM</span>
                <h2>SI PA'MASE-MASE</h2>

                <p>
                    SI PA'MASE-MASE merupakan sistem pengelolaan
                    pengaduan masyarakat <?= e($nama_desa) ?> yang
                    dirancang untuk memudahkan masyarakat dalam
                    menyampaikan berbagai pengaduan dan aspirasi.
                </p>

                <p>
                    Melalui sistem ini, pengaduan dapat disampaikan
                    secara lebih mudah dan terorganisir sehingga
                    pemerintah desa dapat melakukan pengelolaan,
                    tindak lanjut, dan pemantauan terhadap setiap
                    laporan masyarakat.
                </p>

                <a href="tentang.php" class="btn btn-primary-custom">
                    Selengkapnya <i class="bi bi-arrow-right"></i>
                </a>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     GALERI KEGIATAN
===================================================== -->

<section class="galeri-section">

    <div class="container">

        <div class="section-heading text-center mb-5">
            <span class="section-label">DOKUMENTASI</span>
            <h2>Galeri Kegiatan Desa</h2>
            <p>Dokumentasi kegiatan <?= e($nama_desa) ?>.</p>
        </div>

        <div class="row g-4">

            <?php if ($query_galeri && sdb_num_rows($query_galeri) > 0): ?>

                <?php while ($galeri = sdb_fetch_assoc($query_galeri)):

                    $foto_galeri = cari_foto_publik($galeri['foto'] ?? '', ['galeri/', '']);

                ?>

                    <div class="col-md-6 col-lg-4">

                        <div class="galeri-card">

                            <div class="galeri-image">
                                <?php if ($foto_galeri !== ''): ?>
                                    <img src="<?= e($root . $foto_galeri) ?>"
                                         alt="<?= e($galeri['judul']) ?>">
                                <?php else: ?>
                                    <i class="bi bi-image"></i>
                                <?php endif; ?>
                            </div>

                            <div class="galeri-content">
                                <h4><?= e($galeri['judul']) ?></h4>
                                <p><?= e($galeri['deskripsi'] ?? '') ?></p>
                            </div>

                        </div>

                    </div>

                <?php endwhile; ?>

            <?php else: ?>

                <div class="col-12 text-center">
                    <p class="text-muted">Belum ada dokumentasi kegiatan desa.</p>
                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =====================================================
     AJAKAN (CALL TO ACTION)
===================================================== -->

<section class="cta-section">

    <div class="container">

        <div class="cta-box text-center">

            <div class="cta-icon">
                <i class="bi bi-chat-heart-fill"></i>
            </div>

            <h2>Punya Pengaduan atau Aspirasi?</h2>
            <p>Sampaikan kepada kami melalui SI PA'MASE-MASE.</p>

            <a href="pengaduan.php" class="btn btn-white-custom">
                <i class="bi bi-send-fill"></i>
                Buat Pengaduan Sekarang
                <i class="bi bi-arrow-right"></i>
            </a>

        </div>

    </div>

</section>


<?php require __DIR__ . '/partials/footer.php'; ?>
<?php require __DIR__ . '/partials/kaki.php'; ?>
