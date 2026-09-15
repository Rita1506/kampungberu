<?php

// ==================================================
// topbar.php — BILAH ATAS
// --------------------------------------------------
// Berisi: tombol pembuka sidebar (muncul di tablet/HP),
// judul halaman, dan identitas admin yang login.
// Judul diambil dari variabel $judul.
// ==================================================

?>

<header class="topbar">

    <button
        type="button"
        class="topbar-toggle"
        id="toggle-sidebar"
        aria-label="Buka menu navigasi"
    >
        <i class="bi bi-list"></i>
    </button>

    <div class="topbar-title"><?= e($judul ?? 'Panel Admin') ?></div>

    <div class="topbar-spacer"></div>

    <div class="user-chip">
        <div class="user-meta">
            <strong><?= e(admin_nama()) ?></strong>
            <small>Administrator</small>
        </div>
        <div class="avatar">
            <i class="bi bi-person-fill"></i>
        </div>
    </div>

</header>
