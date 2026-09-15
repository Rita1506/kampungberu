<?php

// ==================================================
// LOGIN ADMIN
// ==================================================

$root = '../';
require_once __DIR__ . '/../partials/init.php';

// Sudah login (cookie token valid)? langsung ke dashboard
if (auth_admin_dari_cookie($koneksi) !== null) {
    header('Location: ../admin/dashboard.php');
    exit;
}

// Pesan error dari proses_login.php (dikirim lewat URL)
$error = (string) ($_GET['error'] ?? '');

// Notifikasi setelah logout
$sudah_logout = isset($_GET['logout']);


// Pengaturan tampilan
$judul                 = 'Login Admin';
$menu_aktif            = '';
$kelas_body_tambahan   = 'halaman-login';

require __DIR__ . '/../partials/kepala.php';

$logo_login = is_file(DIR_IMG . 'logo-desa.png')
    ? '../assets/img/logo-desa.png'
    : '';

?>

<div class="login-wrapper">

    <div class="login-card">

        <!-- Logo / ikon -->
        <div class="text-center">

            <?php if ($logo_login !== ''): ?>
                <img src="<?= e($logo_login) ?>" alt="Logo Desa" class="login-logo">
            <?php else: ?>
                <div class="login-icon">
                    <i class="bi bi-shield-lock"></i>
                </div>
            <?php endif; ?>

            <h3 class="login-title">Login Admin</h3>

            <p class="login-subtitle">
                SI PA'MASE-MASE<br>
                Sistem Pengelolaan Pengaduan Masyarakat
            </p>

        </div>


        <!-- Pesan setelah logout -->
        <?php if ($sudah_logout): ?>
            <div class="alert alert-success auto-dismiss">
                <i class="bi bi-check-circle"></i>
                <span>Anda berhasil keluar dari panel admin.</span>
                <button type="button" class="alert-close" aria-label="Tutup notifikasi">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        <?php endif; ?>

        <!-- Pesan error -->
        <?php if ($error !== ''): ?>
            <div class="alert alert-danger auto-dismiss">
                <i class="bi bi-exclamation-circle"></i>
                <span><?= e($error) ?></span>
                <button type="button" class="alert-close" aria-label="Tutup notifikasi">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        <?php endif; ?>


        <!-- Form login -->
        <form action="proses_login.php" method="POST">

            <!-- Username -->
            <div class="mb-3">

                <label for="username" class="form-label">Username</label>

                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                    <input type="text"
                           name="username"
                           id="username"
                           class="form-control"
                           placeholder="Masukkan username"
                           required
                           autocomplete="username">
                </div>

            </div>

            <!-- Password -->
            <div class="mb-4">

                <label for="password" class="form-label">Password</label>

                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    <input type="password"
                           name="password"
                           id="password"
                           class="form-control"
                           placeholder="Masukkan password"
                           required
                           autocomplete="current-password">
                    <button type="button"
                            class="btn"
                            data-toggle-password="password"
                            title="Tampilkan password">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>

            </div>

            <button type="submit" class="btn btn-login-penuh">
                <i class="bi bi-box-arrow-in-right"></i> Login
            </button>

        </form>


        <!-- Kembali ke beranda -->
        <div class="text-center mt-4">
            <a href="../index.php" class="back-home">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda
            </a>
        </div>

        <!-- Kaki kartu -->
        <div class="login-footer">
            &copy; <?= date('Y') ?> SI PA'MASE-MASE<br>
            Desa Kampung Beru
        </div>

    </div>

</div>

<?php require __DIR__ . '/../partials/kaki.php'; ?>
