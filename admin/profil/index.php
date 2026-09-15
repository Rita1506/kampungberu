<?php

// ==================================================
// PROFIL DESA — FORM INFORMASI DESA
// ==================================================

$root = '../../';
require_once __DIR__ . '/../partials/proteksi.php';


// --------------------------------------------------
// Ambil profil desa terakhir
// --------------------------------------------------

$query = sdb_query(
    $koneksi,
    "SELECT *
     FROM profil_desa
     ORDER BY id_profil DESC
     LIMIT 1"
);

$data = sdb_fetch_assoc($query);


// Nilai bawaan bila belum ada profil
$default = [
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

$data = $data ?: $default;


// --------------------------------------------------
// URL foto kepala & ibu desa dari Supabase Storage
// --------------------------------------------------

$foto_kepala_url = storage_url_berkas('profil', $data['foto_desa'] ?? '');
$foto_ibu_url    = storage_url_berkas('profil', $data['foto_ibu_desa'] ?? '');

$foto_kepala_ada = ($foto_kepala_url !== '');
$foto_ibu_ada    = ($foto_ibu_url !== '');


// Pengaturan tampilan
$judul      = 'Profil Desa';
$menu_aktif = 'profil';

require __DIR__ . '/../partials/head.php';

?>

<div class="page-head">

    <div>
        <h1><i class="bi bi-building"></i> Profil Desa</h1>
        <p>Kelola informasi identitas, pejabat, dan profil umum Desa Kampung Beru.</p>
    </div>

</div>


<form method="POST" action="simpan.php" enctype="multipart/form-data">

    <input type="hidden" name="id_profil" value="<?= e($data['id_profil']) ?>">


    <!-- ================= INFORMASI DASAR ================= -->
    <div class="card card-pad mb-3 form-section">

        <div class="section-label"><i class="bi bi-geo-alt"></i> Informasi Dasar Desa</div>

        <div class="form-grid">

            <div class="field">
                <label for="nama_desa">Nama Desa <span class="required">*</span></label>
                <input type="text" id="nama_desa" name="nama_desa" class="input"
                    value="<?= e($data['nama_desa']) ?>" required>
            </div>

            <div class="field">
                <label for="kecamatan">Kecamatan <span class="required">*</span></label>
                <input type="text" id="kecamatan" name="kecamatan" class="input"
                    value="<?= e($data['kecamatan']) ?>" required>
            </div>

            <div class="field">
                <label for="kabupaten">Kabupaten <span class="required">*</span></label>
                <input type="text" id="kabupaten" name="kabupaten" class="input"
                    value="<?= e($data['kabupaten']) ?>" required>
            </div>

            <div class="field">
                <label for="provinsi">Provinsi <span class="required">*</span></label>
                <input type="text" id="provinsi" name="provinsi" class="input"
                    value="<?= e($data['provinsi']) ?>" required>
            </div>

            <div class="field span-2">
                <label for="alamat">Alamat Kantor Desa</label>
                <textarea id="alamat" name="alamat" class="textarea ta-sm"
                    placeholder="Tuliskan alamat lengkap kantor desa..."><?= e($data['alamat']) ?></textarea>
            </div>

            <div class="field">
                <label for="email">Email Desa</label>
                <input type="email" id="email" name="email" class="input"
                    value="<?= e($data['email']) ?>" placeholder="desa@example.com">
            </div>

            <div class="field">
                <label for="no_telepon">No. Telepon</label>
                <input type="text" id="no_telepon" name="no_telepon" class="input"
                    value="<?= e($data['no_telepon']) ?>" placeholder="Contoh: 0411xxxxxx">
            </div>

        </div>

    </div>


    <!-- ================= KEPALA & IBU DESA ================= -->
    <div class="card card-pad mb-3 form-section">

        <div class="form-grid">

            <!-- Kepala desa -->
            <div class="field span-2">
                <div class="sub-title">
                    <i class="bi bi-person-badge"></i> Kepala Desa
                </div>
            </div>

            <div class="field">
                <label for="foto_desa">Foto Kepala Desa</label>

                <?php if ($foto_kepala_ada): ?>
                    <img
                        src="<?= e($foto_kepala_url) ?>"
                        alt="Foto Kepala Desa"
                        class="foto-petugas-tersimpan">
                <?php endif; ?>

                <input
                    type="file"
                    id="foto_desa"
                    name="foto_desa"
                    class="input-file"
                    accept=".jpg,.jpeg,.png,.webp"
                    data-preview="#preview-kepala">

                <span class="hint mt-1">
                    <i class="bi bi-info-circle"></i>
                    Kosongkan jika tidak ingin mengganti. JPG/PNG/WEBP, maks. 2 MB.
                </span>

                <div class="foto-preview foto-petugas">
                    <img id="preview-kepala" src="" alt="Pratinjau foto kepala desa">
                </div>
            </div>

            <div class="field">
                <label for="kepala_desa">Nama Kepala Desa</label>
                <input type="text" id="kepala_desa" name="kepala_desa" class="input"
                    value="<?= e($data['kepala_desa']) ?>"
                    placeholder="Masukkan nama Kepala Desa">
            </div>

            <!-- Ibu desa -->
            <div class="field span-2 mt-1">
                <div class="sub-title">
                    <i class="bi bi-person-heart"></i> Ibu Desa
                </div>
            </div>

            <div class="field">
                <label for="foto_ibu_desa">Foto Ibu Desa</label>

                <?php if ($foto_ibu_ada): ?>
                    <img
                        src="<?= e($foto_ibu_url) ?>"
                        alt="Foto Ibu Desa"
                        class="foto-petugas-tersimpan">
                <?php endif; ?>

                <input
                    type="file"
                    id="foto_ibu_desa"
                    name="foto_ibu_desa"
                    class="input-file"
                    accept=".jpg,.jpeg,.png,.webp"
                    data-preview="#preview-ibu">

                <span class="hint mt-1">
                    <i class="bi bi-info-circle"></i>
                    Kosongkan jika tidak ingin mengganti. JPG/PNG/WEBP, maks. 2 MB.
                </span>

                <div class="foto-preview foto-petugas">
                    <img id="preview-ibu" src="" alt="Pratinjau foto ibu desa">
                </div>
            </div>

            <div class="field">
                <label for="ibu_desa">Nama Ibu Desa</label>
                <input type="text" id="ibu_desa" name="ibu_desa" class="input"
                    value="<?= e($data['ibu_desa']) ?>"
                    placeholder="Masukkan nama Ibu Desa">
            </div>

        </div>

    </div>


    <!-- ================= SEJARAH ================= -->
    <div class="card card-pad mb-3 form-section">

        <div class="section-label"><i class="bi bi-book"></i> Sejarah Berdirinya Desa</div>

        <div class="field">
            <textarea
                id="sejarah"
                name="sejarah"
                class="textarea ta-lg"
                placeholder="Tuliskan sejarah berdirinya Desa Kampung Beru..."><?= e($data['sejarah']) ?></textarea>
        </div>

    </div>


    <!-- ================= VISI & MISI ================= -->
    <div class="card card-pad mb-3 form-section">

        <div class="section-label"><i class="bi bi-bullseye"></i> Visi dan Misi Desa</div>

        <div class="form-grid">

            <div class="field">
                <label for="visi">Visi Desa</label>
                <textarea id="visi" name="visi" class="textarea"
                    placeholder="Tuliskan visi desa..."><?= e($data['visi']) ?></textarea>
            </div>

            <div class="field">
                <label for="misi">Misi Desa</label>
                <textarea id="misi" name="misi" class="textarea"
                    placeholder="Tuliskan misi desa..."><?= e($data['misi']) ?></textarea>
            </div>

        </div>

    </div>


    <!-- ================= DESKRIPSI ================= -->
    <div class="card card-pad mb-3 form-section">

        <div class="section-label"><i class="bi bi-file-text"></i> Deskripsi Singkat Desa</div>

        <div class="field">
            <textarea
                id="deskripsi"
                name="deskripsi"
                class="textarea ta-md"
                placeholder="Tuliskan deskripsi singkat tentang Desa Kampung Beru..."><?= e($data['deskripsi']) ?></textarea>
        </div>

    </div>


    <div class="form-actions">
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-save"></i> Simpan Profil Desa
        </button>
        <a href="index.php" class="btn btn-light">Batal</a>
    </div>

</form>


<?php require __DIR__ . '/../partials/footer.php'; ?>
