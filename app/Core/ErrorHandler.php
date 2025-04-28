<?php
/**
* app/Core/ErrorHandler.php
**/

namespace App\Core;

use App\Services\LoggerService;

class ErrorHandler {
    private $logger;
    
    public function __construct() {
        $this->logger = new LoggerService('error');
        
        $this->registerHandlers();
    }
    
    /**
     * Register error and exception handlers
     */
    protected function registerHandlers() {
        // Set error handler
        set_error_handler([$this, 'handleError']);
        
        // Set exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Register shutdown function
        register_shutdown_function([$this, 'handleShutdown']);
    }
    
    /**
     * Error handler
     * 
     * @param int $level Error level
     * @param string $message Error message
     * @param string $file File where error occurred
     * @param int $line Line where error occurred
     * @return bool
     */
    public function handleError($level, $message, $file, $line) {
        if (!(error_reporting() & $level)) {
            return false;
        }
        
        $context = [
            'file' => $file,
            'line' => $line
        ];
        
        switch ($level) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_PARSE:
                $this->logger->error($message, $context);
                break;
                
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $this->logger->warning($message, $context);
                break;
                
            case E_NOTICE:
            case E_USER_NOTICE:
                $this->logger->notice($message, $context);
                break;
                
            case E_STRICT:
            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $this->logger->info($message, $context);
                break;
                
            default:
                $this->logger->warning($message, $context);
                break;
        }
        
        // Return false to allow PHP's built-in error handler to run
        return false;
    }
    
    /**
     * Exception handler
     * 
     * @param \Throwable $exception
     */
    public function handleException(\Throwable $exception) {
        $this->logger->exception($exception);
        
        // Display error page or JSON response based on request
        $this->displayError($exception);
    }
    
    /**
     * Shutdown handler
     */
    public function handleShutdown() {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $this->logger->error($error['message'], [
                'file' => $error['file'],
                'line' => $error['line']
            ]);
            
            // Display error page or JSON response based on request
            $this->displayError(new \ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            ));
        }
    }
    
    /**
     * Display error page or JSON response
     * 
     * @param \Throwable $exception
     */
    protected function displayError(\Throwable $exception) {
        $isDebug = getenv('APP_DEBUG') === 'true';
        $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $isApi = strpos($_SERVER['REQUEST_URI'], '/api/') === 0;
        
        // Set HTTP status code
        http_response_code(500);
        
        if ($isAjax || $isApi) {
            // Return JSON response for AJAX or API requests
            header('Content-Type: application/json');
            
            $response = [
                'success' => false,
                'message' => $isDebug ? $exception->getMessage() : 'An error occurred while processing your request.'
            ];
            
            if ($isDebug) {
                $response['error'] = [
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => explode("\n", $exception->getTraceAsString())
                ];
            }
            
            echo json_encode($response);
        } else {
            // Display error page for regular requests
            if ($isDebug) {
                echo $this->getDebugErrorPage($exception);
            } else {
                echo $this->getErrorPage();
            }
        }
        
        exit;
    }
    
    /**
     * Get debug error page HTML
     * 
     * @param \Throwable $exception
     * @return string HTML
     */
    protected function getDebugErrorPage(\Throwable $exception) {
        $message = htmlspecialchars($exception->getMessage());
        $file = htmlspecialchars($exception->getFile());
        $line = $exception->getLine();
        $trace = htmlspecialchars($exception->getTraceAsString());
        
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Application Error</title>
            <style>
                body {
                    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    line-height: 1.5;
                    padding: 2rem;
                    color: #333;
                    max-width: 1200px;
                    margin: 0 auto;
                }
                h1 {
                    color: #e53e3e;
                    margin-bottom: 1rem;
                }
                .error-message {
                    background-color: #fff5f5;
                    border-left: 4px solid #e53e3e;
                    padding: 1rem;
                    margin-bottom: 1rem;
                }
                .location {
                    background-color: #f7fafc;
                    padding: 1rem;
                    border-radius: 0.25rem;
                    margin-bottom: 1rem;
                    font-family: monospace;
                    overflow-x: auto;
                }
                pre {
                    background-color: #f7fafc;
                    padding: 1rem;
                    border-radius: 0.25rem;
                    font-family: monospace;
                    overflow-x: auto;
                }
            </style>
        </head>
        <body>
            <h1>Application Error</h1>
            <div class="error-message">
                <strong>Error:</strong> {$message}
            </div>
            <div class="location">
                <strong>File:</strong> {$file}<br>
                <strong>Line:</strong> {$line}
            </div>
            <h2>Stack Trace</h2>
            <pre>{$trace}</pre>
        </body>
        </html>
        HTML;
    }
    
    /**
     * Get error page HTML
     * 
     * @return string HTML
     */
    protected function getErrorPage() {
        return <<<HTML
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Error - ArtSpace</title>
            <style>
                body {
                    font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    line-height: 1.5;
                    padding: 2rem;
                    color: #333;
                    text-align: center;
                    max-width: 800px;
                    margin: 0 auto;
                }
                .logo {
                    font-size: 2rem;
                    font-weight: bold;
                    margin-bottom: 2rem;
                }
                h1 {
                    margin-bottom: 1rem;
                }
                .error-container {
                    background-color: #f7fafc;
                    border-radius: 0.5rem;
                    padding: 2rem;
                    margin-bottom: 2rem;
                }
                .btn {
                    display: inline-block;
                    background-color: #4a5568;
                    color: white;
                    padding: 0.5rem 1rem;
                    border-radius: 0.25rem;
                    text-decoration: none;
                }
                .btn:hover {
                    background-color: #2d3748;
                }
            </style>
        </head>
        <body>
            <div class="logo">ArtSpace</div>
            <div class="error-container">
                <h1>Something went wrong</h1>
                <p>We're sorry, but an error occurred while processing your request.</p>
                <p>Please try again later or contact support if the problem persists.</p>
            </div>
            <a href="/" class="btn">Return to Home</a>
        </body>
        </html>
        HTML;
    }
}