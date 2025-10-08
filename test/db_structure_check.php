<?php
require_once __DIR__ . '/../includes/db.php';

echo "=== Database Structure Check ===\n\n";

try {
    // Check if users table exists and get its structure
    $stmt = $conn->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Users table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n";
    
    // Check if whatsapp_automation_config table exists
    $stmt = $conn->query("DESCRIBE whatsapp_automation_config");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "whatsapp_automation_config table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\n";
    
    // Check if whatsapp_automation_logs table exists
    $stmt = $conn->query("DESCRIBE whatsapp_automation_logs");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "whatsapp_automation_logs table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']})\n";
    }
    
    echo "\nDatabase structure check completed.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Try to check tables with alternative method
    try {
        echo "\nTrying alternative method to check tables...\n";
        $stmt = $conn->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "Available tables:\n";
        foreach ($tables as $table) {
            echo "- $table\n";
        }
    } catch (Exception $e2) {
        echo "Alternative method also failed: " . $e2->getMessage() . "\n";
    }
}
?>
