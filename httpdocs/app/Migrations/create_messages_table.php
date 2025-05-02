<?php
/**
 * Migration file for creating messages table
 */

namespace App\Migrations;

use App\Core\Database;

class CreateMessagesTable {
    public function up() {
        $db = Database::getInstance();
        
        // Create messages table
        $db->query("
            CREATE TABLE IF NOT EXISTS messages (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                sender_id INT UNSIGNED NOT NULL,
                receiver_id INT UNSIGNED NOT NULL,
                content TEXT NOT NULL,
                is_read BOOLEAN DEFAULT FALSE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    public function down() {
        $db = Database::getInstance();
        $db->query("DROP TABLE IF EXISTS messages");
    }
} 