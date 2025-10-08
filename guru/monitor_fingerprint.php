<?php
session_start();
include '../includes/db.php';

// Periksa apakah sesi 'user' tersedia
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'guru') {
    header("Location: ../auth/login.php");
    exit;
}

$active_page = "monitor_fingerprint";
$message = '';

try {
    // Ambil statistik fingerprint hari ini
    $stmt_stats = $conn->prepare("
        SELECT 
            COUNT(*) as total_absensi,
            COUNT(DISTINCT user_id) as total_user,
            MIN(timestamp) as absen_pertama,
            MAX(timestamp) as absen_terakhir
        FROM tbl_kehadiran 
        WHERE DATE(timestamp) = CURDATE()
    ");
    $stmt_stats->execute();
    $fingerprint_stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

    // Ambil data absensi fingerprint hari ini
    $stmt_today = $conn->prepare("
        SELECT 
            kh.timestamp,
            kh.verification_mode,
            kh.status,
            COALESCE(u.name, s.nama_siswa, g.nama_guru) as nama_user,
            u.uid as username,
            CASE 
                WHEN s.id_siswa IS NOT NULL THEN 'Siswa'
                WHEN g.id_guru IS NOT NULL THEN 'Guru'
                ELSE 'Unknown'
            END as tipe_user,
            COALESCE(s.nis, g.nip) as nomor_id,
            COALESCE(k.nama_kelas, '') as kelas
        FROM tbl_kehadiran kh
        LEFT JOIN users u ON kh.user_id = u.id
        LEFT JOIN siswa s ON s.user_id = u.id
        LEFT JOIN guru g ON g.user_id = u.id
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        WHERE DATE(kh.timestamp) = CURDATE()
        ORDER BY kh.timestamp DESC
        LIMIT 50
    ");
    $stmt_today->execute();
    $absensi_today = $stmt_today->fetchAll(PDO::FETCH_ASSOC);

    // Ambil statistik per jam
    $stmt_hourly = $conn->prepare("
        SELECT 
            HOUR(timestamp) as jam,
            COUNT(*) as jumlah
        FROM tbl_kehadiran 
        WHERE DATE(timestamp) = CURDATE()
        GROUP BY HOUR(timestamp)
        ORDER BY jam
    ");
    $stmt_hourly->execute();
    $hourly_stats = $stmt_hourly->fetchAll(PDO::FETCH_ASSOC);

    // Ambil data siswa yang belum absen fingerprint hari ini
    $stmt_missing = $conn->prepare("
        SELECT 
            u.name as nama_siswa,
            s.nis,
            k.nama_kelas
        FROM siswa s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
        LEFT JOIN tbl_kehadiran kh ON s.user_id = kh.user_id AND DATE(kh.timestamp) = CURDATE()
        WHERE kh.user_id IS NULL
        ORDER BY k.nama_kelas, u.name
        LIMIT 20
    ");
    $stmt_missing->execute();
    $missing_students = $stmt_missing->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monitor Fingerprint - Management Salassika</title>
    <link rel="icon" type="image/jpeg" href="../assets/img/logo.jpg">
    <link href="../vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="../css/sb-admin-2.css" rel="stylesheet">
    <script src="../vendor/chart.js/Chart.js"></script>
    <style>
        .fingerprint-badge {
            background-color: #007bff;
            color: white;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 0.7em;
        }

        .status-success {
            background-color: #d4edda;
            color: #155724;
        }

        .status-warning {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .real-time-indicator {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0.5;
            }

            100% {
                opacity: 1;
            }
        }

        .pulsing-badge {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            animation: pulse-green 2s infinite;
        }

        @keyframes pulse-green {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
            }
            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(40, 167, 69, 0);
            }
            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
            }
        }
    </style>
</head>

<body id="page-top">
    <?php include __DIR__ . '/../templates/header.php'; ?>
    <?php include __DIR__ . '/../templates/sidebar.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">
            
            <?php include __DIR__ . '/../templates/navbar.php'; ?>
            <div class="container-fluid">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <!-- <h1 class="h3 mb-0 font-weight-bold text-gray-800">Monitor Fingerprint</h1> -->
                    <span class="badge badge-pill badge-success d-flex align-items-center px-3 py-2 pulsing-badge" style="font-size: 1rem;">
                        <i class="fas fa-circle mr-2" style="font-size: 0.8rem;"></i>
                        Real-time
                    </span>
                </div>

                <!-- Statistik Fingerprint -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card border-left-primary shadow h-100 py-2">
                            <div class="card-body">
                                <div class="row no-gutters align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                            Total Absensi Hari Ini</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $fingerprint_stats['total_absensi']; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-fingerprint fa-2x text-gray-300"></i>
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
                                            Total User</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $fingerprint_stats['total_user']; ?></div>
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
                                            Absen Pertama</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $fingerprint_stats['absen_pertama'] ? date('H:i', strtotime($fingerprint_stats['absen_pertama'])) : '-'; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-sun fa-2x text-gray-300"></i>
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
                                            Absen Terakhir</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                                            <?php echo $fingerprint_stats['absen_terakhir'] ? date('H:i', strtotime($fingerprint_stats['absen_terakhir'])) : '-'; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="fas fa-moon fa-2x text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Grafik Absensi per Jam -->
                    <div class="col-xl-6 col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Absensi per Jam</h6>
                            </div>
                            <div class="card-body">
                                <canvas id="hourlyChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Siswa yang Belum Absen -->
                    <div class="col-xl-6 col-lg-6">
                        <div class="card shadow mb-4">
                            <div class="card-header py-3">
                                <h6 class="m-0 font-weight-bold text-primary">Siswa Belum Absen</h6>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($missing_students)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Nama</th>
                                                    <th>NIS</th>
                                                    <th>Kelas</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($missing_students as $student): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($student['nama_siswa']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['nis']); ?></td>
                                                        <td><?php echo htmlspecialchars($student['nama_kelas']); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-center text-success">
                                        <i class="fas fa-check-circle"></i> Semua siswa sudah absen!
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Absensi Real-time -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Absensi Real-time Hari Ini</h6>
                        <button class="btn btn-sm btn-primary" onclick="location.reload()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($absensi_today)): ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Waktu</th>
                                            <th>Nama</th>
                                            <th>Tipe</th>
                                            <th>ID</th>
                                            <th>Kelas</th>
                                            <th>Mode</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($absensi_today as $absensi): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo date('H:i:s', strtotime($absensi['timestamp'])); ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?php echo date('d/m/Y', strtotime($absensi['timestamp'])); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($absensi['nama_user']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $absensi['tipe_user'] == 'Siswa' ? 'primary' : 'info'; ?>">
                                                        <?php echo htmlspecialchars($absensi['tipe_user']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($absensi['nomor_id']); ?></td>
                                                <td><?php echo htmlspecialchars($absensi['kelas'] ?: '-'); ?></td>
                                                <td>
                                                    <span class="fingerprint-badge">
                                                        <?php echo htmlspecialchars($absensi['verification_mode']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="status-<?php echo $absensi['status'] == 'SUCCESS' ? 'success' : 'danger'; ?>">
                                                        <?php echo htmlspecialchars($absensi['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted">Belum ada data absensi fingerprint hari ini</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php include __DIR__ . '/../templates/footer.php'; ?>
    </div>

    <script>
        // Grafik absensi per jam
        var ctx = document.getElementById('hourlyChart').getContext('2d');
        var hourlyChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [
                    <?php
                    $labels = [];
                    $data = [];
                    for ($i = 0; $i < 24; $i++) {
                        $labels[] = "'" . sprintf('%02d:00', $i) . "'";
                        $data[] = 0;
                    }
                    foreach ($hourly_stats as $stat) {
                        $data[$stat['jam']] = $stat['jumlah'];
                    }
                    echo implode(',', $labels);
                    ?>
                ],
                datasets: [{
                    label: 'Jumlah Absensi',
                    data: [<?php echo implode(',', $data); ?>],
                    backgroundColor: 'rgba(78, 115, 223, 0.5)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Auto refresh setiap 30 detik
        setInterval(function() {
            location.reload();
        }, 30000);
    </script>
</body>

</html>