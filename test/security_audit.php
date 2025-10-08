<?php
/**
 * Comprehensive Security & Validation Audit Script
 * Checks all critical security aspects of the attendance system
 */

echo "=== AUDIT KEAMANAN & VALIDASI SISTEM ABSENSI SEKOLAH ===\n\n";

// 1. Authentication & Session Security
echo "1. AUTHENTICATION & SESSION SECURITY:\n";

$auth_files = [
    'auth/login.php',
    'auth/logout.php', 
    'auth/register.php'
];

foreach ($auth_files as $file) {
    $file_path = __DIR__ . '/../' . $file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        echo "   ✓ $file: EXISTS\n";
        
        // Check for session security
        if (strpos($content, 'session_start()') !== false) {
            echo "     ✓ Session management: IMPLEMENTED\n";
        } else {
            echo "     ✗ Session management: MISSING\n";
        }
        
        // Check for password hashing
        if (strpos($content, 'password_hash') !== false || strpos($content, 'password_verify') !== false) {
            echo "     ✓ Password hashing: IMPLEMENTED\n";
        } else {
            echo "     ⚠ Password hashing: NEEDS VERIFICATION\n";
        }
        
        // Check for SQL injection protection
        if (strpos($content, 'prepare(') !== false && strpos($content, 'bindParam') !== false) {
            echo "     ✓ SQL injection protection: IMPLEMENTED\n";
        } else {
            echo "     ⚠ SQL injection protection: NEEDS VERIFICATION\n";
        }
        
    } else {
        echo "   ✗ $file: NOT FOUND\n";
    }
    echo "\n";
}

// 2. Access Control & Authorization
echo "2. ACCESS CONTROL & AUTHORIZATION:\n";

$admin_dirs = ['admin', 'admin/users', 'admin/whatsapp', 'admin/laporan'];
foreach ($admin_dirs as $dir) {
    $dir_path = __DIR__ . '/../' . $dir;
    if (is_dir($dir_path)) {
        echo "   ✓ $dir/: EXISTS\n";
        
        // Check for .htaccess protection
        $htaccess_path = $dir_path . '/.htaccess';
        if (file_exists($htaccess_path)) {
            echo "     ✓ .htaccess protection: FOUND\n";
        } else {
            echo "     ⚠ .htaccess protection: NOT FOUND\n";
        }
        
        // Check PHP files for authentication checks
        $php_files = glob($dir_path . '/*.php');
        $protected_files = 0;
        $total_files = count($php_files);
        
        foreach ($php_files as $php_file) {
            $content = file_get_contents($php_file);
            if (strpos($content, '$_SESSION[\'user\']') !== false || 
                strpos($content, 'isset($_SESSION[\'user\'])') !== false) {
                $protected_files++;
            }
        }
        
        if ($total_files > 0) {
            $protection_rate = ($protected_files / $total_files) * 100;
            echo "     - Authentication checks: $protected_files/$total_files files (" . round($protection_rate, 1) . "%)\n";
            
            if ($protection_rate >= 90) {
                echo "     ✓ Access control: GOOD\n";
            } elseif ($protection_rate >= 70) {
                echo "     ⚠ Access control: NEEDS IMPROVEMENT\n";
            } else {
                echo "     ✗ Access control: CRITICAL\n";
            }
        }
        
    } else {
        echo "   ✗ $dir/: NOT FOUND\n";
    }
    echo "\n";
}

// 3. Input Validation & Sanitization
echo "3. INPUT VALIDATION & SANITIZATION:\n";

$input_files = [
    'includes/process_fingerprint_attendance.php',
    'admin/users/add_user.php',
    'admin/users/edit_user.php',
    'admin/whatsapp/config.php'
];

foreach ($input_files as $file) {
    $file_path = __DIR__ . '/../' . $file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        echo "   ✓ $file: EXISTS\n";
        
        // Check for input validation
        $validation_functions = ['filter_var', 'htmlspecialchars', 'strip_tags', 'mysqli_real_escape_string'];
        $found_validation = false;
        
        foreach ($validation_functions as $func) {
            if (strpos($content, $func) !== false) {
                echo "     ✓ Input validation ($func): FOUND\n";
                $found_validation = true;
                break;
            }
        }
        
        if (!$found_validation) {
            echo "     ⚠ Input validation: NEEDS VERIFICATION\n";
        }
        
        // Check for prepared statements
        if (strpos($content, 'prepare(') !== false) {
            echo "     ✓ Prepared statements: IMPLEMENTED\n";
        } else {
            echo "     ⚠ Prepared statements: NEEDS VERIFICATION\n";
        }
        
    } else {
        echo "   ⚠ $file: NOT FOUND (may not exist yet)\n";
    }
    echo "\n";
}

// 4. Database Security
echo "4. DATABASE SECURITY:\n";

$db_file = __DIR__ . '/../includes/db.php';
if (file_exists($db_file)) {
    $content = file_get_contents($db_file);
    echo "   ✓ includes/db.php: EXISTS\n";
    
    // Check for PDO usage
    if (strpos($content, 'PDO') !== false) {
        echo "     ✓ PDO usage: IMPLEMENTED\n";
    } else {
        echo "     ⚠ PDO usage: NEEDS VERIFICATION\n";
    }
    
    // Check for error handling
    if (strpos($content, 'try') !== false && strpos($content, 'catch') !== false) {
        echo "     ✓ Error handling: IMPLEMENTED\n";
    } else {
        echo "     ⚠ Error handling: NEEDS IMPROVEMENT\n";
    }
    
    // Check for credentials exposure
    if (strpos($content, 'localhost') !== false || strpos($content, 'root') !== false) {
        echo "     ⚠ Database credentials: VISIBLE (consider environment variables)\n";
    } else {
        echo "     ✓ Database credentials: PROTECTED\n";
    }
    
} else {
    echo "   ✗ includes/db.php: NOT FOUND\n";
}
echo "\n";

// 5. File Upload Security
echo "5. FILE UPLOAD SECURITY:\n";

$upload_patterns = ['move_uploaded_file', '$_FILES', 'upload'];
$upload_files_found = [];

$all_php_files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/../', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($all_php_files as $file) {
    if ($file->getExtension() === 'php') {
        $content = file_get_contents($file->getPathname());
        foreach ($upload_patterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                $relative_path = str_replace(__DIR__ . '/../', '', $file->getPathname());
                if (!in_array($relative_path, $upload_files_found)) {
                    $upload_files_found[] = $relative_path;
                }
                break;
            }
        }
    }
}

if (count($upload_files_found) > 0) {
    echo "   Files with upload functionality found:\n";
    foreach ($upload_files_found as $file) {
        echo "     - $file\n";
        
        $content = file_get_contents(__DIR__ . '/../' . $file);
        
        // Check for file type validation
        if (strpos($content, 'pathinfo') !== false || strpos($content, 'mime_content_type') !== false) {
            echo "       ✓ File type validation: FOUND\n";
        } else {
            echo "       ⚠ File type validation: NEEDS VERIFICATION\n";
        }
        
        // Check for file size limits
        if (strpos($content, 'size') !== false && strpos($content, 'MAX_FILE_SIZE') !== false) {
            echo "       ✓ File size limits: FOUND\n";
        } else {
            echo "       ⚠ File size limits: NEEDS VERIFICATION\n";
        }
    }
} else {
    echo "   ✓ No file upload functionality detected\n";
}
echo "\n";

// 6. XSS Protection
echo "6. XSS PROTECTION:\n";

$output_files = ['templates/header.php', 'templates/sidebar.php'];
foreach ($output_files as $file) {
    $file_path = __DIR__ . '/../' . $file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        echo "   ✓ $file: EXISTS\n";
        
        // Check for output escaping
        if (strpos($content, 'htmlspecialchars') !== false || strpos($content, 'htmlentities') !== false) {
            echo "     ✓ Output escaping: IMPLEMENTED\n";
        } else {
            echo "     ⚠ Output escaping: NEEDS VERIFICATION\n";
        }
        
    } else {
        echo "   ✗ $file: NOT FOUND\n";
    }
}
echo "\n";

// 7. CSRF Protection
echo "7. CSRF PROTECTION:\n";

$form_files = glob(__DIR__ . '/../admin/*/*.php');
$csrf_protected = 0;
$total_forms = 0;

foreach ($form_files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, '<form') !== false) {
        $total_forms++;
        if (strpos($content, 'csrf') !== false || strpos($content, 'token') !== false) {
            $csrf_protected++;
        }
    }
}

if ($total_forms > 0) {
    $csrf_rate = ($csrf_protected / $total_forms) * 100;
    echo "   Forms with CSRF protection: $csrf_protected/$total_forms (" . round($csrf_rate, 1) . "%)\n";
    
    if ($csrf_rate >= 80) {
        echo "   ✓ CSRF protection: GOOD\n";
    } elseif ($csrf_rate >= 50) {
        echo "   ⚠ CSRF protection: NEEDS IMPROVEMENT\n";
    } else {
        echo "   ✗ CSRF protection: CRITICAL\n";
    }
} else {
    echo "   ⚠ No forms detected for CSRF analysis\n";
}
echo "\n";

echo "=== RINGKASAN AUDIT KEAMANAN ===\n";
echo "Audit ini memberikan gambaran umum tentang status keamanan sistem.\n";
echo "Untuk audit keamanan yang lebih mendalam, disarankan:\n";
echo "1. Penetration testing oleh security expert\n";
echo "2. Code review manual untuk logic flaws\n";
echo "3. Vulnerability scanning tools\n";
echo "4. Security headers analysis\n";
echo "5. Database security audit\n\n";

echo "=== AUDIT SELESAI ===\n";
?>
