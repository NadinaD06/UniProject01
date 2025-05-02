<?php
namespace App\Controllers;

use App\Services\AuthService;
use App\Services\Request;
use App\Services\Response;
use App\Services\View;

/**
 * Base Controller class
 * Provides common functionality for all controllers
 */
abstract class Controller {
    protected $config;
    protected $db;
    protected $session;
    protected $pdo;
    protected $auth;
    protected $request;
    protected $response;
    protected $view;

    /**
     * Constructor
     * Initializes common resources
     */
    public function __construct(\PDO $db) {
        $this->config = require CONFIG_PATH . '/config.php';
        $this->db = $db;
        $this->pdo = $db;
        $this->auth = new AuthService();
        $this->request = new Request();
        $this->response = new Response();
        $this->view = new View();
        $this->startSession();
    }

    /**
     * Start or resume session
     */
    protected function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start([
                'cookie_lifetime' => $this->config['session']['lifetime'],
                'cookie_path' => $this->config['session']['path'],
                'cookie_domain' => $this->config['session']['domain'],
                'cookie_secure' => $this->config['session']['secure'],
                'cookie_httponly' => $this->config['session']['httponly']
            ]);
        }
    }

    /**
     * Check if user is logged in
     * @return bool
     */
    protected function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get current user ID
     * @return int|null
     */
    protected function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Check if user is admin
     * @return bool
     */
    protected function isAdmin() {
        return isset($_SESSION['role']) && in_array($_SESSION['role'], $this->config['admin']['roles']);
    }

    /**
     * Require user to be logged in
     * @param string $redirect Redirect URL if not logged in
     */
    protected function requireLogin($redirect = '/login.php') {
        if (!$this->isLoggedIn()) {
            $this->redirect($redirect);
        }
    }

    /**
     * Require user to be admin
     * @param string $redirect Redirect URL if not admin
     */
    protected function requireAdmin($redirect = '/index.php') {
        if (!$this->isAdmin()) {
            $this->redirect($redirect);
        }
    }

    /**
     * Redirect to URL
     * @param string $url URL to redirect to
     */
    protected function redirect($url) {
        return $this->response->redirect($url);
    }

    /**
     * Set flash message
     * @param string $type Message type (success, error, warning, info)
     * @param string $message Message content
     */
    protected function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Get and clear flash message
     * @return array|null
     */
    protected function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }

    /**
     * Render view
     * @param string $view View file path
     * @param array $data Data to pass to view
     */
    protected function render($view, $data = []) {
        return $this->view->render($view, $data);
    }

    /**
     * Send JSON response
     * @param array $data Response data
     * @param int $status HTTP status code
     */
    protected function json($data, $status = 200) {
        return $this->response->json($data, $status);
    }

    /**
     * Get POST data
     * @param string $key Data key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET data
     * @param string $key Data key
     * @param mixed $default Default value if key not found
     * @return mixed
     */
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Get uploaded files
     * @param string $key File key
     * @return array|null
     */
    protected function files($key = null) {
        if ($key === null) {
            return $_FILES;
        }
        return $_FILES[$key] ?? null;
    }

    /**
     * Validate data
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Validation errors
     */
    protected function validate($data, $rules) {
        $errors = [];
        foreach ($rules as $field => $rule) {
            if (strpos($rule, 'required') !== false && (!isset($data[$field]) || empty($data[$field]))) {
                $errors[$field] = ucfirst($field) . ' is required';
            }
        }
        return $errors;
    }

    protected function error($message, $data = [], $status = 400)
    {
        return $this->response->error($message, $data, $status);
    }

    protected function success($data = [], $message = 'Success')
    {
        return $this->response->success($data, $message);
    }

    protected function requireAuth()
    {
        if (!$this->auth->check()) {
            return $this->redirect('/login');
        }
    }

    protected function requireGuest()
    {
        if ($this->auth->check()) {
            return $this->redirect('/');
        }
    }
} 