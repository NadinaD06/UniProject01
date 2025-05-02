<?php

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Base Model class
 * Provides common functionality for all models
 */
abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];

    /**
     * Constructor
     * Initializes database connection
     */
    public function __construct(PDO $db = null) {
        $this->db = $db ?? Database::getInstance()->getConnection();
    }

    /**
     * Find record by ID
     * @param int $id Record ID
     * @return array|false
     */
    public function find($id) {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all records
     * @param array $conditions Where conditions
     * @param array $params Parameters for conditions
     * @return array
     */
    public function all($conditions = [], $params = []) {
        $where = '';
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }
        
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} {$where}"
        );
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new record
     * @param array $data Record data
     * @return int Last insert ID
     */
    public function create($data) {
        // Filter out non-fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));
        
        $fields = array_keys($data);
        $placeholders = array_fill(0, count($fields), '?');
        
        $query = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                 VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }

    /**
     * Update record
     * @param int $id Record ID
     * @param array $data Update data
     * @return int Number of affected rows
     */
    public function update($id, $data) {
        // Filter out non-fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));
        
        $fields = array_map(function($field) {
            return "{$field} = ?";
        }, array_keys($data));
        
        $query = "UPDATE {$this->table} SET " . implode(', ', $fields) . " 
                 WHERE {$this->primaryKey} = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->execute(array_merge(array_values($data), [$id]));
        
        return $stmt->rowCount();
    }

    /**
     * Delete record
     * @param int $id Record ID
     * @return int Number of affected rows
     */
    public function delete($id) {
        $stmt = $this->db->prepare(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?"
        );
        $stmt->execute([$id]);
        return $stmt->rowCount();
    }

    /**
     * Hide specified fields from output
     * @param array $data Record data
     * @return array
     */
    protected function hideFields($data) {
        return array_diff_key($data, array_flip($this->hidden));
    }

    /**
     * Get paginated records
     * @param int $page Page number
     * @param int $perPage Records per page
     * @param array $conditions Where conditions
     * @param array $params Parameters for conditions
     * @return array
     */
    public function paginate($page = 1, $perPage = 10, $conditions = [], $params = []) {
        $offset = ($page - 1) * $perPage;
        $where = '';
        
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM {$this->table} {$where}"
        );
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} {$where} LIMIT ? OFFSET ?"
        );
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $records,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
} 