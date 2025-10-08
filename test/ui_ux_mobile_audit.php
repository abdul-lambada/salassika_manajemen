<?php
/**
 * Comprehensive UI/UX & Mobile Friendly Audit Script
 * Checks all aspects of user interface, user experience, and mobile responsiveness
 */

echo "=== AUDIT UI/UX & MOBILE FRIENDLY SISTEM ABSENSI SEKOLAH ===\n\n";

// 1. Frontend Framework & CSS Analysis
echo "1. FRONTEND FRAMEWORK & CSS ANALYSIS:\n";

$css_files = [
    'assets/css/sb-admin-2.min.css',
    'assets/css/custom.css',
    'assets/css/style.css'
];

$js_files = [
    'assets/js/sb-admin-2.min.js',
    'assets/js/bootstrap.bundle.min.js',
    'assets/js/jquery.min.js'
];

echo "   CSS Files:\n";
foreach ($css_files as $file) {
    $file_path = __DIR__ . '/../' . $file;
    if (file_exists($file_path)) {
        $size = round(filesize($file_path) / 1024, 2);
        echo "     ✓ $file: EXISTS ($size KB)\n";
    } else {
        echo "     ✗ $file: NOT FOUND\n";
    }
}

echo "\n   JavaScript Files:\n";
foreach ($js_files as $file) {
    $file_path = __DIR__ . '/../' . $file;
    if (file_exists($file_path)) {
        $size = round(filesize($file_path) / 1024, 2);
        echo "     ✓ $file: EXISTS ($size KB)\n";
    } else {
        echo "     ✗ $file: NOT FOUND\n";
    }
}

// 2. Template & Layout Analysis
echo "\n2. TEMPLATE & LAYOUT ANALYSIS:\n";

$template_files = [
    'templates/header.php',
    'templates/sidebar.php',
    'templates/footer.php'
];

foreach ($template_files as $file) {
    $file_path = __DIR__ . '/../' . $file;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        echo "   ✓ $file: EXISTS\n";
        
        // Check for responsive meta tag
        if (strpos($content, 'viewport') !== false) {
            echo "     ✓ Responsive viewport meta: FOUND\n";
        } else {
            echo "     ⚠ Responsive viewport meta: MISSING\n";
        }
        
        // Check for Bootstrap
        if (strpos($content, 'bootstrap') !== false) {
            echo "     ✓ Bootstrap framework: DETECTED\n";
        } else {
            echo "     ⚠ Bootstrap framework: NOT DETECTED\n";
        }
        
        // Check for FontAwesome
        if (strpos($content, 'fontawesome') !== false || strpos($content, 'fa-') !== false) {
            echo "     ✓ FontAwesome icons: DETECTED\n";
        } else {
            echo "     ⚠ FontAwesome icons: NOT DETECTED\n";
        }
        
    } else {
        echo "   ✗ $file: NOT FOUND\n";
    }
    echo "\n";
}

// 3. Mobile Responsiveness Check
echo "3. MOBILE RESPONSIVENESS CHECK:\n";

$sample_pages = [
    'index.php',
    'admin/dashboard.php',
    'admin/whatsapp/config.php',
    'admin/users/list_users.php'
];

foreach ($sample_pages as $page) {
    $file_path = __DIR__ . '/../' . $page;
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        echo "   ✓ $page: EXISTS\n";
        
        // Check for responsive classes
        $responsive_classes = ['col-', 'row', 'd-none', 'd-block', 'd-sm-', 'd-md-', 'd-lg-', 'd-xl-'];
        $responsive_found = false;
        
        foreach ($responsive_classes as $class) {
            if (strpos($content, $class) !== false) {
                $responsive_found = true;
                break;
            }
        }
        
        if ($responsive_found) {
            echo "     ✓ Responsive classes: FOUND\n";
        } else {
            echo "     ⚠ Responsive classes: LIMITED\n";
        }
        
        // Check for mobile-specific elements
        if (strpos($content, 'navbar-toggler') !== false) {
            echo "     ✓ Mobile navigation: FOUND\n";
        } else {
            echo "     ⚠ Mobile navigation: NEEDS VERIFICATION\n";
        }
        
    } else {
        echo "   ⚠ $page: NOT FOUND (may not exist yet)\n";
    }
    echo "\n";
}

// 4. User Experience Elements
echo "4. USER EXPERIENCE ELEMENTS:\n";

// Check for loading indicators
$loading_indicators = 0;
$success_messages = 0;
$error_handling = 0;

$all_php_files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__ . '/../', RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($all_php_files as $file) {
    if ($file->getExtension() === 'php' && strpos($file->getPathname(), 'vendor') === false) {
        $content = file_get_contents($file->getPathname());
        
        // Check for loading indicators
        if (strpos($content, 'loading') !== false || strpos($content, 'spinner') !== false) {
            $loading_indicators++;
        }
        
        // Check for success messages
        if (strpos($content, 'alert-success') !== false || strpos($content, 'success') !== false) {
            $success_messages++;
        }
        
        // Check for error handling
        if (strpos($content, 'alert-danger') !== false || strpos($content, 'error') !== false) {
            $error_handling++;
        }
    }
}

echo "   Loading indicators found: $loading_indicators files\n";
echo "   Success messages found: $success_messages files\n";
echo "   Error handling found: $error_handling files\n";

if ($loading_indicators > 0) {
    echo "   ✓ Loading indicators: IMPLEMENTED\n";
} else {
    echo "   ⚠ Loading indicators: NEEDS IMPROVEMENT\n";
}

if ($success_messages > 5) {
    echo "   ✓ Success feedback: GOOD\n";
} else {
    echo "   ⚠ Success feedback: NEEDS IMPROVEMENT\n";
}

if ($error_handling > 5) {
    echo "   ✓ Error handling: GOOD\n";
} else {
    echo "   ⚠ Error handling: NEEDS IMPROVEMENT\n";
}

// 5. Form Usability
echo "\n5. FORM USABILITY:\n";

$form_files = glob(__DIR__ . '/../admin/*/*.php');
$forms_with_validation = 0;
$forms_with_labels = 0;
$total_forms = 0;

foreach ($form_files as $file) {
    $content = file_get_contents($file);
    if (strpos($content, '<form') !== false) {
        $total_forms++;
        
        // Check for client-side validation
        if (strpos($content, 'required') !== false || strpos($content, 'pattern') !== false) {
            $forms_with_validation++;
        }
        
        // Check for proper labels
        if (strpos($content, '<label') !== false) {
            $forms_with_labels++;
        }
    }
}

if ($total_forms > 0) {
    $validation_rate = ($forms_with_validation / $total_forms) * 100;
    $label_rate = ($forms_with_labels / $total_forms) * 100;
    
    echo "   Total forms found: $total_forms\n";
    echo "   Forms with validation: $forms_with_validation (" . round($validation_rate, 1) . "%)\n";
    echo "   Forms with labels: $forms_with_labels (" . round($label_rate, 1) . "%)\n";
    
    if ($validation_rate >= 70) {
        echo "   ✓ Form validation: GOOD\n";
    } else {
        echo "   ⚠ Form validation: NEEDS IMPROVEMENT\n";
    }
    
    if ($label_rate >= 80) {
        echo "   ✓ Form accessibility: GOOD\n";
    } else {
        echo "   ⚠ Form accessibility: NEEDS IMPROVEMENT\n";
    }
} else {
    echo "   ⚠ No forms detected for analysis\n";
}

// 6. Navigation & Menu Structure
echo "\n6. NAVIGATION & MENU STRUCTURE:\n";

$sidebar_file = __DIR__ . '/../templates/sidebar.php';
if (file_exists($sidebar_file)) {
    $content = file_get_contents($sidebar_file);
    echo "   ✓ Sidebar navigation: EXISTS\n";
    
    // Count menu items
    $menu_items = substr_count($content, 'nav-item');
    $dropdown_menus = substr_count($content, 'collapse');
    
    echo "   - Main menu items: $menu_items\n";
    echo "   - Dropdown menus: $dropdown_menus\n";
    
    // Check for active states
    if (strpos($content, 'active') !== false) {
        echo "   ✓ Active menu states: IMPLEMENTED\n";
    } else {
        echo "   ⚠ Active menu states: MISSING\n";
    }
    
    // Check for icons
    if (strpos($content, 'fa-') !== false || strpos($content, 'fas ') !== false) {
        echo "   ✓ Menu icons: IMPLEMENTED\n";
    } else {
        echo "   ⚠ Menu icons: MISSING\n";
    }
    
} else {
    echo "   ✗ Sidebar navigation: NOT FOUND\n";
}

// 7. Data Tables & Lists
echo "\n7. DATA TABLES & LISTS:\n";

$table_features = [
    'DataTables' => 0,
    'pagination' => 0,
    'search' => 0,
    'sorting' => 0,
    'responsive_table' => 0
];

foreach ($all_php_files as $file) {
    if ($file->getExtension() === 'php' && strpos($file->getPathname(), 'vendor') === false) {
        $content = file_get_contents($file->getPathname());
        
        if (strpos($content, 'DataTable') !== false) {
            $table_features['DataTables']++;
        }
        
        if (strpos($content, 'pagination') !== false) {
            $table_features['pagination']++;
        }
        
        if (strpos($content, 'search') !== false) {
            $table_features['search']++;
        }
        
        if (strpos($content, 'sort') !== false) {
            $table_features['sorting']++;
        }
        
        if (strpos($content, 'table-responsive') !== false) {
            $table_features['responsive_table']++;
        }
    }
}

foreach ($table_features as $feature => $count) {
    echo "   $feature: $count files\n";
    if ($count > 0) {
        echo "     ✓ $feature: IMPLEMENTED\n";
    } else {
        echo "     ⚠ $feature: NOT DETECTED\n";
    }
}

// 8. Performance & Loading
echo "\n8. PERFORMANCE & LOADING:\n";

// Check for minified files
$minified_files = 0;
$css_files_found = glob(__DIR__ . '/../assets/css/*.css');
$js_files_found = glob(__DIR__ . '/../assets/js/*.js');

foreach (array_merge($css_files_found, $js_files_found) as $file) {
    if (strpos($file, '.min.') !== false) {
        $minified_files++;
    }
}

echo "   Minified files found: $minified_files\n";

if ($minified_files > 0) {
    echo "   ✓ File optimization: IMPLEMENTED\n";
} else {
    echo "   ⚠ File optimization: NEEDS IMPROVEMENT\n";
}

// Check for CDN usage
$header_file = __DIR__ . '/../templates/header.php';
if (file_exists($header_file)) {
    $content = file_get_contents($header_file);
    if (strpos($content, 'cdn.') !== false || strpos($content, 'googleapis.com') !== false) {
        echo "   ✓ CDN usage: DETECTED\n";
    } else {
        echo "   ⚠ CDN usage: NOT DETECTED\n";
    }
}

// 9. Accessibility Features
echo "\n9. ACCESSIBILITY FEATURES:\n";

$accessibility_features = [
    'alt attributes' => 0,
    'aria labels' => 0,
    'semantic HTML' => 0,
    'skip links' => 0
];

foreach ($all_php_files as $file) {
    if ($file->getExtension() === 'php' && strpos($file->getPathname(), 'vendor') === false) {
        $content = file_get_contents($file->getPathname());
        
        if (strpos($content, 'alt=') !== false) {
            $accessibility_features['alt attributes']++;
        }
        
        if (strpos($content, 'aria-') !== false) {
            $accessibility_features['aria labels']++;
        }
        
        if (strpos($content, '<main>') !== false || strpos($content, '<section>') !== false || strpos($content, '<article>') !== false) {
            $accessibility_features['semantic HTML']++;
        }
        
        if (strpos($content, 'skip') !== false && strpos($content, 'content') !== false) {
            $accessibility_features['skip links']++;
        }
    }
}

foreach ($accessibility_features as $feature => $count) {
    echo "   $feature: $count files\n";
    if ($count > 0) {
        echo "     ✓ $feature: IMPLEMENTED\n";
    } else {
        echo "     ⚠ $feature: NEEDS IMPROVEMENT\n";
    }
}

echo "\n=== RINGKASAN AUDIT UI/UX & MOBILE FRIENDLY ===\n";
echo "Audit ini memberikan gambaran umum tentang status UI/UX dan mobile responsiveness.\n";
echo "Untuk evaluasi yang lebih mendalam, disarankan:\n";
echo "1. Manual testing di berbagai device dan browser\n";
echo "2. User experience testing dengan real users\n";
echo "3. Performance testing dengan tools seperti PageSpeed Insights\n";
echo "4. Accessibility testing dengan screen readers\n";
echo "5. Cross-browser compatibility testing\n\n";

echo "=== AUDIT SELESAI ===\n";
?>
