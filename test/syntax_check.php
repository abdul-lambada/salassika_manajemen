<?php
// Simple syntax check for attendance automation file
$file = __DIR__ . '/../includes/attendance_whatsapp_automation.php';

echo "Checking syntax of: $file\n";

// Check if file exists
if (!file_exists($file)) {
    echo "File does not exist!\n";
    exit(1);
}

// Check syntax
$output = [];
$return_code = 0;
exec("php -l \"$file\"", $output, $return_code);

if ($return_code === 0) {
    echo "Syntax OK\n";
} else {
    echo "Syntax Error:\n";
    foreach ($output as $line) {
        echo "$line\n";
    }
}

// Check for duplicate class declarations
$content = file_get_contents($file);
$class_count = substr_count($content, 'class AttendanceWhatsAppAutomation');
echo "Number of class declarations found: $class_count\n";

if ($class_count > 1) {
    echo "ERROR: Multiple class declarations found!\n";
}
?>
