<?php
/**
 * Base Controller
 * Abstract class that provides common functionality for all controllers
 */

require_once '../config/Database.php';
require_once '../helpers/AuthHelper.php';

abstract class BaseController {
    protected $db;
    protected $conn;
    protected $auth;
    protected $current_user_id;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize database connection
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->db = $database;
        
        // Initialize auth helper
        $this->auth = new AuthHelper();
        
        // Get current user ID if logged in
        $this->current_user_id = $this->auth->getCurrentUserId();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Process API request - abstract method to be implemented by child classes
     */
    abstract public function processRequest();
    
    /**
     * Send success response
     * 
     * @param array $data Response data
     * @param int $status HTTP status code
     */
    protected function respondWithSuccess($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
        exit;
    }
    
    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $status HTTP status code
     * @param array $errors Detailed error information (optional)
     */
    protected function respondWithError($message, $status = 400, $errors = []) {
        http_response_code($status);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Require authentication for the current request
     * 
     * @return bool True if authenticated
     */
    protected function requireAuth() {
        if (!$this->auth->isLoggedIn()) {
            $this->respondWithError('Authentication required', 401);
            return false;
        }
        
        return true;
    }
    
    /**
     * Require admin privileges for the current request
     * 
     * @return bool True if user is admin
     */
    protected function requireAdmin() {
        if (!$this->requireAuth()) {
            return false;
        }
        
        if (!$this->auth->isAdmin()) {
            $this->respondWithError('Admin privileges required', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Require specific permission for the current request
     * 
     * @param string $permission Permission to check
     * @return bool True if user has permission
     */
    protected function requirePermission($permission) {
        if (!$this->requireAuth()) {
            return false;
        }
        
        if (!$this->auth->hasPermission($permission)) {
            $this->respondWithError('You do not have permission to perform this action', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Require ownership of a resource or admin privileges
     * 
     * @param string $resource_type Type of resource
     * @param int $resource_id ID of resource
     * @return bool True if user owns the resource or is admin
     */
    protected function requireOwnershipOrAdmin($resource_type, $resource_id) {
        if (!$this->requireAuth()) {
            return false;
        }
        
        // Admins have access to all resources
        if ($this->auth->isAdmin()) {
            return true;
        }
        
        // Check if user owns the resource
        if (!$this->auth->isResourceOwner($resource_type, $resource_id)) {
            $this->respondWithError('You do not have permission to access this resource', 403);
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate request method
     * 
     * @param string|array $allowed_methods Allowed HTTP methods
     * @return bool True if method is allowed
     */
    protected function validateMethod($allowed_methods) {
        $current_method = $_SERVER['REQUEST_METHOD'];
        
        if (is_string($allowed_methods)) {
            $allowed_methods = [$allowed_methods];
        }
        
        if (!in_array($current_method, $allowed_methods)) {
            $this->respondWithError('Method not allowed', 405, [
                'allowed_methods' => $allowed_methods
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Get input data from the request (JSON or form data)
     * 
     * @return array Input data
     */
    protected function getInputData() {
        $method = $_SERVER['REQUEST_METHOD'];
        $content_type = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
        
        // For GET requests, return query parameters
        if ($method === 'GET') {
            return $_GET;
        }
        
        // For JSON input
        if (strpos($content_type, 'application/json') !== false) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                $this->respondWithError('Invalid JSON data', 400);
            }
            
            return $data ?? [];
        }
        
        // For form data
        return $_POST;
    }
    
    /**
     * Validate required input parameters
     * 
     * @param array $data Input data
     * @param array $required Required parameter names
     * @return bool True if all required parameters are present
     */
    protected function validateRequiredParams($data, $required) {
        $missing = [];
        
        foreach ($required as $param) {
            if (!isset($data[$param]) || (is_string($data[$param]) && trim($data[$param]) === '')) {
                $missing[] = $param;
            }
        }
        
        if (!empty($missing)) {
            $this->respondWithError('Missing required parameters', 400, [
                'missing_params' => $missing
            ]);
            return false;
        }
        
        return true;
    }
    
    /**
     * Begin a database transaction
     * 
     * @return bool Success status
     */
    protected function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit a database transaction
     * 
     * @return bool Success status
     */
    protected function commitTransaction() {
        return $this->db->commit();
    }
    
    /**
     * Rollback a database transaction
     * 
     * @return bool Success status
     */
    protected function rollbackTransaction() {
        return $this->db->rollback();
    }
    
    /**
     * Generate and store CSRF token
     * 
     * @return string CSRF token
     */
    protected function generateCsrfToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token Token to validate
     * @return bool True if token is valid
     */
    protected function validateCsrfToken($token) {
        if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
            $this->respondWithError('Invalid CSRF token', 403);
            return false;
        }
        
        return true;
    }
}