/* =========================================================
   SI PA'MASE-MASE — JAVASCRIPT HALAMAN PUBLIK
   Tanpa Bootstrap: menu mobile, alert, pratinjau foto,
   dan tombol tampil/sembunyi password.
   ========================================================= */

(function () {
    'use strict';


    /* ------------------------------------------------------
       1. MENU NAVBAR DI HP
       ------------------------------------------------------ */

    var tombolMenu = document.getElementById('navbarToggler');
    var menu      = document.getElementById('navbarMenu');

    if (tombolMenu && menu) {

        tombolMenu.addEventListener('click', function () {

            var buka = menu.classList.toggle('show');
            tombolMenu.setAttribute('aria-expanded', buka ? 'true' : 'false');

            var ikon = tombolMenu.querySelector('i');
            if (ikon) {
                ikon.className = buka ? 'bi bi-x-lg' : 'bi bi-list';
            }
        });

        // Tutup menu setelah memilih tautan (khusus mode mobile)
        menu.querySelectorAll('a').forEach(function (tautan) {
            tautan.addEventListener('click', function () {
                if (window.innerWidth < 992) {
                    menu.classList.remove('show');
                    tombolMenu.setAttribute('aria-expanded', 'false');
                    var ikon = tombolMenu.querySelector('i');
                    if (ikon) ikon.className = 'bi bi-list';
                }
            });
        });
    }


    /* ------------------------------------------------------
       2. NOTIFIKASI / ALERT
       ------------------------------------------------------ */

    document.querySelectorAll('.alert .alert-close').forEach(function (tombol) {

        tombol.addEventListener('click', function () {
            var alertBox = tombol.closest('.alert');
            if (alertBox) alertBox.remove();
        });
    });

    // Hilangkan otomatis setelah 5 detik
    document.querySelectorAll('.alert.auto-dismiss').forEach(function (alertBox) {

        setTimeout(function () {
            alertBox.classList.add('alert-fade');
            setTimeout(function () { alertBox.remove(); }, 400);
        }, 5000);
    });


    /* ------------------------------------------------------
       3. TAMPIL / SEMBUNYI PASSWORD (HALAMAN LOGIN)
       ------------------------------------------------------ */

    var tombolPassword = document.querySelector('[data-toggle-password]');

    if (tombolPassword) {

        tombolPassword.addEventListener('click', function () {

            var input = document.getElementById(
                tombolPassword.getAttribute('data-toggle-password')
            );

            if (!input) return;

            var ikon = tombolPassword.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                if (ikon) ikon.className = 'bi bi-eye-slash';
                tombolPassword.setAttribute('title', 'Sembunyikan password');
            } else {
                input.type = 'password';
                if (ikon) ikon.className = 'bi bi-eye';
                tombolPassword.setAttribute('title', 'Tampilkan password');
            }
        });
    }


    /* ------------------------------------------------------
       4. PRATINJAU FOTO SEBELUM DIUNGGAH
          input dengan atribut data-preview="#id-gambar"
          Opsional: data-max-size="2097152" (batas byte).
          Tipe divalidasi dari atribut accept bila ada.
       ------------------------------------------------------ */

    var pasangan = [];

    document.querySelectorAll('input[type="file"][data-preview]')
        .forEach(function (input) {
            var img = document.querySelector(input.getAttribute('data-preview'));
            if (img) pasangan.push([input, img]);
        });

    var fotoBukti = document.getElementById('foto_bukti');
    if (fotoBukti && document.getElementById('preview-foto')) {
        pasangan.push([fotoBukti, document.getElementById('preview-foto')]);
    }

    // Ubah daftar accept (".jpg,.png") menjadi MIME yang didukung
    function mimeDariAccept(accept) {

        var peta = {
            '.jpg': 'image/jpeg', '.jpeg': 'image/jpeg',
            '.png': 'image/png',  '.webp': 'image/webp'
        };

        var hasil = [];

        (accept || '').split(',').forEach(function (suku) {

            suku = suku.trim().toLowerCase();

            if (peta[suku]) hasil.push(peta[suku]);
            else if (suku.indexOf('image/') === 0) hasil.push(suku);
        });

        return hasil;
    }

    pasangan.forEach(function (item) {

        var input = item[0];
        var img   = item[1];
        var wadah = img.closest('.preview-box, .foto-preview');

        function sembunyikan() {
            img.src = '';
            if (wadah) wadah.classList.remove('show');
        }

        input.addEventListener('change', function () {

            var berkas = input.files && input.files[0];

            if (!berkas) { sembunyikan(); return; }

            // Validasi ukuran
            var maks = parseInt(input.getAttribute('data-max-size') || '0', 10);

            if (maks && berkas.size > maks) {
                alert('Ukuran file melebihi batas maksimal ' +
                    Math.round(maks / (1024 * 1024)) + ' MB.');
                input.value = '';
                sembunyikan();
                return;
            }

            // Validasi tipe
            var tipe = mimeDariAccept(input.getAttribute('accept'));

            if (tipe.length && tipe.indexOf(berkas.type) === -1) {
                alert('Format file tidak didukung. Gunakan JPG, PNG, atau WEBP.');
                input.value = '';
                sembunyikan();
                return;
            }

            var pembaca = new FileReader();

            pembaca.onload = function (event) {
                img.src = event.target.result;
                if (wadah) wadah.classList.add('show');
            };

            pembaca.readAsDataURL(berkas);
        });
    });


    /* ------------------------------------------------------
       5. PENGHITUNG KARAKTER
          textarea dengan data-counter="#id-penampil"
       ------------------------------------------------------ */

    document.querySelectorAll('textarea[data-counter]').forEach(function (area) {

        var tampilan = document.querySelector(area.getAttribute('data-counter'));

        function perbarui() {
            if (tampilan) tampilan.textContent = area.value.length;
        }

        area.addEventListener('input', perbarui);
        perbarui();
    });


    /* ------------------------------------------------------
       6. PEMBATAS ISIAN INPUT
          data-mask="angka" (hanya digit) atau
          data-mask="angka-plus" (digit dan tanda +)
       ------------------------------------------------------ */

    document.querySelectorAll('input[data-mask]').forEach(function (input) {

        input.addEventListener('input', function () {

            if (input.getAttribute('data-mask') === 'angka') {
                input.value = input.value.replace(/[^0-9]/g, '');
            } else {
                input.value = input.value.replace(/[^0-9+]/g, '');
            }
        });
    });


    /* ------------------------------------------------------
       7. KONFIRMASI SEBELUM KIRIM FORM + INDIKATOR MEMUAT
          form dengan data-confirm="Pesan yakin?"
          tombol submit diberi data-spinner="Mengirim..."
       ------------------------------------------------------ */

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {

        form.addEventListener('submit', function (event) {

            if (typeof form.checkValidity === 'function' && !form.checkValidity()) {
                event.preventDefault();
                if (form.reportValidity) form.reportValidity();
                return;
            }

            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
                return;
            }

            var tombol = form.querySelector('[data-spinner]');

            if (tombol && !tombol.dataset.htmlAsli) {
                tombol.dataset.htmlAsli = tombol.innerHTML;
                tombol.disabled = true;
                tombol.innerHTML =
                    '<span class="spinner-border spinner-border-sm"></span> ' +
                    (tombol.getAttribute('data-spinner') || 'Memproses...');
            }
        });
    });

    /* ------------------------------------------------------
       8. SALIN KODE/NOMOR
          tombol dengan data-salin="#id-elemen"
       ------------------------------------------------------ */

    document.querySelectorAll('[data-salin]').forEach(function (tombol) {

        tombol.addEventListener('click', function () {

            var sumber = document.querySelector(tombol.getAttribute('data-salin'));
            if (!sumber) return;

            var kode = sumber.textContent.trim();
            var htmlAsli = tombol.innerHTML;

            function berhasil() {
                tombol.innerHTML = '<i class="bi bi-check2 me-1"></i> Kode Berhasil Disalin';
                setTimeout(function () { tombol.innerHTML = htmlAsli; }, 2500);
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {

                navigator.clipboard.writeText(kode).then(berhasil).catch(function () {
                    salinCadangan(kode); berhasil();
                });

            } else {
                salinCadangan(kode); berhasil();
            }
        });
    });

    function salinCadangan(teks) {

        var area = document.createElement('textarea');
        area.value = teks;
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.focus();
        area.select();

        try { document.execCommand('copy'); } catch (e) {}

        area.remove();
    }

})();
