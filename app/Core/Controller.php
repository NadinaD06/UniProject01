<?php
/**
* app/Core/Controller.php
* Base controller class with common functionality
**/

namespace App\Core;

use App\Services\AuthService;
use App\Services\WebSocketService;

abstract class Controller {
    protected $auth;
    protected $webSocket;
    
    public function __construct() {
        // Initialize authentication service
        $this->auth = new AuthService();
        
        // Initialize WebSocket service if enabled
        if (defined('WEBSOCKET_ENABLED') && WEBSOCKET_ENABLED) {
            $this->webSocket = new WebSocketService();
        }
    }
    
    /**
     * Render a view
     * 
     * @param string $view View name
     * @param array $data Data to pass to the view
     * @return string Rendered view
     */
    protected function view($view, $data = []) {
        // Extract data to make variables available in the view
        extract($data);
        
        // Define the path to the view file
        $viewPath = VIEW_PATH . '/' . $view . '.php';
        
        // Check if the view file exists
        if (!file_exists($viewPath)) {
            throw new \Exception("View {$view} not found");
        }
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        include $viewPath;
        
        // Get the contents of the buffer
        $content = ob_get_clean();
        
        // Include the layout file
        include VIEW_PATH . '/layout.php';
        
        return $content;
    }
    
    /**
     * Redirect to another URL
     * 
     * @param string $url URL to redirect to
     * @return void
     */
    protected function redirect($url) {
        header("Location: {$url}");
        exit;
    }
    
    /**
     * Return a success JSON response
     * 
     * @param array $data Response data
     * @param string $message Success message
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function success($data = [], $message = 'Success', $statusCode = 200) {
        $this->jsonResponse(true, $message, $data, $statusCode);
    }
    
    /**
     * Return an error JSON response
     * 
     * @param string $message Error message
     * @param array $errors Validation errors
     * @param int $statusCode HTTP status code
     * @return void
     */
    protected function error($message = 'Error', $errors = [], $statusCode = 400) {
        $this->jsonResponse(false, $message, [], $statusCode, $errors);
    }
    
    /**
     * Return a JSON response
     * 
     * @param bool $success Success status
     * @param string $message Response message
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     * @param array $errors Validation errors
     * @return void
     */
    protected function jsonResponse($success, $message, $data = [], $statusCode = 200, $errors = []) {
        // Set the HTTP status code
        http_response_code($statusCode);
        
        // Set the content type header
        header('Content-Type: application/json');
        
        // Build the response
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        // Add data if provided
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        // Add errors if provided
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        // Output the response
        echo json_encode($response);
        exit;
    }
    
    /**
     * Get input data from request
     * 
     * @return array Input data
     */
    protected function getInputData() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // For GET requests, return $_GET
        if ($method === 'GET') {
            return $_GET;
        }
        
        // Check content type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        // For JSON requests
        if (strpos($contentType, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            return json_decode($json, true) ?? [];
        }
        
        // For standard form submissions
        return $_POST;
    }
    
    /**
     * Check if the user is authenticated
     * 
     * @return bool
     */
    protected function checkAuth() {
        return $this->auth->check();
    }
    
    /**
     * Require authentication to access a route
     * If not authenticated, redirect to login
     * 
     * @return bool
     */
    protected function requireAuth() {
        if (!$this->checkAuth()) {
            if ($this->isAjaxRequest()) {
                $this->error('Authentication required', [], 401);
                return false;
            }
            
            $this->redirect('/login');
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if the request is an AJAX request
     * 
     * @return bool
     */
    protected function isAjaxRequest() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    /**
     * Set a flash message in the session
     * 
     * @param string $message Message content
     * @param string $type Message type (success, error, info, warning)
     * @return void
     */
    protected function setFlashMessage($message, $type = 'info') {
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    /**
     * Validate the request data
     * 
     * @param array $data Request data
     * @param array $rules Validation rules
     * @return array Validation errors
     */
    protected function validate($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            // Split rules by |
            $fieldRules = explode('|', $rule);
            
            foreach ($fieldRules as $fieldRule) {
                // Check if rule has parameters
                if (strpos($fieldRule, ':') !== false) {
                    list($ruleName, $ruleParams) = explode(':', $fieldRule, 2);
                    $ruleParams = explode(',', $ruleParams);
                } else {
                    $ruleName = $fieldRule;
                    $ruleParams = [];
                }
                
                // Apply the rule
                $error = $this->applyValidationRule($field, $ruleName, $data[$field] ?? null, $ruleParams, $data);
                
                if ($error) {
                    $errors[$field] = $error;
                    break; // Only one error per field
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Apply a validation rule to a field
     * 
     * @param string $field Field name
     * @param string $rule Rule name
     * @param mixed $value Field value
     * @param array $params Rule parameters
     * @param array $data All request data
     * @return string|null Error message or null if valid
     */
    private function applyValidationRule($field, $rule, $value, $params, $data) {
        $fieldName = ucfirst(str_replace('_', ' ', $field));
        
        switch ($rule) {
            case 'required':
                if ($value === null || $value === '') {
                    return "{$fieldName} is required";
                }
                break;
                
            case 'email':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    return "{$fieldName} must be a valid email address";
                }
                break;
                
            case 'min':
                if ($value !== null && $value !== '' && strlen($value) < (int)$params[0]) {
                    return "{$fieldName} must be at least {$params[0]} characters";
                }
                break;
                
            case 'max':
                if ($value !== null && $value !== '' && strlen($value) > (int)$params[0]) {
                    return "{$fieldName} cannot exceed {$params[0]} characters";
                }
                break;
                
            case 'matches':
                $otherField = $params[0];
                $otherFieldName = ucfirst(str_replace('_', ' ', $otherField));
                
                if ($value !== null && $value !== '' && $value !== ($data[$otherField] ?? null)) {
                    return "{$fieldName} must match {$otherFieldName}";
                }
                break;
                
            case 'alpha':
                if ($value !== null && $value !== '' && !ctype_alpha($value)) {
                    return "{$fieldName} can only contain letters";
                }
                break;
                
            case 'alpha_num':
                if ($value !== null && $value !== '' && !ctype_alnum($value)) {
                    return "{$fieldName} can only contain letters and numbers";
                }
                break;
                
            case 'alpha_dash':
                if ($value !== null && $value !== '' && !preg_match('/^[a-zA-Z0-9_-]+$/', $value)) {
                    return "{$fieldName} can only contain letters, numbers, dashes, and underscores";
                }
                break;
                
            case 'numeric':
                if ($value !== null && $value !== '' && !is_numeric($value)) {
                    return "{$fieldName} must be a number";
                }
                break;
                
            case 'integer':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_INT)) {
                    return "{$fieldName} must be an integer";
                }
                break;
                
            case 'url':
                if ($value !== null && $value !== '' && !filter_var($value, FILTER_VALIDATE_URL)) {
                    return "{$fieldName} must be a valid URL";
                }
                break;
        }
        
        return null;
    }
    
    /**
     * Set old input data in session
     * 
     * @param array $data
     */
    protected function setOldInput($data) {
        $_SESSION['old_input'] = $data;
    }
    
    /**
     * Set validation errors in session
     * 
     * @param array $errors
     */
    protected function setValidationErrors($errors) {
        $_SESSION['validation_errors'] = $errors;
    }
    
    /**
     * Check if the current route matches the given pattern
     * 
     * @param string $pattern Route pattern to check
     * @return bool True if current route matches the pattern
     */
    protected function isActiveRoute($pattern) {
        $currentRoute = $_SERVER['REQUEST_URI'];
        
        // Remove query string if present
        if (strpos($currentRoute, '?') !== false) {
            $currentRoute = substr($currentRoute, 0, strpos($currentRoute, '?'));
        }
        
        // Exact match
        if ($pattern === $currentRoute) {
            return true;
        }
        
        // Pattern match (e.g., '/post/*')
        if (substr($pattern, -1) === '*') {
            $basePattern = substr($pattern, 0, -1);
            return strpos($currentRoute, $basePattern) === 0;
        }
        
        return false;
    }
    
    /**
     * Format time ago
     * 
     * @param string $timestamp Timestamp to format
     * @return string Formatted time ago string
     */
    protected function formatTimeAgo($timestamp) {
        $time = strtotime($timestamp);
        $now = time();
        $diff = $now - $time;
        
        if ($diff < 60) {
            return 'just now';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 604800) {
            $days = floor($diff / 86400);
            return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 2592000) {
            $weeks = floor($diff / 604800);
            return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
        } elseif ($diff < 31536000) {
            $months = floor($diff / 2592000);
            return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
        } else {
            $years = floor($diff / 31536000);
            return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
        }
    }
}