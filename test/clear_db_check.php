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

// Check if whatsapp_automation_config table exists and get its data
try {
    echo "whatsapp_automation_config data:\n";
    $stmt = $conn->query("SELECT * FROM whatsapp_automation_config LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($config) {
        foreach ($config as $key => $value) {
            echo "  - $key: $value\n";
        }
    } else {
        echo "  No configuration data found.\n";
    }
    echo "\n";
} catch (Exception $e) {
    echo "Error checking whatsapp_automation_config: " . $e->getMessage() . "\n\n";
}
?>
