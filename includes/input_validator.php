<?php
/**
 * Input Validation Helper
 * Provides comprehensive input validation and sanitization
 */

class InputValidator {
    
    /**
     * Sanitize string input
     */
    public static function sanitizeString($input, $max_length = 255) {
        if (!is_string($input)) {
            return '';
        }
        
        $input = trim($input);
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        if ($max_length > 0) {
            $input = substr($input, 0, $max_length);
        }
        
        return $input;
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate phone number
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 15;
    }
    
    /**
     * Validate integer
     */
    public static function validateInteger($value, $min = null, $max = null) {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            return false;
        }
        
        $value = (int)$value;
        
        if ($min !== null && $value < $min) {
            return false;
        }
        
        if ($max !== null && $value > $max) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate required fields
     */
    public static function validateRequired($fields, $data) {
        $errors = [];
        
        foreach ($fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $errors[] = "Field $field is required";
            }
        }
        
        return $errors;
    }
    
    /**
     * Sanitize array of inputs
     */
    public static function sanitizeArray($data, $allowed_keys = []) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (!empty($allowed_keys) && !in_array($key, $allowed_keys)) {
                continue;
            }
            
            if (is_array($value)) {
                $sanitized[$key] = self::sanitizeArray($value, $allowed_keys);
            } else {
                $sanitized[$key] = self::sanitizeString($value);
            }
        }
        
        return $sanitized;
    }
}
?>