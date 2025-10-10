<?php
require_once __DIR__ . '/../includes/admin_bootstrap.php';
require_once __DIR__ . '/../includes/admin_helpers.php';

$currentUser = admin_require_auth(['admin', 'guru']);

$title = 'Absensi Real-time';
$active_page = 'realtime_attendance';
$required_role = ($currentUser['role'] ?? '') === 'admin' ? null : 'guru';

$todayDate = date('Y-m-d');
$currentTime = date('H:i:s');

$stats = [
    'total_attendance' => 0,
    'total_masuk' => 0,
    'total_keluar' => 0,
];

$attendance_today = [];
$hourly_labels = [];
$hourly_masuk = [];
$hourly_keluar = [];
$user_type_counts = [
    'Siswa' => 0,
    'Guru' => 0,
    'Tidak Dikenal' => 0,
];

$error_message = '';

try {
    $stmtAttendance = $conn->prepare(
        "SELECT
            tk.timestamp,
            tk.user_id,
            tk.user_name,
            tk.status,
            tk.verification_mode,
            CASE
                WHEN s.nama_siswa IS NOT NULL THEN CONCAT('Siswa: ', s.nama_siswa)
                WHEN g.nama_guru IS NOT NULL THEN CONCAT('Guru: ', g.nama_guru)
                ELSE COALESCE(tk.user_name, '-')
            END AS mapped_name,
            CASE
                WHEN s.id_siswa IS NOT NULL THEN 'Siswa'
                WHEN g.id_guru IS NOT NULL THEN 'Guru'
                ELSE 'Tidak Dikenal'
            END AS user_type
        FROM tbl_kehadiran tk
        LEFT JOIN users u ON tk.user_id = u.id
        LEFT JOIN siswa s ON s.user_id = u.id
        LEFT JOIN guru g ON g.user_id = u.id
        WHERE DATE(tk.timestamp) = :today
        ORDER BY tk.timestamp DESC
        LIMIT 50"
    );
    $stmtAttendance->execute([':today' => $todayDate]);
    $attendance_today = $stmtAttendance->fetchAll(PDO::FETCH_ASSOC);

    $stmtStats = $conn->prepare(
        'SELECT
            COUNT(*) AS total_attendance,
            SUM(CASE WHEN status = :status_masuk THEN 1 ELSE 0 END) AS total_masuk,
            SUM(CASE WHEN status = :status_keluar THEN 1 ELSE 0 END) AS total_keluar
        FROM tbl_kehadiran
        WHERE DATE(timestamp) = :today'
    );
    $stmtStats->execute([
        ':status_masuk' => 'Masuk',
        ':status_keluar' => 'Keluar',
        ':today' => $todayDate,
    ]);
    $statsRow = $stmtStats->fetch(PDO::FETCH_ASSOC);
    if ($statsRow) {
        $stats['total_attendance'] = (int) ($statsRow['total_attendance'] ?? 0);
        $stats['total_masuk'] = (int) ($statsRow['total_masuk'] ?? 0);
        $stats['total_keluar'] = (int) ($statsRow['total_keluar'] ?? 0);
    }

    $stmtHourly = $conn->prepare(
        'SELECT
            HOUR(timestamp) AS jam,
            SUM(CASE WHEN status = :status_masuk THEN 1 ELSE 0 END) AS masuk,
            SUM(CASE WHEN status = :status_keluar THEN 1 ELSE 0 END) AS keluar
        FROM tbl_kehadiran
        WHERE DATE(timestamp) = :today
        GROUP BY HOUR(timestamp)
        ORDER BY jam'
    );
    $stmtHourly->execute([
        ':status_masuk' => 'Masuk',
        ':status_keluar' => 'Keluar',
        ':today' => $todayDate,
    ]);
    $hourlyRows = $stmtHourly->fetchAll(PDO::FETCH_ASSOC);
    foreach ($hourlyRows as $row) {
        $hourLabel = str_pad((string) ($row['jam'] ?? '0'), 2, '0', STR_PAD_LEFT) . ':00';
        $hourly_labels[] = $hourLabel;
        $hourly_masuk[] = (int) ($row['masuk'] ?? 0);
        $hourly_keluar[] = (int) ($row['keluar'] ?? 0);
    }

    $stmtTypes = $conn->prepare(
        'SELECT
            SUM(CASE WHEN s.id_siswa IS NOT NULL THEN 1 ELSE 0 END) AS total_siswa,
            SUM(CASE WHEN g.id_guru IS NOT NULL THEN 1 ELSE 0 END) AS total_guru,
            SUM(CASE WHEN s.id_siswa IS NULL AND g.id_guru IS NULL THEN 1 ELSE 0 END) AS total_unknown
        FROM tbl_kehadiran tk
        LEFT JOIN users u ON tk.user_id = u.id
        LEFT JOIN siswa s ON s.user_id = u.id
        LEFT JOIN guru g ON g.user_id = u.id
        WHERE DATE(tk.timestamp) = :today'
    );
    $stmtTypes->execute([':today' => $todayDate]);
    $typeRow = $stmtTypes->fetch(PDO::FETCH_ASSOC);
    if ($typeRow) {
        $user_type_counts['Siswa'] = (int) ($typeRow['total_siswa'] ?? 0);
        $user_type_counts['Guru'] = (int) ($typeRow['total_guru'] ?? 0);
        $user_type_counts['Tidak Dikenal'] = (int) ($typeRow['total_unknown'] ?? 0);
    }
} catch (PDOException $e) {
    admin_log_message('realtime_attendance_errors.log', 'Database error: ' . $e->getMessage(), 'ERROR');
    $error_message = 'Terjadi kesalahan saat memuat data absensi real-time.';
}

$lineChartData = [
    'labels' => $hourly_labels,
    'datasets' => [
        [
            'label' => 'Masuk',
            'data' => $hourly_masuk,
            'borderColor' => 'rgb(75, 192, 192)',
            'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
            'tension' => 0.1,
            'fill' => true,
        ],
        [
            'label' => 'Keluar',
            'data' => $hourly_keluar,
            'borderColor' => 'rgb(255, 99, 132)',
            'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
            'tension' => 0.1,
            'fill' => true,
        ],
    ],
];

$userTypeChartData = [
    'labels' => array_keys($user_type_counts),
    'datasets' => [
        [
            'data' => array_values($user_type_counts),
            'backgroundColor' => [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(255, 205, 86, 0.8)',
            ],
            'borderWidth' => 2,
        ],
    ],
];
?>
<?php include __DIR__ . '/../templates/layout_start.php'; ?>
        <div class="container-fluid">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 font-weight-bold text-gray-800">Absensi Real-time</h1>
                <span class="badge badge-pill badge-success d-flex align-items-center px-3 py-2" style="font-size: 1rem;">
                    <i class="fas fa-circle mr-2" style="font-size: 0.75rem;"></i>
                    Real-time
                </span>
            </div>

            <?php if ($error_message !== ''): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            <?php endif; ?>

            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Absensi Hari Ini</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_attendance']; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Masuk</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_masuk']; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Keluar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $stats['total_keluar']; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Waktu Server</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="server-time"><?= htmlspecialchars($currentTime, ENT_QUOTES, 'UTF-8'); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Absensi Hari Ini (<?= htmlspecialchars(date('d/m/Y'), ENT_QUOTES, 'UTF-8'); ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
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
                                <?php if (!empty($attendance_today)): ?>
                                    <?php foreach ($attendance_today as $index => $row): ?>
                                        <?php
                                        $rowClass = $index < 5 ? 'table-warning' : '';
                                        $userType = (string) ($row['user_type'] ?? 'Tidak Dikenal');
                                        $status = (string) ($row['status'] ?? '');
                                        $statusBadge = 'badge-secondary';
                                        if ($status === 'Masuk') {
                                            $statusBadge = 'badge-success';
                                        } elseif ($status === 'Keluar') {
                                            $statusBadge = 'badge-danger';
                                        }
                                        $typeBadge = 'badge-warning';
                                        if ($userType === 'Siswa') {
                                            $typeBadge = 'badge-primary';
                                        } elseif ($userType === 'Guru') {
                                            $typeBadge = 'badge-success';
                                        }
                                        ?>
                                        <tr class="<?= $rowClass; ?>">
                                            <td><?= $index + 1; ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars(date('H:i:s', strtotime((string) $row['timestamp'])), ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars(date('d/m/Y', strtotime((string) $row['timestamp'])), ENT_QUOTES, 'UTF-8'); ?></small>
                                            </td>
                                            <td><?= htmlspecialchars((string) ($row['user_id'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?= htmlspecialchars((string) ($row['mapped_name'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="badge <?= $typeBadge; ?>"><?= htmlspecialchars($userType, ENT_QUOTES, 'UTF-8'); ?></span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $statusBadge; ?>">
                                                    <i class="fas fa-<?= $status === 'Masuk' ? 'sign-in-alt' : ($status === 'Keluar' ? 'sign-out-alt' : 'question'); ?>"></i>
                                                    <?= htmlspecialchars($status !== '' ? $status : 'Tidak Dikenal', ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <i class="fas fa-fingerprint"></i>
                                                    <?= htmlspecialchars((string) ($row['verification_mode'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">
                                            <i class="fas fa-info-circle"></i> Belum ada data absensi hari ini.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Grafik Absensi Hari Ini</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="attendanceChart" height="220"></canvas>
                            <?php if (empty($hourly_labels)): ?>
                                <p class="text-muted mt-3">Belum ada data absensi untuk grafik hari ini.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Distribusi Tipe Pengguna</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="userTypeChart" height="220"></canvas>
                            <?php if (array_sum($user_type_counts) === 0): ?>
                                <p class="text-muted mt-3">Belum ada data untuk ditampilkan.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            (function() {
                var reloadInterval = 30000;
                setInterval(function() {
                    window.location.reload();
                }, reloadInterval);

                setInterval(function() {
                    var el = document.getElementById('server-time');
                    if (!el) {
                        return;
                    }
                    var now = new Date();
                    el.textContent = now.toLocaleTimeString('id-ID');
                }, 1000);

                if (window.Chart) {
                    var lineChartElement = document.getElementById('attendanceChart');
                    var lineChartData = <?= json_encode($lineChartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
                    if (lineChartElement && (lineChartData.labels || []).length > 0) {
                        new Chart(lineChartElement.getContext('2d'), {
                            type: 'line',
                            data: lineChartData,
                            options: {
                                maintainAspectRatio: false,
                                responsive: true,
                                scales: {
                                    yAxes: [{
                                        ticks: {
                                            beginAtZero: true,
                                            precision: 0
                                        }
                                    }],
                                    xAxes: [{
                                        gridLines: {
                                            display: false
                                        }
                                    }]
                                },
                                legend: {
                                    display: true
                                }
                            }
                        });
                    }

                    var userTypeElement = document.getElementById('userTypeChart');
                    var userTypeData = <?= json_encode($userTypeChartData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
                    var totalUserTypes = (userTypeData.datasets && userTypeData.datasets[0] && userTypeData.datasets[0].data)
                        ? userTypeData.datasets[0].data.reduce(function(sum, value) { return sum + value; }, 0)
                        : 0;
                    if (userTypeElement && totalUserTypes > 0) {
                        new Chart(userTypeElement.getContext('2d'), {
                            type: 'doughnut',
                            data: userTypeData,
                            options: {
                                maintainAspectRatio: false,
                                responsive: true,
                                legend: {
                                    position: 'bottom'
                                }
                            }
                        });
                    }
                }
            })();
        </script>
<?php include __DIR__ . '/../templates/layout_end.php'; ?>