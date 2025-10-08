<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header("Location: ../../auth/login.php");
    exit;
}
$title = "Test Koneksi Fingerprint";
$active_page = 'test_fingerprint';
include '../../templates/header.php';
include '../../templates/sidebar.php';
include '../../includes/fingerprint_config.php';
include '../../includes/db.php';
// Ambil daftar device fingerprint aktif
$device_stmt = $conn->query("SELECT * FROM fingerprint_devices WHERE is_active = 1 ORDER BY nama_lokasi, ip");
$device_list = $device_stmt->fetchAll(PDO::FETCH_ASSOC);
$device_ip = isset($_POST['device_ip']) ? $_POST['device_ip'] : (count($device_list) ? $device_list[0]['ip'] : FINGERPRINT_IP);

$test_results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_connection'])) {
    try {
        require '../../includes/zklib/zklibrary.php';
        $zk = new ZKLibrary($device_ip, 4370);
        $test_results['connection'] = [
            'name' => 'Koneksi Dasar',
            'status' => $zk->connect() ? 'SUCCESS' : 'FAILED',
            'message' => $zk->connect() ? 'Berhasil terhubung ke perangkat' : 'Gagal terhubung ke perangkat'
        ];
        if ($zk->connect()) {
            $ping_result = $zk->ping();
            $test_results['ping'] = [
                'name' => 'Ping Device',
                'status' => $ping_result !== 'down' ? 'SUCCESS' : 'FAILED',
                'message' => $ping_result !== 'down' ? "Response time: {$ping_result}ms" : 'Device tidak merespon'
            ];
            $test_results['version'] = [
                'name' => 'Informasi Device',
                'status' => 'SUCCESS',
                'message' => 'Version: ' . $zk->getVersion()
            ];
            $zk->disableDevice();
            $users = $zk->getUser();
            $test_results['users'] = [
                'name' => 'Data Pengguna',
                'status' => 'SUCCESS',
                'message' => 'Total pengguna: ' . count($users)
            ];
            $attendance = $zk->getAttendance();
            $test_results['attendance'] = [
                'name' => 'Data Absensi',
                'status' => 'SUCCESS',
                'message' => 'Total record absensi: ' . count($attendance)
            ];
            $device_time = $zk->getTime();
            $test_results['time'] = [
                'name' => 'Waktu Device',
                'status' => 'SUCCESS',
                'message' => 'Device time: ' . $device_time
            ];
            $zk->enableDevice();
            $zk->disconnect();
        } else {
            $test_results['ping'] = [
                'name' => 'Ping Device',
                'status' => 'SKIPPED',
                'message' => 'Test dilewati karena koneksi gagal'
            ];
            $test_results['version'] = [
                'name' => 'Informasi Device',
                'status' => 'SKIPPED',
                'message' => 'Test dilewati karena koneksi gagal'
            ];
            $test_results['users'] = [
                'name' => 'Data Pengguna',
                'status' => 'SKIPPED',
                'message' => 'Test dilewati karena koneksi gagal'
            ];
            $test_results['attendance'] = [
                'name' => 'Data Absensi',
                'status' => 'SKIPPED',
                'message' => 'Test dilewati karena koneksi gagal'
            ];
            $test_results['time'] = [
                'name' => 'Waktu Device',
                'status' => 'SKIPPED',
                'message' => 'Test dilewati karena koneksi gagal'
            ];
        }
    } catch (Exception $e) {
        $test_results['error'] = [
            'name' => 'Error',
            'status' => 'ERROR',
            'message' => $e->getMessage()
        ];
    }
}
?>
<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <?php include '../../templates/navbar.php'; ?>
        <div class="container-fluid">
            <!-- <h1 class="h3 mb-4 text-gray-800">Test Koneksi Fingerprint</h1> -->
            <div class="row">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Test Koneksi Perangkat Fingerprint</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="device_ip">Device Fingerprint:</label>
                                    <select class="form-control" id="device_ip" name="device_ip" required>
                                        <?php foreach ($device_list as $dev): ?>
                                            <option value="<?= htmlspecialchars($dev['ip']) ?>" <?php if ($device_ip == $dev['ip']) echo 'selected'; ?>>
                                                <?= htmlspecialchars($dev['nama_lokasi']) ?> (<?= htmlspecialchars($dev['ip']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="test_connection" class="btn btn-primary">
                                    <i class="fas fa-plug"></i> Test Koneksi
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <?php if (!empty($test_results)): ?>
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Hasil Test</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive mt-3">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Test</th>
                                            <th>Status</th>
                                            <th>Pesan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($test_results as $test): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($test['name']); ?></td>
                                                <td>
                                                    <?php 
                                                    $badge_class = '';
                                                    switch ($test['status']) {
                                                        case 'SUCCESS':
                                                            $badge_class = 'badge-success';
                                                            break;
                                                        case 'FAILED':
                                                            $badge_class = 'badge-danger';
                                                            break;
                                                        case 'ERROR':
                                                            $badge_class = 'badge-danger';
                                                            break;
                                                        case 'SKIPPED':
                                                            $badge_class = 'badge-warning';
                                                            break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $badge_class; ?>">
                                                        <?php echo $test['status']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($test['message']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Summary -->
                            <div class="mt-3">
                                <?php
                                $success_count = 0;
                                $failed_count = 0;
                                $error_count = 0;
                                $skipped_count = 0;
                                foreach ($test_results as $test) {
                                    switch ($test['status']) {
                                        case 'SUCCESS':
                                            $success_count++;
                                            break;
                                        case 'FAILED':
                                            $failed_count++;
                                            break;
                                        case 'ERROR':
                                            $error_count++;
                                            break;
                                        case 'SKIPPED':
                                            $skipped_count++;
                                            break;
                                    }
                                }
                                ?>
                                <div class="alert alert-info">
                                    <strong>Ringkasan Test:</strong><br>
                                    ✅ Berhasil: <?php echo $success_count; ?><br>
                                    ❌ Gagal: <?php echo $failed_count; ?><br>
                                    ⚠️ Error: <?php echo $error_count; ?><br>
                                    ⏭️ Dilewati: <?php echo $skipped_count; ?>
                                </div>
                                <?php if ($failed_count > 0 || $error_count > 0): ?>
                                    <div class="alert alert-warning">
                                        <strong>Troubleshooting untuk X100-C:</strong><br>
                                        1. <strong>Periksa IP Address:</strong> Pastikan IP address benar (biasanya 192.168.1.201 atau 192.168.1.100)<br>
                                        2. <strong>Koneksi Jaringan:</strong> Pastikan komputer dan fingerprint terhubung ke jaringan yang sama<br>
                                        3. <strong>Port 4370:</strong> Pastikan port 4370 tidak diblokir firewall<br>
                                        4. <strong>Power Supply:</strong> Pastikan fingerprint mendapat power yang cukup<br>
                                        5. <strong>Restart Device:</strong> Restart fingerprint dan tunggu 30 detik<br>
                                        6. <strong>Kabel LAN:</strong> Periksa kabel LAN dan koneksi RJ45<br>
                                        7. <strong>Ping Test:</strong> Coba ping IP fingerprint dari command prompt<br>
                                        8. <strong>Firmware:</strong> Periksa apakah firmware X100-C sudah terbaru<br>
                                        9. <strong>Admin Mode:</strong> Pastikan fingerprint tidak dalam mode admin/enrollment<br>
                                        10. <strong>Antivirus:</strong> Nonaktifkan sementara antivirus yang mungkin memblokir koneksi
                                    </div>
                                    <div class="alert alert-info">
                                        <strong>Langkah Debug Lanjutan:</strong><br>
                                        1. Cek log file di: <code><?php echo defined('FINGERPRINT_LOG_FILE') ? FINGERPRINT_LOG_FILE : '-'; ?></code><br>
                                        2. Coba akses fingerprint via browser: <code>http://<?php echo $device_ip; ?></code><br>
                                        3. Test dengan software ZKTeco Admin jika tersedia<br>
                                        4. Periksa apakah fingerprint mendukung protokol ZKLib<br>
                                        5. <strong>Extension Sockets:</strong> Jika socket test SKIPPED, aktifkan extension sockets di php.ini
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php include '../../templates/footer.php'; ?>
</div> 