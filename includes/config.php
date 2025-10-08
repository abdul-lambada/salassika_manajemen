<?php
/**
 * Global configuration loader
 * - Loads .env values
 * - Defines APP_URL constant
 * - Applies minimal security/session settings for production
 */

// Polyfills for PHP < 8: str_starts_with, str_ends_with
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle)
    {
        if ($needle === '') { return true; }
        return strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle)
    {
        if ($needle === '') { return true; }
        $len = strlen($needle);
        if ($len === 0) { return true; }
        return substr($haystack, -$len) === $needle;
    }
}

if (!function_exists('parse_env')) {
    function parse_env($path)
    {
        $vars = [];
        if (!is_file($path) || !is_readable($path)) {
            return $vars;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $val = trim($parts[1]);
                // strip quotes
                if ((str_starts_with($val, '"') && str_ends_with($val, '"')) || (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                    $val = substr($val, 1, -1);
                }
                $vars[$key] = $val;
            }
        }
        return $vars;
    }
}

if (!function_exists('env')) {
    function env($key, $default = null)
    {
        static $ENV_CACHE = null;
        if ($ENV_CACHE === null) {
            $root = dirname(__DIR__); // project root
            $ENV_CACHE = parse_env($root . DIRECTORY_SEPARATOR . '.env');
        }
        return array_key_exists($key, $ENV_CACHE) ? $ENV_CACHE[$key] : $default;
    }
}

// Define APP_URL if available
if (!defined('APP_URL')) {
    $appUrl = env('APP_URL');
    if (!empty($appUrl)) {
        define('APP_URL', rtrim($appUrl, '/'));
    }
}

// Apply basic production session hardening if APP_ENV=production
if (strtolower((string) env('APP_ENV', 'production')) === 'production') {
    @ini_set('session.cookie_httponly', '1');
    @ini_set('session.use_only_cookies', '1');
    // Enable this if serving over HTTPS
    // @ini_set('session.cookie_secure', '1');
}
