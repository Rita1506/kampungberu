<?php
// =====================================================
// PINTU MASUK TUNGGAL (FRONT CONTROLLER) UNTUK VERCEL
// -----------------------------------------------------
// Di Vercel hanya folder api/ yang bisa menjalankan PHP
// (runtime vercel-php). vercel.json mengarahkan SEMUA
// permintaan ke file ini. File ini lalu menentukan file
// PHP mana di root project yang sebenarnya harus dibuka,
// meniru cara Apache/PHP membuka berkas di htdocs.
//
// Untuk pengembangan lokal cukup:
//   php -S 127.0.0.1:8000 api/index.php
// dari dalam folder project.
//
// Keamanan: hanya halaman di root yang dikenal serta
// folder admin/, auth/, dan proses/ yang boleh dijalankan.
// Folder config/, partials/, database/, tools/, dll.
// TIDAK BISA diakses langsung dari browser.
// =====================================================

define('APP_ROOT', dirname(__DIR__));

/**
 * Tampilkan halaman 404 sederhana dan hentikan proses.
 */
function fc_not_found(): void
{
    http_response_code(404);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="id"><head><meta charset="utf-8">'
        . '<title>404 — Halaman tidak ditemukan</title></head>'
        . '<body style="font-family:system-ui,sans-serif;text-align:center;padding:60px">'
        . '<h1>404</h1><p>Halaman yang dicari tidak ditemukan.</p>'
        . '<p><a href="/">Kembali ke Beranda</a></p>'
        . '</body></html>';
    exit;
}

// Ambil bagian path saja (buang query string), lalu decode
$path = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = (string) parse_url($path, PHP_URL_PATH);
$path = rawurldecode($path);

if ($path === '' ) {
    $path = '/';
}

// ---- 1) Berkas statis (css/js/gambar/font) -------------------------
// Di Vercel, berkas /assets sudah dilayani langsung lewat aturan
// routes di vercel.json sebelum sampai ke sini. Blok ini terutama
// untuk server pengembangan `php -S`, agar aset tetap dilayani
// sebagai berkas statis (return false = serahkan ke server bawaan).
if ($path !== '/' && strpos($path, '/assets/') === 0) {
    if (is_file(APP_ROOT . $path)) {
        return false;
    }
    fc_not_found();
}

// ---- 2) Tentukan berkas PHP yang diminta ---------------------------
$rel = ltrim($path, '/');

if ($rel === '') {
    $rel = 'index.php';              // beranda
} elseif (substr($rel, -1) === '/') {
    $rel .= 'index.php';            // folder -> index.php folder itu
}

// Tanpa ekstensi .php: izinkan hanya bila itu folder yang punya index
if (!preg_match('/\.php$/i', $rel)) {
    $calon = rtrim($rel, '/') . '/index.php';
    if (preg_match('/\.php$/i', $calon) && is_file(APP_ROOT . '/' . $calon)) {
        $rel = $calon;
    } else {
        fc_not_found();
    }
}

// ---- 3) Daftar putih lokasi yang boleh dieksekusi ------------------

// Tolak tegas segala bentuk path traversal (../) sebelum dicek
if (preg_match('#(^|/)\.\.(/|$)#', $rel)) {
    fc_not_found();
}

$halaman_root = [
    'index.php',
    'pengaduan.php',
    'cek_status.php',
    'tentang.php',
];

$folder_boleh = ['admin/', 'auth/', 'proses/'];

$diizinkan = in_array($rel, $halaman_root, true);

if (!$diizinkan) {
    foreach ($folder_boleh as $prefix) {
        if (strpos($rel, $prefix) === 0) {
            $diizinkan = true;
            break;
        }
    }
}

if (!$diizinkan) {
    fc_not_found();
}

// ---- 4) Cegah trik path traversal (../) ----------------------------
$berkas = APP_ROOT . '/' . $rel;
$real   = realpath($berkas);

if ($real === false
    || strpos($real, realpath(APP_ROOT) . DIRECTORY_SEPARATOR) !== 0
    || !is_file($real)
) {
    fc_not_found();
}

// ---- 5) Jalankan halaman yang diminta ------------------------------
// Pindah working directory ke root agar perilakunya seperti docroot
// Apache di XAMPP.
chdir(APP_ROOT);

require $real;
