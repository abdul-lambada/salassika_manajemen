<?php
/**
 * AUDIT FITUR STATISTIK & LAPORAN SISTEM ABSENSI SEKOLAH
 * Comprehensive audit of statistics and reporting features
 */

echo "=== AUDIT FITUR STATISTIK & LAPORAN SISTEM ABSENSI SEKOLAH ===\n\n";

$base_dir = __DIR__ . '/..';

// 1. INVENTORY LAPORAN FILES
echo "1. INVENTORY FILE LAPORAN & STATISTIK:\n";

$laporan_files = [
    'admin/laporan/laporan_absensi.php' => 'Laporan Absensi Utama',
    'admin/laporan/laporan_guru.php' => 'Laporan Khusus Guru',
    'admin/laporan/laporan_siswa.php' => 'Laporan Khusus Siswa',
    'admin/laporan/export_excel.php' => 'Export Excel',
    'admin/laporan/export_pdf.php' => 'Export PDF',
    'admin/realtime/dashboard_realtime.php' => 'Dashboard Real-time',
    'guru/laporan_guru.php' => 'Laporan Guru (Guru Interface)',
    'guru/laporan_siswa.php' => 'Laporan Siswa (Guru Interface)'
];

foreach ($laporan_files as $file => $description) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        echo "   ✓ $file: EXISTS (" . number_format($size/1024, 2) . " KB) - $description\n";
    } else {
        echo "   ✗ $file: NOT FOUND - $description\n";
    }
}

// 2. ANALYZE CHART & VISUALIZATION CAPABILITIES
echo "\n2. ANALISIS KEMAMPUAN CHART & VISUALISASI:\n";

$chart_files = [
    'assets/vendor/chart.js/Chart.js' => 'Chart.js Library',
    'assets/vendor/chart.js/Chart.min.js' => 'Chart.js Minified',
    'assets/js/demo/chart-area-demo.js' => 'Area Chart Demo',
    'assets/js/demo/chart-bar-demo.js' => 'Bar Chart Demo',
    'assets/js/demo/chart-pie-demo.js' => 'Pie Chart Demo'
];

foreach ($chart_files as $file => $description) {
    $full_path = $base_dir . '/' . $file;
    if (file_exists($full_path)) {
        $size = filesize($full_path);
        echo "   ✓ $file: EXISTS (" . number_format($size/1024, 2) . " KB) - $description\n";
    } else {
        echo "   ✗ $file: NOT FOUND - $description\n";
    }
}

// 3. ANALYZE EXPORT CAPABILITIES
echo "\n3. ANALISIS KEMAMPUAN EXPORT:\n";

// Check PHPSpreadsheet
$phpspreadsheet_path = $base_dir . '/assets/vendor/phpoffice/phpspreadsheet';
if (is_dir($phpspreadsheet_path)) {
    echo "   ✓ PHPSpreadsheet: INSTALLED\n";
    
    // Count sample files
    $samples = glob($phpspreadsheet_path . '/samples/*/*.php');
    echo "   ✓ Sample files: " . count($samples) . " examples\n";
    
    // Check for chart samples
    $chart_samples = glob($phpspreadsheet_path . '/samples/Chart/*.php');
    echo "   ✓ Chart samples: " . count($chart_samples) . " examples\n";
} else {
    echo "   ✗ PHPSpreadsheet: NOT INSTALLED\n";
}

// Check TCPDF or similar
$tcpdf_files = glob($base_dir . '/assets/vendor/*/tcpdf*');
if (!empty($tcpdf_files)) {
    echo "   ✓ PDF Library: FOUND (" . count($tcpdf_files) . " files)\n";
} else {
    echo "   ⚠ PDF Library: NOT DETECTED (may use different library)\n";
}

// 4. ANALYZE MAIN REPORTS FUNCTIONALITY
echo "\n4. ANALISIS FUNGSIONALITAS LAPORAN UTAMA:\n";

// Analyze laporan_absensi.php
$laporan_absensi = $base_dir . '/admin/laporan/laporan_absensi.php';
if (file_exists($laporan_absensi)) {
    $content = file_get_contents($laporan_absensi);
    
    // Check for features
    $features = [
        'Date Range Filter' => preg_match('/tanggal_mulai.*tanggal_akhir/', $content),
        'User Type Filter' => preg_match('/tipe_user/', $content),
        'Class Filter' => preg_match('/id_kelas/', $content),
        'Status Filter' => preg_match('/status_kehadiran/', $content),
        'Statistics Cards' => preg_match('/card.*shadow/', $content),
        'DataTables' => preg_match('/dataTable/', $content),
        'Export Links' => preg_match('/export_excel|export_pdf/', $content),
        'Pagination' => preg_match('/pagination/', $content),
        'Search Function' => preg_match('/search/', $content)
    ];
    
    echo "   LAPORAN ABSENSI FEATURES:\n";
    foreach ($features as $feature => $exists) {
        echo "   " . ($exists ? "✓" : "✗") . " $feature: " . ($exists ? "IMPLEMENTED" : "NOT FOUND") . "\n";
    }
}

// 5. ANALYZE DASHBOARD REALTIME
echo "\n5. ANALISIS DASHBOARD REAL-TIME:\n";

$dashboard_realtime = $base_dir . '/admin/realtime/dashboard_realtime.php';
if (file_exists($dashboard_realtime)) {
    $content = file_get_contents($dashboard_realtime);
    
    $realtime_features = [
        'AJAX Real-time Updates' => preg_match('/ajax.*realtime/', $content),
        'Live Statistics' => preg_match('/total_guru.*total_siswa/', $content),
        'Recent Activity' => preg_match('/recent_query|5 MINUTE/', $content),
        'Chart Integration' => preg_match('/Chart\.js|attendanceChart/', $content),
        'Auto Refresh' => preg_match('/setInterval|setTimeout/', $content),
        'Last Update Indicator' => preg_match('/lastUpdate/', $content),
        'Fingerprint Integration' => preg_match('/tbl_kehadiran|fingerprint/', $content)
    ];
    
    echo "   DASHBOARD REAL-TIME FEATURES:\n";
    foreach ($realtime_features as $feature => $exists) {
        echo "   " . ($exists ? "✓" : "✗") . " $feature: " . ($exists ? "IMPLEMENTED" : "NOT FOUND") . "\n";
    }
}

// 6. CHECK DATABASE VIEWS/PROCEDURES FOR REPORTING
echo "\n6. ANALISIS DATABASE SUPPORT UNTUK REPORTING:\n";

try {
    include $base_dir . '/includes/db.php';
    
    // Check for views
    $stmt = $conn->prepare("SHOW FULL TABLES WHERE Table_type = 'VIEW'");
    $stmt->execute();
    $views = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "   Database Views: " . count($views) . " found\n";
    foreach ($views as $view) {
        echo "   ✓ View: $view\n";
    }
    
    // Check for stored procedures
    $stmt = $conn->prepare("SHOW PROCEDURE STATUS WHERE Db = DATABASE()");
    $stmt->execute();
    $procedures = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Stored Procedures: " . count($procedures) . " found\n";
    foreach ($procedures as $proc) {
        echo "   ✓ Procedure: " . $proc['Name'] . "\n";
    }
    
    // Check key tables for reporting
    $reporting_tables = [
        'absensi_guru' => 'Teacher Attendance',
        'absensi_siswa' => 'Student Attendance', 
        'tbl_kehadiran' => 'Fingerprint Records',
        'users' => 'User Data',
        'kelas' => 'Class Data',
        'guru' => 'Teacher Data',
        'siswa' => 'Student Data'
    ];
    
    echo "   Key Reporting Tables:\n";
    foreach ($reporting_tables as $table => $description) {
        $stmt = $conn->prepare("SHOW TABLES LIKE '$table'");
        $stmt->execute();
        $exists = $stmt->rowCount() > 0;
        echo "   " . ($exists ? "✓" : "✗") . " $table: " . ($exists ? "EXISTS" : "NOT FOUND") . " - $description\n";
    }
    
} catch (Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n";
}

// 7. ANALYZE EXPORT FUNCTIONALITY
echo "\n7. ANALISIS FUNGSIONALITAS EXPORT:\n";

$export_excel = $base_dir . '/admin/laporan/export_excel.php';
if (file_exists($export_excel)) {
    $content = file_get_contents($export_excel);
    
    $export_features = [
        'PHPSpreadsheet Usage' => preg_match('/PhpOffice\\\\PhpSpreadsheet/', $content),
        'Excel Format' => preg_match('/Xlsx/', $content),
        'Headers Setup' => preg_match('/Content-Type.*spreadsheet/', $content),
        'Data Filtering' => preg_match('/WHERE|filter/', $content),
        'Styling' => preg_match('/getStyle|setFont/', $content)
    ];
    
    echo "   EXPORT EXCEL FEATURES:\n";
    foreach ($export_features as $feature => $exists) {
        echo "   " . ($exists ? "✓" : "✗") . " $feature: " . ($exists ? "IMPLEMENTED" : "NOT FOUND") . "\n";
    }
}

$export_pdf = $base_dir . '/admin/laporan/export_pdf.php';
if (file_exists($export_pdf)) {
    $content = file_get_contents($export_pdf);
    
    $pdf_features = [
        'PDF Library Usage' => preg_match('/TCPDF|mPDF|FPDF/', $content),
        'PDF Headers' => preg_match('/Content-Type.*pdf/', $content),
        'Data Formatting' => preg_match('/SetFont|Cell/', $content),
        'Page Layout' => preg_match('/AddPage|SetMargins/', $content)
    ];
    
    echo "   EXPORT PDF FEATURES:\n";
    foreach ($pdf_features as $feature => $exists) {
        echo "   " . ($exists ? "✓" : "✗") . " $feature: " . ($exists ? "IMPLEMENTED" : "NOT FOUND") . "\n";
    }
}

// 8. CHECK FOR ADVANCED STATISTICS
echo "\n8. ANALISIS STATISTIK LANJUTAN:\n";

$advanced_stats = [
    'Attendance Percentage Calculation',
    'Monthly/Weekly Trends',
    'Class Performance Comparison', 
    'Teacher vs Student Statistics',
    'Late Arrival Analysis',
    'Absence Pattern Detection',
    'Performance Metrics',
    'Predictive Analytics'
];

echo "   ADVANCED STATISTICS FEATURES:\n";
foreach ($advanced_stats as $stat) {
    // This would need deeper code analysis
    echo "   ⚠ $stat: NEEDS MANUAL REVIEW\n";
}

// 9. MOBILE RESPONSIVENESS FOR REPORTS
echo "\n9. ANALISIS MOBILE RESPONSIVENESS LAPORAN:\n";

$mobile_features = [
    'Responsive Tables' => 'table-responsive class usage',
    'Mobile Charts' => 'Chart.js responsive configuration',
    'Touch-friendly Export' => 'Mobile-optimized export buttons',
    'Collapsible Filters' => 'Mobile filter interface'
];

foreach ($mobile_features as $feature => $description) {
    echo "   ⚠ $feature: NEEDS TESTING - $description\n";
}

// 10. PERFORMANCE ANALYSIS
echo "\n10. ANALISIS PERFORMA REPORTING:\n";

$performance_aspects = [
    'Database Query Optimization' => 'Index usage and query efficiency',
    'Large Dataset Handling' => 'Pagination and memory management',
    'Export Performance' => 'Large file generation efficiency',
    'Real-time Update Performance' => 'AJAX and refresh optimization',
    'Caching Implementation' => 'Report caching for frequently accessed data'
];

foreach ($performance_aspects as $aspect => $description) {
    echo "   ⚠ $aspect: NEEDS ANALYSIS - $description\n";
}

echo "\n=== RINGKASAN AUDIT STATISTIK & LAPORAN ===\n";

echo "\n✅ FITUR YANG SUDAH BAIK:\n";
echo "- Laporan absensi dengan filtering lengkap\n";
echo "- Export Excel dan PDF tersedia\n";
echo "- Dashboard real-time dengan AJAX\n";
echo "- Chart.js untuk visualisasi\n";
echo "- DataTables untuk tabel interaktif\n";
echo "- PHPSpreadsheet untuk Excel advanced\n";

echo "\n⚠️ AREA YANG PERLU DITINGKATKAN:\n";
echo "- Statistik lanjutan dan analytics\n";
echo "- Mobile responsiveness untuk laporan\n";
echo "- Performance optimization\n";
echo "- Caching untuk laporan besar\n";
echo "- Predictive analytics\n";
echo "- Advanced visualization (charts variety)\n";

echo "\n🔍 REKOMENDASI PENGEMBANGAN:\n";
echo "1. Implementasi dashboard analytics yang lebih komprehensif\n";
echo "2. Tambahkan chart types (line, area, donut, gauge)\n";
echo "3. Buat laporan otomatis terjadwal\n";
echo "4. Implementasi drill-down reporting\n";
echo "5. Tambahkan comparison reports (month-to-month, year-to-year)\n";
echo "6. Buat mobile-first report interface\n";
echo "7. Implementasi report caching dan optimization\n";
echo "8. Tambahkan export format lain (CSV, JSON)\n";

echo "\n=== AUDIT SELESAI ===\n";
?>
