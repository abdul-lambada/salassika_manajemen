<?php
require_once __DIR__ . '/../includes/db.php';

$tables = ['absensi_siswa', 'absensi_guru'];

foreach ($tables as $table) {
    echo "$table columns:\n";
    $stmt = $conn->query("SHOW COLUMNS FROM $table");
    while ($row = $stmt->fetch()) {
        echo "  " . $row[0] . " (" . $row[1] . ")\n";
    }
    echo "\n";
}
?>
