<?php
// =====================================================
// AUTENTIKASI ADMIN BERBASIS COOKIE TOKEN
// -----------------------------------------------------
// Pengganti session PHP untuk lingkungan serverless
// (Vercel), di mana tidak ada penyimpanan sesi yang
// permanen di server.
//
// Cara kerja:
//   1. Saat login berhasil, dibuat token acak 256-bit.
//   2. Yang disimpan ke tabel sesi_admin hanya HASH-nya
//      (SHA-256), jadi kebocoran database tidak membuka
//      sesi. Token aslinya hanya ada di cookie admin.
//   3. Cookie dipasang HttpOnly (tak bisa dibaca JS),
//      SameSite=Lax, dan Secure saat situs ber-HTTPS.
//   4. Setiap halaman admin memanggil auth_admin_aktif()
//      yang mencocokkan cookie dengan database dan
//      memeriksa masa berlaku serta status admin.
//
// Agar kode halaman tidak berubah, data admin hasil
// validasi diisi juga ke $_SESSION (hanya berlaku untuk
// satu kali request; tidak ada file sesi di server).
// =====================================================

if (!defined('AUTH_COOKIE_NAMA')) {
    define('AUTH_COOKIE_NAMA', 'pamasesi_admin');
}

if (!defined('AUTH_UMUR_DETIK')) {
    // Berlaku 7 hari sejak login
    define('AUTH_UMUR_DETIK', 60 * 60 * 24 * 7);
}

/**
 * Apakah request ini berjalan di atas HTTPS?
 * Di Vercel/penyangga (proxy), skema asli dikirim lewat
 * header X-Forwarded-Proto.
 */
function auth_https_aktif(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        return true;
    }
    return false;
}

/**
 * Buat token baru berupa 64 karakter heksadesimal (256-bit).
 */
function auth_buat_token(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Hash token untuk disimpan/dicari di database.
 */
function auth_hash_token(string $token): string
{
    return hash('sha256', $token);
}

/**
 * Pasang cookie sesi di browser.
 */
function auth_set_cookie(string $token, int $umur_detik): void
{
    $opsi = [
        'expires'  => time() + $umur_detik,
        'path'     => '/',
        'secure'   => auth_https_aktif(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    if (PHP_SAPI !== 'cli') {
        setcookie(AUTH_COOKIE_NAMA, $token, $opsi);
    }

    // Sediakan juga di $_COOKIE untuk request yang sama
    $_COOKIE[AUTH_COOKIE_NAMA] = $token;
}

/**
 * Hapus cookie sesi dari browser.
 */
function auth_hapus_cookie(): void
{
    $opsi = [
        'expires'  => time() - 42000,
        'path'     => '/',
        'secure'   => auth_https_aktif(),
        'httponly' => true,
        'samesite' => 'Lax',
    ];

    if (PHP_SAPI !== 'cli') {
        setcookie(AUTH_COOKIE_NAMA, '', $opsi);
    }

    unset($_COOKIE[AUTH_COOKIE_NAMA]);
}

/**
 * Catat sesi baru ke database setelah login berhasil,
 * lalu pasang cookie-nya.
 *
 * Mengembalikan true bila sukses.
 */
function auth_buat_sesi($koneksi, array $admin): bool
{
    $token = auth_buat_token();
    $hash  = auth_hash_token($token);

    // Masa berlaku dikirim sebagai teks tanggal lengkap
    $kedaluwarsa = gmdate('Y-m-d H:i:s', time() + AUTH_UMUR_DETIK) . '+00';

    $stmt = sdb_prepare(
        $koneksi,
        "INSERT INTO sesi_admin (token_hash, id_admin, kedaluwarsa_at)
         VALUES (?, ?, ?)"
    );
    sdb_stmt_bind_param(
        $stmt,
        'sis',
        $hash,
        $admin['id_admin'],
        $kedaluwarsa
    );

    if (!sdb_stmt_execute($stmt)) {
        return false;
    }

    // Sekalian bersihkan sesi-sesi yang sudah lewat masa berlaku
    sdb_query($koneksi, "DELETE FROM sesi_admin WHERE kedaluwarsa_at < now()");

    auth_set_cookie($token, AUTH_UMUR_DETIK);
    auth_isi_sesi_request($admin);

    return true;
}

/**
 * Isi $_SESSION untuk satu request ini agar kode halaman
 * yang selama ini membaca $_SESSION tetap bekerja, tanpa
 * benar-benar memakai session PHP.
 */
function auth_isi_sesi_request(array $admin): void
{
    $_SESSION = $_SESSION ?? [];
    $_SESSION['id_admin']     = (int) $admin['id_admin'];
    $_SESSION['nama_lengkap'] = $admin['nama_lengkap'];
    $_SESSION['username']     = $admin['username'];
    $_SESSION['email']        = $admin['email'];
    $_SESSION['foto']         = $admin['foto'];
    $_SESSION['status']       = $admin['status'];
    $_SESSION['login_admin']  = true;
}

/**
 * Ambil data admin yang sedang login berdasarkan cookie.
 * Mengembalikan array data admin, atau null bila tidak
 * ada cookie / token tidak dikenal / sudah kedaluwarsa /
 * akun dinonaktifkan.
 */
function auth_admin_dari_cookie($koneksi): ?array
{
    $token = (string) ($_COOKIE[AUTH_COOKIE_NAMA] ?? '');

    if ($token === '' || !ctype_xdigit($token)) {
        return null;
    }

    $hash = auth_hash_token($token);

    $stmt = sdb_prepare(
        $koneksi,
        "SELECT a.id_admin, a.nama_lengkap, a.username, a.email,
                a.foto, a.status
         FROM sesi_admin s
         JOIN admin a ON a.id_admin = s.id_admin
         WHERE s.token_hash = ?
           AND s.kedaluwarsa_at > now()
           AND a.status = 'aktif'
         LIMIT 1"
    );
    sdb_stmt_bind_param($stmt, 's', $hash);

    if (!sdb_stmt_execute($stmt)) {
        return null;
    }

    $hasil = sdb_stmt_get_result($stmt);
    $admin = sdb_fetch_assoc($hasil);

    if (!$admin) {
        return null;
    }

    auth_isi_sesi_request($admin);

    return $admin;
}

/**
 * GERBANG HALAMAN ADMIN: pastikan admin sudah login.
 * Bila tidak, lempar ke halaman login dan hentikan eksekusi.
 *
 * @param string $root jalur relatif ke root aplikasi
 *                     ('../' atau '../../'), tanpa diawali '/'.
 */
function auth_paksa_login($koneksi, string $root = '../'): void
{
    $admin = auth_admin_dari_cookie($koneksi);

    if ($admin === null) {
        header('Location: ' . $root . 'auth/login.php');
        exit;
    }
}

/**
 * Logout: hapus baris sesi di database (bila ada cookie)
 * lalu buang cookie-nya dari browser.
 */
function auth_hapus_sesi($koneksi): void
{
    $token = (string) ($_COOKIE[AUTH_COOKIE_NAMA] ?? '');

    if ($token !== '' && ctype_xdigit($token)) {
        $hash = auth_hash_token($token);
        $stmt = sdb_prepare(
            $koneksi,
            "DELETE FROM sesi_admin WHERE token_hash = ?"
        );
        sdb_stmt_bind_param($stmt, 's', $hash);
        @sdb_stmt_execute($stmt);
    }

    auth_hapus_cookie();
}
