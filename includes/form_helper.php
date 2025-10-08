<?php
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
?>