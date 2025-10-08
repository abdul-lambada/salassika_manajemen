<?php
require_once __DIR__ . '/../includes/db.php';

echo "=== PENGECEKAN KONFIGURASI WHATSAPP ===\n\n";

// Check table structure
echo "1. STRUKTUR TABEL whatsapp_config:\n";
$stmt = $conn->query('DESCRIBE whatsapp_config');
while($row = $stmt->fetch()) {
    echo "   - " . $row[0] . " (" . $row[1] . ")\n";
}

// Check current data
echo "\n2. DATA KONFIGURASI SAAT INI:\n";
$stmt = $conn->query('SELECT * FROM whatsapp_config LIMIT 1');
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if ($config) {
    foreach($config as $key => $value) {
        $displayValue = $value;
        if (in_array($key, ['api_token', 'token']) && strlen($value) > 10) {
            $displayValue = substr($value, 0, 10) . '...';
        }
        echo "   - $key: " . ($displayValue ?: '[KOSONG]') . "\n";
    }
} else {
    echo "   TIDAK ADA DATA KONFIGURASI\n";
}

echo "\n=== SELESAI ===\n";
?>
