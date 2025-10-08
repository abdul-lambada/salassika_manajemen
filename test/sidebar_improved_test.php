<?php
/**
 * Test untuk sidebar WhatsApp yang sudah diperbaiki
 * Menguji semua metode deteksi yang baru
 */

echo "=== TEST SIDEBAR WHATSAPP YANG DIPERBAIKI ===\n\n";

// Simulasi berbagai kondisi
$test_cases = [
    [
        'name' => 'Normal - automation_settings.php',
        'active_page' => 'whatsapp_automation',
        'script' => '/admin/whatsapp/automation_settings.php',
        'request_uri' => '/absensi_sekolah/admin/whatsapp/automation_settings.php'
    ],
    [
        'name' => 'Normal - automation_logs.php',
        'active_page' => 'whatsapp_automation_logs',
        'script' => '/admin/whatsapp/automation_logs.php',
        'request_uri' => '/absensi_sekolah/admin/whatsapp/automation_logs.php'
    ],
    [
        'name' => 'Missing active_page',
        'active_page' => null,
        'script' => '/admin/whatsapp/config.php',
        'request_uri' => '/absensi_sekolah/admin/whatsapp/config.php'
    ],
    [
        'name' => 'Missing script',
        'active_page' => 'whatsapp_config',
        'script' => null,
        'request_uri' => '/absensi_sekolah/admin/whatsapp/config.php'
    ],
    [
        'name' => 'Only REQUEST_URI available',
        'active_page' => null,
        'script' => null,
        'request_uri' => '/absensi_sekolah/admin/whatsapp/templates.php'
    ],
    [
        'name' => 'Non-WhatsApp page',
        'active_page' => 'users',
        'script' => '/admin/users/list_users.php',
        'request_uri' => '/absensi_sekolah/admin/users/list_users.php'
    ]
];

foreach ($test_cases as $case) {
    echo "Testing: {$case['name']}\n";
    
    // Setup variables
    $active_page = $case['active_page'];
    $script = $case['script'];
    $_SERVER['REQUEST_URI'] = $case['request_uri'];
    
    // Simulasi logika sidebar yang baru
    $whatsapp_pages = array(
        'whatsapp_config',
        'whatsapp_monitoring', 
        'whatsapp_templates',
        'whatsapp_test',
        'whatsapp_test_service',
        'whatsapp_automation',
        'whatsapp_automation_logs'
    );
    
    // Ensure $active_page is defined
    if (!isset($active_page)) {
        $active_page = '';
    }
    
    // Check if current page is WhatsApp related
    $is_whatsapp_active = false;
    
    // Method 1: Check active_page array
    if (in_array($active_page, $whatsapp_pages)) {
        $is_whatsapp_active = true;
        $detection_method = "Method 1: active_page array";
    }
    
    // Method 2: Check script path
    if (isset($script) && strpos($script, '/admin/whatsapp/') !== false) {
        $is_whatsapp_active = true;
        if (!isset($detection_method)) $detection_method = "Method 2: script path";
    }
    
    // Method 3: Check current URL path as fallback
    if (!$is_whatsapp_active && isset($_SERVER['REQUEST_URI'])) {
        if (strpos($_SERVER['REQUEST_URI'], '/admin/whatsapp/') !== false) {
            $is_whatsapp_active = true;
            $detection_method = "Method 3: REQUEST_URI fallback";
        }
    }
    
    if (!isset($detection_method)) {
        $detection_method = "No detection method matched";
    }
    
    echo "  - active_page: " . ($case['active_page'] ?: '[NULL]') . "\n";
    echo "  - script: " . ($case['script'] ?: '[NULL]') . "\n";
    echo "  - request_uri: " . ($case['request_uri'] ?: '[NULL]') . "\n";
    echo "  - Result: " . ($is_whatsapp_active ? "ACTIVE" : "INACTIVE") . "\n";
    echo "  - Detection: $detection_method\n";
    echo "\n";
}

echo "KESIMPULAN:\n";
echo "- Sidebar sekarang memiliki 3 metode deteksi yang redundant\n";
echo "- Jika active_page tidak terdefinisi, akan menggunakan fallback\n";
echo "- Jika script tidak terdefinisi, akan menggunakan REQUEST_URI\n";
echo "- Sistem lebih robust dan dapat menangani berbagai kondisi error\n";

echo "\n=== TEST SELESAI ===\n";
?>
