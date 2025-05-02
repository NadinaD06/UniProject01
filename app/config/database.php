<?php
/**
 * Database Class
 * Handles database connection
 */

class Database {
    // Database credentials
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    private $conn;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Load configuration
        $config = $this->loadConfig();
        
        $this->host = $config['host'];
        $this->db_name = $config['db_name'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->port = $config['port'] ?? 3306;
    }
    
    /**
     * Load database configuration
     * 
     * @return array Configuration parameters
     */
    private function loadConfig() {
        // Check if config file exists
        $config_file = __DIR__ . '/db_config.php';
        
        if (file_exists($config_file)) {
            return include $config_file;
        }
        
        // Default to environment variables if config file doesn't exist
        return [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'db_name' => getenv('DB_NAME') ?: 'artspace',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'port' => getenv('DB_PORT') ?: 3306
        ];
    }
    
    /**
     * Get database connection
     * 
     * @return PDO Database connection
     */
    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
        } catch(PDOException $e) {
            // Log the error to a file
            error_log("Database Connection Error: " . $e->getMessage(), 0);
            
            // Throw a user-friendly exception
            throw new Exception("Database connection failed. Please try again later.");
        }
        
        return $this->conn;
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction() {
        if (!$this->conn) {
            $this->getConnection();
        }
        
        return $this->conn->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool Success status
     */
    public function commit() {
        if (!$this->conn) {
            return false;
        }
        
        return $this->conn->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool Success status
     */
    public function rollback() {
        if (!$this->conn) {
            return false;
        }
        
        return $this->conn->rollback();
    }
}