<?php
/**
 * app/Core/Model.php
 * Base model class for database operations
 */

namespace App\Core;

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find a record by primary key
     * 
     * @param int $id Primary key
     * @return array|false Record data or false if not found
     */
    public function find($id) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", 
            [$id]
        );
    }
    
    /**
     * Find a record by column value
     * 
     * @param string $column Column name
     * @param mixed $value Column value
     * @return array|false Record data or false if not found
     */
    public function findBy($column, $value) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE {$column} = ?", 
            [$value]
        );
    }
    
    /**
     * Find all records matching a condition
     * 
     * @param string $where WHERE clause (excluding 'WHERE')
     * @param array $params Query parameters
     * @param string|null $orderBy ORDER BY clause (excluding 'ORDER BY')
     * @param int|null $limit LIMIT value
     * @param int|null $offset OFFSET value
     * @return array List of records
     */
    public function findAll($where = '1', $params = [], $orderBy = null, $limit = null, $offset = null) {
        $sql = "SELECT * FROM {$this->table} WHERE {$where}";
        
        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Create a new record
     * 
     * @param array $data Record data
     * @return int|bool New record ID or false on failure
     */
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Update a record
     * 
     * @param int $id Record ID
     * @param array $data Record data to update
     * @return bool Success status
     */
    public function update($id, $data) {
        return $this->db->update(
            $this->table, 
            $data, 
            "{$this->primaryKey} = ?", 
            [$id]
        );
    }
    
    /**
     * Delete a record
     * 
     * @param int $id Record ID
     * @return bool Success status
     */
    public function delete($id) {
        return $this->db->delete(
            $this->table, 
            "{$this->primaryKey} = ?", 
            [$id]
        );
    }
    
    /**
     * Count records matching a condition
     * 
     * @param string $where WHERE clause (excluding 'WHERE')
     * @param array $params Query parameters
     * @return int Record count
     */
    public function count($where = '1', $params = []) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE {$where}", 
            $params
        );
        
        return $result ? (int)$result['count'] : 0;
    }
    
    /**
     * Begin a database transaction
     * 
     * @return bool Success status
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * Commit a database transaction
     * 
     * @return bool Success status
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * Rollback a database transaction
     * 
     * @return bool Success status
     */
    public function rollback() {
        return $this->db->rollback();
    }
    
    /**
     * Execute a custom SQL query
     * 
     * @param string $sql SQL query
     * @param array $params Query parameters
     * @return \PDOStatement Statement
     */
    public function query($sql, $params = []) {
        return $this->db->query($sql, $params);
    }
    
    /**
     * Get the last inserted ID
     * 
     * @return string Last inserted ID
     */
    public function lastInsertId() {
        return $this->db->getConnection()->lastInsertId();
    }
}