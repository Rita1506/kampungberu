/* =========================================================
   UX TAMBAHAN — OVERLAY "MEMPROSES..." SAAT SUBMIT FORM
   ----------------------------------------------------------
   Tujuan: mencegah pengguna menekan tombol submit berkali-
   kali (double submit) ketika data sedang dikirim, misalnya
   saat login atau unggah foto.

   Cara kerja tanpa mengubah satu pun form:
   - Setiap <form> yang dikirim akan memunculkan overlay.
   - Tombol submit-nya dinonaktifkan selama proses.
   - Form bisa memilih TIDAK memakai overlay dengan memberi
     atribut  data-loading="false"  atau kelas "tanpa-loading".
   - Bila halaman tidak jadi pindah (mis. koneksi macet),
     overlay otomatis bisa ditutup dengan tombol Escape.
   ========================================================= */

   (function () {
    "use strict";

    document.addEventListener("DOMContentLoaded", function () {

        // 1) Bangun elemen overlay sekali saja
        var overlay = document.createElement("div");
        overlay.className = "ux-loading";
        overlay.setAttribute("role", "dialog");
        overlay.setAttribute("aria-modal", "true");
        overlay.setAttribute("aria-hidden", "true");
        overlay.innerHTML =
            '<div class="ux-loading-card">' +
            '  <i class="bi bi-arrow-repeat ux-spinner" aria-hidden="true"></i>' +
            '  <strong>Memproses&hellip;</strong>' +
            '  <span>Mohon tunggu, jangan menutup atau me-refresh halaman.</span>' +
            "</div>";
        document.body.appendChild(overlay);

        var sedangMengirim = false;

        function tampilkanOverlay() {
            overlay.classList.add("show");
            overlay.setAttribute("aria-hidden", "false");
            document.body.style.overflow = "hidden";
        }

        function sembunyikanOverlay() {
            overlay.classList.remove("show");
            overlay.setAttribute("aria-hidden", "true");
            document.body.style.overflow = "";
            sedangMengirim = false;

            // Aktifkan lagi tombol yang sempat dinonaktifkan
            document.querySelectorAll(".ux-tombol-kirim[disabled]")
                .forEach(function (tombol) {
                    tombol.disabled = false;
                    tombol.classList.remove("ux-tombol-kirim");
                });
        }

        // 2) Pasang pendengar pada fase penangkapan agar jalan
        //    lebih dulu daripada skrip lain yang ada di form.
        document.addEventListener("submit", function (event) {

            var form = event.target;

            if (!(form instanceof HTMLFormElement)) {
                return;
            }

            // Form tertentu boleh mematikan overlay
            if (form.dataset.loading === "false" ||
                form.classList.contains("tanpa-loading")) {
                return;
            }

            // Cegah pengiriman ganda selama belum selesai
            if (sedangMengirim) {
                event.preventDefault();
                event.stopPropagation();
                return;
            }

            sedangMengirim = true;
            tampilkanOverlay();

            // Tandai & nonaktifkan tombol submit pada form ini
            var tombol = form.querySelector(
                'button[type="submit"], input[type="submit"], button:not([type])'
            );

            if (tombol) {
                tombol.classList.add("ux-tombol-kirim");
                tombol.disabled = true;
            }

        }, true);

        // 3) Escape = batalkan overlay (jaga-jaga bila halaman
        //    tidak berpindah karena kesalahan jaringan).
        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape" && sedangMengirim) {
                sembunyikanOverlay();
            }
        });

        // 4) Saat halaman dipulihkan dari cache browser (tombol
        //    Back), pastikan overlay tidak ikut terpanggil.
        window.addEventListener("pageshow", function () {
            sembunyikanOverlay();
        });

    });
})();
