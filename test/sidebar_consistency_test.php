<?php
/**
 * Test script to verify sidebar active page consistency
 * Checks all WhatsApp admin pages for proper $active_page values
 */

echo "=== TEST KONSISTENSI SIDEBAR WHATSAPP PAGES ===\n\n";

$whatsapp_files = [
    'config.php' => 'whatsapp_config',
    'monitoring.php' => 'whatsapp_monitoring',
    'templates.php' => 'whatsapp_templates',
    'test.php' => 'whatsapp_test',
    'test_service.php' => 'whatsapp_test_service',
    'automation_settings.php' => 'whatsapp_automation',
    'automation_logs.php' => 'whatsapp_automation_logs'
];

$sidebar_pages = [
    'whatsapp_config',
    'whatsapp_monitoring',
    'whatsapp_templates',
    'whatsapp_test',
    'whatsapp_test_service',
    'whatsapp_automation',
    'whatsapp_automation_logs'
];

echo "1. PENGECEKAN ACTIVE_PAGE DI SETIAP FILE:\n";
foreach ($whatsapp_files as $file => $expected_active_page) {
    $file_path = __DIR__ . '/../admin/whatsapp/' . $file;
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        
        // Extract active_page value
        if (preg_match('/\$active_page\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
            $actual_active_page = $matches[1];
            
            if ($actual_active_page === $expected_active_page) {
                echo "   ✓ $file: $actual_active_page (BENAR)\n";
            } else {
                echo "   ✗ $file: $actual_active_page (SALAH, seharusnya: $expected_active_page)\n";
            }
            
            // Check if active_page is set before includes
            $active_page_pos = strpos($content, '$active_page');
            $sidebar_include_pos = strpos($content, "include '../../templates/sidebar.php'");
            
            if ($active_page_pos !== false && $sidebar_include_pos !== false) {
                if ($active_page_pos < $sidebar_include_pos) {
                    echo "     ✓ Urutan include: BENAR (active_page sebelum sidebar)\n";
                } else {
                    echo "     ✗ Urutan include: SALAH (active_page setelah sidebar)\n";
                }
            }
            
        } else {
            echo "   ✗ $file: TIDAK DITEMUKAN \$active_page\n";
        }
    } else {
        echo "   ✗ $file: FILE TIDAK DITEMUKAN\n";
    }
    echo "\n";
}

echo "2. PENGECEKAN SIDEBAR.PHP:\n";
$sidebar_path = __DIR__ . '/../templates/sidebar.php';
if (file_exists($sidebar_path)) {
    $sidebar_content = file_get_contents($sidebar_path);
    
    // Check if all expected pages are in the array
    echo "   Halaman yang didefinisikan di sidebar:\n";
    foreach ($sidebar_pages as $page) {
        if (strpos($sidebar_content, "'$page'") !== false) {
            echo "     ✓ $page: ADA\n";
        } else {
            echo "     ✗ $page: TIDAK ADA\n";
        }
    }
    
    // Check if fallback detection exists
    if (strpos($sidebar_content, "strpos(\$script, '/admin/whatsapp/')") !== false) {
        echo "   ✓ Fallback detection: ADA (path-based detection)\n";
    } else {
        echo "   ✗ Fallback detection: TIDAK ADA\n";
    }
    
} else {
    echo "   ✗ sidebar.php: FILE TIDAK DITEMUKAN\n";
}

echo "\n3. REKOMENDASI:\n";
echo "   - Pastikan semua file memiliki \$active_page yang sesuai dengan array di sidebar\n";
echo "   - Pastikan \$active_page didefinisikan SEBELUM include sidebar.php\n";
echo "   - Pastikan sidebar.php memiliki fallback detection untuk path-based detection\n";

echo "\n=== TEST SELESAI ===\n";
?>
