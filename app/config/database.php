<?php
/**
 * Database configuration and connection handler
 * Manages database connections using PDO
 */

class Database {
    private static $instance = null;
    private $pdo;
    private $config;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->config = require __DIR__ . '/config.php';
        $this->connect();
    }

    /**
     * Get database instance (Singleton pattern)
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establish database connection
     * @throws PDOException if connection fails
     */
    private function connect() {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                $this->config['database']['host'],
                $this->config['database']['name'],
                $this->config['database']['charset']
            );

            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $this->pdo = new PDO(
                $dsn,
                $this->config['database']['user'],
                $this->config['database']['pass'],
                $options
            );
        } catch (PDOException $e) {
            throw new PDOException("Connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get PDO instance
     * @return PDO
     */
    public function getConnection() {
        return $this->pdo;
    }

    /**
     * Execute a query with parameters
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement
     */
    public function query($query, $params = []) {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Get single row from database
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return array|false
     */
    public function fetch($query, $params = []) {
        return $this->query($query, $params)->fetch();
    }

    /**
     * Get multiple rows from database
     * @param string $query SQL query
     * @param array $params Parameters for prepared statement
     * @return array
     */
    public function fetchAll($query, $params = []) {
        return $this->query($query, $params)->fetchAll();
    }

    /**
     * Insert data into database
     * @param string $table Table name
     * @param array $data Data to insert
     * @return int Last insert ID
     */
    public function insert($table, $data) {
        $fields = array_keys($data);
        $values = array_fill(0, count($fields), '?');
        
        $query = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $table,
            implode(', ', $fields),
            implode(', ', $values)
        );

        $this->query($query, array_values($data));
        return $this->pdo->lastInsertId();
    }

    /**
     * Update data in database
     * @param string $table Table name
     * @param array $data Data to update
     * @param string $where Where clause
     * @param array $whereParams Parameters for where clause
     * @return int Number of affected rows
     */
    public function update($table, $data, $where, $whereParams = []) {
        $fields = array_map(function($field) {
            return "$field = ?";
        }, array_keys($data));

        $query = sprintf(
            "UPDATE %s SET %s WHERE %s",
            $table,
            implode(', ', $fields),
            $where
        );

        $params = array_merge(array_values($data), $whereParams);
        return $this->query($query, $params)->rowCount();
    }

    /**
     * Delete data from database
     * @param string $table Table name
     * @param string $where Where clause
     * @param array $params Parameters for where clause
     * @return int Number of affected rows
     */
    public function delete($table, $where, $params = []) {
        $query = sprintf("DELETE FROM %s WHERE %s", $table, $where);
        return $this->query($query, $params)->rowCount();
    }
} 