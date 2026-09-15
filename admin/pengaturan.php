<?php

// ==================================================
// PENGATURAN — GANTI PASSWORD ADMIN
// ==================================================

$root = '../';
require_once __DIR__ . '/partials/proteksi.php';

$id_admin = (int) $_SESSION['id_admin'];

$pesan       = '';
$jenis_pesan = '';

// Nilai untuk menjaga fokus; password tidak ditampilkan ulang
$password_lama        = '';
$password_baru        = '';
$konfirmasi_password  = '';


// --------------------------------------------------
// Proses ganti password
// --------------------------------------------------

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {

    $password_lama       = (string) ($_POST['password_lama'] ?? '');
    $password_baru       = (string) ($_POST['password_baru'] ?? '');
    $konfirmasi_password = (string) ($_POST['konfirmasi_password'] ?? '');


    if ($password_lama === '' || $password_baru === '' || $konfirmasi_password === '') {

        $pesan       = 'Semua kolom password wajib diisi.';
        $jenis_pesan = 'danger';

    } elseif (mb_strlen($password_baru) < 6) {

        $pesan       = 'Password baru minimal terdiri dari 6 karakter.';
        $jenis_pesan = 'danger';

    } elseif ($password_baru !== $konfirmasi_password) {

        $pesan       = 'Konfirmasi password baru tidak sesuai.';
        $jenis_pesan = 'danger';

    } else {

        // Ambil password tersimpan
        $stmt = sdb_prepare(
            $koneksi,
            "SELECT password FROM admin
             WHERE id_admin = ?
             LIMIT 1"
        );

        sdb_stmt_bind_param($stmt, 'i', $id_admin);
        sdb_stmt_execute($stmt);

        $data_admin = sdb_fetch_assoc(sdb_stmt_get_result($stmt));

        if (!$data_admin) {

            $pesan       = 'Akun admin tidak ditemukan.';
            $jenis_pesan = 'danger';

        } elseif ($password_lama !== $data_admin['password']) {

            // Cocokkan dengan password tersimpan (mengikuti sistem login yang ada)
            $pesan       = 'Password lama yang dimasukkan salah.';
            $jenis_pesan = 'danger';

        } else {

            $stmt_update = sdb_prepare(
                $koneksi,
                "UPDATE admin SET password = ? WHERE id_admin = ?"
            );

            sdb_stmt_bind_param($stmt_update, 'si', $password_baru, $id_admin);

            if (sdb_stmt_execute($stmt_update)) {

                $pesan       = 'Password berhasil diperbarui. Gunakan password baru saat login berikutnya.';
                $jenis_pesan = 'success';

                // Kosongkan isian setelah berhasil
                $password_lama = $password_baru = $konfirmasi_password = '';

            } else {

                $pesan       = 'Password gagal diperbarui. Silakan coba lagi.';
                $jenis_pesan = 'danger';

            }
        }
    }
}


// Pengaturan tampilan
$judul      = 'Pengaturan';
$menu_aktif = 'pengaturan';

require __DIR__ . '/partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-gear"></i> Pengaturan Akun</h1>
        <p>Kelola keamanan akun administrator Anda.</p>
    </div>

</div>


<?php if ($pesan !== ''): ?>
    <div class="alert alert-<?= $jenis_pesan === 'success' ? 'success' : 'danger' ?> alert-auto-hide">
        <i class="bi <?= $jenis_pesan === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-octagon-fill' ?>"></i>
        <div><?= e($pesan) ?></div>
        <button type="button" class="alert-close" aria-label="Tutup notifikasi">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
<?php endif; ?>


<div class="form-card">

    <div class="card-title">
        <i class="bi bi-shield-lock"></i> Ganti Password
    </div>

    <form method="POST" autocomplete="off">

        <div class="form-grid">

            <!-- Password lama -->
            <div class="field span-2">
                <label for="password_lama">Password Lama <span class="required">*</span></label>
                <input
                    type="password"
                    id="password_lama"
                    name="password_lama"
                    class="input"
                    placeholder="Masukkan password lama Anda"
                    autocomplete="current-password"
                    required>
            </div>

            <!-- Password baru -->
            <div class="field">
                <label for="password_baru">Password Baru <span class="required">*</span></label>
                <input
                    type="password"
                    id="password_baru"
                    name="password_baru"
                    class="input"
                    placeholder="Minimal 6 karakter"
                    autocomplete="new-password"
                    minlength="6"
                    required>
            </div>

            <!-- Konfirmasi -->
            <div class="field">
                <label for="konfirmasi_password">Konfirmasi Password Baru <span class="required">*</span></label>
                <input
                    type="password"
                    id="konfirmasi_password"
                    name="konfirmasi_password"
                    class="input"
                    placeholder="Ulangi password baru"
                    autocomplete="new-password"
                    minlength="6"
                    required>
            </div>

            <div class="field span-2">
                <div class="password-tip">
                    <i class="bi bi-info-circle"></i>
                    <span>Untuk keamanan, gunakan minimal 6 karakter yang menggabungkan huruf dan angka. Jangan bagikan password Anda.</span>
                </div>
            </div>

        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save"></i> Simpan Password
            </button>
        </div>

    </form>

</div>


<?php require __DIR__ . '/partials/footer.php'; ?>
