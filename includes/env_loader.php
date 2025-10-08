<?php
/**
 * Environment Configuration Loader
 * Loads environment variables from .env file
 */

class EnvLoader {
    private static $loaded = false;
    private static $vars = [];
    
    public static function load($path = null) {
        if (self::$loaded) {
            return;
        }
        
        $path = $path ?: __DIR__ . "/../.env";
        
        if (!file_exists($path)) {
            throw new Exception(".env file not found at: " . $path);
        }
        
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos(trim($line), "#") === 0) {
                continue; // Skip comments
            }
            
            if (strpos($line, "=") !== false) {
                list($key, $value) = explode("=", $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((substr($value, 0, 1) === "\"" && substr($value, -1) === "\"") ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                self::$vars[$key] = $value;
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
        
        self::$loaded = true;
    }
    
    public static function get($key, $default = null) {
        self::load();
        return isset(self::$vars[$key]) ? self::$vars[$key] : $default;
    }
    
    public static function getAll() {
        self::load();
        return self::$vars;
    }
}

// Auto-load environment variables
try {
    EnvLoader::load();
} catch (Exception $e) {
    // Fallback to default values if .env not found
    error_log("Environment file not found, using defaults: " . $e->getMessage());
}
?>