<?php
session_start();
$title = "Absensi Real-time";
$active_page = "realtime_attendance";
include __DIR__ . '/../templates/header.php';
include __DIR__ . '/../templates/sidebar.php';
include '../includes/db.php';

// Ambil data absensi hari ini
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT 
        tk.*,
        CASE 
            WHEN s.nama_siswa IS NOT NULL THEN CONCAT('Siswa: ', s.nama_siswa)
            WHEN g.nama_guru IS NOT NULL THEN CONCAT('Guru: ', g.nama_guru)
            ELSE tk.user_name
        END as mapped_name,
        CASE 
            WHEN s.id_siswa IS NOT NULL THEN 'Siswa'
            WHEN g.id_guru IS NOT NULL THEN 'Guru'
            ELSE 'Tidak Dikenal'
        END as user_type
    FROM tbl_kehadiran tk
    LEFT JOIN users u ON tk.user_id = u.id
    LEFT JOIN siswa s ON s.user_id = u.id
    LEFT JOIN guru g ON g.user_id = u.id
    WHERE DATE(tk.timestamp) = ?
    ORDER BY tk.timestamp DESC
    LIMIT 50
");
$stmt->execute([$today]);
$attendance_today = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik hari ini
$stmt_stats = $conn->prepare("
    SELECT 
        COUNT(*) as total_attendance,
        COUNT(CASE WHEN status = 'Masuk' THEN 1 END) as total_masuk,
        COUNT(CASE WHEN status = 'Keluar' THEN 1 END) as total_keluar
    FROM tbl_kehadiran 
    WHERE DATE(timestamp) = ?
");
$stmt_stats->execute([$today]);
$stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include __DIR__ . '/../templates/navbar.php'; ?>
        
        <div class="container-fluid">
            <!-- Statistik Hari Ini -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Absensi Hari Ini
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_attendance']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar fa-2x text-gray-300"></i>
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
                                        Masuk
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_masuk']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-sign-in-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Keluar
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_keluar']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-sign-out-alt fa-2x text-gray-300"></i>
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
                                        Waktu Server
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="server-time">
                                        <?php echo date('H:i:s'); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabel Absensi Real-time -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Absensi Hari Ini (<?php echo date('d/m/Y'); ?>)</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="attendanceTable" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Waktu</th>
                                            <th>User ID</th>
                                            <th>Nama</th>
                                            <th>Tipe</th>
                                            <th>Status</th>
                                            <th>Mode Verifikasi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $no = 1;
                                        foreach ($attendance_today as $row): 
                                        ?>
                                        <tr class="<?php echo $no <= 5 ? 'table-warning' : ''; ?>">
                                            <td><?php echo $no++; ?></td>
                                            <td>
                                                <strong><?php echo date('H:i:s', strtotime($row['timestamp'])); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo date('d/m/Y', strtotime($row['timestamp'])); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                            <td><?php echo htmlspecialchars($row['mapped_name']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $row['user_type'] == 'Siswa' ? 'primary' : ($row['user_type'] == 'Guru' ? 'success' : 'warning'); ?>">
                                                    <?php echo htmlspecialchars($row['user_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $row['status'] == 'Masuk' ? 'success' : 'danger'; ?>">
                                                    <i class="fas fa-<?php echo $row['status'] == 'Masuk' ? 'sign-in-alt' : 'sign-out-alt'; ?>"></i>
                                                    <?php echo htmlspecialchars($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <i class="fas fa-fingerprint"></i>
                                                    <?php echo htmlspecialchars($row['verification_mode']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                        
                                        <?php if (empty($attendance_today)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> Belum ada data absensi hari ini
                                            </td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grafik Absensi -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Grafik Absensi Hari Ini</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart" width="100%" height="50"></canvas>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Distribusi Tipe Pengguna</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="userTypeChart" width="100%" height="50"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../templates/footer.php'; ?>
</div>

<script>
// Auto-refresh setiap 30 detik
setInterval(function() {
    location.reload();
}, 30000);

// Update waktu server setiap detik
setInterval(function() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('id-ID');
    document.getElementById('server-time').textContent = timeString;
}, 1000);

// Grafik absensi
document.addEventListener('DOMContentLoaded', function() {
    // Grafik absensi per jam
    const ctx1 = document.getElementById('attendanceChart').getContext('2d');
    const attendanceChart = new Chart(ctx1, {
        type: 'line',
        data: {
            labels: ['06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'],
            datasets: [{
                label: 'Masuk',
                data: [0, 5, 15, 8, 3, 2, 1, 0, 0, 0, 0, 0], // Data dummy, bisa disesuaikan
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }, {
                label: 'Keluar',
                data: [0, 0, 0, 0, 0, 0, 0, 1, 2, 5, 8, 12], // Data dummy
                borderColor: 'rgb(255, 99, 132)',
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Grafik distribusi tipe pengguna
    const ctx2 = document.getElementById('userTypeChart').getContext('2d');
    const userTypeChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Siswa', 'Guru', 'Tidak Dikenal'],
            datasets: [{
                data: [<?php 
                    $siswa_count = 0;
                    $guru_count = 0;
                    $unknown_count = 0;
                    foreach ($attendance_today as $row) {
                        if ($row['user_type'] == 'Siswa') $siswa_count++;
                        elseif ($row['user_type'] == 'Guru') $guru_count++;
                        else $unknown_count++;
                    }
                    echo "$siswa_count, $guru_count, $unknown_count";
                ?>],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 205, 86, 0.8)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});
</script>

 