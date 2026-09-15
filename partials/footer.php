<?php

// ==================================================
// footer.php — PENUTUP BAWAH SETIAP HALAMAN PUBLIK
// --------------------------------------------------
// Memakai $profil (hasil ambil_profil_desa()).
// ==================================================

$profil = $profil ?? ambil_profil_desa($koneksi);

$logo_footer = is_file(DIR_IMG . 'logotakalar.jpe')
    ? $root . 'assets/img/logotakalar.jpe'
    : '';

$anchor_pemerintah_f = ($menu_aktif === 'beranda') ? '#pemerintah-desa' : 'index.php#pemerintah-desa';

?>

<footer class="footer">

    <div class="container">

        <div class="row g-4">

            <!-- Brand -->
            <div class="col-lg-5">

                <div class="footer-brand">

                    <div class="footer-logo">
                        <?php if ($logo_footer !== ''): ?>
                            <img src="<?= e($logo_footer) ?>" alt="Logo Kabupaten Takalar">
                        <?php else: ?>
                            <span class="logo-pengganti logo-pengganti-terang"><i class="bi bi-megaphone-fill"></i></span>
                        <?php endif; ?>
                    </div>

                    <div>
                        <h4>SI PA'MASE-MASE</h4>
                        <p>Sistem Pengelolaan Pengaduan Masyarakat</p>
                    </div>

                </div>

                <p class="footer-description">
                    Sistem pengelolaan pengaduan masyarakat
                    Desa Kampung Beru, Kecamatan Polombangkeng Timur,
                    Kabupaten Takalar.
                </p>

            </div>


            <!-- Menu -->
            <div class="col-lg-3">

                <h5>Menu</h5>

                <ul class="footer-menu">
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="tentang.php">Profil Desa</a></li>
                    <li><a href="<?= e($anchor_pemerintah_f) ?>">Pemerintah Desa</a></li>
                    <li><a href="pengaduan.php">Buat Pengaduan</a></li>
                    <li><a href="cek_status.php">Cek Status</a></li>
                </ul>

            </div>


            <!-- Informasi -->
            <div class="col-lg-4">

                <h5>Informasi</h5>

                <ul class="footer-info">

                    <li>
                        <i class="bi bi-geo-alt-fill"></i>
                        <span>
                            Desa Kampung Beru, Kecamatan Polombangkeng Timur,
                            Kabupaten Takalar
                        </span>
                    </li>

                    <li>
                        <i class="bi bi-megaphone-fill"></i>
                        <span>Sampaikan pengaduan Anda melalui SI PA'MASE-MASE.</span>
                    </li>

                    <?php if (!empty($profil['email'])): ?>
                        <li>
                            <i class="bi bi-envelope-fill"></i>
                            <span><?= e($profil['email']) ?></span>
                        </li>
                    <?php endif; ?>

                    <?php if (!empty($profil['no_telepon'])): ?>
                        <li>
                            <i class="bi bi-telephone-fill"></i>
                            <span><?= e($profil['no_telepon']) ?></span>
                        </li>
                    <?php endif; ?>

                </ul>

            </div>

        </div>

        <hr>

        <div class="footer-bottom text-center">
            <p>&copy; <?= date('Y') ?> SI PA'MASE-MASE. Desa Kampung Beru. Semua Hak Dilindungi.</p>
        </div>

    </div>

</footer>
