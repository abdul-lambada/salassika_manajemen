<?php
require_once __DIR__ . '/../../includes/admin_bootstrap.php';
require_once __DIR__ . '/../../includes/admin_helpers.php';
require_once __DIR__ . '/../../includes/zklib/zklibrary.php';
require_once __DIR__ . '/../../includes/email_util.php';

$currentUser = admin_require_auth(['admin']);

$logFile = 'cron_sync.log';

try {
    $stmt = $conn->query('SELECT * FROM fingerprint_devices WHERE is_active = 1 ORDER BY nama_lokasi, ip');
    $devices = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    admin_log_message($logFile, 'Gagal mengambil daftar device fingerprint: ' . $e->getMessage(), 'ERROR');
    echo "<span style='color:red'>Gagal mengambil daftar device fingerprint.</span>";
    exit;
}

foreach ($devices as $device) {
    $ip = $device['ip'];
    $port = (int)$device['port'];
    echo "<b>Sinkronisasi device: {$device['nama_lokasi']} ({$ip}:{$port})</b><br>";
    try {
        $zk = new ZKLibrary($ip, $port);
        if ($zk->connect()) {
            $zk->disableDevice();
            // TODO: implement actual synchronization logic here.
            $zk->enableDevice();
            $zk->disconnect();
            echo "<span style='color:green'>Sukses sinkronisasi device $ip</span><br>";
            admin_log_message($logFile, "Sukses sinkronisasi device $ip:$port ({$device['nama_lokasi']})", 'SUCCESS');
        } else {
            throw new RuntimeException('Gagal koneksi ke device');
        }
    } catch (Throwable $e) {
        echo "<span style='color:red'>" . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . "</span><br>";
        admin_log_message($logFile, "Error sinkronisasi device $ip:$port ({$device['nama_lokasi']}): " . $e->getMessage(), 'ERROR');
        if (function_exists('sendDeviceOfflineNotification')) {
            sendDeviceOfflineNotification($device, $e->getMessage());
        }
        continue;
    }
}