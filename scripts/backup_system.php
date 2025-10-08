<?php
/**
 * Database Backup System
 * Automated backup and restore functionality
 */

require_once __DIR__ . "/../includes/env_loader.php";

class BackupSystem {
    private $conn;
    private $backup_path;
    private $retention_days;
    
    public function __construct($database_connection) {
        $this->conn = $database_connection;
        $this->backup_path = EnvLoader::get("BACKUP_PATH", "../backups/");
        $this->retention_days = EnvLoader::get("BACKUP_RETENTION_DAYS", 30);
        
        // Create backup directory if not exists
        if (!is_dir($this->backup_path)) {
            mkdir($this->backup_path, 0755, true);
        }
    }
    
    public function createBackup($type = "full") {
        try {
            $timestamp = date("Y-m-d_H-i-s");
            $filename = "backup_{$type}_{$timestamp}.sql";
            $filepath = $this->backup_path . $filename;
            
            $tables = $this->getTables();
            $sql_content = $this->generateSQLDump($tables, $type);
            
            file_put_contents($filepath, $sql_content);
            
            // Compress the backup
            $compressed_file = $filepath . ".gz";
            $this->compressFile($filepath, $compressed_file);
            unlink($filepath); // Remove uncompressed file
            
            $this->logBackup($compressed_file, $type, filesize($compressed_file));
            $this->cleanOldBackups();
            
            return [
                "success" => true,
                "filename" => basename($compressed_file),
                "size" => filesize($compressed_file),
                "path" => $compressed_file
            ];
            
        } catch (Exception $e) {
            error_log("Backup failed: " . $e->getMessage());
            return [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    }
    
    private function getTables() {
        $stmt = $this->conn->query("SHOW TABLES");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function generateSQLDump($tables, $type) {
        $sql = "-- Database Backup\n";
        $sql .= "-- Generated: " . date("Y-m-d H:i:s") . "\n";
        $sql .= "-- Type: $type\n\n";
        
        $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            // Skip log tables for incremental backups
            if ($type === "incremental" && in_array($table, ["whatsapp_logs", "integration_logs"])) {
                continue;
            }
            
            $sql .= "-- Table: $table\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            
            // Get table structure
            $create_stmt = $this->conn->query("SHOW CREATE TABLE `$table`");
            $create_row = $create_stmt->fetch(PDO::FETCH_ASSOC);
            $sql .= $create_row["Create Table"] . ";\n\n";
            
            // Get table data
            $data_stmt = $this->conn->query("SELECT * FROM `$table`");
            while ($row = $data_stmt->fetch(PDO::FETCH_ASSOC)) {
                $sql .= $this->generateInsertStatement($table, $row);
            }
            
            $sql .= "\n";
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }
    
    private function generateInsertStatement($table, $row) {
        $columns = array_keys($row);
        $values = array_values($row);
        
        $escaped_values = array_map(function($value) {
            return $value === null ? "NULL" : $this->conn->quote($value);
        }, $values);
        
        return "INSERT INTO `$table` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $escaped_values) . ");\n";
    }
    
    private function compressFile($source, $destination) {
        $file = fopen($source, "rb");
        $gz = gzopen($destination, "wb9");
        
        while (!feof($file)) {
            gzwrite($gz, fread($file, 1024 * 512));
        }
        
        fclose($file);
        gzclose($gz);
    }
    
    private function logBackup($filepath, $type, $size) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO backup_logs (filename, type, size_bytes, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([basename($filepath), $type, $size]);
        } catch (Exception $e) {
            // Table might not exist, create it
            $this->createBackupLogsTable();
            $stmt = $this->conn->prepare("
                INSERT INTO backup_logs (filename, type, size_bytes, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([basename($filepath), $type, $size]);
        }
    }
    
    private function createBackupLogsTable() {
        $sql = "
            CREATE TABLE IF NOT EXISTS backup_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                filename VARCHAR(255) NOT NULL,
                type ENUM(\"full\", \"incremental\") NOT NULL,
                size_bytes BIGINT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ";
        $this->conn->exec($sql);
    }
    
    private function cleanOldBackups() {
        $cutoff_date = date("Y-m-d", strtotime("-{$this->retention_days} days"));
        $pattern = $this->backup_path . "backup_*_" . str_replace("-", "-*", $cutoff_date) . "*.sql.gz";
        
        foreach (glob($pattern) as $old_backup) {
            if (filemtime($old_backup) < strtotime($cutoff_date)) {
                unlink($old_backup);
            }
        }
    }
    
    public function listBackups() {
        $backups = [];
        $files = glob($this->backup_path . "backup_*.sql.gz");
        
        foreach ($files as $file) {
            $backups[] = [
                "filename" => basename($file),
                "size" => filesize($file),
                "created" => date("Y-m-d H:i:s", filemtime($file)),
                "path" => $file
            ];
        }
        
        usort($backups, function($a, $b) {
            return strtotime($b["created"]) - strtotime($a["created"]);
        });
        
        return $backups;
    }
}

// CLI usage
if (php_sapi_name() === "cli" && basename(__FILE__) === basename($_SERVER["SCRIPT_NAME"])) {
    require_once __DIR__ . "/../includes/db.php";
    
    $backup = new BackupSystem($conn);
    $type = isset($argv[1]) ? $argv[1] : "full";
    
    echo "Starting $type backup...\n";
    $result = $backup->createBackup($type);
    
    if ($result["success"]) {
        echo "Backup completed successfully!\n";
        echo "File: " . $result["filename"] . "\n";
        echo "Size: " . number_format($result["size"] / 1024, 2) . " KB\n";
    } else {
        echo "Backup failed: " . $result["error"] . "\n";
        exit(1);
    }
}
?>