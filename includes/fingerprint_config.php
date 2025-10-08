<?php
/**
 * Konfigurasi Fingerprint Device
 * File ini berisi konfigurasi untuk perangkat fingerprint X100-C
 */

// IP Address default perangkat fingerprint
define('FINGERPRINT_IP', '192.168.1.201');

// Port default perangkat fingerprint
define('FINGERPRINT_PORT', 4370);

// Timeout koneksi (dalam detik) - diperpanjang untuk X100-C
define('FINGERPRINT_TIMEOUT', 10);

// Protocol koneksi (TCP atau UDP)
define('FINGERPRINT_PROTOCOL', 'UDP'); // Coba ganti ke 'TCP' jika UDP gagal

// Debug mode untuk troubleshooting
define('FINGERPRINT_DEBUG', true);

// Interval sinkronisasi otomatis (dalam detik)
define('SYNC_INTERVAL', 300); // 5 menit

// Path log file
define('FINGERPRINT_LOG_FILE', __DIR__ . '/../logs/fingerprint_sync.log');

// Konfigurasi database untuk log
define('LOG_TABLE', 'fingerprint_logs');

// Status koneksi
global $CONNECTION_STATUS;
$CONNECTION_STATUS = [
    'SUCCESS' => 'success',
    'ERROR' => 'error',
    'WARNING' => 'warning'
];

// Mode verifikasi
global $VERIFICATION_MODES;
$VERIFICATION_MODES = [
    1 => 'Fingerprint',
    2 => 'PIN',
    3 => 'Card',
    4 => 'Face',
    5 => 'Password'
];

// Status kehadiran
global $ATTENDANCE_STATUS;
$ATTENDANCE_STATUS = [
    0 => 'Masuk',
    1 => 'Keluar'
];

/**
 * Fungsi untuk menulis log fingerprint
 */
function writeFingerprintLog($action, $message, $status = 'success') {
    global $conn;
    
    try {
        $stmt = $conn->prepare("INSERT INTO " . LOG_TABLE . " (action, message, status) VALUES (?, ?, ?)");
        $stmt->execute([$action, $message, $status]);
        
        // Juga tulis ke file log
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] [$status] [$action] $message" . PHP_EOL;
        file_put_contents(FINGERPRINT_LOG_FILE, $log_message, FILE_APPEND | LOCK_EX);
        
        return true;
    } catch (Exception $e) {
        error_log("Error writing fingerprint log: " . $e->getMessage());
        return false;
    }
}

/**
 * Fungsi untuk mendapatkan status koneksi device dengan debug info
 */
function getDeviceStatus($ip = null) {
    if (!$ip) {
        $ip = FINGERPRINT_IP;
    }
    
    try {
        require_once __DIR__ . '/zklib/zklibrary.php';
        $zk = new ZKLibrary($ip, FINGERPRINT_PORT);
        
        if (FINGERPRINT_DEBUG) {
            writeFingerprintLog('CONNECTION_TEST', "Mencoba koneksi ke $ip:" . FINGERPRINT_PORT, 'info');
        }
        
        if ($zk->connect()) {
            $version = $zk->getVersion();
            $zk->disconnect();
            
            if (FINGERPRINT_DEBUG) {
                writeFingerprintLog('CONNECTION_SUCCESS', "Berhasil terhubung ke $ip. Version: $version", 'success');
            }
            
            return [
                'status' => 'connected',
                'message' => "Device terhubung. Version: $version",
                'ip' => $ip,
                'version' => $version
            ];
        } else {
            if (FINGERPRINT_DEBUG) {
                writeFingerprintLog('CONNECTION_FAILED', "Gagal terhubung ke $ip:" . FINGERPRINT_PORT, 'error');
            }
            
            return [
                'status' => 'disconnected',
                'message' => 'Device tidak dapat dihubungi. Periksa IP, port, dan koneksi jaringan.',
                'ip' => $ip
            ];
        }
    } catch (Exception $e) {
        if (FINGERPRINT_DEBUG) {
            writeFingerprintLog('CONNECTION_ERROR', "Error koneksi: " . $e->getMessage(), 'error');
        }
        
        return [
            'status' => 'error',
            'message' => 'Error: ' . $e->getMessage(),
            'ip' => $ip
        ];
    }
}

/**
 * Fungsi untuk test socket connection
 */
function testSocketConnection($ip, $port) {
    // Cek apakah extension sockets tersedia
    if (!extension_loaded('sockets')) {
        return [
            'status' => 'SKIPPED',
            'message' => 'Extension sockets tidak tersedia di PHP'
        ];
    }
    
    $socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    if ($socket === false) {
        return [
            'status' => 'FAILED',
            'message' => 'Gagal membuat socket UDP'
        ];
    }
    
    socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 3, 'usec' => 0));
    
    $result = @socket_sendto($socket, 'test', 4, 0, $ip, $port);
    socket_close($socket);
    
    return [
        'status' => $result !== false ? 'SUCCESS' : 'FAILED',
        'message' => $result !== false ? 'Socket connection berhasil' : 'Socket connection gagal'
    ];
}

/**
 * Fungsi untuk test koneksi dengan detail (pendekatan sederhana seperti test.php)
 */
function testFingerprintConnection($ip = null) {
    if (!$ip) {
        $ip = FINGERPRINT_IP;
    }
    
    $results = [];
    
    try {
        // Test 1: Ping sederhana
        $ping_result = pingDevice($ip);
        $results['ping'] = [
            'name' => 'Ping Test',
            'status' => $ping_result !== 'down' ? 'SUCCESS' : 'FAILED',
            'message' => $ping_result !== 'down' ? "Response time: {$ping_result}ms" : 'Device tidak merespon ping'
        ];
        
        // Test 2: ZKLib connection (pendekatan sederhana seperti test.php)
        require_once __DIR__ . '/zklib/zklibrary.php';
        $zk = new ZKLibrary($ip, FINGERPRINT_PORT);
        
        if ($zk->connect()) {
            $version = $zk->getVersion();
            $zk->disconnect();
            
            $results['zklib'] = [
                'name' => 'ZKLib Connection',
                'status' => 'SUCCESS',
                'message' => "Berhasil terhubung. Version: $version"
            ];
        } else {
            $results['zklib'] = [
                'name' => 'ZKLib Connection',
                'status' => 'FAILED',
                'message' => 'Gagal terhubung ke fingerprint'
            ];
        }
        
    } catch (Exception $e) {
        $results['error'] = [
            'name' => 'Test Error',
            'status' => 'ERROR',
            'message' => 'Error dalam test koneksi: ' . $e->getMessage()
        ];
        
        if (FINGERPRINT_DEBUG) {
            writeFingerprintLog('TEST_CONNECTION_ERROR', $e->getMessage(), 'error');
        }
    }
    
    return $results;
}

/**
 * Fungsi untuk ping device
 */
function pingDevice($ip, $timeout = 2) {
    $time1 = microtime(true);
    $pfile = @fsockopen($ip, FINGERPRINT_PORT, $errno, $errstr, $timeout);
    if (!$pfile) {
        return 'down';
    }
    $time2 = microtime(true);
    fclose($pfile);
    return round((($time2 - $time1) * 1000), 0);
}

/**
 * Fungsi untuk mendapatkan statistik fingerprint
 */
function getFingerprintStats() {
    global $conn;
    
    try {
        // Total user di device
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users");
        $stmt->execute();
        $total_users = $stmt->fetchColumn();
        
        // Total kehadiran hari ini
        $today = date('Y-m-d');
        $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_kehadiran WHERE DATE(timestamp) = ?");
        $stmt->execute([$today]);
        $today_attendance = $stmt->fetchColumn();
        
        // Total kehadiran bulan ini
        $month = date('Y-m');
        $stmt = $conn->prepare("SELECT COUNT(*) FROM tbl_kehadiran WHERE DATE_FORMAT(timestamp, '%Y-%m') = ?");
        $stmt->execute([$month]);
        $month_attendance = $stmt->fetchColumn();
        
        // Status koneksi device
        $device_status = getDeviceStatus();
        
        return [
            'total_users' => $total_users,
            'today_attendance' => $today_attendance,
            'month_attendance' => $month_attendance,
            'device_status' => $device_status
        ];
    } catch (Exception $e) {
        writeFingerprintLog('GET_STATS', 'Error: ' . $e->getMessage(), 'error');
        return false;
    }
}
?>