<?php
/**
 * Production Cleanup & Optimization Script
 * Sistem Absensi Sekolah Salassika
 * 
 * Script ini membersihkan file-file yang tidak diperlukan
 * dan mengoptimasi sistem untuk production deployment
 */

// Prevent direct access from browser
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from CLI.");
}

$dryRun = isset($argv[1]) && $argv[1] === '--dry-run';
$removedFiles = [];
$protectedFiles = [];
$optimizations = [];

echo "========================================\n";
echo "PRODUCTION CLEANUP & OPTIMIZATION\n";
echo "Sistem Absensi Sekolah Salassika\n";
echo "========================================\n\n";

if ($dryRun) {
    echo "[DRY RUN MODE - No files will be deleted]\n\n";
}

// 1. Remove test files
echo "1. CLEANING TEST FILES...\n";
$testFiles = [
    'admin/automatic_test.php',
    'admin/debug_test.php',
    'admin/test_integration.php',
    'admin/fingerprint/test_fingerprint_connection.php',
    'admin/whatsapp/test.php',
    'admin/whatsapp/test_service.php',
    'includes/zklib/test.php',
    'test/integration_ecosystem_testing.php',
    'test/sidebar_consistency_test.php',
    'test/sidebar_improved_test.php',
    'test/sidebar_realworld_test.php',
    'test/whatsapp_automation_test.php'
];

foreach ($testFiles as $file) {
    if (file_exists($file)) {
        if (!$dryRun) {
            if (unlink($file)) {
                echo "  [REMOVED] $file\n";
                $removedFiles[] = $file;
            } else {
                echo "  [ERROR] Could not remove $file\n";
            }
        } else {
            echo "  [WOULD REMOVE] $file\n";
            $removedFiles[] = $file;
        }
    }
}

// 2. Create .htaccess for security
echo "\n2. SECURING SENSITIVE DIRECTORIES...\n";
$htaccessContent = "# Deny access to all files
<Files *>
    Order Deny,Allow
    Deny from all
</Files>

# Allow access to index files
<Files index.php>
    Order Allow,Deny
    Allow from all
</Files>";

$sensitiveDirectories = [
    'includes',
    'db',
    'logs',
    'vendor',
    'migrations',
    'scripts'
];

foreach ($sensitiveDirectories as $dir) {
    if (is_dir($dir)) {
        $htaccessFile = $dir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            if (!$dryRun) {
                file_put_contents($htaccessFile, $htaccessContent);
                echo "  [SECURED] $dir/.htaccess created\n";
            } else {
                echo "  [WOULD SECURE] $dir/.htaccess\n";
            }
            $protectedFiles[] = $htaccessFile;
        } else {
            echo "  [ALREADY SECURED] $dir/.htaccess exists\n";
        }
    }
}

// 3. Protect root .env file
echo "\n3. PROTECTING CONFIGURATION FILES...\n";
$rootHtaccess = '.htaccess';
$envProtection = "\n# Protect .env file\n<Files .env>\n    Order Deny,Allow\n    Deny from all\n</Files>\n";

if (file_exists($rootHtaccess)) {
    $currentContent = file_get_contents($rootHtaccess);
    if (strpos($currentContent, 'Protect .env file') === false) {
        if (!$dryRun) {
            file_put_contents($rootHtaccess, $currentContent . $envProtection, FILE_APPEND);
            echo "  [PROTECTED] .env file protection added to .htaccess\n";
        } else {
            echo "  [WOULD PROTECT] .env file\n";
        }
    } else {
        echo "  [ALREADY PROTECTED] .env file\n";
    }
} else {
    if (!$dryRun) {
        file_put_contents($rootHtaccess, $envProtection);
        echo "  [CREATED] .htaccess with .env protection\n";
    } else {
        echo "  [WOULD CREATE] .htaccess with .env protection\n";
    }
}

// 4. Clear logs
echo "\n4. CLEARING OLD LOGS...\n";
$logFiles = glob('logs/*.log');
foreach ($logFiles as $logFile) {
    $fileSize = filesize($logFile);
    if ($fileSize > 10485760) { // 10MB
        if (!$dryRun) {
            file_put_contents($logFile, '');
            echo "  [CLEARED] $logFile (was " . number_format($fileSize / 1024 / 1024, 2) . " MB)\n";
        } else {
            echo "  [WOULD CLEAR] $logFile (" . number_format($fileSize / 1024 / 1024, 2) . " MB)\n";
        }
    } else {
        echo "  [KEPT] $logFile (" . number_format($fileSize / 1024, 2) . " KB)\n";
    }
}

// 5. Create production configuration file
echo "\n5. CREATING PRODUCTION CONFIGURATION...\n";
$prodConfigFile = 'config/production.php';
$prodConfig = '<?php
/**
 * Production Configuration
 * Auto-generated on ' . date('Y-m-d H:i:s') . '
 */

// Error reporting - disable for production
error_reporting(0);
ini_set("display_errors", 0);
ini_set("log_errors", 1);
ini_set("error_log", __DIR__ . "/../logs/php_errors.log");

// Session configuration
ini_set("session.cookie_httponly", 1);
ini_set("session.use_only_cookies", 1);
ini_set("session.cookie_secure", 1); // Enable if using HTTPS

// Security headers
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Timezone
date_default_timezone_set("Asia/Jakarta");

// Memory and execution limits
ini_set("memory_limit", "256M");
ini_set("max_execution_time", 300);
ini_set("post_max_size", "20M");
ini_set("upload_max_filesize", "10M");

// Database connection pooling
define("DB_PERSISTENT", true);

// Cache settings
define("CACHE_ENABLED", true);
define("CACHE_DURATION", 3600); // 1 hour

// Production mode flag
define("PRODUCTION_MODE", true);
?>';

if (!is_dir('config')) {
    if (!$dryRun) {
        mkdir('config', 0755);
    }
}

if (!$dryRun) {
    file_put_contents($prodConfigFile, $prodConfig);
    echo "  [CREATED] Production configuration file\n";
} else {
    echo "  [WOULD CREATE] Production configuration file\n";
}

// 6. Optimize database
echo "\n6. DATABASE OPTIMIZATION RECOMMENDATIONS...\n";
$optimizations[] = "Run ANALYZE TABLE on all tables to update statistics";
$optimizations[] = "Add indexes on frequently queried columns (user_id, nis, nip)";
$optimizations[] = "Enable query cache in MySQL configuration";
$optimizations[] = "Set up regular backup schedule";
$optimizations[] = "Configure slow query log for monitoring";

foreach ($optimizations as $opt) {
    echo "  - $opt\n";
}

// 7. Create deployment checklist
echo "\n7. CREATING DEPLOYMENT CHECKLIST...\n";
$checklist = "PRODUCTION DEPLOYMENT CHECKLIST
================================
Generated: " . date('Y-m-d H:i:s') . "

PRE-DEPLOYMENT:
□ Backup current production database
□ Backup current production files
□ Test deployment on staging server
□ Review all configuration files
□ Ensure all passwords are strong

DEPLOYMENT:
□ Upload files to production server
□ Import database schema and data
□ Update database connection settings
□ Set proper file permissions (755 for directories, 644 for files)
□ Configure web server (Apache/Nginx)
□ Set up SSL certificate
□ Configure domain and DNS

POST-DEPLOYMENT:
□ Test login functionality
□ Test fingerprint integration
□ Test WhatsApp integration
□ Verify all user roles work correctly
□ Check error logs for issues
□ Monitor server performance
□ Set up monitoring and alerts
□ Document deployment process

SECURITY:
□ Remove .git directory
□ Protect sensitive files with .htaccess
□ Disable directory listing
□ Configure firewall rules
□ Set up fail2ban for brute force protection
□ Regular security updates

MAINTENANCE:
□ Set up automated backups
□ Configure log rotation
□ Monitor disk space
□ Plan for regular updates
□ Document known issues

FILES REMOVED IN CLEANUP:
" . implode("\n", array_map(function($f) { return "- $f"; }, $removedFiles)) . "

FILES PROTECTED:
" . implode("\n", array_map(function($f) { return "- $f"; }, $protectedFiles)) . "
";

if (!$dryRun) {
    file_put_contents('DEPLOYMENT_CHECKLIST.txt', $checklist);
    echo "  [CREATED] DEPLOYMENT_CHECKLIST.txt\n";
} else {
    echo "  [WOULD CREATE] DEPLOYMENT_CHECKLIST.txt\n";
}

// Summary
echo "\n========================================\n";
echo "CLEANUP SUMMARY\n";
echo "========================================\n";
echo "Files removed: " . count($removedFiles) . "\n";
echo "Files protected: " . count($protectedFiles) . "\n";
echo "Optimizations suggested: " . count($optimizations) . "\n";

if ($dryRun) {
    echo "\n[DRY RUN COMPLETE - No changes were made]\n";
    echo "Run without --dry-run flag to apply changes.\n";
} else {
    echo "\n[CLEANUP COMPLETE]\n";
    echo "System is optimized for production deployment.\n";
    echo "Review DEPLOYMENT_CHECKLIST.txt for next steps.\n";
}

echo "========================================\n";
?>
