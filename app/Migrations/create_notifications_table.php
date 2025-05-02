<?php
/**
 * Create notifications table migration
 * Stores user notifications for various actions
 */
class CreateNotificationsTable {
    public function up($db) {
        $sql = "CREATE TABLE IF NOT EXISTS notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type ENUM('message', 'like', 'comment', 'report', 'admin_action') NOT NULL,
            content TEXT NOT NULL,
            reference_id INT,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $db->exec($sql);
    }

    public function down($db) {
        $sql = "DROP TABLE IF EXISTS notifications";
        $db->exec($sql);
    }
} 