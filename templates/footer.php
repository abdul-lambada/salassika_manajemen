<?php
if (!function_exists('template_url')) {
    function template_url(string $path = ''): string
    {
        $base = defined('APP_URL') ? rtrim(APP_URL, '/') : rtrim(admin_app_url(''), '/');
        $trimmed = ltrim($path, '/');
        return $base . ($trimmed !== '' ? '/' . $trimmed : '');
    }
}

$year = (int) date('Y');
?>
<!-- Footer -->
<footer class="app-footer bg-white border-top py-3">
    <div class="container-fluid">
        <div class="d-flex flex-column flex-md-row align-items-center justify-content-between small text-muted">
            <span class="mb-2 mb-md-0">&copy; Salassika <?= $year ?></span>
            <div class="d-flex align-items-center">
                <a class="text-muted mr-3" href="<?= htmlspecialchars(template_url('profil.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-info-circle mr-1"></i> Tentang
                </a>
                <a class="text-muted" href="<?= htmlspecialchars(template_url('pengaduan/index.php'), ENT_QUOTES, 'UTF-8'); ?>">
                    <i class="fas fa-life-ring mr-1"></i> Bantuan
                </a>
            </div>
        </div>
    </div>
</footer>
<!-- End of Footer -->

<!-- Close Wrapper -->
</div>
