<?php
namespace App\Services;

class View {
    /**
     * Render a view file
     * 
     * @param string $view The view file path relative to the views directory
     * @param array $data Data to pass to the view
     * @return string The rendered view
     */
    public function render($view, $data = []) {
        // Extract data to make variables available in view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Build the full path to the view file
        $viewFile = dirname(dirname(__DIR__)) . '/app/Views/' . $view . '.php';
        
        // Check if view file exists
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }
        
        // Include the view file
        require $viewFile;
        
        // Get the contents of the buffer and clean it
        $content = ob_get_clean();
        
        return $content;
    }
    
    /**
     * Escape HTML special characters
     * 
     * @param string $text The text to escape
     * @return string The escaped text
     */
    public function escape($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Include a partial view
     * 
     * @param string $partial The partial view file path
     * @param array $data Data to pass to the partial
     */
    public function partial($partial, $data = []) {
        // Extract data to make variables available in partial
        extract($data);
        
        // Build the full path to the partial file
        $partialFile = dirname(dirname(__DIR__)) . '/app/Views/partials/' . $partial . '.php';
        
        // Check if partial file exists
        if (!file_exists($partialFile)) {
            throw new \Exception("Partial file not found: {$partialFile}");
        }
        
        // Include the partial file
        require $partialFile;
    }
    
    /**
     * Get the current flash message
     * 
     * @return array|null The flash message or null if none exists
     */
    public function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
    
    /**
     * Get the current user
     * 
     * @return array|null The current user or null if not logged in
     */
    public function getCurrentUser() {
        return $_SESSION['user'] ?? null;
    }
    
    /**
     * Check if user is logged in
     * 
     * @return bool True if user is logged in, false otherwise
     */
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Get CSRF token
     * 
     * @return string The CSRF token
     */
    public function getCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     * 
     * @param string $token The token to validate
     * @return bool True if token is valid, false otherwise
     */
    public function validateCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
} 