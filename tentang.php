<?php

// ==================================================
// TENTANG / PROFIL DESA (PUBLIK)
// ==================================================

$root = '';
require_once __DIR__ . '/partials/init.php';


// --------------------------------------------------
// Ambil profil desa (id pertama = profil resmi desa)
// --------------------------------------------------

$query_profil = sdb_query(
    $koneksi,
    "SELECT *
     FROM profil_desa
     WHERE id_profil = 1
     LIMIT 1"
);

$profil = sdb_fetch_assoc($query_profil);

if (!$profil) {

    $profil = [
        'nama_desa' => 'Desa Kampung Beru',
        'kecamatan' => 'Polombangkeng Timur',
        'kabupaten' => 'Takalar',
        'provinsi'  => 'Sulawesi Selatan',
        'visi'      => '',
        'misi'      => '',
    ];
}

$visi_desa = $profil['visi'] ?? '';
$misi_desa = $profil['misi'] ?? '';

// Pecah misi berdasarkan penomoran "1. ", "2. ", dst.
$misi_list = [];

if ($misi_desa !== '') {

    $misi_list = preg_split(
        '/(?=\d+\.\s)/',
        trim($misi_desa),
        -1,
        PREG_SPLIT_NO_EMPTY
    );
}

// Logo desa
$logo_desa_url = is_file(DIR_IMG . 'logo-desa.png')
    ? 'assets/img/logo-desa.png'
    : '';


// Pengaturan tampilan
$judul      = 'Profil Desa';
$menu_aktif = 'profil';

require __DIR__ . '/partials/kepala.php';
require __DIR__ . '/partials/navbar.php';

?>


<!-- =====================================================
     HERO
===================================================== -->

<section class="about-hero">

    <div class="container">

        <div class="row align-items-center">

            <div class="col-lg-7">

                <span class="about-badge">TENTANG DESA</span>

                <h1 class="fw-bold mb-3"><?= e($profil['nama_desa']) ?></h1>

                <p class="lead text-muted mb-0">
                    Mengenal lebih dekat <?= e($profil['nama_desa']) ?>,
                    Kecamatan <?= e($profil['kecamatan']) ?>,
                    Kabupaten <?= e($profil['kabupaten']) ?>.
                </p>

            </div>

            <div class="col-lg-5 text-center mt-5 mt-lg-0">

                <div class="about-logo-box">
                    <?php if ($logo_desa_url !== ''): ?>
                        <img src="<?= e($logo_desa_url) ?>" alt="Logo <?= e($profil['nama_desa']) ?>">
                    <?php else: ?>
                        <span class="logo-pengganti"><i class="bi bi-mortarboard-fill"></i></span>
                    <?php endif; ?>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     INFORMASI UMUM
===================================================== -->

<section class="py-5">

    <div class="container">

        <div class="text-center mb-5">

            <span class="about-section-label">PROFIL DESA</span>
            <h2 class="fw-bold mt-2 about-section-title">Mengenal <?= e($profil['nama_desa']) ?></h2>
            <p class="text-muted">Informasi umum mengenai <?= e($profil['nama_desa']) ?></p>

        </div>

        <div class="row g-4">

            <!-- Informasi desa -->
            <div class="col-lg-6">

                <div class="about-card">

                    <div class="card-body p-4">

                        <div class="d-flex align-items-center mb-4">
                            <div class="about-icon me-3"><i class="bi bi-building fs-4"></i></div>
                            <h4 class="fw-bold mb-0">Informasi Desa</h4>
                        </div>

                        <div class="table-responsive">

                            <table class="table table-borderless about-table">
                                <tbody>

                                    <tr>
                                        <td width="40%"><strong>Nama Desa</strong></td>
                                        <td><?= e($profil['nama_desa'] ?? 'Desa Kampung Beru') ?></td>
                                    </tr>

                                    <tr>
                                        <td><strong>Kecamatan</strong></td>
                                        <td><?= e($profil['kecamatan'] ?? 'Polombangkeng Timur') ?></td>
                                    </tr>

                                    <tr>
                                        <td><strong>Kabupaten</strong></td>
                                        <td><?= e($profil['kabupaten'] ?? 'Takalar') ?></td>
                                    </tr>

                                    <tr>
                                        <td><strong>Provinsi</strong></td>
                                        <td><?= e($profil['provinsi'] ?? 'Sulawesi Selatan') ?></td>
                                    </tr>

                                    <tr>
                                        <td><strong>Negara</strong></td>
                                        <td>Indonesia</td>
                                    </tr>

                                </tbody>
                            </table>

                        </div>

                    </div>

                </div>

            </div>

            <!-- Tentang sistem -->
            <div class="col-lg-6">

                <div class="about-card">

                    <div class="card-body p-4">

                        <div class="d-flex align-items-center mb-4">
                            <div class="about-icon me-3"><i class="bi bi-megaphone fs-4"></i></div>
                            <h4 class="fw-bold mb-0">Tentang SI PA&rsquo;MASE-MASE</h4>
                        </div>

                        <p class="text-muted">
                            <strong>SI PA&rsquo;MASE-MASE</strong> merupakan
                            Sistem Pengelolaan Pengaduan Masyarakat yang dirancang
                            untuk membantu masyarakat <?= e($profil['nama_desa']) ?>
                            dalam menyampaikan aspirasi, keluhan, maupun laporan
                            mengenai berbagai permasalahan yang terjadi di lingkungan desa.
                        </p>

                        <p class="text-muted mb-0">
                            Melalui sistem ini, masyarakat dapat menyampaikan pengaduan
                            secara lebih mudah, cepat, dan terstruktur serta dapat
                            memantau perkembangan pengaduan yang telah disampaikan.
                        </p>

                    </div>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     VISI
===================================================== -->

<section class="vision-about-section">

    <div class="container">

        <div class="text-center mb-5">

            <span class="about-section-label">ARAH PEMBANGUNAN DESA</span>
            <h2 class="fw-bold mt-2 about-section-title">Visi Desa</h2>
            <p class="text-muted">Visi <?= e($profil['nama_desa']) ?></p>

        </div>

        <div class="row justify-content-center">

            <div class="col-lg-10">

                <div class="vision-about-card text-center">

                    <div class="vision-about-icon">
                        <i class="bi bi-eye fs-3"></i>
                    </div>

                    <h3 class="fw-bold">Visi <?= e($profil['nama_desa']) ?></h3>

                    <?php if ($visi_desa !== ''): ?>
                        <p class="vision-text mt-3"><?= e($visi_desa) ?></p>
                    <?php else: ?>
                        <p class="text-muted mt-3 mb-0">Data visi desa belum tersedia.</p>
                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     MISI
===================================================== -->

<section class="py-5">

    <div class="container">

        <div class="text-center mb-5">

            <span class="about-section-label">ARAH PEMBANGUNAN</span>
            <h2 class="fw-bold mt-2 about-section-title">Misi Desa</h2>
            <p class="text-muted">Arah dan tujuan pembangunan <?= e($profil['nama_desa']) ?></p>

        </div>

        <div class="row g-4">

            <?php if (!empty($misi_list)): ?>

                <?php
                $nomor_misi = 1;
                foreach ($misi_list as $misi):

                    $misi = trim($misi);

                    if ($misi === '') {
                        continue;
                    }

                    // Buang penomoran "1. " di awal teks
                    $misi_text = preg_replace('/^\d+\.\s*/', '', $misi);
                ?>

                    <div class="col-md-6 col-lg-4">

                        <div class="mission-about-card">

                            <div class="d-flex align-items-start gap-3">

                                <div class="mission-number"><?= $nomor_misi ?></div>

                                <div>
                                    <h5 class="fw-bold mb-2">Misi <?= $nomor_misi ?></h5>
                                    <p><?= e($misi_text) ?></p>
                                </div>

                            </div>

                        </div>

                    </div>

                <?php
                    $nomor_misi++;
                endforeach;
                ?>

            <?php else: ?>

                <div class="col-12">

                    <div class="text-center py-4 misi-kosong">
                        <i class="bi bi-info-circle fs-2"></i>
                        <p class="text-muted mt-2 mb-0">Data misi desa belum tersedia.</p>
                    </div>

                </div>

            <?php endif; ?>

        </div>

    </div>

</section>


<!-- =====================================================
     MANFAAT SISTEM
===================================================== -->

<section class="benefit-section">

    <div class="container">

        <div class="text-center mb-5">
            <h2 class="fw-bold about-section-title">Manfaat SI PA&rsquo;MASE-MASE</h2>
            <p class="text-muted">Bersama membangun desa melalui pelayanan yang lebih baik</p>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="benefit-item text-center">
                    <div class="benefit-icon"><i class="bi bi-send fs-2"></i></div>
                    <h5 class="fw-bold">Pengaduan Mudah</h5>
                    <p class="text-muted">Masyarakat dapat menyampaikan pengaduan dengan mudah melalui sistem.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="benefit-item text-center">
                    <div class="benefit-icon"><i class="bi bi-search fs-2"></i></div>
                    <h5 class="fw-bold">Pantau Pengaduan</h5>
                    <p class="text-muted">Masyarakat dapat mengetahui perkembangan pengaduan menggunakan nomor pengaduan.</p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="benefit-item text-center">
                    <div class="benefit-icon"><i class="bi bi-people fs-2"></i></div>
                    <h5 class="fw-bold">Partisipasi Masyarakat</h5>
                    <p class="text-muted">Mendorong masyarakat untuk ikut berpartisipasi dalam pembangunan dan pelayanan desa.</p>
                </div>
            </div>

        </div>

    </div>

</section>


<?php require __DIR__ . '/partials/footer.php'; ?>
<?php require __DIR__ . '/partials/kaki.php'; ?>
