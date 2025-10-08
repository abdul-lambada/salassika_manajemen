<?php
session_start();
$title = "404 Not Found";
$active_page = "404";

// Tentukan dashboard berdasarkan role
$dashboard_url = '/absensi_sekolah/';
if (isset($_SESSION['user']['role'])) {
    if ($_SESSION['user']['role'] === 'admin') {
        $dashboard_url = '/absensi_sekolah/admin/index.php';
    } elseif ($_SESSION['user']['role'] === 'guru') {
        $dashboard_url = '/absensi_sekolah/guru/index.php';
    }
}

include 'header.php';
include 'sidebar.php';
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include 'navbar.php'; ?>
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
                        <a href="<?= $dashboard_url ?>" class="btn btn-primary btn-lg">
                            <i class="fas fa-home"></i> Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'footer.php'; ?>
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

</body>
</html> 