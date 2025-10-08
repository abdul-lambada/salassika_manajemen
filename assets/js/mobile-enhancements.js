/**
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
    const submitButtons = document.querySelectorAll('button[type="submit"], input[type="submit"]');
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
    const dropdownToggles = document.querySelectorAll("[data-toggle='dropdown']");
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
