<?php
namespace App\Services;

class View {
    private $viewPath;
    private $layoutPath;
    
    public function __construct() {
        $this->viewPath = dirname(__DIR__, 2) . '/Views/';
        $this->layoutPath = $this->viewPath . 'layouts/';
    }
    
    /**
     * Render a view with optional layout
     * 
     * @param string $template Template name (without .php extension)
     * @param array $data Data to pass to the view
     * @param string|null $layout Layout name (without .php extension)
     * @return string Rendered view
     */
    public function render($template, $data = [], $layout = 'main') {
        // Extract data to make variables available in view
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewFile = $this->viewPath . $template . '.php';
        if (!file_exists($viewFile)) {
            throw new \Exception("View file not found: {$viewFile}");
        }
        require $viewFile;
        
        // Get the view content
        $content = ob_get_clean();
        
        // If no layout specified, return the content directly
        if (!$layout) {
            return $content;
        }
        
        // Include the layout
        $layoutFile = $this->layoutPath . $layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \Exception("Layout file not found: {$layoutFile}");
        }
        
        // Start output buffering for layout
        ob_start();
        require $layoutFile;
        return ob_get_clean();
    }
    
    /**
     * Render a partial view
     * 
     * @param string $template Partial template name
     * @param array $data Data to pass to the partial
     * @return string Rendered partial
     */
    public function partial($template, $data = []) {
        extract($data);
        
        ob_start();
        $partialFile = $this->viewPath . 'partials/' . $template . '.php';
        if (!file_exists($partialFile)) {
            throw new \Exception("Partial file not found: {$partialFile}");
        }
        require $partialFile;
        return ob_get_clean();
    }
    
    /**
     * Escape HTML special characters
     * 
     * @param string $text Text to escape
     * @return string Escaped text
     */
    public function escape($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
} 