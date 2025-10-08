<?php
require_once __DIR__ . '/../includes/db.php';

echo "siswa table columns:\n";
$stmt = $conn->query("SHOW COLUMNS FROM siswa");
while ($row = $stmt->fetch()) {
    echo "  " . $row[0] . " (" . $row[1] . ")\n";
}
?>
