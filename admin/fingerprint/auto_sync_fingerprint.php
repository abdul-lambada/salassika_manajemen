<?php
include_once '../../includes/email_util.php';

// Tambahkan fungsi logging
function write_log($message, $status = 'INFO') {
    $log_file = __DIR__ . '/../../logs/cron_sync.log';
    $time = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$time][$status] $message\n", FILE_APPEND);
}

// ... (other code)

foreach ($devices as $device) {
    $ip = $device['ip'];
    $port = $device['port'];
    echo "<b>Sinkronisasi device: {$device['nama_lokasi']} ({$ip}:{$port})</b><br>";
    try {
        $zk = new ZKLibrary($ip, $port);
        if ($zk->connect()) {
            $zk->disableDevice();
            // ... sinkronisasi ...
            $zk->enableDevice();
            $zk->disconnect();
            echo "<span style='color:green'>Sukses sinkronisasi device $ip</span><br>";
            write_log("Sukses sinkronisasi device $ip:$port ({$device['nama_lokasi']})", 'SUCCESS');
        } else {
            echo "<span style='color:red'>Gagal koneksi ke device $ip</span><br>";
            write_log("Gagal koneksi ke device $ip:$port ({$device['nama_lokasi']})", 'ERROR');
            if (function_exists('sendDeviceOfflineNotification')) {
                sendDeviceOfflineNotification($device, 'Gagal koneksi ke device');
            }
        }
    } catch (Exception $e) {
        echo "<span style='color:red'>Error sinkronisasi device $ip: " . $e->getMessage() . "</span><br>";
        write_log("Error sinkronisasi device $ip:$port ({$device['nama_lokasi']}): " . $e->getMessage(), 'ERROR');
        if (function_exists('sendDeviceOfflineNotification')) {
            sendDeviceOfflineNotification($device, $e->getMessage());
        }
        // Lanjutkan ke device berikutnya
        continue;
    }
}

// ... (other code) 