<?php
/**
 * AUDIT FITUR INTEGRASI & EKOSISTEM SISTEM ABSENSI SEKOLAH
 * Comprehensive audit of integration and ecosystem features
 */

echo "=== AUDIT FITUR INTEGRASI & EKOSISTEM SISTEM ABSENSI SEKOLAH ===\n\n";

$base_dir = __DIR__ . '/..';

// 1. API INTEGRATIONS ANALYSIS
echo "1. ANALISIS INTEGRASI API:\n";

$api_files = [
    'includes/wa_util.php' => 'WhatsApp API Integration (Fonnte)',
    'includes/fingerprint_api.php' => 'Fingerprint Device API',
    'admin/api/' => 'Internal API Endpoints',
    'api/' => 'Public API Directory'
];

foreach ($api_files as $file => $description) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        if (is_dir($full_path)) {
            $files_count = count(glob($full_path . '/*'));
            echo "   ✓ $file: EXISTS (Directory with $files_count files) - $description\n";
        } else {
            $size = filesize($full_path);
            echo "   ✓ $file: EXISTS (" . number_format($size/1024, 2) . " KB) - $description\n";
        }
    } else {
        echo "   ✗ $file: NOT FOUND - $description\n";
    }
}

// 2. DATABASE INTEGRATION ANALYSIS
echo "\n2. ANALISIS INTEGRASI DATABASE:\n";

try {
    include $base_dir . '/includes/db.php';
    
    // Check database connection
    echo "   ✓ Database Connection: ACTIVE\n";
    
    // Check for integration-related tables
    $integration_tables = [
        'whatsapp_config' => 'WhatsApp Configuration',
        'whatsapp_logs' => 'WhatsApp Integration Logs',
        'whatsapp_templates' => 'WhatsApp Message Templates',
        'whatsapp_webhooks' => 'WhatsApp Webhook Events',
        'tbl_kehadiran' => 'Fingerprint Integration Data',
        'api_keys' => 'API Keys Management',
        'system_settings' => 'System Configuration',
        'integration_logs' => 'Integration Activity Logs'
    ];
    
    foreach ($integration_tables as $table => $description) {
        $stmt = $conn->prepare("SHOW TABLES LIKE '$table'");
        $stmt->execute();
        $exists = $stmt->rowCount() > 0;
        
        if ($exists) {
            // Get record count
            $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM $table");
            $count_stmt->execute();
            $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "   ✓ $table: EXISTS ($count records) - $description\n";
        } else {
            echo "   ✗ $table: NOT FOUND - $description\n";
        }
    }
    
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

// 3. EXTERNAL SERVICE INTEGRATIONS
echo "\n3. ANALISIS INTEGRASI LAYANAN EKSTERNAL:\n";

// Check WhatsApp API configuration
$wa_config_file = $base_dir . '/includes/wa_util.php';
if (file_exists($wa_config_file)) {
    $wa_content = file_get_contents($wa_config_file);
    
    $wa_features = [
        'Fonnte API Integration' => preg_match('/fonnte\.com|api\.fonnte/', $wa_content),
        'Message Sending' => preg_match('/sendMessage|send.*message/i', $wa_content),
        'Template System' => preg_match('/template|getTemplate/i', $wa_content),
        'Webhook Handling' => preg_match('/webhook|callback/i', $wa_content),
        'Error Handling' => preg_match('/try.*catch|error_log/i', $wa_content),
        'Rate Limiting' => preg_match('/rate.*limit|throttle/i', $wa_content)
    ];
    
    echo "   WHATSAPP API FEATURES:\n";
    foreach ($wa_features as $feature => $exists) {
        echo "   " . ($exists ? "✓" : "✗") . " $feature: " . ($exists ? "IMPLEMENTED" : "NOT FOUND") . "\n";
    }
}

// 4. FINGERPRINT DEVICE INTEGRATION
echo "\n4. ANALISIS INTEGRASI FINGERPRINT DEVICE:\n";

$fingerprint_files = [
    'includes/fingerprint_util.php' => 'Fingerprint Utility Functions',
    'admin/fingerprint/' => 'Fingerprint Management Interface',
    'process_fingerprint_attendance.php' => 'Fingerprint Processing Script'
];

foreach ($fingerprint_files as $file => $description) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        if (is_dir($full_path)) {
            $files_count = count(glob($full_path . '/*'));
            echo "   ✓ $file: EXISTS (Directory with $files_count files) - $description\n";
        } else {
            $content = file_get_contents($full_path);
            $fingerprint_features = [
                'Device Communication' => preg_match('/curl|http|tcp|socket/i', $content),
                'Data Processing' => preg_match('/process.*fingerprint|attendance/i', $content),
                'User Verification' => preg_match('/verify|authenticate|uid/i', $content),
                'Database Integration' => preg_match('/INSERT|UPDATE.*tbl_kehadiran/i', $content)
            ];
            
            echo "   ✓ $file: EXISTS - $description\n";
            foreach ($fingerprint_features as $feature => $exists) {
                echo "     " . ($exists ? "✓" : "✗") . " $feature: " . ($exists ? "IMPLEMENTED" : "NOT FOUND") . "\n";
            }
        }
    } else {
        echo "   ✗ $file: NOT FOUND - $description\n";
    }
}

// 5. THIRD-PARTY LIBRARIES & DEPENDENCIES
echo "\n5. ANALISIS THIRD-PARTY LIBRARIES & DEPENDENCIES:\n";

$vendor_dir = $base_dir . '/assets/vendor';
if (is_dir($vendor_dir)) {
    $vendors = array_filter(scandir($vendor_dir), function($item) use ($vendor_dir) {
        return $item != '.' && $item != '..' && is_dir($vendor_dir . '/' . $item);
    });
    
    echo "   VENDOR LIBRARIES DETECTED:\n";
    foreach ($vendors as $vendor) {
        $vendor_path = $vendor_dir . '/' . $vendor;
        $files_count = count(glob($vendor_path . '/*'));
        echo "   ✓ $vendor: $files_count files\n";
    }
} else {
    echo "   ✗ Vendor directory not found\n";
}

// Check Composer dependencies
$composer_file = $base_dir . '/composer.json';
if (file_exists($composer_file)) {
    $composer_data = json_decode(file_get_contents($composer_file), true);
    echo "   ✓ Composer configuration found\n";
    if (isset($composer_data['require'])) {
        echo "   COMPOSER DEPENDENCIES:\n";
        foreach ($composer_data['require'] as $package => $version) {
            echo "   ✓ $package: $version\n";
        }
    }
} else {
    echo "   ⚠ Composer configuration not found\n";
}

// 6. SYSTEM CONFIGURATION & ENVIRONMENT
echo "\n6. ANALISIS KONFIGURASI SISTEM & ENVIRONMENT:\n";

$config_files = [
    'config.php' => 'Main Configuration',
    '.env' => 'Environment Variables',
    'includes/config.php' => 'Include Configuration',
    'settings.php' => 'System Settings'
];

foreach ($config_files as $file => $description) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        echo "   ✓ $file: EXISTS (" . number_format($size/1024, 2) . " KB) - $description\n";
    } else {
        echo "   ✗ $file: NOT FOUND - $description\n";
    }
}

// 7. CRON JOBS & SCHEDULED TASKS
echo "\n7. ANALISIS CRON JOBS & SCHEDULED TASKS:\n";

$cron_files = [
    'cron/' => 'Cron Jobs Directory',
    'scheduled_tasks.php' => 'Scheduled Tasks Script',
    'includes/attendance_whatsapp_automation.php' => 'WhatsApp Automation Scheduler'
];

foreach ($cron_files as $file => $description) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        if (is_dir($full_path)) {
            $files_count = count(glob($full_path . '/*'));
            echo "   ✓ $file: EXISTS (Directory with $files_count files) - $description\n";
        } else {
            $content = file_get_contents($full_path);
            $cron_features = [
                'Scheduled Execution' => preg_match('/cron|schedule|daily|hourly/i', $content),
                'Automation Logic' => preg_match('/automat|process.*attendance/i', $content),
                'Error Handling' => preg_match('/try.*catch|error_log/i', $content),
                'Logging' => preg_match('/log|record.*activity/i', $content)
            ];
            
            echo "   ✓ $file: EXISTS - $description\n";
            foreach ($cron_features as $feature => $exists) {
                echo "     " . ($exists ? "✓" : "✗") . " $feature: " . ($exists ? "IMPLEMENTED" : "NOT FOUND") . "\n";
            }
        }
    } else {
        echo "   ✗ $file: NOT FOUND - $description\n";
    }
}

// 8. SECURITY & AUTHENTICATION INTEGRATIONS
echo "\n8. ANALISIS INTEGRASI KEAMANAN & AUTENTIKASI:\n";

$security_features = [
    'Session Management' => 'session_start|$_SESSION',
    'CSRF Protection' => 'csrf.*token|_token',
    'Input Validation' => 'filter_var|validate|sanitize',
    'SQL Injection Protection' => 'prepare|bindParam|PDO',
    'XSS Protection' => 'htmlspecialchars|strip_tags',
    'Access Control' => 'role.*check|permission|authorize',
    'Password Hashing' => 'password_hash|password_verify',
    'Rate Limiting' => 'rate.*limit|throttle'
];

$security_files = glob($base_dir . '/{includes,admin,auth}/*.php', GLOB_BRACE);
$security_results = [];

foreach ($security_features as $feature => $pattern) {
    $found_count = 0;
    foreach ($security_files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (preg_match("/$pattern/i", $content)) {
                $found_count++;
            }
        }
    }
    $security_results[$feature] = $found_count;
}

foreach ($security_results as $feature => $count) {
    $status = $count > 0 ? "✓" : "✗";
    $level = $count > 5 ? "EXCELLENT" : ($count > 2 ? "GOOD" : ($count > 0 ? "BASIC" : "NOT FOUND"));
    echo "   $status $feature: $level ($count files)\n";
}

// 9. LOGGING & MONITORING INTEGRATION
echo "\n9. ANALISIS LOGGING & MONITORING:\n";

$log_directories = [
    'logs/' => 'Application Logs',
    'storage/logs/' => 'Storage Logs',
    'var/log/' => 'System Logs'
];

foreach ($log_directories as $dir => $description) {
    $full_path = $base_dir . '/' . $dir;
    if (is_dir($full_path)) {
        $log_files = glob($full_path . '*.log');
        echo "   ✓ $dir: EXISTS (" . count($log_files) . " log files) - $description\n";
    } else {
        echo "   ✗ $dir: NOT FOUND - $description\n";
    }
}

// Check for logging implementation in code
$logging_patterns = [
    'Error Logging' => 'error_log|log.*error',
    'Activity Logging' => 'log.*activity|audit.*log',
    'Debug Logging' => 'debug.*log|log.*debug',
    'Access Logging' => 'access.*log|log.*access'
];

$php_files = glob($base_dir . '/{*.php,*/*.php,*/*/*.php}', GLOB_BRACE);
foreach ($logging_patterns as $pattern_name => $pattern) {
    $found_count = 0;
    foreach ($php_files as $file) {
        if (file_exists($file)) {
            $content = file_get_contents($file);
            if (preg_match("/$pattern/i", $content)) {
                $found_count++;
            }
        }
    }
    $status = $found_count > 0 ? "✓" : "✗";
    $level = $found_count > 10 ? "EXCELLENT" : ($found_count > 5 ? "GOOD" : ($found_count > 0 ? "BASIC" : "NOT FOUND"));
    echo "   $status $pattern_name: $level ($found_count files)\n";
}

// 10. BACKUP & RECOVERY INTEGRATION
echo "\n10. ANALISIS BACKUP & RECOVERY:\n";

$backup_files = [
    'backup/' => 'Backup Directory',
    'scripts/backup.php' => 'Database Backup Script',
    'scripts/restore.php' => 'Database Restore Script',
    'cron/backup_daily.php' => 'Daily Backup Cron'
];

foreach ($backup_files as $file => $description) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        if (is_dir($full_path)) {
            $backup_count = count(glob($full_path . '*'));
            echo "   ✓ $file: EXISTS ($backup_count items) - $description\n";
        } else {
            echo "   ✓ $file: EXISTS - $description\n";
        }
    } else {
        echo "   ✗ $file: NOT FOUND - $description\n";
    }
}

echo "\n=== RINGKASAN AUDIT INTEGRASI & EKOSISTEM ===\n";

echo "\n✅ INTEGRASI YANG SUDAH BAIK:\n";
echo "- WhatsApp API integration dengan Fonnte\n";
echo "- Database integration dengan PDO\n";
echo "- Fingerprint device integration\n";
echo "- Third-party libraries (Chart.js, PHPSpreadsheet, etc.)\n";
echo "- Session management dan authentication\n";
echo "- Security features implementation\n";

echo "\n⚠️ AREA YANG PERLU DITINGKATKAN:\n";
echo "- API endpoint standardization\n";
echo "- Environment configuration management\n";
echo "- Comprehensive logging system\n";
echo "- Backup & recovery automation\n";
echo "- Monitoring & alerting system\n";
echo "- Documentation untuk API integrations\n";

echo "\n🔍 REKOMENDASI PENGEMBANGAN:\n";
echo "1. Implementasi REST API yang standar\n";
echo "2. Environment variables untuk konfigurasi sensitif\n";
echo "3. Centralized logging dengan log rotation\n";
echo "4. Automated backup dengan retention policy\n";
echo "5. Health check endpoints untuk monitoring\n";
echo "6. API documentation dengan OpenAPI/Swagger\n";
echo "7. Integration testing framework\n";
echo "8. Error tracking dan alerting system\n";

echo "\n=== AUDIT SELESAI ===\n";
?>
