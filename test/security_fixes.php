<?php
/**
 * Security Fixes Implementation Script
 * Implements critical security fixes for the attendance system
 */

echo "=== IMPLEMENTASI PERBAIKAN KEAMANAN ===\n\n";

// 1. Create .htaccess files for admin directories
echo "1. MEMBUAT .HTACCESS PROTECTION:\n";

$admin_dirs = [
    'admin',
    'admin/users', 
    'admin/whatsapp',
    'admin/laporan',
    'admin/guru',
    'admin/siswa',
    'admin/pengaduan'
];

$htaccess_content = "# Protect admin directories
<Files *.php>
    Order Deny,Allow
    Deny from all
    Allow from 127.0.0.1
    Allow from ::1
    # Add your IP addresses here
</Files>

# Prevent direct access to sensitive files
<Files ~ \"\\.(sql|log|txt|md)$\">
    Order Allow,Deny
    Deny from all
</Files>

# Disable directory browsing
Options -Indexes

# Prevent access to config files
<Files \"config.php\">
    Order Allow,Deny
    Deny from all
</Files>
";

foreach ($admin_dirs as $dir) {
    $dir_path = __DIR__ . '/../' . $dir;
    $htaccess_path = $dir_path . '/.htaccess';
    
    if (is_dir($dir_path)) {
        if (!file_exists($htaccess_path)) {
            file_put_contents($htaccess_path, $htaccess_content);
            echo "   ✓ Created: $dir/.htaccess\n";
        } else {
            echo "   ✓ Exists: $dir/.htaccess\n";
        }
    } else {
        echo "   ⚠ Directory not found: $dir\n";
    }
}

echo "\n2. MEMBUAT CSRF PROTECTION HELPER:\n";

// Create CSRF protection helper
$csrf_helper = '<?php
/**
 * CSRF Protection Helper
 * Provides CSRF token generation and validation
 */

class CSRFProtection {
    
    /**
     * Generate CSRF token
     */
    public static function generateToken() {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION[\'csrf_token\'])) {
            $_SESSION[\'csrf_token\'] = bin2hex(random_bytes(32));
        }
        
        return $_SESSION[\'csrf_token\'];
    }
    
    /**
     * Get CSRF token HTML input
     */
    public static function getTokenInput() {
        $token = self::generateToken();
        return \'<input type="hidden" name="csrf_token" value="\' . htmlspecialchars($token) . \'">\';
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateToken($token) {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION[\'csrf_token\']) || !isset($token)) {
            return false;
        }
        
        return hash_equals($_SESSION[\'csrf_token\'], $token);
    }
    
    /**
     * Validate CSRF token from POST
     */
    public static function validatePostToken() {
        return self::validateToken($_POST[\'csrf_token\'] ?? \'\');
    }
}
?>';

$csrf_file = __DIR__ . '/../includes/csrf_protection.php';
file_put_contents($csrf_file, $csrf_helper);
echo "   ✓ Created: includes/csrf_protection.php\n";

echo "\n3. MEMBUAT INPUT VALIDATION HELPER:\n";

// Create input validation helper
$validation_helper = '<?php
/**
 * Input Validation Helper
 * Provides comprehensive input validation and sanitization
 */

class InputValidator {
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input, $max_length = 255) {
        if (!is_string($input)) {
            return \'\';
        }
        
        $input = trim($input);
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, \'UTF-8\');
        
        if ($max_length > 0) {
            $input = substr($input, 0, $max_length);
        }
        
        return $input;
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number
     */
    public static function validatePhone($phone) {
        $phone = preg_replace(\'/[^0-9+]/\', \'\', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 15;
    }
    
    /**
     * Validate integer
     */
    public static function validateInteger($value, $min = null, $max = null) {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            return false;
        }
        
        $value = (int)$value;
        
        if ($min !== null && $value < $min) {
            return false;
        }
        
        if ($max !== null && $value > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate required fields
     */
    public static function validateRequired($fields, $data) {
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Field $field is required";
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize array of inputs
     */
    public static function sanitizeArray($data, $allowed_keys = []) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (!empty($allowed_keys) && !in_array($key, $allowed_keys)) {
                continue;
            }
            
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $allowed_keys);
            } else {
                $sanitized[$key] = self::sanitizeString($value);
            }
        }
        
        return $sanitized;
    }
}
?>';

$validation_file = __DIR__ . '/../includes/input_validator.php';
file_put_contents($validation_file, $validation_helper);
echo "   ✓ Created: includes/input_validator.php\n";

echo "\n4. MEMBUAT SECURITY HEADERS HELPER:\n";

// Create security headers helper
$headers_helper = '<?php
/**
 * Security Headers Helper
 * Implements security headers for XSS and other protections
 */

class SecurityHeaders {
    
    /**
     * Set security headers
     */
    public static function setHeaders() {
        // Prevent XSS attacks
        header("X-XSS-Protection: 1; mode=block");
        
        // Prevent content type sniffing
        header("X-Content-Type-Options: nosniff");
        
        // Prevent clickjacking
        header("X-Frame-Options: SAMEORIGIN");
        
        // Referrer policy
        header("Referrer-Policy: strict-origin-when-cross-origin");
        
        // Content Security Policy (basic)
        header("Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\' \'unsafe-eval\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:;");
    }
    
    /**
     * Set secure session parameters
     */
    public static function setSecureSession() {
        // Set secure session parameters
        ini_set(\'session.cookie_httponly\', 1);
        ini_set(\'session.cookie_secure\', 0); // Set to 1 for HTTPS
        ini_set(\'session.use_strict_mode\', 1);
        ini_set(\'session.cookie_samesite\', \'Strict\');
        
        // Regenerate session ID periodically
        if (!isset($_SESSION[\'last_regeneration\'])) {
            $_SESSION[\'last_regeneration\'] = time();
        } elseif (time() - $_SESSION[\'last_regeneration\'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION[\'last_regeneration\'] = time();
        }
    }
}
?>';

$headers_file = __DIR__ . '/../includes/security_headers.php';
file_put_contents($headers_file, $headers_helper);
echo "   ✓ Created: includes/security_headers.php\n";

echo "\n5. MEMBUAT AUTH MIDDLEWARE:\n";

// Create authentication middleware
$auth_middleware = '<?php
/**
 * Authentication Middleware
 * Centralized authentication and authorization checks
 */

require_once __DIR__ . \'/security_headers.php\';

class AuthMiddleware {
    
    /**
     * Check if user is logged in
     */
    public static function requireLogin() {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        SecurityHeaders::setSecureSession();
        
        if (!isset($_SESSION[\'user\']) || empty($_SESSION[\'user\'])) {
            header("Location: " . self::getBaseUrl() . "/auth/login.php");
            exit;
        }
        
        return $_SESSION[\'user\'];
    }
    
    /**
     * Check if user has admin role
     */
    public static function requireAdmin() {
        $user = self::requireLogin();
        
        if (!isset($user[\'role\']) || $user[\'role\'] !== \'admin\') {
            header("Location: " . self::getBaseUrl() . "/auth/login.php?error=access_denied");
            exit;
        }
        
        return $user;
    }
    
    /**
     * Check if user has specific role
     */
    public static function requireRole($required_role) {
        $user = self::requireLogin();
        
        if (!isset($user[\'role\']) || $user[\'role\'] !== $required_role) {
            header("Location: " . self::getBaseUrl() . "/auth/login.php?error=access_denied");
            exit;
        }
        
        return $user;
    }
    
    /**
     * Get base URL
     */
    private static function getBaseUrl() {
        $protocol = isset($_SERVER[\'HTTPS\']) && $_SERVER[\'HTTPS\'] === \'on\' ? \'https\' : \'http\';
        $host = $_SERVER[\'HTTP_HOST\'];
        $path = dirname(dirname($_SERVER[\'SCRIPT_NAME\']));
        return $protocol . \'://\' . $host . $path;
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $details = []) {
        $log_entry = [
            \'timestamp\' => date(\'Y-m-d H:i:s\'),
            \'event\' => $event,
            \'ip\' => $_SERVER[\'REMOTE_ADDR\'] ?? \'unknown\',
            \'user_agent\' => $_SERVER[\'HTTP_USER_AGENT\'] ?? \'unknown\',
            \'user_id\' => $_SESSION[\'user\'][\'id\'] ?? null,
            \'details\' => $details
        ];
        
        $log_file = __DIR__ . \'/../logs/security.log\';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>';

$auth_file = __DIR__ . '/../includes/auth_middleware.php';
file_put_contents($auth_file, $auth_middleware);
echo "   ✓ Created: includes/auth_middleware.php\n";

echo "\n=== PERBAIKAN KEAMANAN SELESAI ===\n";
echo "\nFILE HELPER KEAMANAN YANG DIBUAT:\n";
echo "1. includes/csrf_protection.php - CSRF token management\n";
echo "2. includes/input_validator.php - Input validation & sanitization\n";
echo "3. includes/security_headers.php - Security headers & session security\n";
echo "4. includes/auth_middleware.php - Centralized authentication\n";
echo "5. .htaccess files - Directory protection\n";

echo "\nLANGKAH SELANJUTNYA:\n";
echo "1. Implementasikan CSRF protection di semua form\n";
echo "2. Gunakan AuthMiddleware di semua halaman admin\n";
echo "3. Implementasikan input validation di semua form processing\n";
echo "4. Set security headers di semua halaman\n";
echo "5. Review dan update password hashing\n";
echo "6. Implementasikan rate limiting untuk login\n";

echo "\n=== SELESAI ===\n";
?>
