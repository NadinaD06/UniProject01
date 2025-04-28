<?php
/**
 * tests/TestCase.php
 */

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use App\Core\Database;

class TestCase extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();
        
        // Load testing environment variables
        if (file_exists(__DIR__ . '/../.env.testing')) {
            $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..', '.env.testing');
            $dotenv->load();
        }
    }
    
    /**
     * Create a new database connection
     * 
     * @return \PDO
     */
    protected function getConnection() {
        return Database::getInstance()->getConnection();
    }
    
    /**
     * Create test database tables
     */
    protected function createTestTables() {
        $db = $this->getConnection();
        
        // Create users table
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(100),
                profile_picture VARCHAR(255),
                bio TEXT,
                is_admin TINYINT(1) DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Add more tables as needed
    }
    
    /**
     * Drop test database tables
     */
    protected function dropTestTables() {
        $db = $this->getConnection();
        
        $db->exec("DROP TABLE IF EXISTS users");
        // Add more tables as needed
    }
}