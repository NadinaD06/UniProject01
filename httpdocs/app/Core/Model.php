<?php
/**
 * app/Core/Model.php
 * Base model class for database operations
 */

namespace App\Models;

use PDO;
use App\Core\Database;

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    
    /**
     * Constructor
     */
    public function __construct(PDO $db = null) {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }
    
    /**
     * Find a record by primary key
     * 
     * @param int $id Primary key
     * @return array|false Record data or false if not found
     */
    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Find all records
     * 
     * @return array List of records
     */
    public function all() {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new record
     * 
     * @param array $data Record data
     * @return int|bool New record ID or false on failure
     */
    public function create($data) {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        $columns = implode(', ', array_keys($fields));
        $values = implode(', ', array_fill(0, count($fields), '?'));
        
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ({$columns}) VALUES ({$values})");
        $stmt->execute(array_values($fields));
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Update a record
     * 
     * @param int $id Record ID
     * @param array $data Record data to update
     * @return bool Success status
     */
    public function update($id, $data) {
        $fields = array_intersect_key($data, array_flip($this->fillable));
        $set = implode(', ', array_map(function($field) {
            return "{$field} = ?";
        }, array_keys($fields)));
        
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE {$this->primaryKey} = ?");
        $values = array_values($fields);
        $values[] = $id;
        
        return $stmt->execute($values);
    }
    
    /**
     * Delete a record
     * 
     * @param int $id Record ID
     * @return bool Success status
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Find a record by column value
     * 
     * @param string $column Column name
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