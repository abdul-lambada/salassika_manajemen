<?php
/**
 * Production Readiness Check Script
 * Sistem Absensi Sekolah Salassika
 * 
 * Script ini melakukan pengecekan komprehensif untuk memastikan
 * sistem siap untuk production deployment
 */

// Prevent direct access from browser
if (php_sapi_name() !== 'cli' && !isset($_GET['key']) || (isset($_GET['key']) && $_GET['key'] !== 'secure_check_2024')) {
    die("Access denied. This script can only be run from CLI or with proper key.");
}

// Configuration
$checks = [];
$errors = [];
$warnings = [];
$info = [];

// Color codes for CLI output
$colors = [
    'red' => "\033[31m",
    'green' => "\033[32m",
    'yellow' => "\033[33m",
    'blue' => "\033[34m",
    'reset' => "\033[0m"
];

function colorize($text, $color) {
    // Disable colors for better compatibility
    return $text;
}

function addCheck($category, $item, $status, $message = '') {
    global $checks;
    $checks[$category][] = [
        'item' => $item,
        'status' => $status,
        'message' => $message
    ];
}

echo colorize("========================================\n", 'blue');
echo colorize("PRODUCTION READINESS CHECK\n", 'blue');
echo colorize("Sistem Absensi Sekolah Salassika\n", 'blue');
echo colorize("========================================\n\n", 'blue');

// 1. PHP Version Check
echo colorize("1. CHECKING PHP ENVIRONMENT...\n", 'yellow');
$phpVersion = phpversion();
if (version_compare($phpVersion, '7.4.0', '>=')) {
    addCheck('PHP', 'PHP Version', 'pass', "PHP $phpVersion (OK - >= 7.4)");
    echo colorize("✓ PHP Version: $phpVersion\n", 'green');
} else {
    addCheck('PHP', 'PHP Version', 'fail', "PHP $phpVersion (Required >= 7.4)");
    echo colorize("✗ PHP Version: $phpVersion (Required >= 7.4)\n", 'red');
    $errors[] = "PHP version is too old";
}

// Check required PHP extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'session', 'mbstring'];
foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        addCheck('PHP', "Extension: $ext", 'pass');
        echo colorize("✓ Extension $ext loaded\n", 'green');
    } else {
        addCheck('PHP', "Extension: $ext", 'fail');
        echo colorize("✗ Extension $ext not loaded\n", 'red');
        $errors[] = "PHP extension $ext is missing";
    }
}

// 2. Database Connection Check
echo colorize("\n2. CHECKING DATABASE CONNECTION...\n", 'yellow');
try {
    include_once 'includes/db.php';
    if (isset($conn) && $conn instanceof PDO) {
        addCheck('Database', 'Connection', 'pass');
        echo colorize("✓ Database connection successful\n", 'green');
        
        // Check database tables
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $requiredTables = ['users', 'guru', 'siswa', 'kelas', 'jurusan', 'absensi_guru', 'absensi_siswa'];
        
        foreach ($requiredTables as $table) {
            if (in_array($table, $tables)) {
                addCheck('Database', "Table: $table", 'pass');
                echo colorize("✓ Table '$table' exists\n", 'green');
            } else {
                addCheck('Database', "Table: $table", 'fail');
                echo colorize("✗ Table '$table' missing\n", 'red');
                $errors[] = "Database table '$table' is missing";
            }
        }
    }
} catch (Exception $e) {
    addCheck('Database', 'Connection', 'fail', $e->getMessage());
    echo colorize("✗ Database connection failed: " . $e->getMessage() . "\n", 'red');
    $errors[] = "Database connection failed";
}

// 3. File Permissions Check
echo colorize("\n3. CHECKING FILE PERMISSIONS...\n", 'yellow');
$writableDirs = ['uploads', 'logs', 'templates'];
foreach ($writableDirs as $dir) {
    if (is_dir($dir)) {
        if (is_writable($dir)) {
            addCheck('Permissions', "Directory: $dir", 'pass');
            echo colorize("✓ Directory '$dir' is writable\n", 'green');
        } else {
            addCheck('Permissions', "Directory: $dir", 'warning');
            echo colorize("⚠ Directory '$dir' is not writable\n", 'yellow');
            $warnings[] = "Directory '$dir' should be writable";
        }
    } else {
        addCheck('Permissions', "Directory: $dir", 'warning');
        echo colorize("⚠ Directory '$dir' does not exist\n", 'yellow');
        $warnings[] = "Directory '$dir' does not exist";
    }
}

// 4. Security Configuration Check
echo colorize("\n4. CHECKING SECURITY CONFIGURATION...\n", 'yellow');

// Check .env file
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    
    // Check debug mode
    if (strpos($envContent, 'APP_DEBUG=false') !== false) {
        addCheck('Security', 'Debug Mode', 'pass');
        echo colorize("✓ Debug mode is disabled\n", 'green');
    } else {
        addCheck('Security', 'Debug Mode', 'fail');
        echo colorize("✗ Debug mode is enabled (should be disabled in production)\n", 'red');
        $errors[] = "Debug mode should be disabled in production";
    }
    
    // Check environment
    if (strpos($envContent, 'APP_ENV=production') !== false) {
        addCheck('Security', 'Environment', 'pass');
        echo colorize("✓ Environment is set to production\n", 'green');
    } else {
        addCheck('Security', 'Environment', 'warning');
        echo colorize("⚠ Environment is not set to production\n", 'yellow');
        $warnings[] = "Environment should be set to production";
    }
    
    // Check for default passwords
    if (strpos($envContent, 'DB_PASS=') !== false && strpos($envContent, 'DB_PASS=""') === false) {
        addCheck('Security', 'Database Password', 'pass');
        echo colorize("✓ Database password is set\n", 'green');
    } else {
        addCheck('Security', 'Database Password', 'fail');
        echo colorize("✗ Database password is empty\n", 'red');
        $errors[] = "Database password should not be empty in production";
    }
} else {
    addCheck('Security', '.env File', 'fail');
    echo colorize("✗ .env file not found\n", 'red');
    $errors[] = ".env file is missing";
}

// Check for sensitive files
$sensitiveFiles = ['.git', 'composer.json', 'composer.lock', '.env'];
foreach ($sensitiveFiles as $file) {
    if (file_exists($file)) {
        if ($file === '.env' || $file === 'composer.json' || $file === 'composer.lock') {
            // These are needed but should be protected
            addCheck('Security', "File: $file", 'warning');
            echo colorize("⚠ $file exists (ensure it's protected from public access)\n", 'yellow');
            $warnings[] = "$file should be protected from public access";
        } elseif ($file === '.git') {
            addCheck('Security', "Directory: $file", 'warning');
            echo colorize("⚠ $file directory exists (should be removed in production)\n", 'yellow');
            $warnings[] = ".git directory should be removed in production";
        }
    }
}

// 5. Dependencies Check
echo colorize("\n5. CHECKING DEPENDENCIES...\n", 'yellow');
if (file_exists('vendor/autoload.php')) {
    addCheck('Dependencies', 'Composer Autoload', 'pass');
    echo colorize("✓ Composer dependencies installed\n", 'green');
} else {
    addCheck('Dependencies', 'Composer Autoload', 'fail');
    echo colorize("✗ Composer dependencies not installed\n", 'red');
    $errors[] = "Run 'composer install' to install dependencies";
}

// 6. Error Handling Check
echo colorize("\n6. CHECKING ERROR HANDLING...\n", 'yellow');
$errorLogFile = 'logs/db_errors.log';
if (file_exists($errorLogFile)) {
    if (is_writable($errorLogFile)) {
        addCheck('Logging', 'Error Log File', 'pass');
        echo colorize("✓ Error log file is writable\n", 'green');
    } else {
        addCheck('Logging', 'Error Log File', 'warning');
        echo colorize("⚠ Error log file is not writable\n", 'yellow');
        $warnings[] = "Error log file should be writable";
    }
} else {
    addCheck('Logging', 'Error Log File', 'warning');
    echo colorize("⚠ Error log file does not exist\n", 'yellow');
    $warnings[] = "Error log file does not exist";
}

// 7. Session Configuration
echo colorize("\n7. CHECKING SESSION CONFIGURATION...\n", 'yellow');
$sessionSavePath = session_save_path();
if (!empty($sessionSavePath) && is_writable($sessionSavePath)) {
    addCheck('Session', 'Save Path', 'pass');
    echo colorize("✓ Session save path is writable\n", 'green');
} else {
    addCheck('Session', 'Save Path', 'warning');
    echo colorize("⚠ Session save path may not be properly configured\n", 'yellow');
    $warnings[] = "Session save path should be checked";
}

// 8. Check for test files
echo colorize("\n8. CHECKING FOR TEST FILES...\n", 'yellow');
$testFiles = glob('*test*.php');
$testFiles = array_merge($testFiles, glob('admin/*test*.php'));
$testFiles = array_merge($testFiles, glob('guru/*test*.php'));

if (count($testFiles) > 0) {
    addCheck('Cleanup', 'Test Files', 'warning', count($testFiles) . ' test files found');
    echo colorize("⚠ " . count($testFiles) . " test files found (should be removed in production)\n", 'yellow');
    foreach ($testFiles as $testFile) {
        echo colorize("  - $testFile\n", 'yellow');
        $warnings[] = "Test file '$testFile' should be removed";
    }
} else {
    addCheck('Cleanup', 'Test Files', 'pass');
    echo colorize("✓ No test files found\n", 'green');
}

// 9. Check critical files existence
echo colorize("\n9. CHECKING CRITICAL FILES...\n", 'yellow');
$criticalFiles = [
    'index.php' => 'Main entry point',
    'auth/login.php' => 'Login page',
    'includes/db.php' => 'Database configuration',
    'admin/index.php' => 'Admin dashboard',
    'guru/index.php' => 'Teacher dashboard'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        addCheck('Files', $description, 'pass');
        echo colorize("✓ $description ($file) exists\n", 'green');
    } else {
        addCheck('Files', $description, 'fail');
        echo colorize("✗ $description ($file) missing\n", 'red');
        $errors[] = "$description ($file) is missing";
    }
}

// 10. Performance Recommendations
echo colorize("\n10. PERFORMANCE RECOMMENDATIONS...\n", 'yellow');
$info[] = "Enable OPcache for better PHP performance";
$info[] = "Configure proper caching headers for static assets";
$info[] = "Optimize database indexes for frequently queried tables";
$info[] = "Implement database query caching";
$info[] = "Minify CSS and JavaScript files";

foreach ($info as $recommendation) {
    echo colorize("ℹ $recommendation\n", 'blue');
}

// Summary
echo colorize("\n========================================\n", 'blue');
echo colorize("SUMMARY\n", 'blue');
echo colorize("========================================\n", 'blue');

$totalErrors = count($errors);
$totalWarnings = count($warnings);

if ($totalErrors === 0) {
    echo colorize("✓ No critical errors found\n", 'green');
} else {
    echo colorize("✗ $totalErrors critical error(s) found:\n", 'red');
    foreach ($errors as $error) {
        echo colorize("  - $error\n", 'red');
    }
}

if ($totalWarnings > 0) {
    echo colorize("\n⚠ $totalWarnings warning(s) found:\n", 'yellow');
    foreach ($warnings as $warning) {
        echo colorize("  - $warning\n", 'yellow');
    }
}

// Production readiness status
echo colorize("\n========================================\n", 'blue');
if ($totalErrors === 0) {
    if ($totalWarnings === 0) {
        echo colorize("PRODUCTION STATUS: READY ✓\n", 'green');
        echo colorize("System is ready for production deployment!\n", 'green');
    } else {
        echo colorize("PRODUCTION STATUS: READY WITH WARNINGS ⚠\n", 'yellow');
        echo colorize("System can be deployed but address warnings for optimal performance.\n", 'yellow');
    }
} else {
    echo colorize("PRODUCTION STATUS: NOT READY ✗\n", 'red');
    echo colorize("Critical issues must be resolved before production deployment.\n", 'red');
}
echo colorize("========================================\n", 'blue');

// Generate report file
$reportContent = "PRODUCTION READINESS CHECK REPORT\n";
$reportContent .= "Generated: " . date('Y-m-d H:i:s') . "\n";
$reportContent .= "=====================================\n\n";

foreach ($checks as $category => $items) {
    $reportContent .= "$category:\n";
    foreach ($items as $item) {
        $status = $item['status'] === 'pass' ? '[PASS]' : ($item['status'] === 'fail' ? '[FAIL]' : '[WARN]');
        $reportContent .= "  $status {$item['item']}";
        if (!empty($item['message'])) {
            $reportContent .= " - {$item['message']}";
        }
        $reportContent .= "\n";
    }
    $reportContent .= "\n";
}

$reportContent .= "SUMMARY:\n";
$reportContent .= "- Errors: $totalErrors\n";
$reportContent .= "- Warnings: $totalWarnings\n";
$reportContent .= "- Status: " . ($totalErrors === 0 ? 'READY' : 'NOT READY') . "\n";

file_put_contents('production_check_report_' . date('Y-m-d_H-i-s') . '.txt', $reportContent);
echo colorize("\nReport saved to production_check_report_" . date('Y-m-d_H-i-s') . ".txt\n", 'blue');

// Exit with appropriate code
exit($totalErrors === 0 ? 0 : 1);
?>
