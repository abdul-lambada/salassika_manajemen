<?php
session_start();
include '../../includes/db.php';
include '../../includes/advanced_stats_helper.php';

if (!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin', 'guru'])) {
    header("Location: ../auth/login.php");
    exit;
}

$title = "Dashboard Real-time";
$active_page = "dashboard_realtime";

// AJAX request untuk data real-time
if (isset($_GET['ajax']) && $_GET['ajax'] === 'realtime') {
    try {
        // Statistik hari ini
        $stats_query = "
            SELECT 
                (SELECT COUNT(*) FROM users WHERE role = 'guru') as total_guru,
                (SELECT COUNT(*) FROM users WHERE role = 'siswa') as total_siswa,
                (SELECT COUNT(*) FROM absensi_guru WHERE tanggal = CURDATE() AND status_kehadiran = 'Hadir') as guru_hadir,
                (SELECT COUNT(*) FROM absensi_siswa WHERE tanggal = CURDATE() AND status_kehadiran = 'Hadir') as siswa_hadir,
                (SELECT COUNT(*) FROM absensi_guru WHERE tanggal = CURDATE() AND status_kehadiran = 'Telat') as guru_telat,
                (SELECT COUNT(*) FROM absensi_siswa WHERE tanggal = CURDATE() AND status_kehadiran = 'Telat') as siswa_telat,
                (SELECT COUNT(*) FROM tbl_kehadiran WHERE DATE(timestamp) = CURDATE()) as total_fingerprint
        ";
        $stmt_stats = $conn->prepare($stats_query);
        $stmt_stats->execute();
        $stats = $stmt_stats->fetch(PDO::FETCH_ASSOC);

        // Absensi terbaru (5 menit terakhir)
        $recent_query = "
            SELECT 
                u.name as nama_user,
                u.role as tipe_user,
                kh.timestamp as waktu_fingerprint,
                kh.verification_mode,
                g.nip,
                s.nis,
                k.nama_kelas
            FROM tbl_kehadiran kh
            JOIN users u ON kh.user_id = u.id
            LEFT JOIN guru g ON u.id = g.user_id
            LEFT JOIN siswa s ON u.id = s.user_id
            LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
            WHERE kh.timestamp >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ORDER BY kh.timestamp DESC
            LIMIT 10
        ";
        $stmt_recent = $conn->prepare($recent_query);
        $stmt_recent->execute();
        $recent_attendance = $stmt_recent->fetchAll(PDO::FETCH_ASSOC);

        // Data untuk grafik (7 hari terakhir)
        $chart_query = "
            SELECT 
                DATE(tanggal) as tanggal,
                COUNT(*) as total_absensi,
                SUM(CASE WHEN status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN status_kehadiran = 'Telat' THEN 1 ELSE 0 END) as telat
            FROM (
                SELECT tanggal, status_kehadiran FROM absensi_guru WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                UNION ALL
                SELECT tanggal, status_kehadiran FROM absensi_siswa WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            ) combined
            GROUP BY tanggal
            ORDER BY tanggal
        ";
        $stmt_chart = $conn->prepare($chart_query);
        $stmt_chart->execute();
        $chart_data = $stmt_chart->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'stats' => $stats,
            'recent_attendance' => $recent_attendance,
            'chart_data' => $chart_data,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;

    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

include '../../templates/header.php';
include '../../templates/sidebar.php';
?>

<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Dashboard Real-time</h1> -->
            
            <!-- Last Update Indicator -->
            <div class="alert alert-info" id="lastUpdate">
                <i class="fas fa-sync-alt"></i> 
                Data terakhir diperbarui: <span id="updateTime">-</span>
                <button class="btn btn-sm btn-outline-info ml-2" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <!-- Statistik Real-time -->
            <div class="row mb-4" id="statsContainer">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Guru</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalGuru">-</div>
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
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Siswa</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalSiswa">-</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
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
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Hadir Hari Ini</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="hadirHariIni">-</div>
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
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Telat Hari Ini</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="telatHariIni">-</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Grafik Absensi -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Grafik Absensi 7 Hari Terakhir</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Absensi Terbaru -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Absensi Terbaru (5 Menit)</h6>
                        </div>
                        <div class="card-body">
                            <div id="recentAttendance">
                                <div class="text-center text-muted">
                                    <i class="fas fa-spinner fa-spin"></i> Loading...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../templates/footer.php'; ?>
</div>

<script src="../assets/vendor/chart.js/Chart.js"></script>
<script>
let attendanceChart;
let refreshInterval;

// Initialize chart
function initChart() {
    const ctx = document.getElementById('attendanceChart').getContext('2d');
    attendanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: [],
            datasets: [{
                label: 'Hadir',
                data: [],
                borderColor: 'rgb(40, 167, 69)',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                tension: 0.1
            }, {
                label: 'Telat',
                data: [],
                borderColor: 'rgb(255, 193, 7)',
                backgroundColor: 'rgba(255, 193, 7, 0.1)',
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
}

// Refresh data
function refreshData() {
    fetch('dashboard_realtime.php?ajax=realtime')
        .then(response => response.json())
        .then(data => {
            // Update stats
            document.getElementById('totalGuru').textContent = data.stats.total_guru;
            document.getElementById('totalSiswa').textContent = data.stats.total_siswa;
            document.getElementById('hadirHariIni').textContent = parseInt(data.stats.guru_hadir) + parseInt(data.stats.siswa_hadir);
            document.getElementById('telatHariIni').textContent = parseInt(data.stats.guru_telat) + parseInt(data.stats.siswa_telat);
            
            // Update chart
            updateChart(data.chart_data);
            
            // Update recent attendance
            updateRecentAttendance(data.recent_attendance);
            
            // Update timestamp
            document.getElementById('updateTime').textContent = data.timestamp;
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            document.getElementById('lastUpdate').className = 'alert alert-danger';
            document.getElementById('lastUpdate').innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error loading data';
        });
}

// Update chart
function updateChart(chartData) {
    const labels = chartData.map(item => {
        const date = new Date(item.tanggal);
        return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
    });
    
    const hadirData = chartData.map(item => item.hadir);
    const telatData = chartData.map(item => item.telat);
    
    attendanceChart.data.labels = labels;
    attendanceChart.data.datasets[0].data = hadirData;
    attendanceChart.data.datasets[1].data = telatData;
    attendanceChart.update();
}

// Update recent attendance
function updateRecentAttendance(recentData) {
    const container = document.getElementById('recentAttendance');
    
    if (recentData.length === 0) {
        container.innerHTML = '<div class="text-center text-muted">Tidak ada absensi terbaru</div>';
        return;
    }
    
    let html = '';
    recentData.forEach(item => {
        const time = new Date(item.waktu_fingerprint).toLocaleTimeString('id-ID', { 
            hour: '2-digit', 
            minute: '2-digit' 
        });
        
        html += `
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong>${item.nama_user}</strong>
                    <br>
                    <small class="text-muted">
                        ${item.tipe_user === 'guru' ? 'Guru' : 'Siswa'} 
                        ${item.nip ? `(${item.nip})` : ''}
                        ${item.nis ? `(${item.nis})` : ''}
                        ${item.k.nama_kelas ? ` - ${item.k.nama_kelas}` : ''}
                    </small>
                    <br>
                    <small class="text-muted">
                        ${item.verification_mode === 'fingerprint' ? 'Fingerprint' : 'Face Recognition'}
                    </small>
                </div>
                <div>
                    <small class="text-muted">${time}</small>
                </div>
            </div>
            <hr class="my-2">
        `;
    });
    container.innerHTML = html;
}