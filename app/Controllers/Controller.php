<?php
namespace App\Controllers;

class Controller {
    protected $pdo;
    protected $config;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->config = require_once __DIR__ . '/../../config/config.php';
    }
    
    protected function view($view, $data = []) {
        // Extract data to make variables available in view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        require_once __DIR__ . '/../Views/' . $view . '.php';
        
        // Get the contents of the buffer
        $content = ob_get_clean();
        
        // Include the layout
        require_once __DIR__ . '/../Views/layouts/main.php';
    }
    
    protected function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
    
    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    protected function getPost($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }
    
    protected function getQuery($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }
} 