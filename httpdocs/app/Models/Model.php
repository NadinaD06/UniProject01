<?php
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
    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Find record by ID
     * @param int $id Record ID
     * @return array|false
     */
    public function find($id) {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
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
        
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} {$where}",
            $params
        );
    }

    /**
     * Create new record
     * @param array $data Record data
     * @return int Last insert ID
     */
    public function create($data) {
        // Filter out non-fillable fields
        $data = array_intersect_key($data, array_flip($this->fillable));
        return $this->db->insert($this->table, $data);
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
        return $this->db->update(
            $this->table,
            $data,
            "{$this->primaryKey} = ?",
            [$id]
        );
    }

    /**
     * Delete record
     * @param int $id Record ID
     * @return int Number of affected rows
     */
    public function delete($id) {
        return $this->db->delete(
            $this->table,
            "{$this->primaryKey} = ?",
            [$id]
        );
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

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} {$where}",
            $params
        )['count'];

        $records = $this->db->fetchAll(
            "SELECT * FROM {$this->table} {$where} LIMIT ? OFFSET ?",
            array_merge($params, [$perPage, $offset])
        );

        return [
            'data' => $records,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
} 