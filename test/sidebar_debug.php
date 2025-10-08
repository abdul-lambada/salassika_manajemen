<?php
/**
 * Debug script untuk menguji sidebar WhatsApp
 * Simulasi kondisi yang ada di halaman WhatsApp
 */

echo "=== DEBUG SIDEBAR WHATSAPP ===\n\n";

// Simulasi berbagai kondisi $active_page
$test_cases = [
    'whatsapp_config',
    'whatsapp_monitoring',
    'whatsapp_templates',
    'whatsapp_test',
    'whatsapp_test_service',
    'whatsapp_automation',
    'whatsapp_automation_logs',
    'other_page'
];

// Simulasi $script variable (yang mungkin tidak terdefinisi)
$script_cases = [
    '/admin/whatsapp/config.php',
    '/admin/whatsapp/automation_settings.php',
    '/admin/users/list_users.php',
    null, // undefined
    ''    // empty
];

echo "1. TEST ARRAY WHATSAPP_PAGES:\n";
$whatsapp_pages = array(
    'whatsapp_config',
    'whatsapp_monitoring', 
    'whatsapp_templates',
    'whatsapp_test',
    'whatsapp_test_service',
    'whatsapp_automation',
    'whatsapp_automation_logs'
);

foreach ($test_cases as $active_page) {
    $in_array = in_array($active_page, $whatsapp_pages);
    echo "   $active_page: " . ($in_array ? "✓ ADA" : "✗ TIDAK ADA") . "\n";
}

echo "\n2. TEST SCRIPT PATH DETECTION:\n";
foreach ($script_cases as $script) {
    echo "   Script: " . ($script ?: '[NULL/EMPTY]') . "\n";
    
    // Test kondisi isset dan strpos
    $isset_result = isset($script);
    $strpos_result = $isset_result ? strpos($script, '/admin/whatsapp/') : false;
    
    echo "     - isset(\$script): " . ($isset_result ? "TRUE" : "FALSE") . "\n";
    echo "     - strpos result: " . ($strpos_result !== false ? "FOUND at position $strpos_result" : "NOT FOUND") . "\n";
    echo "     - Final condition: " . (($isset_result && $strpos_result !== false) ? "TRUE" : "FALSE") . "\n\n";
}

echo "3. TEST KOMBINASI KONDISI:\n";
foreach ($test_cases as $active_page) {
    foreach ($script_cases as $script) {
        $in_array = in_array($active_page, $whatsapp_pages);
        $script_check = isset($script) && strpos($script, '/admin/whatsapp/') !== false;
        $is_whatsapp_active = $in_array || $script_check;
        
        echo "   active_page: $active_page, script: " . ($script ?: '[NULL]') . "\n";
        echo "     - in_array: " . ($in_array ? "TRUE" : "FALSE") . "\n";
        echo "     - script_check: " . ($script_check ? "TRUE" : "FALSE") . "\n";
        echo "     - is_whatsapp_active: " . ($is_whatsapp_active ? "TRUE" : "FALSE") . "\n\n";
        
        if ($active_page === 'whatsapp_automation' && $script === '/admin/whatsapp/automation_settings.php') {
            echo "   *** CASE KHUSUS: automation_settings.php dengan active_page whatsapp_automation ***\n";
            echo "   *** Hasil: " . ($is_whatsapp_active ? "AKTIF" : "TIDAK AKTIF") . " ***\n\n";
        }
    }
}

echo "4. KEMUNGKINAN MASALAH:\n";
echo "   - Variable \$script mungkin tidak terdefinisi di beberapa halaman\n";
echo "   - Variable \$active_page mungkin tidak terdefinisi sebelum sidebar di-include\n";
echo "   - Ada kemungkinan conflict dengan CSS atau JavaScript\n";
echo "   - Bootstrap collapse mungkin tidak berfungsi dengan benar\n";

echo "\n=== DEBUG SELESAI ===\n";
?>
