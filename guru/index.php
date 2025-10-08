<?php
session_start();
include __DIR__ . '/../includes/db.php';
$title = "Dashboard Guru";
$active_page = "dashboard";

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Check if user has valid role
$role = $_SESSION['user']['role'];
if (!in_array($role, ['admin', 'guru'])) {
    header("Location: ../auth/login.php");
    exit;
}

include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';

// Tambahan: endpoint AJAX untuk polling fingerprint terbaru
if (isset($_GET['ajax']) && $_GET['ajax'] === 'fingerprint_status') {
    include __DIR__ . '/../includes/db.php';
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tbl_kehadiran WHERE DATE(timestamp) = ?");
    $stmt->execute([$today]);
    $total_fp = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    // Dummy: status device online jika ada fingerprint hari ini
    $device_status = $total_fp > 0 ? 'Online' : 'Offline';
    echo json_encode([
        'total_fp' => $total_fp,
        'device_status' => $device_status
    ]);
    exit;
}

// Ambil data statistik sesuai role
if ($role === 'admin') {
    // Statistik untuk admin
    $stmt = $conn->query("SELECT COUNT(*) as total FROM guru");
    $guru_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt = $conn->query("SELECT COUNT(*) as total FROM siswa");
    $siswa_count = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $today = date('Y-m-d');
    $stmt_guru = $conn->prepare("SELECT COUNT(*) as total FROM absensi_guru WHERE DATE(tanggal) = :today");
    $stmt_guru->bindParam(':today', $today);
    $stmt_guru->execute();
    $absensi_guru_today = $stmt_guru->fetch(PDO::FETCH_ASSOC)['total'];
    $stmt_siswa = $conn->prepare("SELECT COUNT(*) as total FROM absensi_siswa WHERE DATE(tanggal) = :today");
    $stmt_siswa->bindParam(':today', $today);
    $stmt_siswa->execute();
    $absensi_siswa_today = $stmt_siswa->fetch(PDO::FETCH_ASSOC)['total'];
    $absensi_today = $absensi_guru_today + $absensi_siswa_today;
    $device_status = 'Online'; // Placeholder, bisa dibuat dinamis jika ada pengecekan device
} elseif ($role === 'guru') {
    $today = date('Y-m-d');
    $id_guru = isset($_SESSION['user']['id_guru']) ? $_SESSION['user']['id_guru'] : null;
    $stats = [
        'Hadir' => 0,
        'Telat' => 0,
        'Izin' => 0,
        'Sakit' => 0,
        'Alfa' => 0
    ];
    $stmt = $conn->prepare("SELECT status_kehadiran, COUNT(*) as total FROM absensi_guru WHERE tanggal = :today AND id_guru = :id_guru GROUP BY status_kehadiran");
    $stmt->execute([':today' => $today, ':id_guru' => $id_guru]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $status = $row['status_kehadiran'];
        if (isset($stats[$status])) $stats[$status] += $row['total'];
    }
    // Inisialisasi variabel agar tidak undefined
    $absensi_today = array_sum($stats);
    // Ambil kelas dan total siswa (tanpa filter id_guru karena kolom tidak ada)
    $nama_kelas = '';
    $siswa_count = 0;
    $stmt_kelas = $conn->query("SELECT k.nama_kelas, COUNT(s.id_siswa) as total_siswa FROM kelas k JOIN siswa s ON s.id_kelas = k.id_kelas GROUP BY k.id_kelas ORDER BY total_siswa DESC LIMIT 1");
    if ($row_kelas = $stmt_kelas->fetch(PDO::FETCH_ASSOC)) {
        $nama_kelas = $row_kelas['nama_kelas'];
        $siswa_count = $row_kelas['total_siswa'];
    }
    // Status kehadiran hari ini (ambil status absensi_guru hari ini)
    $status_kehadiran = '-';
    $stmt_status = $conn->prepare("SELECT status_kehadiran FROM absensi_guru WHERE tanggal = :today AND id_guru = :id_guru LIMIT 1");
    $stmt_status->execute([':today' => $today, ':id_guru' => $id_guru]);
    if ($row_status = $stmt_status->fetch(PDO::FETCH_ASSOC)) {
        $status_kehadiran = $row_status['status_kehadiran'];
    }
    // Menu tersedia (hitung menu sidebar untuk guru)
    $menu_tersedia = 7;
}
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include __DIR__ . '/../templates/navbar.php'; ?>
        
        <div class="container-fluid">
            <!-- Page Heading -->
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">
                    <?php if ($role === 'admin'): ?>
                        Dashboard Admin
                    <?php elseif ($role === 'guru'): ?>
                        Dashboard Guru
                    <?php endif; ?>
                </h1>
                <div class="d-none d-sm-inline-block">
                    <span class="badge bg-primary"><?= ucfirst($role) ?></span>
                </div>
            </div>

            <!-- Content Row -->
            <div class="row">
                <?php if ($role === 'admin'): ?>
                    <!-- Admin Dashboard Content -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Guru</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $guru_count ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Siswa</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $siswa_count ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Absensi Hari Ini</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $absensi_today ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clipboard-list fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Device Fingerprint</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $device_status ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-fingerprint fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($role === 'guru'): ?>
                    <!-- Guru Dashboard Content -->
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Absensi Hari Ini</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $absensi_today ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-success shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                            Total Siswa<?= $nama_kelas ? ' ('.$nama_kelas.')' : '' ?></div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $siswa_count ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-users fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-info shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                            Status Kehadiran</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?= $status_kehadiran ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Menu Tersedia</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $menu_tersedia ?> Menu</div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-bars fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-warning shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                            Device Fingerprint</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="device-status">Offline</div>
                                        <span id="badge-fingerprint" class="badge bg-secondary">Tidak Ada Absen</span>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-fingerprint fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Welcome Message -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                Selamat Datang, <?= htmlspecialchars($_SESSION['user']['name']) ?>!
                            </h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">
                                <?php if ($role === 'admin'): ?>
                                    Anda login sebagai Administrator. Anda dapat mengelola data guru, siswa, 
                                    absensi, dan mengatur sistem fingerprint.
                                <?php elseif ($role === 'guru'): ?>
                                    Anda login sebagai Guru. Anda dapat melihat absensi siswa, 
                                    mengelola absensi pribadi, dan mengakses fitur-fitur guru lainnya.
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php if ($role === 'guru'): ?>
<div class="row">
    <div class="col-12">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Statistik Absensi Saya Hari Ini</h6>
            </div>
            <div class="card-body">
                <canvas id="absensiChart" height="80"></canvas>
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../templates/footer.php'; ?>
</div>

<!-- JS SB Admin -->
<script src="/absensi_sekolah/assets/vendor/jquery/jquery.min.js"></script>
<script src="/absensi_sekolah/assets/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="/absensi_sekolah/assets/vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="/absensi_sekolah/assets/js/sb-admin-2.min.js"></script>


<script src="/absensi_sekolah/assets/vendor/chart.js/Chart.bundle.min.js"></script>
<script>
var ctx = document.getElementById('absensiChart').getContext('2d');
var absensiChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: ['Hadir', 'Telat', 'Izin', 'Sakit', 'Alfa'],
        datasets: [{
            label: 'Jumlah',
            data: [<?= $stats['Hadir'] ?>, <?= $stats['Telat'] ?>, <?= $stats['Izin'] ?>, <?= $stats['Sakit'] ?>, <?= $stats['Alfa'] ?>],
            backgroundColor: [
                'rgba(40,167,69,0.7)',
                'rgba(255,193,7,0.7)',
                'rgba(23,162,184,0.7)',
                'rgba(220,53,69,0.7)',
                'rgba(108,117,125,0.7)'
            ],
            borderColor: [
                'rgba(40,167,69,1)',
                'rgba(255,193,7,1)',
                'rgba(23,162,184,1)',
                'rgba(220,53,69,1)',
                'rgba(108,117,125,1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
<script>
// Polling fingerprint status setiap 10 detik
setInterval(function() {
    fetch('index.php?ajax=fingerprint_status')
        .then(res => res.json())
        .then(data => {
            document.getElementById('device-status').textContent = data.device_status;
            document.getElementById('badge-fingerprint').textContent = data.total_fp > 0 ? 'Ada Absen Baru' : 'Tidak Ada Absen';
            document.getElementById('badge-fingerprint').className = data.total_fp > 0 ? 'badge bg-success' : 'badge bg-secondary';
        });
}, 10000);
</script>
<?php endif; ?>

</body>
</html>