<?php
// =====================================================
// LAPISAN DATABASE SUPABASE (REST / PostgREST)
// -----------------------------------------------------
// Pengganti mysqli_* untuk lingkungan tanpa MySQL
// (Vercel + Supabase). Nama fungsi memakai awalan sdb_
// (Supabase DataBase) dan perilakunya dibuat mirip mysqli
// agar alur kode halaman tidak berubah:
//
//   sdb_connect / sdb_connect_error / sdb_set_charset
//   sdb_query / sdb_prepare
//   sdb_stmt_bind_param / sdb_stmt_execute / sdb_stmt_get_result
//   sdb_stmt_store_result / sdb_stmt_num_rows / sdb_stmt_close
//   sdb_fetch_assoc / sdb_fetch_all / sdb_num_rows
//   sdb_insert_id / sdb_error / sdb_real_escape_string
//
// Kredensial dibaca dari Environment Variable (Vercel):
//   SUPABASE_URL, SUPABASE_SERVICE_KEY, SUPABASE_ANON_KEY
// Saat dijalankan di XAMPP/lokal, taruh salinan nilai di
//   config/rahasia.php (lihat config/rahasia.contoh.php).
// File rahasia.php TIDAK boleh diunggah ke GitHub.
// =====================================================

if (!defined('MYSQLI_ASSOC')) {
    define('MYSQLI_ASSOC', 1);
}

// -----------------------------------------------------
// Pembacaan konfigurasi
// -----------------------------------------------------

function sdb_konfigurasi(): array
{
    static $konf = null;

    if ($konf !== null) {
        return $konf;
    }

    $file_rahasia = __DIR__ . '/rahasia.php';
    $rahasia = is_file($file_rahasia) ? (array) require $file_rahasia : [];

    // Nama baku SUPABASE_*; nama pendek (url/anon_key/service_key)
    // diterima juga bila Environment Variable dibuat dengan nama itu.
    $konf = [
        'url'         => rtrim(
            (string) (getenv('SUPABASE_URL')
                ?: getenv('url')
                ?: ($rahasia['url'] ?? '')),
            '/'
        ),
        'service_key' => (string) (getenv('SUPABASE_SERVICE_KEY')
            ?: getenv('service_key')
            ?: ($rahasia['service_key'] ?? '')),
        'anon_key'    => (string) (getenv('SUPABASE_ANON_KEY')
            ?: getenv('anon_key')
            ?: ($rahasia['anon_key'] ?? '')),
    ];

    return $konf;
}

// -----------------------------------------------------
// Kelompol pengganti objek mysqli
// -----------------------------------------------------

class SdbKoneksi
{
    public $url;
    public $kunci;
    public $error        = '';
    public $errno        = 0;
    public $insert_id    = 0;
    public $affected_rows = 0;

    public function __construct(string $url, string $kunci)
    {
        $this->url   = $url;
        $this->kunci = $kunci;
    }
}

class SdbHasil
{
    private $baris;
    private $posisi = 0;
    public $num_rows;

    public function __construct(array $baris)
    {
        $this->baris    = array_values($baris);
        $this->num_rows = count($this->baris);
    }

    #[\ReturnTypeWillChange]
    public function fetch_assoc()
    {
        if (!isset($this->baris[$this->posisi])) {
            return null;
        }
        return $this->baris[$this->posisi++];
    }

    public function fetch_all($mode = MYSQLI_ASSOC): array
    {
        return $this->baris;
    }

    public function free(): void
    {
        $this->baris = [];
        $this->num_rows = 0;
    }
}

class SdbStmt
{
    private $koneksi;
    private $sql;
    public $tipe = '';
    public $refs = [];
    public $num_rows = 0;
    public $affected_rows = 0;
    public $insert_id = 0;
    public $error = '';
    public $errno = 0;
    private $baris = [];

    public function __construct(SdbKoneksi $koneksi, string $sql)
    {
        $this->koneksi = $koneksi;
        $this->sql     = $sql;
    }

    public function ikat(string $tipe, array $refs): bool
    {
        $this->tipe = $tipe;
        $this->refs = $refs;
        return true;
    }

    public function jalankan(): bool
    {
        $params = [];
        foreach ($this->refs as $nilai) {
            $params[] = $nilai; // dereferensi nilai terkini
        }

        $res = sdb_kirim_sql($this->koneksi, $this->sql, $params);

        if ($res === null) {
            $this->error = $this->koneksi->error;
            $this->errno = $this->koneksi->errno;
            return false;
        }

        $this->baris         = $res['rows'];
        $this->num_rows      = $res['num_rows'];
        $this->affected_rows = $res['num_rows'];
        $this->insert_id     = $res['insert_id'];
        $this->error         = '';
        $this->errno         = 0;
        return true;
    }

    public function ambil_hasil(): SdbHasil
    {
        return new SdbHasil($this->baris);
    }
}

// -----------------------------------------------------
// Normalisasi dialek MySQL -> PostgreSQL
// -----------------------------------------------------

function sdb_normalisasi_sql(string $sql): string
{
    // Hapus tanda kutip identifier gaya MySQL (`kolom`)
    $sql = str_replace('`', '', $sql);

    // LIKE di MySQL bersifat case-insensitive (collation ci),
    // di PostgreSQL harus ILIKE agar perilakunya sama.
    $sql = preg_replace('/\bLIKE\b/iu', 'ILIKE', $sql);

    // Pagination MySQL "LIMIT mulai, jumlah"
    // -> PostgreSQL "LIMIT jumlah OFFSET mulai"
    $sql = preg_replace(
        '/\bLIMIT\s+(\d+)\s*,\s*(\d+)\b/iu',
        'LIMIT $2 OFFSET $1',
        $sql
    );

    return trim($sql);
}

// -----------------------------------------------------
// Komunikasi HTTP ke PostgREST
// -----------------------------------------------------

/**
 * Header respons HTTP terakhir dari pemanggilan berbasis
 * file_get_contents (jalur cadangan tanpa cURL).
 *
 * PHP 8.4 menyediakan http_get_last_response_headers(); variabel
 * ajaib $http_response_header didepresiasi sejak PHP 8.5.
 */
if (!function_exists('sdb_header_terakhir')) {
    function sdb_header_terakhir(): array
    {
        if (function_exists('http_get_last_response_headers')) {
            $header = http_get_last_response_headers();
            return is_array($header) ? $header : [];
        }
        return [];
    }
}

function sdb_kirim_sql(SdbKoneksi $koneksi, string $sql, array $params): ?array
{
    $sql = sdb_normalisasi_sql($sql);

    $endpoint = $koneksi->url . '/rest/v1/rpc/app_query';
    $body     = json_encode(
        [
            'p_sql'    => $sql,
            'p_params' => array_values($params),
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    $header = [
        'Content-Type: application/json',
        'Accept: application/json',
        'apikey: ' . $koneksi->kunci,
        'Authorization: Bearer ' . $koneksi->kunci,
        'Prefer: return=representation',
        // Mencegah error HTTP/2 PROTOCOL_ERROR pada cURL bawaan XAMPP
        'Expect:',
        'Connection: close',
    ];

    $raw  = null;
    $code = 0;

    if (function_exists('curl_init')) {

        $maks_coba  = 3;
        $error_curl = '';
        $errno      = 0;

        for ($coba = 1; $coba <= $maks_coba; $coba++) {

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => $header,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_SSL_VERIFYPEER => true,
                // Paksa HTTP/1.1 demi kompatibilitas cURL lama di XAMPP
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            ]);

            $raw  = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error_curl = curl_error($ch);
            $errno      = curl_errno($ch);
            curl_close($ch);

            if ($raw !== false) {
                break;
            }

            $boleh_ulang = in_array($errno, [
                CURLE_RECV_ERROR, CURLE_SEND_ERROR, CURLE_HTTP2,
                CURLE_HTTP2_STREAM, CURLE_PARTIAL_FILE,
                CURLE_OPERATION_TIMEDOUT, CURLE_GOT_NOTHING,
            ], true);

            if (!$boleh_ulang || $coba === $maks_coba) {
                break;
            }

            usleep(400000);
        }

        if ($raw === false) {
            $koneksi->error = 'Gagal menghubungi Supabase: ' . $error_curl
                . ' (periksa koneksi, VPN/proxy bila dipakai)';
            $koneksi->errno = $errno ?: 1;
            return null;
        }

    } else {

        // Cadangan bila ekstensi cURL tidak tersedia
        $baris_header = implode("\r\n", $header);
        $konteks = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => $baris_header,
                'content'       => $body,
                'timeout'       => 30,
                'ignore_errors' => true,
            ],
        ]);

        // Deklarasikan lebih dulu agar tidak dianggap variabel
        // ajaib $http_response_header (didepresiasi PHP 8.5).
        $http_response_header = [];
        $raw = @file_get_contents($endpoint, false, $konteks);

        $header_terakhir = function_exists('http_get_last_response_headers')
            ? sdb_header_terakhir()
            : $http_response_header;

        if (isset($header_terakhir[0])
            && preg_match('#HTTP/\S+\s+(\d{3})#', $header_terakhir[0], $m)) {
            $code = (int) $m[1];
        }

        if ($raw === false) {
            $koneksi->error = 'Gagal menghubungi Supabase (file_get_contents).';
            $koneksi->errno = 1;
            return null;
        }
    }

    $data = json_decode((string) $raw, true);

    if ($code < 200 || $code >= 300) {
        $pesan = $data['message']
            ?? $data['error']
            ?? ('HTTP ' . $code . ': ' . substr((string) $raw, 0, 300));
        $koneksi->error = 'Kesalahan database: ' . $pesan;
        $koneksi->errno = $code;
        return null;
    }

    if (!is_array($data) || empty($data['ok'])) {
        $koneksi->error = 'Respons database tidak dikenali: ' . substr((string) $raw, 0, 300);
        $koneksi->errno = 2;
        return null;
    }

    $baris = isset($data['rows']) && is_array($data['rows']) ? $data['rows'] : [];
    $jumlah = (int) ($data['num_rows'] ?? count($baris));

    // Cari nilai kunci primer hasil INSERT/UPDATE.
    // Urutan kolom JSON dari DML tidak dijamin, jadi dicari
    // berdasarkan nama kolom: utamakan id_<nama_tabel>, lalu
    // kolom mana pun yang diawali "id_" (semua PK aplikasi begitu).
    $insert_id = 0;
    if ($baris && is_array($baris[0])) {
        $baris0 = $baris[0];
        if (preg_match(
            '/^\s*(?:insert\s+into|update|delete\s+from)\s+["\']?([a-z0-9_]+)/iu',
            $sql,
            $cocok
        )) {
            $kunci_pk = 'id_' . $cocok[1];
            if (isset($baris0[$kunci_pk]) && is_numeric($baris0[$kunci_pk])) {
                $insert_id = (int) $baris0[$kunci_pk];
            }
        }
        if ($insert_id === 0) {
            foreach ($baris0 as $kunci => $nilai) {
                if (str_starts_with($kunci, 'id_') && is_numeric($nilai)) {
                    $insert_id = (int) $nilai;
                    break;
                }
            }
        }
    }

    $koneksi->error = '';
    $koneksi->errno = 0;
    $koneksi->affected_rows = $jumlah;
    $koneksi->insert_id = $insert_id;

    return [
        'rows'      => $baris,
        'num_rows'  => $jumlah,
        'insert_id' => $insert_id,
    ];
}

// -----------------------------------------------------
// Fungsi-fungsi kompatibel (awalan sdb_)
// -----------------------------------------------------

function sdb_connect($host = null, $user = null, $pass = null, $db = null)
{
    $konf = sdb_konfigurasi();

    if ($konf['url'] === '' || $konf['service_key'] === '') {
        SdbPusat::$error_terakhir =
            'Konfigurasi Supabase belum diisi. Buat config/rahasia.php '
            . '(lihat config/rahasia.contoh.php) atau set Environment Variable.';
        return false;
    }

    // Waktu default aplikasi: WITA (Sulawesi Selatan)
    if (!ini_get('date.timezone')) {
        date_default_timezone_set('Asia/Makassar');
    }

    return new SdbKoneksi($konf['url'], $konf['service_key']);
}

class SdbPusat
{
    public static $error_terakhir = '';
}

function sdb_connect_error(): string
{
    return SdbPusat::$error_terakhir;
}

function sdb_set_charset($koneksi, $charset): bool
{
    return true; // PostgREST selalu UTF-8
}

function sdb_query($koneksi, $sql)
{
    $res = sdb_kirim_sql($koneksi, (string) $sql, []);

    if ($res === null) {
        return false;
    }

    return new SdbHasil($res['rows']);
}

function sdb_prepare($koneksi, $sql)
{
    return new SdbStmt($koneksi, (string) $sql);
}

function sdb_stmt_bind_param($stmt, $tipe, &...$variabel)
{
    return $stmt->ikat((string) $tipe, $variabel);
}

function sdb_stmt_execute($stmt): bool
{
    return $stmt->jalankan();
}

function sdb_stmt_get_result($stmt): SdbHasil
{
    return $stmt->ambil_hasil();
}

function sdb_stmt_store_result($stmt): bool
{
    return true; // hasil sudah diambil saat execute
}

function sdb_stmt_num_rows($stmt): int
{
    return (int) $stmt->num_rows;
}

function sdb_stmt_close($stmt): bool
{
    return true;
}

function sdb_fetch_assoc($hasil)
{
    return $hasil ? $hasil->fetch_assoc() : null;
}

function sdb_fetch_all($hasil, $mode = MYSQLI_ASSOC): array
{
    return $hasil ? $hasil->fetch_all($mode) : [];
}

function sdb_num_rows($hasil): int
{
    return $hasil ? (int) $hasil->num_rows : 0;
}

function sdb_insert_id($koneksi): int
{
    return (int) $koneksi->insert_id;
}

function sdb_error($koneksi): string
{
    return (string) $koneksi->error;
}

function sdb_close($koneksi): bool
{
    return true;
}

/**
 * Pengaman nilai yang ditempel langsung ke SQL (gaya mysqli lama).
 * Aturan kutip PostgreSQL: apostrof digandakan; backslash adalah
 * karakter biasa saat standard_conforming_strings menyala.
 */
function sdb_real_escape_string($koneksi, $nilai): string
{
    $nilai = (string) $nilai;
    $nilai = str_replace("\0", '', $nilai);
    return str_replace("'", "''", $nilai);
}
