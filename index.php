<?php
session_start();

// Load global/production configs
if (file_exists(__DIR__ . '/includes/config.php')) {
    include __DIR__ . '/includes/config.php';
}
if (file_exists(__DIR__ . '/config/production.php')) {
    include __DIR__ . '/config/production.php';
}

// Dapatkan nama file yang sedang diakses
$current_page = basename($_SERVER['PHP_SELF']);

// Pengecualian untuk halaman pengaduan
if ($current_page === 'index.php' && strpos($_SERVER['REQUEST_URI'], '/pengaduan/') !== false) {
    // Biarkan akses tanpa login
} else {
    // Redirect jika tidak ada session user
    if (!isset($_SESSION['user'])) {
        // Redirect ke halaman login
        if (defined('APP_URL')) {
            header('Location: ' . APP_URL . '/auth/login.php');
        } else {
            header('Location: auth/login.php');
        }
        exit;
    }

    // Redirect berdasarkan role
    if (isset($_SESSION['user']['role'])) {
        if ($_SESSION['user']['role'] === 'admin') {
            if (defined('APP_URL')) {
                header('Location: ' . APP_URL . '/admin/index.php');
            } else {
                header('Location: admin/index.php');
            }
            exit;
        } elseif ($_SESSION['user']['role'] === 'guru') {
            if (defined('APP_URL')) {
                header('Location: ' . APP_URL . '/guru/index.php');
            } else {
                header('Location: guru/index.php');
            }
            exit;
        }
    }
}
?>