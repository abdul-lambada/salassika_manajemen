<?php
session_start();

// Dapatkan nama file yang sedang diakses
$current_page = basename($_SERVER['PHP_SELF']);

// Pengecualian untuk halaman pengaduan
if ($current_page === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/pengaduan/') !== false) {
    // Biarkan akses tanpa login
} else {
    // Redirect jika tidak ada session user
    if (!isset($_SESSION['user'])) {
        header("Location: auth/login.php");
        exit;
    }

    // Redirect berdasarkan role
    if ($_SESSION['user']['role'] === 'admin') {
        header("Location: admin/index.php");
        exit;
    } elseif ($_SESSION['user']['role'] === 'guru') {
        header("Location: guru/index.php");
        exit;
    }
}
?>