<?php
// Load env/config helpers so DB creds come from .env
if (file_exists(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php';
}

try {
    $host = function_exists('env') ? env('DB_HOST', 'localhost') : 'localhost';
    $dbname = function_exists('env') ? env('DB_NAME', 'absensi_sekolah') : 'absensi_sekolah';
    $username = function_exists('env') ? env('DB_USER', 'root') : 'root';
    $password = function_exists('env') ? env('DB_PASS', '') : '';
    $port = function_exists('env') ? env('DB_PORT', '') : '';

    $dsn = "mysql:host={$host};" . ($port ? "port={$port};" : "") . "dbname={$dbname};charset=utf8mb4";
    $conn = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>