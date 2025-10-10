<?php
if (!function_exists('template_url')) {
    function template_url(string $path = ''): string
    {
        $base = defined('APP_URL') ? rtrim(APP_URL, '/') : rtrim(admin_app_url(''), '/');
        $trimmed = ltrim($path, '/');
        return $base . ($trimmed !== '' ? '/' . $trimmed : '');
    }
}

if (!isset($currentUser) || !is_array($currentUser)) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $currentUser = $GLOBALS['currentUser'] ?? ($_SESSION['user'] ?? []);
}

$role = strtolower($currentUser['role'] ?? '');
if ($role === 'admin') {
    $dashboardUrl = template_url('admin/index.php');
} elseif ($role === 'guru') {
    $dashboardUrl = template_url('guru/index.php');
} else {
    $dashboardUrl = template_url('index.php');
}
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">
            <div class="error-page">
                <h1 class="display-1 text-primary font-weight-bold">404</h1>
                <h2 class="mb-4 text-gray-800">Halaman tidak ditemukan</h2>
                <p class="lead mb-4 text-gray-600">
                    Maaf, halaman yang Anda cari tidak tersedia atau sudah dipindahkan.
                </p>
                <div class="mb-4">
                    <i class="fas fa-search fa-3x text-gray-300"></i>
                </div>
                <a href="<?= htmlspecialchars($dashboardUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-primary btn-lg">
                    <i class="fas fa-home"></i> Kembali ke Beranda
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.error-page {
    padding: 60px 0;
}

.error-page h1 {
    font-size: 8rem;
    margin-bottom: 1rem;
}

.error-page h2 {
    font-size: 2rem;
    margin-bottom: 2rem;
}

.error-page .lead {
    font-size: 1.1rem;
    margin-bottom: 3rem;
}

.error-page .btn-lg {
    padding: 12px 30px;
    font-size: 1.1rem;
}
</style>