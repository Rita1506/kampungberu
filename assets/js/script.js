/* =========================================================
   SI PA'MASE-MASE
   JAVASCRIPT UMUM
   ========================================================= */


/* =========================================================
   DOCUMENT READY
   ========================================================= */

   document.addEventListener("DOMContentLoaded", function () {

    console.log("SI PA'MASE-MASE berhasil dimuat.");

    initPhotoPreview();

    initCharacterCounter();

    initPhoneInput();

    initAutoHideAlert();

    initAlertClose();

    initConfirmDelete();

    initSmoothScroll();

    initMobileSidebar();

});


/* =========================================================
   PREVIEW FOTO
   ========================================================= */

function initPhotoPreview() {

    /*
      Mendukung beberapa pasangan input -> gambar pratinjau:
      1. elemen input ber-atribut data-preview="#id-gambar" (cara baru)
      2. #foto_bukti -> #preview-foto  (form publik, kompatibilitas lama)
      3. #foto        -> #preview-foto atau #preview (form galeri)
    */

    const pasangan = [];

    document
        .querySelectorAll('input[type="file"][data-preview]')
        .forEach(function (input) {

            const img = document.querySelector(input.dataset.preview);

            if (img) {
                pasangan.push([input, img]);
            }

        });

    const fotoBukti = document.getElementById("foto_bukti");

    if (fotoBukti && document.getElementById("preview-foto")) {
        pasangan.push([fotoBukti, document.getElementById("preview-foto")]);
    }

    const fotoGaleri = document.getElementById("foto");

    if (fotoGaleri) {

        const target =
            document.getElementById("preview-foto") ||
            document.getElementById("preview");

        if (target && !pasangan.some(function (p) { return p[0] === fotoGaleri; })) {
            pasangan.push([fotoGaleri, target]);
        }

    }


    pasangan.forEach(function (pasanganItem) {

        const inputFoto = pasanganItem[0];
        const previewFoto = pasanganItem[1];
        const wadah = previewFoto.closest(".foto-preview");

        function sembunyikan() {

            previewFoto.src = "";

            if (wadah) {
                wadah.classList.remove("show");
            } else {
                previewFoto.style.display = "none";
            }

        }

        inputFoto.addEventListener("change", function () {

            const file = this.files[0];

            if (!file) {
                sembunyikan();
                return;
            }

            /* Cek tipe file */
            if (!file.type.startsWith("image/")) {

                alert("File yang dipilih harus berupa gambar.");
                this.value = "";
                sembunyikan();
                return;

            }

            /* Cek ukuran maksimal 2 MB */
            if (file.size > 2 * 1024 * 1024) {

                alert("Ukuran foto maksimal adalah 2 MB.");
                this.value = "";
                sembunyikan();
                return;

            }

            const reader = new FileReader();

            reader.onload = function (event) {

                previewFoto.src = event.target.result;

                if (wadah) {
                    wadah.classList.add("show");
                } else {
                    previewFoto.style.display = "block";
                }

            };

            reader.readAsDataURL(file);

        });

    });

}


/* =========================================================
   COUNTER KARAKTER
   ========================================================= */

function initCharacterCounter() {

    const textarea = document.getElementById("isi_pengaduan");

    const counter = document.getElementById("jumlah-karakter");


    if (!textarea || !counter) {
        return;
    }


    function updateCounter() {

        counter.textContent =
            textarea.value.length;

    }


    textarea.addEventListener(
        "input",
        updateCounter
    );


    updateCounter();

}


/* =========================================================
   INPUT NOMOR TELEPON
   ========================================================= */

function initPhoneInput() {

    const phoneInputs =
        document.querySelectorAll(
            'input[name="no_telepon"]'
        );


    phoneInputs.forEach(function (input) {

        input.addEventListener(
            "input",
            function () {

                this.value =
                    this.value.replace(
                        /[^0-9+]/g,
                        ""
                    );

            }
        );

    });

}


/* =========================================================
   ALERT OTOMATIS HILANG
   ========================================================= */

function initAutoHideAlert() {

    const alerts =
        document.querySelectorAll(
            ".alert-auto-hide"
        );


    alerts.forEach(function (alert) {

        setTimeout(function () {

            alert.style.transition =
                "opacity 0.5s ease";

            alert.style.opacity = "0";


            setTimeout(function () {

                alert.remove();

            }, 500);

        }, 4000);

    });

}


/* =========================================================
   TOMBOL TUTUP ALERT (X)
   ========================================================= */

function initAlertClose() {

    const tombolTutup =
        document.querySelectorAll(".alert-close");


    tombolTutup.forEach(function (tombol) {

        tombol.addEventListener("click", function () {

            const alertBox =
                tombol.closest(".alert");

            if (alertBox) {
                alertBox.remove();
            }

        });

    });

}


/* =========================================================
   KONFIRMASI HAPUS
   ========================================================= */

function initConfirmDelete() {

    const buttons =
        document.querySelectorAll(
            ".btn-hapus"
        );


    buttons.forEach(function (button) {

        button.addEventListener(
            "click",
            function (event) {

                const pesan =
                    this.dataset.confirm ||
                    "Apakah Anda yakin ingin menghapus data ini?";


                if (!confirm(pesan)) {

                    event.preventDefault();

                }

            }
        );

    });

}


/* =========================================================
   SMOOTH SCROLL
   ========================================================= */

function initSmoothScroll() {

    const links =
        document.querySelectorAll(
            'a[href^="#"]'
        );


    links.forEach(function (link) {

        link.addEventListener(
            "click",
            function (event) {

                const targetId =
                    this.getAttribute("href");


                if (
                    !targetId ||
                    targetId === "#"
                ) {
                    return;
                }


                const target =
                    document.querySelector(
                        targetId
                    );


                if (target) {

                    event.preventDefault();


                    target.scrollIntoView({
                        behavior: "smooth",
                        block: "start"
                    });

                }

            }
        );

    });

}


/* =========================================================
   MOBILE SIDEBAR ADMIN
   ========================================================= */

function initMobileSidebar() {

    const sidebar =
        document.querySelector(".sidebar");

    const toggle =
        document.getElementById("toggle-sidebar");

    const backdrop =
        document.getElementById("sidebarBackdrop");

    const tombolTutup =
        document.getElementById("sidebar-close");


    if (!sidebar || !toggle) {
        return;
    }


    function bukaSidebar() {

        sidebar.classList.add("show");

        if (backdrop) {
            backdrop.classList.add("show");
        }

    }


    function tutupSidebar() {

        sidebar.classList.remove("show");

        if (backdrop) {
            backdrop.classList.remove("show");
        }

    }


    toggle.addEventListener("click", function () {

        if (sidebar.classList.contains("show")) {
            tutupSidebar();
        } else {
            bukaSidebar();
        }

    });


    if (backdrop) {

        backdrop.addEventListener("click", tutupSidebar);

    }


    /* Tombol X di dalam sidebar (layar sentuh) */
    if (tombolTutup) {

        tombolTutup.addEventListener("click", tutupSidebar);

    }


    /* Tombol Escape juga menutup sidebar */
    document.addEventListener("keydown", function (event) {

        if (event.key === "Escape" &&
            sidebar.classList.contains("show")) {

            tutupSidebar();

        }

    });


    /* Saat layar kembali ke ukuran desktop, bersihkan
       keadaan terbuka agar tidak nyangkut. */
    window.addEventListener("resize", function () {

        if (window.innerWidth > 991 &&
            sidebar.classList.contains("show")) {

            tutupSidebar();

        }

    });


    /* Setelah memilih menu di layar kecil, sidebar langsung tertutup */
    sidebar
        .querySelectorAll("a")
        .forEach(function (tautan) {

            tautan.addEventListener(
                "click",
                function () {

                    if (window.innerWidth <= 992) {
                        tutupSidebar();
                    }

                }
            );

        });

}


/* =========================================================
   KONFIRMASI FORM
   ========================================================= */

function confirmSubmit(
    pesan = "Apakah data sudah benar?"
) {

    return confirm(pesan);

}


/* =========================================================
   FORMAT NOMOR PENGADUAN
   ========================================================= */

function copyNomorPengaduan(
    nomor
) {

    if (
        !navigator.clipboard
    ) {

        alert(
            "Browser tidak mendukung fitur salin otomatis."
        );

        return;

    }


    navigator.clipboard.writeText(
        nomor
    ).then(function () {

        alert(
            "Nomor pengaduan berhasil disalin."
        );

    }).catch(function () {

        alert(
            "Nomor pengaduan gagal disalin."
        );

    });

}