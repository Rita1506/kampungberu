<?php

// ==================================================
// init.php — FONDASI HALAMAN PUBLIK
// --------------------------------------------------
// Disertakan di paling atas setiap halaman publik
// (index, pengaduan, cek_status, tentang).
//
// Menggantikan baris yang dulu disalin:
//   require_once "config/koneksi.php";
//
// Fungsi bersama:
//   e($teks)               -> aman dicetak di HTML
//   ambil_profil_desa()    -> data profil + nilai bawaan
//   cari_foto_publik()     -> mencari file foto di assets/img
// ==================================================

$root = $root ?? '';

require_once __DIR__ . '/../config/koneksi.php';

// Catatan: halaman publik tidak memakai session PHP lagi.
// Login admin ditangani lewat cookie token (config/auth_admin.php)
// agar tetap berfungsi di Vercel yang stateless.

// Folder fisik gambar & alamat URL-nya
if (!defined('DIR_IMG')) {
    define('DIR_IMG', __DIR__ . '/../assets/img/');
}

if (!isset($url_img)) {
    $url_img = $root . 'assets/img/';
}


/**
 * Cetak teks dengan aman di dalam HTML (mencegah XSS).
 */
if (!function_exists('e')) {
    function e($teks)
    {
        return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
    }
}


/**
 * Ambil satu data profil desa terakhir, lengkap dengan
 * nilai bawaan bila datanya belum ada.
 */
function ambil_profil_desa($koneksi)
{
    $bawaan = [
        'id_profil'     => '',
        'nama_desa'     => 'Desa Kampung Beru',
        'kecamatan'     => 'Polombangkeng Timur',
        'kabupaten'     => 'Takalar',
        'provinsi'      => 'Sulawesi Selatan',
        'alamat'        => '',
        'email'         => '',
        'no_telepon'    => '',
        'kepala_desa'   => '',
        'ibu_desa'      => '',
        'sejarah'       => '',
        'visi'          => '',
        'misi'          => '',
        'deskripsi'     => '',
        'foto_desa'     => '',
        'foto_ibu_desa' => '',
    ];

    $query = sdb_query(
        $koneksi,
        "SELECT * FROM profil_desa
         ORDER BY id_profil DESC
         LIMIT 1"
    );

    $data = $query ? sdb_fetch_assoc($query) : null;

    if (!$data) {
        $data = [];
    }

    // Gabungkan: data asli menimpa nilai bawaan
    return array_merge($bawaan, $data);
}


/**
 * Warna lencana status untuk halaman publik
 * (nama warna Bootstrap: dipakai pada kelas .bg-* di publik.css).
 */
if (!function_exists('status_warna_publik')) {
    function status_warna_publik($status)
    {
        switch (strtolower((string) $status)) {
            case 'baru':         return 'primary';
            case 'diverifikasi': return 'info';
            case 'diproses':     return 'warning';
            case 'selesai':      return 'success';
            case 'ditolak':      return 'danger';
            default:             return 'secondary';
        }
    }
}

/**
 * Label ramah status untuk halaman publik.
 */
if (!function_exists('status_nama_publik')) {
    function status_nama_publik($status)
    {
        switch (strtolower((string) $status)) {
            case 'baru':         return 'Baru';
            case 'diverifikasi': return 'Diverifikasi';
            case 'diproses':     return 'Sedang Diproses';
            case 'selesai':      return 'Selesai';
            case 'ditolak':      return 'Ditolak';
            default:             return ucfirst((string) $status);
        }
    }
}

/**
 * Ikon status untuk halaman publik.
 */
if (!function_exists('status_ikon_publik')) {
    function status_ikon_publik($status)
    {
        switch (strtolower((string) $status)) {
            case 'baru':         return 'bi-file-earmark-text';
            case 'diverifikasi': return 'bi-check-circle';
            case 'diproses':     return 'bi-hourglass-split';
            case 'selesai':      return 'bi-check-circle-fill';
            case 'ditolak':      return 'bi-x-circle-fill';
            default:             return 'bi-question-circle';
        }
    }
}


/**
 * URL foto berdasarkan nama berkas dan kelompok subfoldernya.
 *
 * Sejak migrasi Supabase Storage, foto (galeri, ikon kategori,
 * foto kepala/ibu desa) diambil dari bucket publik, bukan lagi
 * dari folder lokal. Parameter $subfolder dipertahankan agar
 * pemanggilan lama tidak berubah; subfolder pertama yang berisi
 * dipakai sebagai awalan path objek.
 *
 * Mengembalikan URL Storage, atau string kosong bila nama kosong.
 */
function cari_foto_publik($nama, array $subfolder)
{
    $nama = basename((string) $nama);

    if ($nama === '') {
        return '';
    }

    $prefix = '';

    foreach ($subfolder as $sub) {
        if ($sub !== '') {
            $prefix = $sub;
            break;
        }
    }

    return storage_url($prefix . $nama);
}
