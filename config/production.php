<?php
/**
 * Production Configuration
 * Auto-generated on 2025-10-08 12:12:00
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
?>