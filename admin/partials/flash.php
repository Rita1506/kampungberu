<?php

// ==================================================
// flash.php — PENERIMA PESAN NOTIFIKASI
// --------------------------------------------------
// Dulu file proses melempar ?pesan=tambah_berhasil
// tetapi tidak ada yang menampilkannya. Partial ini
// menerjemahkan kode pesan menjadi alert yang rapi.
//
// Cukup dipanggil satu kali di dalam area .page.
// Alert akan menghilang otomatis setelah 4 detik
// (lihat initAutoHideAlert di assets/js/script.js).
// ==================================================

$peta_pesan = [

    'tambah_berhasil' => ['success', 'Data baru berhasil ditambahkan.'],
    'edit_berhasil'   => ['success', 'Perubahan berhasil disimpan.'],
    'hapus_berhasil'  => ['success', 'Data berhasil dihapus.'],
    'status_berhasil' => ['success', 'Status pengaduan berhasil diperbarui.'],
    'profil_berhasil' => ['success', 'Profil desa berhasil disimpan.'],
    'profil_validasi' => ['warning', 'Data desa, kecamatan, kabupaten, dan provinsi wajib diisi.'],
    'password_berhasil' => ['success', 'Password berhasil diperbarui.'],
    'berhasil'        => ['success', 'Data berhasil diproses.'],

    'gagal'           => ['danger',  'Terjadi kesalahan. Silakan coba lagi.'],
    'hapus_gagal'     => ['danger',  'Data gagal dihapus.'],
    'profil_gagal'    => ['danger',  'Profil desa gagal disimpan.'],
    'password_gagal'  => ['danger',  'Password gagal diperbarui.'],
    'query_gagal'     => ['danger',  'Gagal terhubung ke database.'],

    'data_tidak_ditemukan' => ['warning', 'Data yang dituju tidak ditemukan.'],
    'id_tidak_valid'       => ['warning', 'ID data tidak valid.'],
    'kategori_digunakan'   => ['warning', 'Kategori tidak dapat dihapus karena masih dipakai oleh pengaduan.'],
];

$kode_pesan = $_GET['pesan'] ?? '';

if ($kode_pesan !== '' && isset($peta_pesan[$kode_pesan])):

    [$jenis, $isi_pesan] = $peta_pesan[$kode_pesan];

    $ikon_pesan = [
        'success' => 'bi-check-circle-fill',
        'danger'  => 'bi-exclamation-octagon-fill',
        'warning' => 'bi-exclamation-triangle-fill',
        'info'    => 'bi-info-circle-fill',
    ][$jenis] ?? 'bi-info-circle-fill';

    ?>

    <div class="alert alert-<?= $jenis ?> alert-auto-hide" role="alert">
        <i class="bi <?= $ikon_pesan ?>"></i>
        <div><?= e($isi_pesan) ?></div>
        <button type="button" class="alert-close" aria-label="Tutup notifikasi">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

<?php endif; ?>
