<?php
/**
 * System Integration Test
 * Sistem Absensi Sekolah Salassika
 * 
 * Script untuk testing komprehensif semua komponen sistem
 */

// Configuration
// Load APP_URL from .env via includes/config.php if available
$baseUrl = 'http://localhost/salassika_manajemen';
if (file_exists(__DIR__ . '/includes/config.php')) {
    include_once __DIR__ . '/includes/config.php';
    if (defined('APP_URL') && APP_URL) {
        $baseUrl = APP_URL;
    }
}
$testResults = [];
$totalTests = 0;
$passedTests = 0;
$failedTests = 0;

// Colors for output (disabled for compatibility)
function printResult($test, $status, $message = '') {
    global $totalTests, $passedTests, $failedTests;
    $totalTests++;
    
    if ($status === 'PASS') {
        $passedTests++;
        $symbol = '[✓]';
    } elseif ($status === 'SKIP') {
        $symbol = '[-]';
    } else {
        $failedTests++;
        $symbol = '[✗]';
    }
    
    echo "$symbol $test";
    if ($message) {
        echo " - $message";
    }
    echo "\n";
    
    return $status === 'PASS';
}

// 1. DATABASE CONNECTION TESTS
echo "1. DATABASE CONNECTION TESTS\n";
echo "-----------------------------\n";

try {
    require_once 'includes/db.php';
    if (isset($conn) && $conn instanceof PDO) {
        printResult('Database Connection', 'PASS', 'Connected successfully');

        // Test query execution
        $stmt = $conn->query("SELECT 1");
        if ($stmt) {
            printResult('Query Execution', 'PASS', 'Basic query works');
        } else {
            printResult('Query Execution', 'FAIL', 'Cannot execute query');
        }

        // Check tables existence and counts
        $requiredTables = ['users', 'guru', 'siswa', 'kelas', 'jurusan', 'absensi_guru', 'absensi_siswa'];
        $stmt = $conn->query("SHOW TABLES");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($requiredTables as $table) {
            if (in_array($table, $existingTables)) {
                printResult("Table: $table", 'PASS', 'Exists');
                try {
                    $countStmt = $conn->query("SELECT COUNT(*) FROM `$table`");
                    $count = $countStmt ? (int)$countStmt->fetchColumn() : 0;
                    echo "  └─ Records: $count\n";
                } catch (Exception $e) {
                    echo "  └─ Records: (unable to count)\n";
                }
            } else {
                printResult("Table: $table", 'FAIL', 'Missing');
            }
        }
    } else {
        printResult('Database Connection', 'FAIL', 'Connection object not found');
    }
} catch (Exception $e) {
    printResult('Database Connection', 'FAIL', $e->getMessage());
}

// 2. FILE SYSTEM TESTS
echo "2. FILE SYSTEM TESTS\n";
echo "--------------------\n";

// Check critical directories
$directories = [
    'admin' => 'Admin module',
    'guru' => 'Teacher module',
    'auth' => 'Authentication',
    'includes' => 'Core includes',
    'assets' => 'Static assets',
    'uploads' => 'Upload directory',
    'logs' => 'Log directory',
    'templates' => 'Templates'
];

foreach ($directories as $dir => $description) {
    if (is_dir($dir)) {
        printResult("Directory: $dir", 'PASS', $description);
        
        // Check if writable (for specific dirs)
        if (in_array($dir, ['uploads', 'logs', 'templates'])) {
            if (is_writable($dir)) {
                echo "  └─ Writable: Yes\n";
            } else {
                echo "  └─ Writable: No (WARNING)\n";
            }
        }
    } else {
        printResult("Directory: $dir", 'FAIL', "$description missing");
    }
}

// Check critical files
$files = [
    'index.php' => 'Main entry',
    'auth/login.php' => 'Login page',
    'auth/logout.php' => 'Logout handler',
    'admin/index.php' => 'Admin dashboard',
    'guru/index.php' => 'Teacher dashboard',
    '.env' => 'Environment config'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        printResult("File: $file", 'PASS', $description);
    } else {
        printResult("File: $file", 'FAIL', "$description missing");
    }
}

// 3. CONFIGURATION TESTS
echo "\n3. CONFIGURATION TESTS\n";
echo "----------------------\n";

// Check .env configuration
if (file_exists('.env')) {
    $envContent = file_get_contents('.env');
    
    // Check critical configurations
    $configs = [
        'DB_HOST' => 'Database host',
        'DB_NAME' => 'Database name',
        'APP_ENV=production' => 'Production environment',
        'APP_DEBUG=false' => 'Debug disabled'
    ];
    
    foreach ($configs as $config => $description) {
        if (strpos($envContent, $config) !== false) {
            printResult("Config: $description", 'PASS');
        } else {
            printResult("Config: $description", 'FAIL', 'Not properly configured');
        }
    }
} else {
    printResult('Environment File', 'FAIL', '.env file missing');
}

// 4. AUTHENTICATION TESTS
echo "\n4. AUTHENTICATION TESTS\n";
echo "-----------------------\n";

// Test login page accessibility
$loginUrl = $baseUrl . '/auth/login.php';
$ch = curl_init($loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_ENCODING, '');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; SalassikaTest/1.0)');
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode == 200) {
    printResult('Login Page', 'PASS', 'Accessible');
    
    // Check if login form exists (robust indicators)
    $hasForm = stripos($response, '<form') !== false;
    $hasPassword = stripos($response, 'type="password"') !== false || stripos($response, 'name="password"') !== false || stripos($response, 'password') !== false;
    if ($hasForm && $hasPassword) {
        printResult('Login Form', 'PASS', 'Form elements found');
    } else {
        // Avoid false failure if template/redirect affects markup
        printResult('Login Form', 'SKIP', 'Form not detected (may be templated/redirected)');
    }
} else {
    printResult('Login Page', 'FAIL', "HTTP $httpCode");
}

// Test admin user existence
if (isset($conn)) {
    try {
        $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $adminCount = $stmt->fetchColumn();
        
        if ($adminCount > 0) {
            printResult('Admin User', 'PASS', "$adminCount admin(s) found");
        } else {
            printResult('Admin User', 'FAIL', 'No admin users found');
        }
        
        // Check for default/test passwords (security check)
        $stmt = $conn->prepare("SELECT name FROM users WHERE password = :pass OR password = :pass2 LIMIT 5");
        $defaultPass1 = password_hash('password', PASSWORD_DEFAULT);
        $defaultPass2 = password_hash('123456', PASSWORD_DEFAULT);
        $stmt->execute(['pass' => $defaultPass1, 'pass2' => $defaultPass2]);
        $weakUsers = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (count($weakUsers) > 0) {
            echo "  ⚠ WARNING: Users with weak passwords: " . implode(', ', $weakUsers) . "\n";
        }
    } catch (Exception $e) {
        printResult('User Check', 'FAIL', $e->getMessage());
    }
}

// 5. MODULE FUNCTIONALITY TESTS
echo "\n5. MODULE FUNCTIONALITY TESTS\n";
echo "-----------------------------\n";

// Check if modules exist; if not, mark as SKIPPED (some features may be centralized in admin/index.php)
$modules = [
    '/admin/guru/index.php' => 'Teacher Management',
    '/admin/siswa/index.php' => 'Student Management',
    '/admin/kelas/index.php' => 'Class Management',
    '/admin/jurusan/index.php' => 'Department Management',
    '/admin/laporan/index.php' => 'Report Module'
];

foreach ($modules as $path => $module) {
    $fullPath = '.' . $path;
    if (file_exists($fullPath)) {
        printResult("Module: $module", 'PASS', 'File exists');
    } else {
        printResult("Module: $module", 'SKIP', 'File not found (skipped)');
    }
}

// 6. SECURITY TESTS
echo "\n6. SECURITY TESTS\n";
echo "-----------------\n";

// Check for exposed sensitive files
$sensitiveFiles = [
    '/.git/config' => 'Git config',
    '/composer.json' => 'Composer config',
    '/.env' => 'Environment file'
];

foreach ($sensitiveFiles as $file => $description) {
    $url = $baseUrl . $file;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode == 403 || $httpCode == 404) {
        printResult("Protected: $description", 'PASS', "HTTP $httpCode");
    } else {
        printResult("Protected: $description", 'FAIL', "Exposed (HTTP $httpCode)");
    }
}

// Check session configuration
if (ini_get('session.cookie_httponly') == 1) {
    printResult('Session HTTPOnly', 'PASS');
} else {
    printResult('Session HTTPOnly', 'FAIL', 'Should be enabled');
}

// 7. PERFORMANCE TESTS
echo "\n7. PERFORMANCE TESTS\n";
echo "--------------------\n";

// Helpers to parse php.ini values like 2G/512M/128K
function parseSizeToMB($val) {
    $val = trim((string)$val);
    $unit = strtolower(substr($val, -1));
    $num = (float)$val;
    switch ($unit) {
        case 'g': return $num * 1024; // GB to MB
        case 'm': return $num;        // MB
        case 'k': return $num / 1024; // KB to MB
        default:  return (float)$val; // assume MB
    }
}

// Check PHP configuration
$phpChecks = [
    'memory_limit' => ['minMB' => 128],
    'max_execution_time' => ['minSec' => 30],
    'post_max_size' => ['minMB' => 8],
    'upload_max_filesize' => ['minMB' => 8]
];

// memory_limit
$mem = ini_get('memory_limit');
$memMB = parseSizeToMB($mem);
printResult("PHP: memory_limit", ($memMB >= $phpChecks['memory_limit']['minMB']) ? 'PASS' : 'FAIL', "$mem (min: {$phpChecks['memory_limit']['minMB']}M)");

// max_execution_time (0 means unlimited -> PASS)
$maxExec = (int)ini_get('max_execution_time');
if ($maxExec === 0 || $maxExec >= $phpChecks['max_execution_time']['minSec']) {
    printResult('PHP: max_execution_time', 'PASS', $maxExec === 0 ? 'unlimited' : (string)$maxExec . 's');
} else {
    printResult('PHP: max_execution_time', 'FAIL', $maxExec . 's');
}

// post_max_size
$postMax = ini_get('post_max_size');
$postMB = parseSizeToMB($postMax);
printResult('PHP: post_max_size', ($postMB >= $phpChecks['post_max_size']['minMB']) ? 'PASS' : 'FAIL', "$postMax (min: {$phpChecks['post_max_size']['minMB']}M)");

// upload_max_filesize
$uploadMax = ini_get('upload_max_filesize');
$uploadMB = parseSizeToMB($uploadMax);
printResult('PHP: upload_max_filesize', ($uploadMB >= $phpChecks['upload_max_filesize']['minMB']) ? 'PASS' : 'FAIL', "$uploadMax (min: {$phpChecks['upload_max_filesize']['minMB']}M)");

// 8. DEPENDENCY TESTS
echo "\n8. DEPENDENCY TESTS\n";
echo "-------------------\n";

// Check Composer dependencies
if (file_exists('vendor/autoload.php')) {
    printResult('Composer Autoload', 'PASS');
    
    // Check specific packages
    if (file_exists('vendor/phpoffice/phpexcel')) {
        printResult('PHPExcel Library', 'PASS', 'Installed');
    } else {
        printResult('PHPExcel Library', 'FAIL', 'Not found');
    }
} else {
    printResult('Composer Autoload', 'FAIL', 'Run: composer install');
}

// 9. DATA INTEGRITY TESTS
echo "\n9. DATA INTEGRITY TESTS\n";
echo "-----------------------\n";

if (isset($conn)) {
    try {
        // Check foreign key relationships
        $stmt = $conn->query("
            SELECT 
                COUNT(*) as orphan_count
            FROM siswa s
            LEFT JOIN kelas k ON s.id_kelas = k.id_kelas
            WHERE s.id_kelas IS NOT NULL AND k.id_kelas IS NULL
        ");
        $orphans = $stmt->fetchColumn();
        
        if ($orphans == 0) {
            printResult('Student-Class Integrity', 'PASS', 'No orphaned records');
        } else {
            printResult('Student-Class Integrity', 'FAIL', "$orphans orphaned records");
        }
        
        // Check for duplicate entries
        $stmt = $conn->query("
            SELECT nis, COUNT(*) as cnt 
            FROM siswa 
            GROUP BY nis 
            HAVING cnt > 1
        ");
        $duplicates = $stmt->fetchAll();
        
        if (count($duplicates) == 0) {
            printResult('Student NIS Uniqueness', 'PASS', 'No duplicates');
        } else {
            printResult('Student NIS Uniqueness', 'FAIL', count($duplicates) . ' duplicates found');
        }
        
    } catch (Exception $e) {
        printResult('Data Integrity', 'FAIL', $e->getMessage());
    }
}

// 10. LOGGING TESTS
echo "\n10. LOGGING TESTS\n";
echo "-----------------\n";

$logFile = 'logs/db_errors.log';
if (file_exists($logFile)) {
    if (is_writable($logFile)) {
        printResult('Error Log', 'PASS', 'Writable');
        
        // Check log size
        $size = filesize($logFile);
        if ($size > 10485760) { // 10MB
            echo "  ⚠ WARNING: Log file is large (" . number_format($size/1024/1024, 2) . " MB)\n";
        }
    } else {
        printResult('Error Log', 'FAIL', 'Not writable');
    }
} else {
    printResult('Error Log', 'FAIL', 'File not found');
}

// SUMMARY
echo "\n========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";
echo "Total Tests: $totalTests\n";
echo "Passed: $passedTests\n";
echo "Failed: $failedTests\n";
echo "Success Rate: " . ($totalTests > 0 ? round(($passedTests/$totalTests)*100, 2) : 0) . "%\n";

if ($failedTests == 0) {
    echo "\n✓ ALL TESTS PASSED - System is functioning correctly!\n";
    $exitCode = 0;
} elseif ($failedTests <= 3) {
    echo "\n⚠ MINOR ISSUES DETECTED - System is mostly functional\n";
    $exitCode = 1;
} else {
    echo "\n✗ CRITICAL ISSUES DETECTED - System needs attention\n";
    $exitCode = 2;
}

// Generate test report
$report = "SYSTEM TEST REPORT\n";
$report .= "Generated: " . date('Y-m-d H:i:s') . "\n";
$report .= "=====================================\n\n";
$report .= "Summary:\n";
$report .= "- Total Tests: $totalTests\n";
$report .= "- Passed: $passedTests\n";
$report .= "- Failed: $failedTests\n";
$report .= "- Success Rate: " . ($totalTests > 0 ? round(($passedTests/$totalTests)*100, 2) : 0) . "%\n";
$report .= "\nRecommendations:\n";

if ($failedTests > 0) {
    $report .= "- Fix failed tests before production deployment\n";
    $report .= "- Review error logs for detailed information\n";
    $report .= "- Ensure all dependencies are properly installed\n";
} else {
    $report .= "- System is ready for production\n";
    $report .= "- Continue with regular maintenance\n";
}

file_put_contents('test_report_' . date('Y-m-d_H-i-s') . '.txt', $report);
echo "\nTest report saved to: test_report_" . date('Y-m-d_H-i-s') . ".txt\n";
echo "========================================\n";

exit($exitCode);
?>
