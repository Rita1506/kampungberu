<?php

// Hubungkan ke database (sekaligus memuat helper auth token)
require_once __DIR__ . '/../config/koneksi.php';

// Hapus baris sesi di database lalu buang cookie di browser
auth_hapus_sesi($koneksi);

// Kembali ke halaman login
header("Location: login.php?logout=berhasil");
exit;
