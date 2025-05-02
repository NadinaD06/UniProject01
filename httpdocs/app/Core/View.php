<?php
namespace App\Core;

class View {
    private $layout = 'layout';
    private $viewPath;

    public function __construct() {
        $this->viewPath = dirname(__DIR__) . '/Views/';
    }

    public function render($template, $data = []) {
        // Extract data to make variables available in the view
        extract($data);

        // Start output buffering
        ob_start();

        // Include the template file
        $templateFile = $this->viewPath . $template . '.php';
        if (!file_exists($templateFile)) {
            throw new \Exception("View template {$template} not found");
        }
        include $templateFile;

        // Get the contents of the buffer
        $content = ob_get_clean();

        // Include the layout file
        $layoutFile = $this->viewPath . $this->layout . '.php';
        if (!file_exists($layoutFile)) {
            throw new \Exception("Layout {$this->layout} not found");
        }
        include $layoutFile;

        return ob_get_clean();
    }

    public function setLayout($layout) {
        $this->layout = $layout;
    }

    public function partial($template, $data = []) {
        extract($data);
        $templateFile = $this->viewPath . $template . '.php';
        if (!file_exists($templateFile)) {
            throw new \Exception("Partial template {$template} not found");
        }
        include $templateFile;
    }
} 