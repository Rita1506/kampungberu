<?php
// =====================================================
// LAPISAN SUPABASE STORAGE (unggah/hapus/URL foto)
// -----------------------------------------------------
// Dipakai menggantikan move_uploaded_file()/unlink() ke
// folder lokal, karena di Vercel filesystem bersifat
// sementara. Semua foto disimpan di satu bucket publik
// (default: "foto") dengan kelompok subfolder:
//   galeri/  kategori/  pengaduan/  profil/
//
// Kredensial dibaca dari config/rahasia.php (lokal) atau
// Environment Variable SUPABASE_URL / SUPABASE_SERVICE_KEY
// / SUPABASE_BUCKET (saat deploy di Vercel).
// =====================================================

/**
 * Konfigurasi storage (dibaca sekali).
 */
function storage_konfigurasi(): array
{
    static $konf = null;

    if ($konf !== null) {
        return $konf;
    }

    $file_rahasia = __DIR__ . '/rahasia.php';
    $rahasia = is_file($file_rahasia) ? (array) require $file_rahasia : [];

    $konf = [
        'url'    => rtrim(
            (string) (getenv('SUPABASE_URL')
                ?: getenv('url')
                ?: ($rahasia['url'] ?? '')),
            '/'
        ),
        'kunci'  => (string) (getenv('SUPABASE_SERVICE_KEY')
            ?: getenv('service_key')
            ?: ($rahasia['service_key'] ?? '')),
        'bucket' => (string) (getenv('SUPABASE_BUCKET')
            ?: getenv('bucket')
            ?: ($rahasia['bucket'] ?? 'foto')),
    ];

    return $konf;
}

/**
 * Pesan error operasi storage terakhir.
 */
function storage_error(): string
{
    return $GLOBALS['_storage_error'] ?? '';
}

function storage_set_error(string $pesan): void
{
    $GLOBALS['_storage_error'] = $pesan;
}

/**
 * Encode tiap segmen path aman untuk URL, "/" tetap dipertahankan.
 */
function storage_encode_path(string $path): string
{
    $path = str_replace('\\', '/', $path);
    $segmen = array_map(
        static fn ($s) => rawurlencode($s),
        explode('/', $path)
    );
    return implode('/', $segmen);
}

/**
 * Rapikan path objek: buang folder lokal yang tidak relevan
 * dan ambil nama file bila yang masuk berupa path.
 */
function storage_rapikan_path(string $objek): string
{
    $objek = trim(str_replace('\\', '/', $objek));
    $objek = ltrim($objek, '/');
    return $objek;
}

/**
 * URL PUBLIK sebuah objek. Mengembalikan '' bila nama kosong
 * atau konfigurasi belum diisi.
 *   Contoh: storage_url('galeri/abc.jpg')
 */
function storage_url(?string $objek): string
{
    $objek = storage_rapikan_path((string) $objek);

    if ($objek === '') {
        return '';
    }

    $konf = storage_konfigurasi();

    if ($konf['url'] === '') {
        return '';
    }

    return $konf['url']
        . '/storage/v1/object/public/'
        . rawurlencode($konf['bucket'])
        . '/' . storage_encode_path($objek);
}

/**
 * URL sebuah objek berdasarkan kelompok + nama file.
 *   storage_url_berkas('galeri', $nama)
 */
function storage_url_berkas(string $kelompok, string $nama): string
{
    $nama = basename(trim($nama));
    if ($nama === '') {
        return '';
    }
    return storage_url($kelompok . '/' . $nama);
}

/**
 * Kirim permintaan HTTP ke Storage API.
 * Mengembalikan ['code'=>int, 'body'=>string] atau null.
 */
function storage_http(string $method, string $objek, $isi = null, array $header_extra = []): ?array
{
    $konf = storage_konfigurasi();

    if ($konf['url'] === '' || $konf['kunci'] === '') {
        storage_set_error('Konfigurasi Supabase Storage belum diisi.');
        return null;
    }

    $url = $konf['url']
        . '/storage/v1/object/'
        . rawurlencode($konf['bucket'])
        . '/' . storage_encode_path(storage_rapikan_path($objek));

    // 'Expect:' mematikan header "Expect: 100-continue" yang sering
    // memicu error HTTP/2 PROTOCOL_ERROR pada cURL bawaan XAMPP.
    $header = array_merge([
        'apikey: ' . $konf['kunci'],
        'Authorization: Bearer ' . $konf['kunci'],
        'Expect:',
        'Connection: close',
    ], $header_extra);

    if (function_exists('curl_init')) {

        // Hingga 3 percobaan: unggahan besar kadang terputus karena
        // gangguan jaringan / negosiasi HTTP/2.
        $maks_coba   = 3;
        $terakhir_err = '';

        for ($coba = 1; $coba <= $maks_coba; $coba++) {

            $ch = curl_init($url);
            $opsi = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => $method,
                CURLOPT_HTTPHEADER     => $header,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT        => 120,
                // Paksa HTTP/1.1 agar kompatibel dengan cURL lama di XAMPP
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_SSL_VERIFYPEER => true,
            ];

            if ($isi !== null) {
                $opsi[CURLOPT_POSTFIELDS] = $isi;
                // Pastikan Content-Length terkirim untuk unggahan
                $opsi[CURLOPT_HTTPHEADER][] = 'Content-Length: ' . strlen($isi);
            }

            curl_setopt_array($ch, $opsi);
            $raw  = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err  = curl_error($ch);
            $errno = curl_errno($ch);
            // curl_close() tidak dipanggil: tidak berefek sejak PHP 8.0
            // dan didepresiasi sejak PHP 8.5 (handle ditutup otomatis).

            if ($raw !== false) {
                return ['code' => $code, 'body' => (string) $raw];
            }

            $terakhir_err = $err;

            // Hanya ulangi untuk gangguan jaringan/protokol;
            // berhenti lebih cepat untuk error lain.
            $boleh_ulang = in_array($errno, [
                CURLE_RECV_ERROR, CURLE_SEND_ERROR, CURLE_HTTP2,
                CURLE_HTTP2_STREAM, CURLE_PARTIAL_FILE,
                CURLE_OPERATION_TIMEDOUT, CURLE_GOT_NOTHING,
            ], true);

            if (!$boleh_ulang || $coba === $maks_coba) {
                break;
            }

            usleep(500000); // jeda 0,5 detik sebelum mencoba lagi
        }

        $saran = '';
        if (stripos($terakhir_err, 'PROTOCOL_ERROR') !== false
            || stripos($terakhir_err, 'HTTP/2') !== false) {
            $saran = ' (gangguan protokol HTTP; coba jaringan lain atau nonaktifkan VPN/proxy)';
        } elseif ($terakhir_err === '') {
            $saran = ' (respons kosong dari server; periksa koneksi internet)';
        }

        storage_set_error('Gagal menghubungi Storage: ' . $terakhir_err . $saran);
        return null;
    }

    // Cadangan tanpa ekstensi cURL (file_get_contents), paksa HTTP/1.1
    $baris_header = implode("\r\n", $header);
    $konteks = stream_context_create([
        'http' => [
            'method'           => $method,
            'header'           => $baris_header,
            'content'          => $isi,
            'timeout'          => 120,
            'ignore_errors'    => true,
            'protocol_version' => 1.1,
        ],
    ]);

    // Deklarasikan lebih dulu agar tidak dianggap variabel ajaib
    // $http_response_header (didepresiasi PHP 8.5).
    $http_response_header = [];
    $raw = @file_get_contents($url, false, $konteks);

    if ($raw === false) {
        storage_set_error('Gagal menghubungi Storage (file_get_contents). Pastikan koneksi internet lancar.');
        return null;
    }

    $code = 0;
    $header_terakhir = function_exists('http_get_last_response_headers')
        ? sdb_header_terakhir()
        : $http_response_header;

    if (isset($header_terakhir[0])
        && preg_match('#HTTP/\S+\s+(\d{3})#', $header_terakhir[0], $m)) {
        $code = (int) $m[1];
    }

    return ['code' => $code, 'body' => (string) $raw];
}

/**
 * Unggah isi file (path di server) ke sebuah path objek.
 *
 * @param string $sumber Path file sumber (mis. tmp_name hasil upload)
 * @param string $objek  Path tujuan di bucket, mis. "galeri/abc.jpg"
 * @param string $mime   Tipe MIME; kosongkan untuk ditebak
 * @return bool sukses
 */
function storage_upload_file(string $sumber, string $objek, string $mime = ''): bool
{
    if (!is_file($sumber) || !is_readable($sumber)) {
        storage_set_error('Berkas sumber tidak terbaca: ' . basename($sumber));
        return false;
    }

    $isi = file_get_contents($sumber);

    if ($isi === false) {
        storage_set_error('Gagal membaca berkas sumber.');
        return false;
    }

    if ($mime === '') {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = (string) finfo_file($finfo, $sumber);
            finfo_close($finfo);
        } else {
            $mime = 'application/octet-stream';
        }
    }

    // x-upsert: bila objek sudah ada (mis. migrasi diulang), timpa
    $res = storage_http(
        'POST',
        $objek,
        $isi,
        [
            'Content-Type: ' . $mime,
            'x-upsert: true',
            'Cache-Control: public, max-age=31536000',
        ]
    );

    if ($res === null) {
        return false; // pesan error sudah di-set
    }

    $code = $res['code'];

    if ($code === 200 || $code === 201) {
        storage_set_error('');
        return true;
    }

    $data = json_decode($res['body'], true);
    $pesan = $data['message']
        ?? $data['error']
        ?? ('HTTP ' . $code . ': ' . substr($res['body'], 0, 200));
    storage_set_error('Gagal mengunggah ke Storage: ' . $pesan);
    return false;
}

/**
 * Hapus sebuah objek dari bucket. Bila objek tidak ada (404),
 * dianggap sudah terhapus sehingga alur tetap lanjut.
 */
function storage_hapus(string $objek): bool
{
    if (storage_rapikan_path($objek) === '') {
        return true;
    }

    $res = storage_http('DELETE', $objek);

    if ($res === null) {
        return false;
    }

    if ($res['code'] >= 200 && $res['code'] < 300 || $res['code'] === 404) {
        storage_set_error('');
        return true;
    }

    $data = json_decode($res['body'], true);
    $pesan = $data['message'] ?? ('HTTP ' . $res['code']);
    storage_set_error('Gagal menghapus dari Storage: ' . $pesan);
    return false;
}

/**
 * Cek apakah sebuah objek ada (permintaan HEAD).
 * Tidak dipakai untuk daftar panjang (berat per gambar);
 * disediakan untuk pemeriksaan tertentu saja.
 */
function storage_ada(string $objek): bool
{
    $res = storage_http('HEAD', $objek);

    if ($res === null) {
        return false;
    }

    return $res['code'] >= 200 && $res['code'] < 300;
}
