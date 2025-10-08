<?php
session_start();
include '../../includes/db.php';
include '../../includes/advanced_stats_helper.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'guru'])) {
    header("Location: ../auth/login.php");
    exit;
}

$title = "Laporan Absensi";
$active_page = "laporan_absensi";

// Filter parameters
$tanggal_mulai = isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : date('Y-m-01');
$tanggal_akhir = isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : date('Y-m-d');
$tipe_user = isset($_GET['tipe_user']) ? $_GET['tipe_user'] : 'all'; // 'guru', 'siswa', 'all'
$id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : '';
$status_kehadiran = isset($_GET['status_kehadiran']) ? $_GET['status_kehadiran'] : '';

try {
    // Ambil daftar kelas untuk filter
    $stmt_kelas = $conn->prepare("SELECT id_kelas, nama_kelas FROM kelas ORDER BY nama_kelas");
    $stmt_kelas->execute();
    $kelas_list = $stmt_kelas->fetchAll(PDO::FETCH_ASSOC);

    // Build query conditions
    $where_conditions = [];
    $params = [];

    if ($tipe_user === 'guru') {
        $where_conditions[] = "u.role = 'guru'";
    } elseif ($tipe_user === 'siswa') {
        $where_conditions[] = "u.role = 'siswa'";
    }

    if ($id_kelas && $tipe_user === 'siswa') {
        $where_conditions[] = "s.id_kelas = :id_kelas";
        $params[':id_kelas'] = $id_kelas;
    }

    if ($status_kehadiran) {
        $where_conditions[] = "COALESCE(ag.status_kehadiran, asis.status_kehadiran) = :status_kehadiran";
        $params[':status_kehadiran'] = $status_kehadiran;
    }

    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    // Query untuk laporan absensi
    $query = "
        SELECT 
            u.id as user_id,
            u.name as nama_user,
            u.role as tipe_user,
            u.uid as fingerprint_uid,
            g.nip,
            s.nis,
            s.nisn,
            k.nama_kelas,
            kh.timestamp as waktu_fingerprint,
            kh.verification_mode,
            kh.status as status_fingerprint,
            ag.status_kehadiran as status_guru,
            ag.catatan as catatan_guru,
            asis.status_kehadiran as status_siswa,
            asis.catatan as catatan_siswa,
            ag.tanggal as tanggal_guru,
            asis.tanggal as tanggal_siswa
        FROM users u
        LEFT JOIN guru g ON u.id = g.user_id
        LEFT JOIN siswa s ON u.id = s.user_id
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN tbl_kehadiran kh ON u.id = kh.user_id 
            AND DATE(kh.timestamp) BETWEEN :tanggal_mulai AND :tanggal_akhir
        LEFT JOIN absensi_guru ag ON g.id_guru = ag.id_guru 
            AND ag.tanggal BETWEEN :tanggal_mulai AND :tanggal_akhir
        LEFT JOIN absensi_siswa asis ON s.id_siswa = asis.id_siswa 
            AND asis.tanggal BETWEEN :tanggal_mulai AND :tanggal_akhir
        $where_clause
        ORDER BY u.name, COALESCE(ag.tanggal, asis.tanggal) DESC
    ";

    $params[':tanggal_mulai'] = $tanggal_mulai;
    $params[':tanggal_akhir'] = $tanggal_akhir;

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $laporan_data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistik
    $stats_query = "
        SELECT 
            COUNT(DISTINCT u.id) as total_user,
            SUM(CASE WHEN u.role = 'guru' THEN 1 ELSE 0 END) as total_guru,
            SUM(CASE WHEN u.role = 'siswa' THEN 1 ELSE 0 END) as total_siswa,
            SUM(CASE WHEN COALESCE(ag.status_kehadiran, asis.status_kehadiran) = 'Hadir' THEN 1 ELSE 0 END) as total_hadir,
            SUM(CASE WHEN COALESCE(ag.status_kehadiran, asis.status_kehadiran) = 'Telat' THEN 1 ELSE 0 END) as total_telat,
            SUM(CASE WHEN COALESCE(ag.status_kehadiran, asis.status_kehadiran) IN ('Izin', 'Sakit') THEN 1 ELSE 0 END) as total_izin_sakit,
            SUM(CASE WHEN COALESCE(ag.status_kehadiran, asis.status_kehadiran) = 'Alfa' THEN 1 ELSE 0 END) as total_alfa
        FROM users u
        LEFT JOIN guru g ON u.id = g.user_id
        LEFT JOIN siswa s ON u.id = s.user_id
        LEFT JOIN absensi_guru ag ON g.id_guru = ag.id_guru 
            AND ag.tanggal BETWEEN :tanggal_mulai AND :tanggal_akhir
        LEFT JOIN absensi_siswa asis ON s.id_siswa = asis.id_siswa 
            AND asis.tanggal BETWEEN :tanggal_mulai AND :tanggal_akhir
        $where_clause
    ";

    $stmt_stats = $conn->prepare($stats_query);
    $stmt_stats->execute($params);
    $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}

include '../../templates/header.php';
include '../../templates/sidebar.php';
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Laporan Absensi</h1> -->

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>

            <!-- Advanced Analytics Charts -->
            <?php if (isset($stats) && $stats['total_user'] > 0): ?>
            <div class="row mb-4">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Trend Kehadiran</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="attendanceTrendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Status Kehadiran</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="statusPieChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Filter Form -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Filter Laporan</h6>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row">
                        <div class="col-md-2">
                            <label>Tanggal Mulai:</label>
                            <input type="date" name="tanggal_mulai" class="form-control" value="<?= $tanggal_mulai ?>">
                        </div>
                        <div class="col-md-2">
                            <label>Tanggal Akhir:</label>
                            <input type="date" name="tanggal_akhir" class="form-control" value="<?= $tanggal_akhir ?>">
                        </div>
                        <div class="col-md-2">
                            <label>Tipe User:</label>
                            <select name="tipe_user" class="form-control">
                                <option value="all" <?= $tipe_user === 'all' ? 'selected' : '' ?>>Semua</option>
                                <option value="guru" <?= $tipe_user === 'guru' ? 'selected' : '' ?>>Guru</option>
                                <option value="siswa" <?= $tipe_user === 'siswa' ? 'selected' : '' ?>>Siswa</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Kelas:</label>
                            <select name="id_kelas" class="form-control">
                                <option value="">Semua Kelas</option>
                                <?php foreach ($kelas_list as $kelas): ?>
                                    <option value="<?= $kelas['id_kelas'] ?>" <?= $id_kelas == $kelas['id_kelas'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Status:</label>
                            <select name="status_kehadiran" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="Hadir" <?= $status_kehadiran === 'Hadir' ? 'selected' : '' ?>>Hadir</option>
                                <option value="Telat" <?= $status_kehadiran === 'Telat' ? 'selected' : '' ?>>Telat</option>
                                <option value="Izin" <?= $status_kehadiran === 'Izin' ? 'selected' : '' ?>>Izin</option>
                                <option value="Sakit" <?= $status_kehadiran === 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                                <option value="Alfa" <?= $status_kehadiran === 'Alfa' ? 'selected' : '' ?>>Alfa</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>&nbsp;</label>
                            <div>
                                <button type="submit" class="btn btn-primary">Filter</button>
                                <a href="?" class="btn btn-secondary">Reset</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Statistik -->
            <?php if (isset($stats)): ?>
            <div class="row mb-4">
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total User</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_user'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Hadir</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_hadir'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Telat</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_telat'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Izin/Sakit</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_izin_sakit'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-times fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-2 col-md-4 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Alfa</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_alfa'] ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-times fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Tabel Laporan -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Data Absensi</h6>
                    <div>
                        <a href="export_excel.php?<?= http_build_query($_GET) ?>" class="btn btn-success btn-sm mr-2">
                            <i class="fas fa-file-excel"></i> Export Excel
                        </a>
                        <a href="export_pdf.php?<?= http_build_query($_GET) ?>" class="btn btn-danger btn-sm">
                            <i class="fas fa-file-pdf"></i> Export PDF
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable">
                            <thead>
                                <tr>
                                    <th>Nama</th>
                                    <th>Tipe</th>
                                    <th>NIP/NIS</th>
                                    <th>Kelas</th>
                                    <th>UID Fingerprint</th>
                                    <th>Tanggal</th>
                                    <th>Waktu Fingerprint</th>
                                    <th>Status Manual</th>
                                    <th>Status Fingerprint</th>
                                    <th>Catatan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($laporan_data as $data): ?>
                                <tr>
                                    <td><?= htmlspecialchars($data['nama_user']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= $data['tipe_user'] === 'guru' ? 'primary' : 'success' ?>">
                                            <?= ucfirst($data['tipe_user']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($data['nip'] ?: $data['nis']) ?></td>
                                    <td><?= htmlspecialchars($data['nama_kelas'] ?: '-') ?></td>
                                    <td><?= htmlspecialchars($data['fingerprint_uid']) ?></td>
                                    <td><?= htmlspecialchars($data['tanggal_guru'] ?: $data['tanggal_siswa']) ?></td>
                                    <td>
                                        <?php if ($data['waktu_fingerprint']): ?>
                                            <span class="badge badge-info">
                                                <?= date('H:i:s', strtotime($data['waktu_fingerprint'])) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $status_manual = $data['status_guru'] ?: $data['status_siswa'];
                                        if ($status_manual): 
                                            $status_class = '';
                                            switch ($status_manual) {
                                                case 'Hadir': $status_class = 'success'; break;
                                                case 'Telat': $status_class = 'warning'; break;
                                                case 'Izin': 
                                                case 'Sakit': $status_class = 'info'; break;
                                                default: $status_class = 'danger';
                                            }
                                        ?>
                                            <span class="badge badge-<?= $status_class ?>">
                                                <?= htmlspecialchars($status_manual) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($data['status_fingerprint']): ?>
                                            <span class="badge badge-secondary">
                                                <?= htmlspecialchars($data['status_fingerprint']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($data['catatan_guru'] ?: $data['catatan_siswa'] ?: '-') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../templates/footer.php'; ?>
</div>

<?php include '../../templates/scripts.php'; ?>

<!-- Chart.js -->
<script src="/absensi_sekolah/assets/vendor/chart.js/Chart.min.js"></script>

<script>
// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Status Pie Chart Data
    const statusData = {
        labels: ['Hadir', 'Telat', 'Izin/Sakit', 'Alfa'],
        datasets: [{
            data: [<?= $stats['total_hadir'] ?>, <?= $stats['total_telat'] ?>, <?= $stats['total_izin_sakit'] ?>, <?= $stats['total_alfa'] ?>],
            backgroundColor: ['#1cc88a', '#f6c23e', '#36b9cc', '#e74a3b'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    };
    
    // Create Status Pie Chart
    const statusCtx = document.getElementById('statusPieChart');
    if (statusCtx) {
        new Chart(statusCtx, {
            type: 'doughnut',
            data: statusData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: window.innerWidth < 768 ? 'bottom' : 'right'
                    }
                }
            }
        });
    }
    
    // Sample trend data (you can enhance this with real monthly data)
    const trendData = {
        labels: ['Minggu 1', 'Minggu 2', 'Minggu 3', 'Minggu 4'],
        attendance_rates: [85, 88, 92, 87]
    };
    
    // Create Attendance Trend Chart
    const trendCtx = document.getElementById('attendanceTrendChart');
    if (trendCtx && window.enhancedCharts) {
        window.enhancedCharts.createAttendanceTrendChart('attendanceTrendChart', trendData);
    }
});
</script>

</body>
</html> 