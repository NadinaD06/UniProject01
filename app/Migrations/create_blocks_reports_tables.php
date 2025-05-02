<?php
/**
 * Migration file for creating blocks and reports tables
 */

namespace App\Migrations;

use App\Core\Database;

class CreateBlocksReportsTables {
    public function up() {
        $db = Database::getInstance();
        
        // Create blocks table
        $db->query("
            CREATE TABLE IF NOT EXISTS blocks (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                blocker_id INT UNSIGNED NOT NULL,
                blocked_id INT UNSIGNED NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (blocker_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (blocked_id) REFERENCES users(id) ON DELETE CASCADE,
                UNIQUE KEY unique_block (blocker_id, blocked_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create reports table
        $db->query("
            CREATE TABLE IF NOT EXISTS reports (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                reporter_id INT UNSIGNED NOT NULL,
                reported_id INT UNSIGNED NOT NULL,
                reason VARCHAR(255) NOT NULL,
                details TEXT,
                status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
                admin_action VARCHAR(50),
                admin_notes TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (reported_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    public function down() {
        $db = Database::getInstance();
        
        // Drop tables in reverse order due to foreign key constraints
        $db->query("DROP TABLE IF EXISTS reports");
        $db->query("DROP TABLE IF EXISTS blocks");
    }
} 