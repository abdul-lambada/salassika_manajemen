<?php
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
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
    }
    
    /**
     * Set secure session parameters
     */
    public static function setSecureSession() {
        // Set secure session parameters
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
}
?>