<?php
/**
 * app/Core/Database.php
 * Database connection and query handling
 */

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database {
    private static $instance = null;
    private $pdo;
    
    /**
     * Private constructor to enforce singleton pattern
     */
    private function __construct() {
        try {
            $this->pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME,
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
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
     * @return PDO
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Execute a query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return PDOStatement Statement
     */
    public function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
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
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Fetch all rows
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return array List of rows
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Insert a new record
     * 
     * @param string $table Table name
     * @param array $data Column data (column => value)
     * @return int|bool Last insert ID or false on failure
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $query = "INSERT INTO {$table} (" . implode(', ', $fields) . ") 
                 VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(array_values($data));
        
        return $this->pdo->lastInsertId();
    }
    
    /**
     * Update records
     * 
     * @param string $table Table name
     * @param array $data Column data to update (column => value)
     * @param string $where WHERE clause (excluding 'WHERE')
     * @param array $params WHERE clause parameters
     * @return bool Success status
     */
    public function update($table, $data, $where, $params = []) {
        $fields = array_map(function($field) {
            return "{$field} = ?";
        }, array_keys($data));
        
        $query = "UPDATE {$table} SET " . implode(', ', $fields) . " WHERE {$where}";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(array_merge(array_values($data), $params));
        
        return $stmt->rowCount();
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
        $query = "DELETE FROM {$table} WHERE {$where}";
        
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        
        return $stmt->rowCount();
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction() {
        return $this->pdo->beginTransaction();
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool Success status
     */
    public function commit() {
        return $this->pdo->commit();
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool Success status
     */
    public function rollBack() {
        return $this->pdo->rollBack();
    }
}