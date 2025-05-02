<?php
/**
* app/Services/LoggerService.php
**/

namespace App\Services;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class LoggerService {
    private $logger;
    
    /**
     * Constructor
     * 
     * @param string $channel Logger channel name
     */
    public function __construct($channel = 'app') {
        $this->logger = new Logger($channel);
        
        $this->setupHandlers();
    }
    
    /**
     * Set up log handlers
     */
    protected function setupHandlers() {
        // Create log directory if it doesn't exist
        $logPath = __DIR__ . '/../../storage/logs';
        
        if (!is_dir($logPath)) {
            mkdir($logPath, 0755, true);
        }
        
        // Line formatter
        $formatter = new LineFormatter(
            "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n",
            'Y-m-d H:i:s',
            true,
            true
        );
        
        // Daily rotating file handler
        $fileHandler = new RotatingFileHandler(
            $logPath . '/app.log',
            30, // Keep 30 days of logs
            Logger::DEBUG
        );
        $fileHandler->setFormatter($formatter);
        
        // Add handlers to logger
        $this->logger->pushHandler($fileHandler);
        
        // Add error log handler in development
        if (getenv('APP_ENV') === 'development') {
            $errorHandler = new StreamHandler('php://stderr', Logger::DEBUG);
            $errorHandler->setFormatter($formatter);
            $this->logger->pushHandler($errorHandler);
        }
    }
    
    /**
     * Log emergency message
     * 
     * @param string $message Log message
     * @param array $context Context data
     */
    public function emergency($message, array $context = []) {
        $this->logger->emergency($message, $context);
    }
    
    /**
     * Log alert message
     * 
     * @param string $message Log message
     * @param array $context Context data
     */
    public function alert($message, array $context = []) {
        $this->logger->alert($message, $context);
    }
    
    /**
     * Log critical message
     * 
     * @param string $message Log message
     * @param array $context Context data
     */
    public function critical($message, array $context = []) {
        $this->logger->critical($message, $context);
    }
    
    /**
     * Log error message
     * 
     * @param string $message Log message
     * @param array $context Context data
     */
    public function error($message, array $context = []) {
        $this->logger->error($message, $context);
    }
    
    /**
     * Log warning message
     * 
     * @param string $message Log message
     * @param array $context Context data
     */
    public function warning($message, array $context = []) {
        $this->logger->warning($message, $context);
    }
    
    /**
     * Log notice message
     * 
     * @param string $message Log message
     * @param array $context Context data
     */
    public function notice($message, array $context = []) {
        $this->logger->notice($message, $context);
    }
    
    /**
     * Log info message
     * 
     * @param string $message Log message
     * @param array $context Context data
     */
    public function info($message, array $context = []) {
        $this->logger->info($message, $context);
    }
    
    /**
     * Log debug message
     * 
     * @param string $message Log message
     * @param array $context Context data
     */
    public function debug($message, array $context = []) {
        $this->logger->debug($message, $context);
    }
    
    /**
     * Log exception
     * 
     * @param \Throwable $exception Exception to log
     * @param array $context Additional context
     */
    public function exception(\Throwable $exception, array $context = []) {
        $context['file'] = $exception->getFile();
        $context['line'] = $exception->getLine();
        $context['trace'] = $exception->getTraceAsString();
        
        $this->error($exception->getMessage(), $context);
    }
}