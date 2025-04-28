<?php
/**
 * app/Core/Model.php
 */

namespace App\Core;

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?", 
            [$id]
        );
    }
    
    public function findBy($column, $value) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE {$column} = ?", 
            [$value]
        );
    }
    
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
    
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    public function update($id, $data) {
        return $this->db->update(
            $this->table, 
            $data, 
            "{$this->primaryKey} = ?", 
            [$id]
        );
    }
    
    public function delete($id) {
        return $this->db->delete(
            $this->table, 
            "{$this->primaryKey} = ?", 
            [$id]
        );
    }
    
    public function count($where = '1', $params = []) {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE {$where}", 
            $params
        );
        
        return $result['count'];
    }
}