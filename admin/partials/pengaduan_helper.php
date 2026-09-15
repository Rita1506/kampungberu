<?php

// ==================================================
// pengaduan_helper.php — FUNGSI BANTU PENGADUAN
// --------------------------------------------------
// Satu sumber untuk label, warna, ikon status, dan
// format tanggal. Dipakai oleh: index, detail, dan
// ubah_status (sebelumnya fungsi ini disalin 3x).
// ==================================================


/**
 * Daftar status yang sah beserta labelnya.
 */
function daftar_status_pengaduan()
{
    return [
        'baru'         => 'Baru',
        'diverifikasi' => 'Diverifikasi',
        'diproses'     => 'Diproses',
        'selesai'      => 'Selesai',
        'ditolak'      => 'Ditolak',
    ];
}


/**
 * Label ramah dari kode status.
 */
function nama_status($status)
{
    $daftar = daftar_status_pengaduan();

    $status = strtolower((string) $status);

    return $daftar[$status] ?? ucfirst($status);
}


/**
 * Kelas warna badge (mengikuti design system admin.css).
 */
function kelas_status($status)
{
    switch (strtolower((string) $status)) {
        case 'baru':
            return 'badge-info';       // biru
        case 'diverifikasi':
            return 'badge-purple';     // ungu
        case 'diproses':
            return 'badge-warning';    // kuning
        case 'selesai':
            return 'badge-success';    // hijau
        case 'ditolak':
            return 'badge-danger';     // merah
        default:
            return 'badge-gray';
    }
}


/**
 * Ikon Bootstrap Icons untuk tiap status.
 */
function ikon_status($status)
{
    switch (strtolower((string) $status)) {
        case 'baru':
            return 'bi-inbox';
        case 'diverifikasi':
            return 'bi-shield-check';
        case 'diproses':
            return 'bi-arrow-repeat';
        case 'selesai':
            return 'bi-check-circle-fill';
        case 'ditolak':
            return 'bi-x-circle';
        default:
            return 'bi-question-circle';
    }
}


/**
 * Format tanggal-waktu Indonesia: 13/09/2026 14:30
 */
function format_tanggal($tanggal)
{
    if (empty($tanggal) || $tanggal === '0000-00-00 00:00:00') {
        return '-';
    }

    $waktu = strtotime($tanggal);

    return $waktu ? date('d/m/Y H:i', $waktu) : '-';
}
