<?php
/**
 * Real-world test untuk sidebar WhatsApp
 * Simulasi kondisi sebenarnya di browser
 */

// Simulasi session dan environment
session_start();
$_SESSION['user'] = ['role' => 'admin', 'name' => 'Test Admin'];

// Test berbagai skenario active_page
$test_scenarios = [
    [
        'page' => 'automation_settings.php',
        'active_page' => 'whatsapp_automation',
        'title' => 'Pengaturan Otomatisasi WhatsApp'
    ],
    [
        'page' => 'automation_logs.php', 
        'active_page' => 'whatsapp_automation_logs',
        'title' => 'Log Otomatisasi WhatsApp'
    ],
    [
        'page' => 'config.php',
        'active_page' => 'whatsapp_config',
        'title' => 'Konfigurasi WhatsApp'
    ]
];

echo "=== REAL-WORLD SIDEBAR TEST ===\n\n";

foreach ($test_scenarios as $scenario) {
    echo "Testing: {$scenario['page']}\n";
    echo "Active Page: {$scenario['active_page']}\n";
    
    // Set variables seperti di halaman asli
    $active_page = $scenario['active_page'];
    $title = $scenario['title'];
    $script = '/admin/whatsapp/' . $scenario['page'];
    
    // Simulasi logika sidebar
    $whatsapp_pages = array(
        'whatsapp_config',
        'whatsapp_monitoring', 
        'whatsapp_templates',
        'whatsapp_test',
        'whatsapp_test_service',
        'whatsapp_automation',
        'whatsapp_automation_logs'
    );
    
    $is_whatsapp_active = in_array($active_page, $whatsapp_pages) || (isset($script) && strpos($script, '/admin/whatsapp/') !== false);
    
    echo "Result: " . ($is_whatsapp_active ? "ACTIVE" : "INACTIVE") . "\n";
    
    // Test individual menu items
    echo "Menu Items:\n";
    $menu_items = [
        'whatsapp_config' => 'Konfigurasi',
        'whatsapp_monitoring' => 'Monitoring',
        'whatsapp_templates' => 'Template Pesan',
        'whatsapp_automation' => 'Otomatisasi',
        'whatsapp_automation_logs' => 'Log Otomatisasi',
        'whatsapp_test' => 'Test Koneksi',
        'whatsapp_test_service' => 'Test Service'
    ];
    
    foreach ($menu_items as $page_key => $menu_name) {
        $is_active = ($active_page === $page_key);
        echo "  - $menu_name: " . ($is_active ? "ACTIVE" : "inactive") . "\n";
    }
    
    echo "\n";
}

// Test untuk mengecek apakah ada masalah dengan undefined variables
echo "CHECKING FOR UNDEFINED VARIABLES:\n";
$test_vars = ['active_page', 'script', 'title'];
foreach ($test_vars as $var) {
    echo "  - \$$var: " . (isset($$var) ? "DEFINED" : "UNDEFINED") . "\n";
}

echo "\n=== TEST SELESAI ===\n";
?>
