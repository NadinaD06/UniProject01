<?php
namespace App\Controllers;

/**
 * Base Controller class
 * Provides common functionality for all controllers
 */
abstract class Controller {
    protected $config;
    protected $db;
    protected $session;
    protected $pdo;

    /**
     * Constructor
     * Initializes common resources
     */
    public function __construct($pdo = null) {
        $this->config = require __DIR__ . '/../config/config.php';
        $this->db = Database::getInstance();
        $this->pdo = $pdo ?? $this->db->getConnection();
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
        header("Location: {$url}");
        exit;
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
        // Extract data to make variables available in view
        extract($data);

        // Get flash message
        $flash = $this->getFlash();

        // Start output buffering
        ob_start();

        // Include view file
        require __DIR__ . "/../views/{$view}.php";

        // Get buffered content
        $content = ob_get_clean();

        // Include layout if not an API response
        if (!isset($data['is_api'])) {
            require __DIR__ . "/../views/layouts/main.php";
        } else {
            echo $content;
        }
    }

    /**
     * Send JSON response
     * @param array $data Response data
     * @param int $status HTTP status code
     */
    protected function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
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
            $value = $data[$field] ?? null;
            $ruleArray = explode('|', $rule);

            foreach ($ruleArray as $singleRule) {
                $params = [];
                if (strpos($singleRule, ':') !== false) {
                    list($singleRule, $param) = explode(':', $singleRule);
                    $params = explode(',', $param);
                }

                switch ($singleRule) {
                    case 'required':
                        if (empty($value)) {
                            $errors[$field][] = "The {$field} field is required.";
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "The {$field} must be a valid email address.";
                        }
                        break;

                    case 'min':
                        if (!empty($value) && strlen($value) < $params[0]) {
                            $errors[$field][] = "The {$field} must be at least {$params[0]} characters.";
                        }
                        break;

                    case 'max':
                        if (!empty($value) && strlen($value) > $params[0]) {
                            $errors[$field][] = "The {$field} may not be greater than {$params[0]} characters.";
                        }
                        break;

                    case 'matches':
                        if ($value !== $data[$params[0]]) {
                            $errors[$field][] = "The {$field} must match {$params[0]}.";
                        }
                        break;

                    case 'unique':
                        list($table, $column) = $params;
                        $exists = $this->db->fetch(
                            "SELECT 1 FROM {$table} WHERE {$column} = ?",
                            [$value]
                        );
                        if ($exists) {
                            $errors[$field][] = "The {$field} has already been taken.";
                        }
                        break;
                }
            }
        }

        return $errors;
    }
} 