<?php
/**
 * UI/UX & Mobile Friendly Improvements Implementation
 * Creates enhancements for better user experience and mobile responsiveness
 */

echo "=== IMPLEMENTASI PERBAIKAN UI/UX & MOBILE FRIENDLY ===\n\n";

// 1. Create Custom CSS for Mobile Enhancements
echo "1. MEMBUAT CUSTOM CSS UNTUK MOBILE ENHANCEMENTS:\n";

$mobile_css = '/* Mobile-First Responsive Enhancements */

/* Mobile Navigation Improvements */
@media (max-width: 768px) {
    .sidebar {
        width: 100% !important;
        position: fixed !important;
        z-index: 1050;
        transform: translateX(-100%);
        transition: transform 0.3s ease-in-out;
    }
    
    .sidebar.show {
        transform: translateX(0);
    }
    
    .sidebar-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 1040;
        display: none;
    }
    
    .sidebar-overlay.show {
        display: block;
    }
    
    .content-wrapper {
        margin-left: 0 !important;
    }
    
    /* Mobile Table Improvements */
    .table-responsive {
        font-size: 0.875rem;
    }
    
    .table-responsive .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
    }
    
    /* Mobile Form Improvements */
    .form-group {
        margin-bottom: 1rem;
    }
    
    .form-control {
        font-size: 16px; /* Prevent zoom on iOS */
    }
    
    /* Mobile Card Improvements */
    .card {
        margin-bottom: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .card-header {
        padding: 0.75rem 1rem;
    }
    
    .card-body {
        padding: 1rem;
    }
}

/* Touch-Friendly Improvements */
.btn {
    min-height: 44px; /* Apple recommended touch target */
    min-width: 44px;
}

.nav-link {
    min-height: 44px;
    display: flex;
    align-items: center;
}

/* Loading States */
.loading {
    position: relative;
    pointer-events: none;
}

.loading::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Success/Error Message Improvements */
.alert {
    border-radius: 0.5rem;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.alert-dismissible .close {
    padding: 0.75rem 1rem;
    color: inherit;
}

/* Form Validation Styles */
.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-control.is-valid {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.invalid-feedback {
    display: block;
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.valid-feedback {
    display: block;
    color: #28a745;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

/* Accessibility Improvements */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}

/* Focus Improvements */
.btn:focus,
.form-control:focus,
.nav-link:focus {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* Print Styles */
@media print {
    .sidebar,
    .navbar,
    .btn,
    .no-print {
        display: none !important;
    }
    
    .content-wrapper {
        margin-left: 0 !important;
    }
    
    .card {
        border: 1px solid #ddd;
        page-break-inside: avoid;
    }
}
';

$css_file = __DIR__ . '/../assets/css/mobile-enhancements.css';
$css_dir = dirname($css_file);
if (!is_dir($css_dir)) {
    mkdir($css_dir, 0755, true);
}
file_put_contents($css_file, $mobile_css);
echo "   ✓ Created: assets/css/mobile-enhancements.css\n";

// 2. Create Mobile Navigation JavaScript
echo "\n2. MEMBUAT MOBILE NAVIGATION JAVASCRIPT:\n";

$mobile_js = '/**
 * Mobile Navigation Enhancement
 * Improves mobile navigation experience
 */

document.addEventListener("DOMContentLoaded", function() {
    // Mobile sidebar toggle
    const sidebarToggle = document.querySelector("#sidebarToggle");
    const sidebar = document.querySelector(".sidebar");
    const contentWrapper = document.querySelector("#content-wrapper");
    
    // Create overlay for mobile
    const overlay = document.createElement("div");
    overlay.className = "sidebar-overlay";
    document.body.appendChild(overlay);
    
    // Toggle sidebar on mobile
    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", function() {
            if (window.innerWidth <= 768) {
                sidebar.classList.toggle("show");
                overlay.classList.toggle("show");
                document.body.classList.toggle("sidebar-open");
            }
        });
    }
    
    // Close sidebar when clicking overlay
    overlay.addEventListener("click", function() {
        sidebar.classList.remove("show");
        overlay.classList.remove("show");
        document.body.classList.remove("sidebar-open");
    });
    
    // Handle window resize
    window.addEventListener("resize", function() {
        if (window.innerWidth > 768) {
            sidebar.classList.remove("show");
            overlay.classList.remove("show");
            document.body.classList.remove("sidebar-open");
        }
    });
    
    // Form validation enhancement
    const forms = document.querySelectorAll("form");
    forms.forEach(function(form) {
        form.addEventListener("submit", function(e) {
            const requiredFields = form.querySelectorAll("[required]");
            let isValid = true;
            
            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add("is-invalid");
                    isValid = false;
                    
                    // Show error message
                    let errorMsg = field.parentNode.querySelector(".invalid-feedback");
                    if (!errorMsg) {
                        errorMsg = document.createElement("div");
                        errorMsg.className = "invalid-feedback";
                        field.parentNode.appendChild(errorMsg);
                    }
                    errorMsg.textContent = "This field is required";
                } else {
                    field.classList.remove("is-invalid");
                    field.classList.add("is-valid");
                    
                    // Remove error message
                    const errorMsg = field.parentNode.querySelector(".invalid-feedback");
                    if (errorMsg) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
        
        // Real-time validation
        const inputs = form.querySelectorAll("input, textarea, select");
        inputs.forEach(function(input) {
            input.addEventListener("blur", function() {
                if (input.hasAttribute("required") && !input.value.trim()) {
                    input.classList.add("is-invalid");
                    input.classList.remove("is-valid");
                } else if (input.value.trim()) {
                    input.classList.remove("is-invalid");
                    input.classList.add("is-valid");
                }
            });
        });
    });
    
    // Loading state for buttons
    const submitButtons = document.querySelectorAll(\'button[type="submit"], input[type="submit"]\');
    submitButtons.forEach(function(button) {
        button.addEventListener("click", function() {
            const form = button.closest("form");
            if (form && form.checkValidity()) {
                button.classList.add("loading");
                button.disabled = true;
                
                // Re-enable after 5 seconds as fallback
                setTimeout(function() {
                    button.classList.remove("loading");
                    button.disabled = false;
                }, 5000);
            }
        });
    });
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll(".alert");
    alerts.forEach(function(alert) {
        if (!alert.querySelector(".close")) {
            setTimeout(function() {
                alert.style.transition = "opacity 0.5s";
                alert.style.opacity = "0";
                setTimeout(function() {
                    alert.remove();
                }, 500);
            }, 5000);
        }
    });
    
    // Improve table responsiveness
    const tables = document.querySelectorAll("table");
    tables.forEach(function(table) {
        if (!table.closest(".table-responsive")) {
            const wrapper = document.createElement("div");
            wrapper.className = "table-responsive";
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
    
    // Touch-friendly dropdowns
    const dropdownToggles = document.querySelectorAll("[data-toggle=\'dropdown\']");
    dropdownToggles.forEach(function(toggle) {
        toggle.addEventListener("touchstart", function(e) {
            e.preventDefault();
            toggle.click();
        });
    });
});

// Utility functions
function showLoading(element) {
    element.classList.add("loading");
    element.disabled = true;
}

function hideLoading(element) {
    element.classList.remove("loading");
    element.disabled = false;
}

function showAlert(message, type = "info") {
    const alert = document.createElement("div");
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    `;
    
    const container = document.querySelector(".container-fluid") || document.body;
    container.insertBefore(alert, container.firstChild);
    
    // Auto dismiss
    setTimeout(function() {
        alert.remove();
    }, 5000);
}
';

$js_file = __DIR__ . '/../assets/js/mobile-enhancements.js';
$js_dir = dirname($js_file);
if (!is_dir($js_dir)) {
    mkdir($js_dir, 0755, true);
}
file_put_contents($js_file, $mobile_js);
echo "   ✓ Created: assets/js/mobile-enhancements.js\n";

// 3. Create Enhanced Header Template
echo "\n3. MEMBUAT ENHANCED HEADER TEMPLATE:\n";

$enhanced_header = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Sistem Absensi Sekolah - Manajemen kehadiran siswa dan guru">
    <meta name="author" content="Sistem Absensi Sekolah">
    <meta name="theme-color" content="#4e73df">
    
    <!-- PWA Meta Tags -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Absensi Sekolah">
    
    <title><?php echo isset($title) ? $title . " - " : ""; ?>Sistem Absensi Sekolah</title>
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="/absensi_sekolah/assets/css/sb-admin-2.min.css" as="style">
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" as="style">
    
    <!-- CSS -->
    <link href="/absensi_sekolah/assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="/absensi_sekolah/assets/css/mobile-enhancements.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.bootstrap4.min.css" rel="stylesheet">
    
    <!-- Custom Favicon -->
    <link rel="icon" type="image/x-icon" href="/absensi_sekolah/assets/img/favicon.ico">
    <link rel="apple-touch-icon" href="/absensi_sekolah/assets/img/apple-touch-icon.png">
    
    <!-- Security Headers -->
    <?php
    // Set security headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: SAMEORIGIN");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    ?>
</head>

<body id="page-top" class="<?php echo isset($body_class) ? $body_class : \'\'; ?>">
    
    <!-- Skip to main content for accessibility -->
    <a class="sr-only sr-only-focusable" href="#main-content">Skip to main content</a>
    
    <!-- Page Wrapper -->
    <div id="wrapper">
';

$header_file = __DIR__ . '/../templates/header-enhanced.php';
file_put_contents($header_file, $enhanced_header);
echo "   ✓ Created: templates/header-enhanced.php\n";

// 4. Create Form Validation Helper
echo "\n4. MEMBUAT FORM VALIDATION HELPER:\n";

$form_helper = '<?php
/**
 * Form Validation and Enhancement Helper
 * Provides utilities for better form UX and validation
 */

class FormHelper {
    
    /**
     * Generate form input with validation
     */
    public static function input($name, $type = "text", $options = []) {
        $id = $options["id"] ?? $name;
        $label = $options["label"] ?? ucfirst(str_replace("_", " ", $name));
        $required = $options["required"] ?? false;
        $value = $options["value"] ?? "";
        $placeholder = $options["placeholder"] ?? "";
        $class = $options["class"] ?? "form-control";
        $help = $options["help"] ?? "";
        
        $html = "<div class=\"form-group\">\n";
        
        // Label
        $html .= "    <label for=\"$id\"";
        if ($required) {
            $html .= " class=\"required\"";
        }
        $html .= ">$label";
        if ($required) {
            $html .= " <span class=\"text-danger\">*</span>";
        }
        $html .= "</label>\n";
        
        // Input
        $html .= "    <input type=\"$type\" id=\"$id\" name=\"$name\" class=\"$class\"";
        if ($value) {
            $html .= " value=\"" . htmlspecialchars($value) . "\"";
        }
        if ($placeholder) {
            $html .= " placeholder=\"" . htmlspecialchars($placeholder) . "\"";
        }
        if ($required) {
            $html .= " required";
        }
        $html .= ">\n";
        
        // Help text
        if ($help) {
            $html .= "    <small class=\"form-text text-muted\">$help</small>\n";
        }
        
        // Validation feedback placeholders
        $html .= "    <div class=\"invalid-feedback\"></div>\n";
        $html .= "    <div class=\"valid-feedback\"></div>\n";
        
        $html .= "</div>\n";
        
        return $html;
    }
    
    /**
     * Generate select dropdown with validation
     */
    public static function select($name, $options = [], $form_options = []) {
        $id = $form_options["id"] ?? $name;
        $label = $form_options["label"] ?? ucfirst(str_replace("_", " ", $name));
        $required = $form_options["required"] ?? false;
        $selected = $form_options["selected"] ?? "";
        $class = $form_options["class"] ?? "form-control";
        $help = $form_options["help"] ?? "";
        
        $html = "<div class=\"form-group\">\n";
        
        // Label
        $html .= "    <label for=\"$id\"";
        if ($required) {
            $html .= " class=\"required\"";
        }
        $html .= ">$label";
        if ($required) {
            $html .= " <span class=\"text-danger\">*</span>";
        }
        $html .= "</label>\n";
        
        // Select
        $html .= "    <select id=\"$id\" name=\"$name\" class=\"$class\"";
        if ($required) {
            $html .= " required";
        }
        $html .= ">\n";
        
        if (!$required) {
            $html .= "        <option value=\"\">-- Pilih $label --</option>\n";
        }
        
        foreach ($options as $value => $text) {
            $html .= "        <option value=\"" . htmlspecialchars($value) . "\"";
            if ($selected == $value) {
                $html .= " selected";
            }
            $html .= ">" . htmlspecialchars($text) . "</option>\n";
        }
        
        $html .= "    </select>\n";
        
        // Help text
        if ($help) {
            $html .= "    <small class=\"form-text text-muted\">$help</small>\n";
        }
        
        // Validation feedback placeholders
        $html .= "    <div class=\"invalid-feedback\"></div>\n";
        $html .= "    <div class=\"valid-feedback\"></div>\n";
        
        $html .= "</div>\n";
        
        return $html;
    }
    
    /**
     * Generate submit button with loading state
     */
    public static function submitButton($text = "Submit", $options = []) {
        $class = $options["class"] ?? "btn btn-primary";
        $id = $options["id"] ?? "submit-btn";
        $icon = $options["icon"] ?? "fas fa-save";
        
        $html = "<div class=\"form-group\">\n";
        $html .= "    <button type=\"submit\" id=\"$id\" class=\"$class\">\n";
        $html .= "        <i class=\"$icon mr-2\"></i>$text\n";
        $html .= "    </button>\n";
        $html .= "</div>\n";
        
        return $html;
    }
    
    /**
     * Generate CSRF token input
     */
    public static function csrfToken() {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION["csrf_token"])) {
            $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
        }
        
        return "<input type=\"hidden\" name=\"csrf_token\" value=\"" . $_SESSION["csrf_token"] . "\">\n";
    }
}
?>';

$form_file = __DIR__ . '/../includes/form_helper.php';
file_put_contents($form_file, $form_helper);
echo "   ✓ Created: includes/form_helper.php\n";

echo "\n=== PERBAIKAN UI/UX & MOBILE FRIENDLY SELESAI ===\n";
echo "\nFILE ENHANCEMENT YANG DIBUAT:\n";
echo "1. assets/css/mobile-enhancements.css - Mobile-first responsive styles\n";
echo "2. assets/js/mobile-enhancements.js - Mobile navigation & form enhancements\n";
echo "3. templates/header-enhanced.php - Enhanced header with PWA & security\n";
echo "4. includes/form_helper.php - Form validation & accessibility helper\n";

echo "\nFITUR YANG DITAMBAHKAN:\n";
echo "✓ Mobile-first responsive design\n";
echo "✓ Touch-friendly navigation\n";
echo "✓ Form validation enhancements\n";
echo "✓ Loading states & feedback\n";
echo "✓ Accessibility improvements\n";
echo "✓ PWA-ready meta tags\n";
echo "✓ Security headers\n";
echo "✓ Performance optimizations\n";

echo "\nLANGKAH IMPLEMENTASI:\n";
echo "1. Ganti header.php dengan header-enhanced.php\n";
echo "2. Include mobile-enhancements.css dan .js di semua halaman\n";
echo "3. Gunakan FormHelper untuk form baru\n";
echo "4. Test responsiveness di berbagai device\n";
echo "5. Validasi accessibility dengan screen reader\n";

echo "\n=== SELESAI ===\n";
?>
