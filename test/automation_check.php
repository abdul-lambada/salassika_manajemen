<?php
/**
 * Comprehensive WhatsApp Automation Check Script
 * Checks all components of the WhatsApp & Absensi automation system
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/attendance_whatsapp_automation.php';

echo "=== PENGECEKAN FITUR OTOMATISASI WHATSAPP & ABSENSI ===\n\n";

// 1. Check Database Tables
echo "1. PENGECEKAN DATABASE TABLES:\n";
$tables = ['whatsapp_automation_config', 'whatsapp_automation_logs', 'whatsapp_config', 'users', 'siswa', 'guru'];
foreach($tables as $table) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM $table");
        $count = $stmt->fetch()['count'];
        echo "   ✓ $table: $count records\n";
    } catch(Exception $e) {
        echo "   ✗ $table: ERROR - " . $e->getMessage() . "\n";
    }
}

// 2. Check WhatsApp Configuration
echo "\n2. PENGECEKAN KONFIGURASI WHATSAPP:\n";
try {
    $stmt = $conn->query("SELECT * FROM whatsapp_config LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($config) {
        echo "   ✓ WhatsApp Config: " . ($config['is_active'] ? 'AKTIF' : 'TIDAK AKTIF') . "\n";
        echo "   ✓ API Token: " . (strlen($config['api_token']) > 10 ? 'TERSEDIA' : 'TIDAK LENGKAP') . "\n";
        echo "   ✓ Target: " . $config['target'] . "\n";
    } else {
        echo "   ✗ WhatsApp Config: TIDAK DITEMUKAN\n";
    }
} catch(Exception $e) {
    echo "   ✗ WhatsApp Config Error: " . $e->getMessage() . "\n";
}

// 3. Check Automation Configuration
echo "\n3. PENGECEKAN KONFIGURASI OTOMATISASI:\n";
try {
    $stmt = $conn->query("SELECT * FROM whatsapp_automation_config WHERE is_active = 1 LIMIT 1");
    $autoConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($autoConfig) {
        echo "   ✓ Automation Config: AKTIF\n";
        echo "   ✓ Notify Late Arrival: " . ($autoConfig['notify_late_arrival'] ? 'YA' : 'TIDAK') . "\n";
        echo "   ✓ Notify Absence: " . ($autoConfig['notify_absence'] ? 'YA' : 'TIDAK') . "\n";
        echo "   ✓ Notify Parents: " . ($autoConfig['notify_parents'] ? 'YA' : 'TIDAK') . "\n";
        echo "   ✓ Notify Admin: " . ($autoConfig['notify_admin'] ? 'YA' : 'TIDAK') . "\n";
        echo "   ✓ Late Threshold: " . $autoConfig['late_threshold_minutes'] . " menit\n";
        echo "   ✓ Absence Check Time: " . $autoConfig['absence_check_time'] . "\n";
    } else {
        echo "   ✗ Automation Config: TIDAK AKTIF atau TIDAK DITEMUKAN\n";
    }
} catch(Exception $e) {
    echo "   ✗ Automation Config Error: " . $e->getMessage() . "\n";
}

// 4. Test Automation Class
echo "\n4. PENGECEKAN CLASS OTOMATISASI:\n";
try {
    $automation = new AttendanceWhatsAppAutomation($conn);
    echo "   ✓ AttendanceWhatsAppAutomation Class: BERHASIL DIINISIALISASI\n";
    
    // Test daily summary
    $summary = $automation->returnDailyAttendanceSummary();
    if ($summary) {
        echo "   ✓ Daily Summary Function: BERFUNGSI\n";
        echo "     - Hadir: " . (isset($summary['hadir']) ? $summary['hadir'] : 0) . "\n";
        echo "     - Terlambat: " . (isset($summary['terlambat']) ? $summary['terlambat'] : 0) . "\n";
        echo "     - Tidak Hadir: " . (isset($summary['tidak_hadir']) ? $summary['tidak_hadir'] : 0) . "\n";
        echo "     - Izin: " . (isset($summary['izin']) ? $summary['izin'] : 0) . "\n";
        echo "     - Sakit: " . (isset($summary['sakit']) ? $summary['sakit'] : 0) . "\n";
    } else {
        echo "   ✗ Daily Summary Function: ERROR\n";
    }
    
    // Test absent check
    $absentCheck = $automation->checkAbsentStudents();
    if ($absentCheck['success']) {
        echo "   ✓ Absent Check Function: BERFUNGSI\n";
        echo "     - Siswa tidak hadir: " . $absentCheck['absent_count'] . "\n";
    } else {
        echo "   ✗ Absent Check Function: ERROR - " . $absentCheck['message'] . "\n";
    }
    
} catch(Exception $e) {
    echo "   ✗ Automation Class Error: " . $e->getMessage() . "\n";
}

// 5. Check File Permissions and Structure
echo "\n5. PENGECEKAN FILE DAN STRUKTUR:\n";
$files = [
    'includes/attendance_whatsapp_automation.php',
    'includes/wa_util.php',
    'admin/whatsapp/automation_settings.php',
    'admin/whatsapp/automation_logs.php',
    'cron/whatsapp_attendance_automation.php'
];

foreach($files as $file) {
    $fullPath = __DIR__ . '/../' . $file;
    if (file_exists($fullPath)) {
        echo "   ✓ $file: ADA\n";
    } else {
        echo "   ✗ $file: TIDAK DITEMUKAN\n";
    }
}

// 6. Check Recent Logs
echo "\n6. PENGECEKAN LOG OTOMATISASI TERBARU:\n";
try {
    $stmt = $conn->query("SELECT * FROM whatsapp_automation_logs ORDER BY created_at DESC LIMIT 5");
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($logs) > 0) {
        echo "   ✓ Ditemukan " . count($logs) . " log terbaru:\n";
        foreach($logs as $log) {
            $status = $log['status'] == 'success' ? '✓' : '✗';
            echo "     $status " . $log['created_at'] . " - " . $log['event_type'] . " - " . $log['status'] . "\n";
        }
    } else {
        echo "   ⚠ Belum ada log otomatisasi (normal untuk sistem baru)\n";
    }
} catch(Exception $e) {
    echo "   ✗ Log Check Error: " . $e->getMessage() . "\n";
}

// 7. Check Integration with Attendance Processing
echo "\n7. PENGECEKAN INTEGRASI DENGAN PROSES ABSENSI:\n";
$attendanceFile = __DIR__ . '/../includes/process_fingerprint_attendance.php';
if (file_exists($attendanceFile)) {
    $content = file_get_contents($attendanceFile);
    if (strpos($content, 'AttendanceWhatsAppAutomation') !== false) {
        echo "   ✓ Integrasi dengan process_fingerprint_attendance.php: TERPASANG\n";
    } else {
        echo "   ✗ Integrasi dengan process_fingerprint_attendance.php: BELUM TERPASANG\n";
    }
} else {
    echo "   ✗ File process_fingerprint_attendance.php: TIDAK DITEMUKAN\n";
}

echo "\n=== PENGECEKAN SELESAI ===\n";
?>
