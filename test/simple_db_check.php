<?php
require_once __DIR__ . '/../includes/db.php';

$stmt = $conn->query('DESCRIBE users');
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($columns as $column) {
    echo $column['Field'] . ' (' . $column['Type'] . ")\n";
}
?>
