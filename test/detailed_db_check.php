<?php
require_once __DIR__ . '/../includes/db.php';

echo "=== Detailed Database Structure Check ===\n\n";

$tables = ['users', 'siswa', 'guru', 'kelas', 'absensi_siswa', 'absensi_guru'];

foreach ($tables as $table) {
    try {
        echo "$table table structure:\n";
        $stmt = $conn->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "  - {$column['Field']} ({$column['Type']})\n";
        }
        echo "\n";
    } catch (Exception $e) {
        echo "Error checking $table: " . $e->getMessage() . "\n\n";
    }
}
?>
