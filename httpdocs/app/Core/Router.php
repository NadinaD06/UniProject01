<?php
namespace App\Core;

class Router {
    private $routes = [];
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }

    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }

    public function put($path, $callback) {
        $this->addRoute('PUT', $path, $callback);
    }

    public function delete($path, $callback) {
        $this->addRoute('DELETE', $path, $callback);
    }

    private function addRoute($method, $path, $callback) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback
        ];
    }

    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->convertRouteToRegex($route['path']);
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove the full match

                if (is_array($route['callback'])) {
                    [$controller, $action] = $route['callback'];
                    $controller = "App\\Controllers\\{$controller}";
                    $controller = new $controller($this->db);
                    $response = call_user_func_array([$controller, $action], $matches);
                    
                    // Handle the response
                    if (is_string($response)) {
                        echo $response;
                    } elseif (is_array($response)) {
                        header('Content-Type: application/json');
                        echo json_encode($response);
                    }
                    return;
                }

                $response = call_user_func_array($route['callback'], $matches);
                if (is_string($response)) {
                    echo $response;
                } elseif (is_array($response)) {
                    header('Content-Type: application/json');
                    echo json_encode($response);
                }
                return;
            }
        }

        // No route found
        header("HTTP/1.0 404 Not Found");
        echo "404 Not Found";
    }

    private function convertRouteToRegex($route) {
        return '#^' . preg_replace('#\{([a-zA-Z0-9_]+)\}#', '([^/]+)', $route) . '$#';
    }
} 