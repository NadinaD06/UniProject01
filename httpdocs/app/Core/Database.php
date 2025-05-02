<?php
/**
 * app/Core/Database.php
 * Database connection and query handling
 */

namespace App\Core;

class Database {
    private static $instance = null;
    private $connection;
    
    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct() {
        $config = require_once(APP_ROOT . '/config/database.php');
        
        try {
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            
            $this->connection = new \PDO($dsn, $config['username'], $config['password'], $config['options']);
        } catch (\PDOException $e) {
            // Log error and throw custom exception
            error_log("Database connection error: " . $e->getMessage());
            throw new \Exception("Database connection failed. Please try again later.");
        }
    }
    
    /**
     * Get database instance (singleton)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get database connection
     * 
     * @return \PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Execute a query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return \PDOStatement Statement
     */
    public function query($sql, $params = []) {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
    
    /**
     * Fetch a single row
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array|false Row data or false if no row found
     */
    public function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Fetch all rows
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array List of rows
     */
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Insert a new record
     * 
     * @param string $table Table name
     * @param array $data Column data (column => value)
     * @return int|bool Last insert ID or false on failure
     */
    public function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        $this->query($sql, array_values($data));
        return $this->connection->lastInsertId();
    }
    
    /**
     * Update records
     * 
     * @param string $table Table name
     * @param array $data Column data to update (column => value)
     * @param string $where WHERE clause (excluding 'WHERE')
     * @param array $whereParams WHERE clause parameters
     * @return bool Success status
     */
    public function update($table, $data, $where, $whereParams = []) {
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Delete records
     * 
     * @param string $table Table name
     * @param string $where WHERE clause (excluding 'WHERE')
     * @param array $params WHERE clause parameters
     * @return bool Success status
     */
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $params);
        
        return $stmt->rowCount() > 0;
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool Success status
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool Success status
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
}