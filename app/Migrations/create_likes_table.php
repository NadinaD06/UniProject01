<?php
/**
 * Create likes table migration
 * Stores post likes
 */
class CreateLikesTable {
    public function up($db) {
        $sql = "CREATE TABLE IF NOT EXISTS likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            post_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE,
            UNIQUE KEY unique_like (user_id, post_id)
        )";
        
        $db->exec($sql);
    }

    public function down($db) {
        $sql = "DROP TABLE IF EXISTS likes";
        $db->exec($sql);
    }
} 