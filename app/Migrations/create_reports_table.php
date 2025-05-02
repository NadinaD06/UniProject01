<?php
/**
 * Create reports table migration
 * Stores user reports and their status
 */
class CreateReportsTable {
    public function up($db) {
        $sql = "CREATE TABLE IF NOT EXISTS reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reporter_id INT NOT NULL,
            reported_user_id INT NOT NULL,
            reason TEXT NOT NULL,
            status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
            admin_action TEXT,
            action_date TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (reported_user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $db->exec($sql);
    }

    public function down($db) {
        $sql = "DROP TABLE IF EXISTS reports";
        $db->exec($sql);
    }
} 