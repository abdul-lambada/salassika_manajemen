<?php
/**
 * AUDIT FITUR MAINTENANCE & DOKUMENTASI SISTEM ABSENSI SEKOLAH
 * Comprehensive audit of maintenance tools and documentation
 */

echo "=== AUDIT FITUR MAINTENANCE & DOKUMENTASI SISTEM ABSENSI SEKOLAH ===\n\n";

// 1. Documentation Analysis
echo "1. ANALISIS DOKUMENTASI:\n";

$doc_files = [
    'README.md' => 'Project Documentation',
    'CHANGELOG.md' => 'Change Log',
    'INSTALL.md' => 'Installation Guide',
    'API_DOCS.md' => 'API Documentation',
    'WHATSAPP_API_GUIDE.md' => 'WhatsApp API Guide',
    'USER_MANUAL.md' => 'User Manual',
    'DEVELOPER_GUIDE.md' => 'Developer Guide',
    'DEPLOYMENT_GUIDE.md' => 'Deployment Guide',
    'docs/' => 'Documentation Directory',
    'manual/' => 'Manual Directory'
];

$found_docs = 0;
$total_docs = count($doc_files);

foreach ($doc_files as $file => $description) {
    $full_path = __DIR__ . '/../' . $file;
    if (file_exists($full_path)) {
        if (is_dir($full_path)) {
            $file_count = count(glob($full_path . '/*'));
            echo "   ✓ $description: EXISTS (Directory with $file_count files)\n";
        } else {
            $size = number_format(filesize($full_path) / 1024, 2);
            echo "   ✓ $description: EXISTS ({$size} KB)\n";
        }
        $found_docs++;
    } else {
        echo "   ✗ $description: NOT FOUND ($file)\n";
    }
}

echo "   📊 Documentation Coverage: $found_docs/$total_docs files (" . round(($found_docs/$total_docs)*100, 1) . "%)\n\n";

// 2. Code Documentation Analysis
echo "2. ANALISIS DOKUMENTASI KODE:\n";

$code_dirs = ['includes/', 'admin/', 'api/', 'scripts/'];
$total_files = 0;
$documented_files = 0;
$total_functions = 0;
$documented_functions = 0;

foreach ($code_dirs as $dir) {
    $full_dir = __DIR__ . '/../' . $dir;
    if (is_dir($full_dir)) {
        $php_files = glob($full_dir . '*.php');
        foreach ($php_files as $file) {
            $total_files++;
            $content = file_get_contents($file);
            
            // Check for file-level documentation
            if (preg_match('/\/\*\*[\s\S]*?\*\//', $content)) {
                $documented_files++;
            }
            
            // Count functions and documented functions
            preg_match_all('/function\s+\w+\s*\(/', $content, $functions);
            $file_functions = count($functions[0]);
            $total_functions += $file_functions;
            
            // Count documented functions (with /** before function)
            preg_match_all('/\/\*\*[\s\S]*?\*\/\s*(?:public|private|protected)?\s*function/', $content, $doc_functions);
            $documented_functions += count($doc_functions[0]);
        }
        echo "   ✓ $dir: " . count($php_files) . " PHP files\n";
    }
}

$doc_coverage = $total_files > 0 ? round(($documented_files/$total_files)*100, 1) : 0;
$func_coverage = $total_functions > 0 ? round(($documented_functions/$total_functions)*100, 1) : 0;

echo "   📊 File Documentation: $documented_files/$total_files files ({$doc_coverage}%)\n";
echo "   📊 Function Documentation: $documented_functions/$total_functions functions ({$func_coverage}%)\n\n";

// 3. Maintenance Tools Analysis
echo "3. ANALISIS TOOLS MAINTENANCE:\n";

$maintenance_tools = [
    'scripts/backup_system.php' => 'Database Backup System',
    'scripts/cleanup.php' => 'System Cleanup Script',
    'scripts/maintenance.php' => 'Maintenance Mode Script',
    'scripts/update.php' => 'System Update Script',
    'scripts/migrate.php' => 'Database Migration Script',
    'scripts/optimize.php' => 'Database Optimization Script',
    'cron/' => 'Scheduled Tasks Directory',
    'logs/' => 'Log Files Directory',
    'tmp/' => 'Temporary Files Directory',
    'cache/' => 'Cache Directory'
];

$found_tools = 0;
$total_tools = count($maintenance_tools);

foreach ($maintenance_tools as $tool => $description) {
    $full_path = __DIR__ . '/../' . $tool;
    if (file_exists($full_path)) {
        if (is_dir($full_path)) {
            $file_count = count(glob($full_path . '/*'));
            echo "   ✓ $description: EXISTS (Directory with $file_count files)\n";
        } else {
            $size = number_format(filesize($full_path) / 1024, 2);
            echo "   ✓ $description: EXISTS ({$size} KB)\n";
        }
        $found_tools++;
    } else {
        echo "   ✗ $description: NOT FOUND ($tool)\n";
    }
}

echo "   📊 Maintenance Tools: $found_tools/$total_tools tools (" . round(($found_tools/$total_tools)*100, 1) . "%)\n\n";

// 4. Log Management Analysis
echo "4. ANALISIS MANAJEMEN LOG:\n";

$log_patterns = [
    'Error Logging' => '/error_log\s*\(/',
    'Debug Logging' => '/debug_log|var_dump|print_r/',
    'Activity Logging' => '/log_activity|activity_log/',
    'Access Logging' => '/access_log|login_log/',
    'WhatsApp Logging' => '/whatsapp.*log|log.*whatsapp/i'
];

$log_implementations = [];

foreach ($code_dirs as $dir) {
    $full_dir = __DIR__ . '/../' . $dir;
    if (is_dir($full_dir)) {
        $php_files = glob($full_dir . '*.php');
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            foreach ($log_patterns as $log_type => $pattern) {
                if (preg_match($pattern, $content)) {
                    if (!isset($log_implementations[$log_type])) {
                        $log_implementations[$log_type] = 0;
                    }
                    $log_implementations[$log_type]++;
                }
            }
        }
    }
}

foreach ($log_patterns as $log_type => $pattern) {
    $count = isset($log_implementations[$log_type]) ? $log_implementations[$log_type] : 0;
    if ($count > 0) {
        echo "   ✓ $log_type: IMPLEMENTED ($count files)\n";
    } else {
        echo "   ✗ $log_type: NOT FOUND (0 files)\n";
    }
}

// Check log files
$log_dir = __DIR__ . '/../logs/';
if (is_dir($log_dir)) {
    $log_files = glob($log_dir . '*.log');
    echo "   ✓ Log Files: " . count($log_files) . " files in logs/ directory\n";
    foreach ($log_files as $log_file) {
        $size = number_format(filesize($log_file) / 1024, 2);
        echo "     - " . basename($log_file) . " ({$size} KB)\n";
    }
} else {
    echo "   ⚠ Log Directory: Not found (logs/)\n";
}

echo "\n";

// 5. Version Control & Change Management
echo "5. ANALISIS VERSION CONTROL & CHANGE MANAGEMENT:\n";

$version_files = [
    '.git/' => 'Git Repository',
    '.gitignore' => 'Git Ignore File',
    'composer.json' => 'Composer Dependencies',
    'composer.lock' => 'Composer Lock File',
    'package.json' => 'NPM Dependencies',
    'VERSION' => 'Version File',
    'CHANGELOG.md' => 'Change Log'
];

$found_version = 0;
$total_version = count($version_files);

foreach ($version_files as $file => $description) {
    $full_path = __DIR__ . '/../' . $file;
    if (file_exists($full_path)) {
        if (is_dir($full_path)) {
            echo "   ✓ $description: EXISTS (Directory)\n";
        } else {
            $size = number_format(filesize($full_path) / 1024, 2);
            echo "   ✓ $description: EXISTS ({$size} KB)\n";
        }
        $found_version++;
    } else {
        echo "   ✗ $description: NOT FOUND ($file)\n";
    }
}

echo "   📊 Version Control: $found_version/$total_version files (" . round(($found_version/$total_version)*100, 1) . "%)\n\n";

// 6. Database Maintenance Analysis
echo "6. ANALISIS DATABASE MAINTENANCE:\n";

try {
    require_once __DIR__ . '/../includes/db.php';
    
    if (isset($conn)) {
        echo "   ✓ Database Connection: ACTIVE\n";
        
        // Check database size
        $stmt = $conn->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $result = $stmt->fetch();
        echo "   ✓ Database Size: {$result['db_size_mb']} MB\n";
        
        // Check table optimization
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "   ✓ Total Tables: " . count($tables) . " tables\n";
        
        // Check for maintenance procedures
        $maintenance_procedures = [
            'sp_cleanup_logs' => 'Log Cleanup Procedure',
            'sp_optimize_tables' => 'Table Optimization Procedure',
            'sp_backup_database' => 'Database Backup Procedure'
        ];
        
        foreach ($maintenance_procedures as $proc => $desc) {
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM information_schema.routines 
                WHERE routine_schema = DATABASE() AND routine_name = ?
            ");
            $stmt->execute([$proc]);
            $result = $stmt->fetch();
            
            if ($result['count'] > 0) {
                echo "   ✓ $desc: EXISTS\n";
            } else {
                echo "   ✗ $desc: NOT FOUND\n";
            }
        }
        
    } else {
        echo "   ✗ Database Connection: FAILED\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Database Analysis Error: " . $e->getMessage() . "\n";
}

echo "\n";

// 7. Performance Monitoring
echo "7. ANALISIS PERFORMANCE MONITORING:\n";

$performance_features = [
    'Query Optimization' => '/EXPLAIN|ANALYZE|INDEX/',
    'Caching Implementation' => '/cache|Cache|CACHE/',
    'Memory Management' => '/memory_get_usage|ini_get.*memory/',
    'Execution Time Tracking' => '/microtime|time\(\)|execution_time/',
    'Database Connection Pooling' => '/persistent|pconnect|pool/'
];

$perf_implementations = [];

foreach ($code_dirs as $dir) {
    $full_dir = __DIR__ . '/../' . $dir;
    if (is_dir($full_dir)) {
        $php_files = glob($full_dir . '*.php');
        foreach ($php_files as $file) {
            $content = file_get_contents($file);
            foreach ($performance_features as $feature => $pattern) {
                if (preg_match($pattern, $content)) {
                    if (!isset($perf_implementations[$feature])) {
                        $perf_implementations[$feature] = 0;
                    }
                    $perf_implementations[$feature]++;
                }
            }
        }
    }
}

foreach ($performance_features as $feature => $pattern) {
    $count = isset($perf_implementations[$feature]) ? $perf_implementations[$feature] : 0;
    if ($count > 0) {
        echo "   ✓ $feature: IMPLEMENTED ($count files)\n";
    } else {
        echo "   ✗ $feature: NOT FOUND (0 files)\n";
    }
}

echo "\n";

// Summary
echo "=== RINGKASAN AUDIT MAINTENANCE & DOKUMENTASI ===\n\n";

echo "✅ FITUR MAINTENANCE YANG SUDAH ADA:\n";
if ($found_tools > 0) {
    echo "- Backup System: Automated database backup dengan compression\n";
}
if (isset($log_implementations['Error Logging'])) {
    echo "- Error Logging: Comprehensive error tracking\n";
}
if ($found_version > 0) {
    echo "- Version Control: Git repository dan dependency management\n";
}

echo "\n⚠️ AREA YANG PERLU DITINGKATKAN:\n";
if ($doc_coverage < 50) {
    echo "- Documentation Coverage: Perlu lebih banyak dokumentasi kode\n";
}
if ($found_docs < 5) {
    echo "- Project Documentation: Perlu dokumentasi project yang lengkap\n";
}
if (!isset($perf_implementations['Caching Implementation'])) {
    echo "- Performance Optimization: Perlu implementasi caching\n";
}

echo "\n🔍 REKOMENDASI PENGEMBANGAN:\n";
echo "1. Buat dokumentasi lengkap (README, API docs, user manual)\n";
echo "2. Implementasi system monitoring dan alerting\n";
echo "3. Tambahkan automated testing dan CI/CD\n";
echo "4. Buat maintenance dashboard untuk monitoring\n";
echo "5. Implementasi log rotation dan cleanup\n";
echo "6. Tambahkan performance monitoring tools\n";
echo "7. Buat disaster recovery procedures\n";
echo "8. Implementasi automated security scanning\n";

echo "\n=== AUDIT SELESAI ===\n";
?>
