<?php
/**
 * Test script for WhatsApp Attendance Automation
 * This script simulates attendance events and tests the automation notifications
 */

// Include necessary files
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/attendance_whatsapp_automation.php';

echo "=== WhatsApp Attendance Automation Test ===\n\n";

// Initialize automation service
$automation = new AttendanceWhatsAppAutomation($conn);

// Test data
$test_users = [
    [
        'user_id' => 1,
        'status_kehadiran' => 'hadir',
        'timestamp' => date('Y-m-d H:i:s'),
        'user_type' => 'siswa'
    ],
    [
        'user_id' => 2,
        'status_kehadiran' => 'terlambat',
        'timestamp' => date('Y-m-d H:i:s'),
        'user_type' => 'guru'
    ],
    [
        'user_id' => 3,
        'status_kehadiran' => 'izin',
        'timestamp' => date('Y-m-d H:i:s'),
        'user_type' => 'siswa'
    ],
    [
        'user_id' => 4,
        'status_kehadiran' => 'sakit',
        'timestamp' => date('Y-m-d H:i:s'),
        'user_type' => 'guru'
    ]
];

echo "Testing attendance notifications...\n";
foreach ($test_users as $user_data) {
    echo "\n--- Testing user ID: {$user_data['user_id']} with status: {$user_data['status_kehadiran']} ---\n";
    
    try {
        $result = $automation->processAttendanceNotifications($user_data);
        if ($result['success']) {
            echo "✓ Notification processed successfully\n";
            echo "  Messages sent: {$result['messages_sent']}\n";
        } else {
            echo "✗ Notification processing failed: {$result['message']}\n";
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
    }
}

echo "\nTesting daily attendance summary...\n";
try {
    $result = $automation->sendDailyAttendanceSummary(date('Y-m-d'));
    if ($result['success']) {
        echo "✓ Daily attendance summary sent successfully\n";
        echo "  Present: {$result['summary']['hadir']}\n";
        echo "  Late: {$result['summary']['terlambat']}\n";
        echo "  Absent: {$result['summary']['tidak_hadir']}\n";
        echo "  Permission: {$result['summary']['izin']}\n";
        echo "  Sick: {$result['summary']['sakit']}\n";
        echo "  Total: {$result['summary']['total']}\n";
    } else {
        echo "✗ Daily attendance summary failed: {$result['message']}\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\nTesting absent student check...\n";
try {
    $result = $automation->checkAbsentStudents(date('Y-m-d'));
    if ($result['success']) {
        echo "✓ Absent student check completed\n";
        echo "  Absent students found: {$result['absent_count']}\n";
    } else {
        echo "✗ Absent student check failed: {$result['message']}\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}

echo "\n=== Test completed ===\n";
?>
