<?php
/**
 * Authentication Middleware
 * Centralized authentication and authorization checks
 */

require_once __DIR__ . '/security_headers.php';

class AuthMiddleware {
    
    /**
     * Check if user is logged in
     */
    public static function requireLogin() {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        SecurityHeaders::setSecureSession();
        
        if (!isset($_SESSION['user']) || empty($_SESSION['user'])) {
            header("Location: " . self::getBaseUrl() . "/auth/login.php");
            exit;
        }
        
        return $_SESSION['user'];
    }
    
    /**
     * Check if user has admin role
     */
    public static function requireAdmin() {
        $user = self::requireLogin();
        
        if (!isset($user['role']) || $user['role'] !== 'admin') {
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
        
        if (!isset($user['role']) || $user['role'] !== $required_role) {
            header("Location: " . self::getBaseUrl() . "/auth/login.php?error=access_denied");
            exit;
        }
        
        return $user;
    }
    
    /**
     * Get base URL
     */
    private static function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = dirname(dirname($_SERVER['SCRIPT_NAME']));
        return $protocol . '://' . $host . $path;
    }
    
    /**
     * Log security event
     */
    public static function logSecurityEvent($event, $details = []) {
        $log_entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'user_id' => $_SESSION['user']['id'] ?? null,
            'details' => $details
        ];
        
        $log_file = __DIR__ . '/../logs/security.log';
        $log_dir = dirname($log_file);
        
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0755, true);
        }
        
        file_put_contents($log_file, json_encode($log_entry) . "\n", FILE_APPEND | LOCK_EX);
    }
}
?>