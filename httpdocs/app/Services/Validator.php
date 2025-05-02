<?php
namespace App\Services;

class Validator {
    /**
     * Validate data against rules
     */
    public function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Required rule
            if (strpos($rule, 'required') !== false && empty($value)) {
                $errors[$field][] = ucfirst($field) . ' is required';
                continue;
            }
            
            // Skip other validations if field is empty and not required
            if (empty($value)) {
                continue;
            }
            
            // Email rule
            if (strpos($rule, 'email') !== false && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                $errors[$field][] = 'Invalid email format';
            }
            
            // Min length rule
            if (preg_match('/min:(\d+)/', $rule, $matches)) {
                $min = $matches[1];
                if (strlen($value) < $min) {
                    $errors[$field][] = ucfirst($field) . ' must be at least ' . $min . ' characters';
                }
            }
            
            // Max length rule
            if (preg_match('/max:(\d+)/', $rule, $matches)) {
                $max = $matches[1];
                if (strlen($value) > $max) {
                    $errors[$field][] = ucfirst($field) . ' must not exceed ' . $max . ' characters';
                }
            }
            
            // Numeric rule
            if (strpos($rule, 'numeric') !== false && !is_numeric($value)) {
                $errors[$field][] = ucfirst($field) . ' must be a number';
            }
            
            // Alpha rule
            if (strpos($rule, 'alpha') !== false && !ctype_alpha($value)) {
                $errors[$field][] = ucfirst($field) . ' must contain only letters';
            }
            
            // Alphanumeric rule
            if (strpos($rule, 'alphanumeric') !== false && !ctype_alnum($value)) {
                $errors[$field][] = ucfirst($field) . ' must contain only letters and numbers';
            }
        }
        
        return $errors;
    }
} 