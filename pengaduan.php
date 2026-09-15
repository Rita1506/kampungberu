<?php

// ==================================================
// FORM BUAT PENGADUAN (PUBLIK)
// ==================================================

$root = '';
require_once __DIR__ . '/partials/init.php';


// Kategori aktif untuk pilihan
$query_kategori = sdb_query(
    $koneksi,
    "SELECT * FROM kategori
     WHERE status = 'aktif'
     ORDER BY id_kategori ASC"
);

// Kategori terpilih dari tautan beranda: pengaduan.php?kategori=1
$kategori_dipilih = isset($_GET['kategori']) ? (int) $_GET['kategori'] : 0;

// Pesan error yang dikembalikan proses/simpan_pengaduan.php
$daftar_error = [
    'data_belum_lengkap' => [
        'danger',
        'Mohon lengkapi semua kolom wajib (bertanda *) sebelum mengirim pengaduan.',
    ],
    'belum_menyetujui' => [
        'danger',
        'Anda harus mencentang pernyataan kebenaran data sebelum mengirim.',
    ],
    'email_tidak_valid' => [
        'danger',
        'Format email yang dimasukkan tidak valid. Mohon periksa kembali.',
    ],
    'kategori_tidak_valid' => [
        'danger',
        'Kategori pengaduan yang dipilih tidak valid. Silakan pilih ulang kategori.',
    ],
    'gagal' => [
        'danger',
        'Pengaduan gagal dikirim karena gangguan sistem. Silakan coba beberapa saat lagi.',
    ],
];

$kode_error = (string) ($_GET['error'] ?? '');
$error_tampil = $daftar_error[$kode_error] ?? null;


// Pengaturan tampilan
$judul      = 'Buat Pengaduan';
$menu_aktif = 'pengaduan';

require __DIR__ . '/partials/kepala.php';
require __DIR__ . '/partials/navbar.php';

?>


<!-- =====================================================
     KEPALA HALAMAN
===================================================== -->

<section class="complaint-header">

    <div class="container">

        <div class="complaint-header-content text-center">

            <div class="complaint-header-icon">
                <i class="bi bi-megaphone-fill"></i>
            </div>

            <h1>Sampaikan Pengaduan Anda</h1>

            <p>
                Bantu kami memberikan pelayanan yang lebih baik.
                Sampaikan pengaduan, aspirasi, atau permasalahan
                yang terjadi di lingkungan Desa Kampung Beru.
            </p>

        </div>

    </div>

</section>


<!-- =====================================================
     PENUNJUK LANGKAH
===================================================== -->

<section class="complaint-progress">

    <div class="container">

        <div class="progress-card">

            <div class="d-flex align-items-center">

                <div class="progress-item active">
                    <div class="progress-number">01</div>
                    <div class="progress-text">
                        <strong>Data Pelapor</strong>
                        <span>Informasi diri</span>
                    </div>
                </div>

                <div class="progress-line"></div>

                <div class="progress-item active">
                    <div class="progress-number">02</div>
                    <div class="progress-text">
                        <strong>Detail Pengaduan</strong>
                        <span>Isi laporan</span>
                    </div>
                </div>

                <div class="progress-line"></div>

                <div class="progress-item active">
                    <div class="progress-number">03</div>
                    <div class="progress-text">
                        <strong>Pernyataan</strong>
                        <span>Konfirmasi</span>
                    </div>
                </div>

            </div>

        </div>

    </div>

</section>


<!-- =====================================================
     AREA FORM
===================================================== -->

<section class="complaint-area">

    <div class="container">

        <div class="complaint-container">

            <?php if ($error_tampil !== null): ?>
                <div class="alert alert-<?= e($error_tampil[0]) ?> auto-dismiss">
                    <i class="bi bi-exclamation-octagon-fill"></i>
                    <span><?= e($error_tampil[1]) ?></span>
                    <button type="button" class="alert-close" aria-label="Tutup notifikasi">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- Petunjuk -->
            <div class="instruction-card">

                <div class="instruction-icon">
                    <i class="bi bi-info-lg"></i>
                </div>

                <div>
                    <h5>Sebelum mengisi pengaduan</h5>
                    <p>
                        Pastikan informasi yang Anda masukkan
                        benar dan jelas. Setelah pengaduan berhasil
                        dikirim, sistem akan memberikan
                        <strong>nomor pengaduan</strong> yang dapat
                        digunakan untuk memantau status laporan.
                    </p>
                </div>

            </div>


            <!-- Kartu formulir -->
            <div class="complaint-form-card">

                <div class="form-top">
                    <h3>Formulir Pengaduan Masyarakat</h3>
                    <p>Lengkapi data berikut dengan informasi yang sesuai.</p>
                </div>

                <div class="form-body">

                    <form action="proses/simpan_pengaduan.php"
                          method="POST"
                          enctype="multipart/form-data"
                          id="formPengaduan"
                          data-confirm="Apakah Anda yakin semua data pengaduan sudah benar dan ingin mengirimnya?">


                        <!-- ================= 01 DATA PELAPOR ================= -->

                        <div class="form-section-heading">
                            <div class="section-number">01</div>
                            <div>
                                <h4>Data Pelapor</h4>
                                <p>Informasi ini membantu pihak desa menghubungi Anda jika diperlukan.</p>
                            </div>
                        </div>

                        <div class="row g-4">

                            <div class="col-md-6">
                                <label for="nama_lengkap" class="form-label">
                                    Nama Lengkap <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-fill"></i></span>
                                    <input type="text" name="nama_lengkap" id="nama_lengkap"
                                           class="form-control" placeholder="Contoh: Ahmad"
                                           maxlength="100" autocomplete="name" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="nik" class="form-label">
                                    NIK <span class="optional-label">(Opsional)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person-vcard-fill"></i></span>
                                    <input type="text" name="nik" id="nik"
                                           class="form-control" placeholder="Masukkan NIK"
                                           maxlength="20" inputmode="numeric"
                                           data-mask="angka">
                                </div>
                                <small class="input-help">NIK dapat dikosongkan jika tidak diperlukan.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="no_telepon" class="form-label">
                                    Nomor HP <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone-fill"></i></span>
                                    <input type="tel" name="no_telepon" id="no_telepon"
                                           class="form-control" placeholder="Contoh: 081234567890"
                                           maxlength="20" autocomplete="tel" inputmode="tel"
                                           data-mask="angka-plus" required>
                                </div>
                                <small class="input-help">Digunakan apabila pihak desa perlu menghubungi Anda.</small>
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label">
                                    Email <span class="optional-label">(Opsional)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                    <input type="email" name="email" id="email"
                                           class="form-control" placeholder="nama@email.com"
                                           maxlength="100" autocomplete="email">
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="alamat" class="form-label">
                                    Alamat Tempat Tinggal <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-house-fill"></i></span>
                                    <textarea name="alamat" id="alamat" class="form-control"
                                              rows="3" placeholder="Masukkan alamat tempat tinggal Anda" required></textarea>
                                </div>
                            </div>

                        </div>

                        <hr class="form-divider">


                        <!-- ================= 02 DETAIL PENGADUAN ================= -->

                        <div class="form-section-heading">
                            <div class="section-number">02</div>
                            <div>
                                <h4>Detail Pengaduan</h4>
                                <p>Jelaskan permasalahan atau aspirasi yang ingin Anda sampaikan.</p>
                            </div>
                        </div>

                        <div class="row g-4">

                            <div class="col-12">
                                <label for="id_kategori" class="form-label">
                                    Kategori Pengaduan <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-grid-3x3-gap-fill"></i></span>
                                    <select name="id_kategori" id="id_kategori"
                                            class="form-select" required>
                                        <option value="" disabled
                                            <?= $kategori_dipilih === 0 ? 'selected' : '' ?>>
                                            -- Pilih Bidang Pengaduan --
                                        </option>

                                        <?php if ($query_kategori && sdb_num_rows($query_kategori) > 0): ?>
                                            <?php while ($kategori = sdb_fetch_assoc($query_kategori)): ?>
                                                <option value="<?= (int) $kategori['id_kategori'] ?>"
                                                    <?= $kategori_dipilih === (int) $kategori['id_kategori'] ? 'selected' : '' ?>>
                                                    <?= e($kategori['nama_kategori']) ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="category-help">
                                    <i class="bi bi-lightbulb-fill"></i>
                                    Pilih bidang yang paling sesuai dengan permasalahan yang ingin Anda laporkan.
                                </div>
                            </div>

                            <div class="col-12">
                                <label for="judul_pengaduan" class="form-label">
                                    Judul Pengaduan <span class="required">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-card-heading"></i></span>
                                    <input type="text" name="judul_pengaduan" id="judul_pengaduan"
                                           class="form-control" placeholder="Contoh: Jalan rusak di Dusun ..."
                                           maxlength="200" required>
                                </div>
                                <small class="input-help">Buat judul singkat yang menggambarkan permasalahan Anda.</small>
                            </div>

                            <div class="col-12">
                                <label for="isi_pengaduan" class="form-label">
                                    Isi Pengaduan <span class="required">*</span>
                                </label>
                                <div class="textarea-wrapper">
                                    <textarea name="isi_pengaduan" id="isi_pengaduan"
                                              class="form-control" rows="7" maxlength="2000"
                                              data-counter="#jumlahKarakter"
                                              placeholder="Jelaskan permasalahan secara jelas. Anda dapat mencantumkan waktu kejadian, kondisi yang terjadi, lokasi, serta informasi lain yang dianggap penting."
                                              required></textarea>
                                    <div class="character-count">
                                        <span id="jumlahKarakter">0</span> / 2000
                                    </div>
                                </div>
                                <small class="input-help">
                                    Semakin jelas informasi yang diberikan, semakin mudah pengaduan ditindaklanjuti.
                                </small>
                            </div>

                            <div class="col-12">
                                <label for="alamat_lokasi" class="form-label">
                                    Lokasi Kejadian <span class="optional-label">(Opsional)</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-geo-alt-fill"></i></span>
                                    <textarea name="alamat_lokasi" id="alamat_lokasi"
                                              class="form-control" rows="3"
                                              placeholder="Contoh: Jalan poros Kampung Beru, dekat kantor desa..."></textarea>
                                </div>
                                <small class="input-help">
                                    Tuliskan lokasi sedetail mungkin jika pengaduan berkaitan dengan suatu tempat.
                                </small>
                            </div>

                            <div class="col-12">
                                <label for="foto_bukti" class="form-label">
                                    Foto / Bukti Pengaduan <span class="optional-label">(Opsional)</span>
                                </label>

                                <div class="upload-area">

                                    <div class="upload-icon">
                                        <i class="bi bi-cloud-arrow-up-fill"></i>
                                    </div>

                                    <h5>Tambahkan Foto Bukti</h5>
                                    <p>Foto dapat membantu pihak desa memahami kondisi yang Anda laporkan.</p>

                                    <input type="file" name="foto_bukti" id="foto_bukti"
                                           accept=".jpg,.jpeg,.png,.webp"
                                           data-preview="#gambarPreview"
                                           data-max-size="2097152">

                                    <small class="upload-info">
                                        <i class="bi bi-image"></i>
                                        JPG, JPEG, PNG, atau WEBP &nbsp;•&nbsp; Maksimal 2 MB
                                    </small>

                                </div>

                                <div id="previewFoto" class="preview-box mt-3">
                                    <div class="preview-box-title">
                                        <i class="bi bi-eye"></i> Pratinjau Foto
                                    </div>
                                    <img id="gambarPreview" src="" alt="Pratinjau foto pengaduan" class="preview-image">
                                </div>
                            </div>

                        </div>

                        <hr class="form-divider">


                        <!-- ================= 03 PERNYATAAN ================= -->

                        <div class="form-section-heading">
                            <div class="section-number">03</div>
                            <div>
                                <h4>Pernyataan</h4>
                                <p>Pastikan data yang Anda masukkan sudah benar.</p>
                            </div>
                        </div>

                        <div class="agreement-card">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox"
                                       name="persetujuan" id="persetujuan" value="1" required>
                                <label class="form-check-label" for="persetujuan">
                                    Saya menyatakan bahwa data dan informasi yang saya berikan
                                    dalam pengaduan ini adalah benar dan dapat dipertanggungjawabkan.
                                    Saya memahami bahwa pengaduan akan diproses sesuai dengan
                                    ketentuan pelayanan yang berlaku.
                                </label>
                            </div>
                        </div>

                        <div class="form-buttons">
                            <a href="index.php" class="btn-back">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                            <button type="submit" class="btn-submit" id="btnKirim"
                                    data-spinner="Mengirim...">
                                <i class="bi bi-send-fill"></i> Kirim Pengaduan
                            </button>
                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</section>


<?php require __DIR__ . '/partials/footer.php'; ?>
<?php require __DIR__ . '/partials/kaki.php'; ?>
