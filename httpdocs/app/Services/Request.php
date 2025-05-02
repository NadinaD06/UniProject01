<?php
namespace App\Services;

class Request {
    /**
     * Get all request data
     */
    public function all() {
        return array_merge($_GET, $_POST);
    }
    
    /**
     * Get a specific request parameter
     */
    public function get($key, $default = null) {
        return $_REQUEST[$key] ?? $default;
    }
    
    /**
     * Check if a request parameter exists
     */
    public function has($key) {
        return isset($_REQUEST[$key]);
    }
    
    /**
     * Get request method
     */
    public function method() {
        return $_SERVER['REQUEST_METHOD'];
    }
    
    /**
     * Check if request is POST
     */
    public function isPost() {
        return $this->method() === 'POST';
    }
    
    /**
     * Check if request is GET
     */
    public function isGet() {
        return $this->method() === 'GET';
    }
    
    /**
     * Check if request is AJAX
     */
    public function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
} 