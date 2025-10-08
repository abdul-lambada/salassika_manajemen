<?php
/**
 * Cron Job for WhatsApp Attendance Automation
 * This script should be run periodically to check for attendance events
 * and send automated WhatsApp notifications
 */

// Include necessary files
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/attendance_whatsapp_automation.php';

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// Initialize automation service
$automation = new AttendanceWhatsAppAutomation($conn);

// Get current date and time
$current_date = date('Y-m-d');
$current_time = date('H:i:s');
$current_day = date('N'); // 1 (Monday) to 7 (Sunday)

echo "WhatsApp Attendance Automation Cron Job\n";
echo "Date: $current_date\n";
echo "Time: $current_time\n";
echo "Day: $current_day\n\n";

try {
    // Load automation configuration
    $stmt = $conn->prepare("
        SELECT * FROM whatsapp_automation_config 
        WHERE is_active = 1 
        LIMIT 1
    ");
    $stmt->execute();
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        echo "No active automation configuration found.\n";
        exit(1);
    }
    
    // Check if weekend notifications are disabled and today is weekend
    if ($config['weekend_notifications'] == 0 && ($current_day == 6 || $current_day == 7)) {
        echo "Weekend notifications are disabled. Exiting.\n";
        exit(0);
    }
    
    // Check for absent students at specified time
    $absence_check_time = substr($config['absence_check_time'], 0, 5); // HH:MM format
    if (substr($current_time, 0, 5) === $absence_check_time) {
        echo "Checking absent students...\n";
        $result = $automation->checkAbsentStudents($current_date);
        if ($result['success']) {
            echo "Absent students check completed. Found {$result['absent_count']} absent students.\n";
        } else {
            echo "Absent students check failed: {$result['message']}\n";
        }
    }
    
    // Send daily summary at specified time
    $daily_summary_time = substr($config['daily_summary_time'], 0, 5); // HH:MM format
    if (substr($current_time, 0, 5) === $daily_summary_time) {
        echo "Sending daily attendance summary...\n";
        $result = $automation->sendDailyAttendanceSummary($current_date);
        if ($result['success']) {
            echo "Daily attendance summary sent successfully.\n";
        } else {
            echo "Failed to send daily attendance summary: {$result['message']}\n";
        }
    }
    
    echo "Cron job execution completed.\n";
    
} catch (Exception $e) {
    echo "Error in cron job execution: " . $e->getMessage() . "\n";
    error_log("WhatsApp Automation Cron Error: " . $e->getMessage());
    exit(1);
}
?>
