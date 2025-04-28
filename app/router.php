<?php
/**
* app/Core/Router.php
**/

namespace App\Core;

class Router {
    /**
     * Routes collection
     *
     * @var array
     */
    protected $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'DELETE' => []
    ];
    
    /**
     * Base path for the application
     *
     * @var string
     */
    protected $basePath = '';
    
    /**
     * Constructor
     *
     * @param string $basePath Base path for the application
     */
    public function __construct($basePath = '') {
        $this->basePath = $basePath;
    }
    
    /**
     * Add a GET route
     *
     * @param string $uri URI to match
     * @param string|callable $handler Controller@method or callable
     * @return Router
     */
    public function get($uri, $handler) {
        $this->routes['GET'][$uri] = $handler;
        return $this;
    }
    
    /**
     * Add a POST route
     *
     * @param string $uri URI to match
     * @param string|callable $handler Controller@method or callable
     * @return Router
     */
    public function post($uri, $handler) {
        $this->routes['POST'][$uri] = $handler;
        return $this;
    }
    
    /**
     * Add a PUT route
     *
     * @param string $uri URI to match
     * @param string|callable $handler Controller@method or callable
     * @return Router
     */
    public function put($uri, $handler) {
        $this->routes['PUT'][$uri] = $handler;
        return $this;
    }
    
    /**
     * Add a DELETE route
     *
     * @param string $uri URI to match
     * @param string|callable $handler Controller@method or callable
     * @return Router
     */
    public function delete($uri, $handler) {
        $this->routes['DELETE'][$uri] = $handler;
        return $this;
    }
    
    /**
     * Add a route for multiple HTTP methods
     *
     * @param array $methods HTTP methods
     * @param string $uri URI to match
     * @param string|callable $handler Controller@method or callable
     * @return Router
     */
    public function match(array $methods, $uri, $handler) {
        foreach ($methods as $method) {
            $this->routes[strtoupper($method)][$uri] = $handler;
        }
        return $this;
    }
    
    /**
     * Add a route that responds to any HTTP method
     *
     * @param string $uri URI to match
     * @param string|callable $handler Controller@method or callable
     * @return Router
     */
    public function any($uri, $handler) {
        foreach ($this->routes as $method => $routes) {
            $this->routes[$method][$uri] = $handler;
        }
        return $this;
    }
    
    /**
     * Dispatch the router
     *
     * @param string $uri URI to dispatch
     * @param string $method HTTP method
     * @return mixed
     */
    public function dispatch($uri = null, $method = null) {
        $uri = $uri ?: $this->getCurrentUri();
        $method = $method ?: $_SERVER['REQUEST_METHOD'];
        
        // Check if method exists
        if (!isset($this->routes[$method])) {
            $this->handleMethodNotAllowed();
        }
        
        // Look for direct match
        if (isset($this->routes[$method][$uri])) {
            return $this->handle($this->routes[$method][$uri]);
        }
        
        // Look for pattern match
        $handler = $this->findPatternMatch($uri, $method);
        
        if ($handler) {
            return $this->handle($handler);
        }
        
        // No match found
        $this->handleNotFound();
    }
    
    /**
     * Find a pattern match for the URI
     *
     * @param string $uri URI to match
     * @param string $method HTTP method
     * @return mixed Handler or null
     */
    protected function findPatternMatch($uri, $method) {
        foreach ($this->routes[$method] as $pattern => $handler) {
            // Convert route to regex pattern
            $pattern = $this->convertRouteToRegex($pattern);
            
            if (preg_match($pattern, $uri, $matches)) {
                // Extract named parameters
                array_shift($matches); // Remove full match
                
                // Set route parameters in $_GET
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $_GET[$key] = $value;
                    }
                }
                
                return $handler;
            }
        }
        
        return null;
    }
    
    /**
     * Convert route pattern to regex
     *
     * @param string $route Route pattern
     * @return string Regex pattern
     */
    protected function convertRouteToRegex($route) {
        // Convert {param} to named capture group: (?<param>[^/]+)
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?<$1>[^/]+)', $route);
        
        // Add start and end markers
        $pattern = '#^' . $pattern . '$#';
        
        return $pattern;
    }
    
    /**
     * Handle a route
     *
     * @param string|callable $handler Controller@method or callable
     * @return mixed
     */
    protected function handle($handler) {
        // If handler is callable, call it
        if (is_callable($handler)) {
            return call_user_func($handler);
        }
        
        // If handler is string, assume Controller@method format
        if (is_string($handler)) {
            return $this->callControllerMethod($handler);
        }
        
        throw new \Exception('Invalid route handler');
    }
    
    /**
     * Call a controller method
     *
     * @param string $handler Controller@method
     * @return mixed
     */
    protected function callControllerMethod($handler) {
        list($controller, $method) = explode('@', $handler);
        
        // Add namespace if not fully qualified
        if (strpos($controller, '\\') !== 0) {
            $controller = "\\App\\Controllers\\{$controller}";
        }
        
        // Instantiate controller
        $controller = new $controller();
        
        // Call method
        return $controller->$method();
    }
    
    /**
     * Get current URI without query string
     *
     * @return string Current URI
     */
    protected function getCurrentUri() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        if (strpos($uri, '?') !== false) {
            $uri = substr($uri, 0, strpos($uri, '?'));
        }
        
        // Remove base path
        if ($this->basePath && strpos($uri, $this->basePath) === 0) {
            $uri = substr($uri, strlen($this->basePath));
        }
        
        // Ensure leading slash
        $uri = '/' . ltrim($uri, '/');
        
        return $uri;
    }
    
    /**
     * Handle 404 Not Found
     */
    protected function handleNotFound() {
        http_response_code(404);
        
        // Check if request wants JSON
        if ($this->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Not Found',
                'code' => 404
            ]);
            exit;
        }
        
        // Check if 404 view exists
        $notFoundView = dirname(__DIR__) . '/Views/errors/404.php';
        
        if (file_exists($notFoundView)) {
            include $notFoundView;
            exit;
        }
        
        // Default 404 message
        echo '<h1>404 Not Found</h1>';
        echo '<p>The requested resource could not be found.</p>';
        exit;
    }
    
    /**
     * Handle 405 Method Not Allowed
     */
    protected function handleMethodNotAllowed() {
        http_response_code(405);
        
        // Check if request wants JSON
        if ($this->wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Method Not Allowed',
                'code' => 405
            ]);
            exit;
        }
        
        // Check if 405 view exists
        $methodNotAllowedView = dirname(__DIR__) . '/Views/errors/405.php';
        
        if (file_exists($methodNotAllowedView)) {
            include $methodNotAllowedView;
            exit;
        }
        
        // Default 405 message
        echo '<h1>405 Method Not Allowed</h1>';
        echo '<p>The requested method is not allowed for this resource.</p>';
        exit;
    }
    
    /**
     * Check if request wants JSON response
     *
     * @return bool
     */
    protected function wantsJson() {
        // Check if Accept header contains application/json
        if (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
            return true;
        }
        
        // Check if X-Requested-With header is XMLHttpRequest
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get all routes
     *
     * @return array Routes
     */
    public function getRoutes() {
        return $this->routes;
    }
}