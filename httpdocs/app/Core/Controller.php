<?php
/**
* app/Core/Controller.php
* Base controller class with common functionality
**/

namespace App\Core;

use App\Services\AuthService;
use App\Services\WebSocketService;
use App\Services\View;
use App\Services\Request;
use App\Services\Response;
use App\Services\Validator;

abstract class Controller {
    protected $auth;
    protected $webSocket;
    protected $db;
    protected $view;
    protected $request;
    protected $response;
    
    public function __construct($db) {
        $this->db = $db;
        $this->view = new View();
        $this->request = new Request();
        $this->response = new Response();
        
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
     * @param string $template Template name
     * @param array $data Data to pass to the view
     * @return string Rendered view
     */
    protected function view($template, $data = []) {
        return $this->view->render($template, $data);
    }
    
    /**
     * Redirect to another URL
     * 
     * @param string $url URL to redirect to
     * @return void
     */
    protected function redirect($url) {
        return $this->response->redirect($url);
    }
    
    /**
     * Return a success JSON response
     * 
     * @param array $data Response data
     * @param int $status HTTP status code
     * @return void
     */
    protected function json($data, $status = 200) {
        return $this->response->json($data, $status);
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
        $validator = new Validator();
        return $validator->validate($data, $rules);
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

    protected function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'];
    }

    protected function requireAdmin() {
        if (!$this->isAdmin()) {
            return $this->redirect('/');
        }
    }
}