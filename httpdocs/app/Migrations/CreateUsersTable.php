<?php
/**
* app/Migrations/CreateUsersTable.php
**/

namespace App\Migrations;

use App\Core\Database;

class CreateUsersTable {
    public function up() {
        $db = Database::getInstance();
        
        $db->query("
            CREATE TABLE IF NOT EXISTS users (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                full_name VARCHAR(100),
                profile_picture VARCHAR(255),
                cover_image VARCHAR(255),
                bio TEXT,
                website VARCHAR(255),
                interests TEXT,
                is_verified TINYINT(1) DEFAULT 0,
                is_admin TINYINT(1) DEFAULT 0,
                is_active TINYINT(1) DEFAULT 1,
                is_online TINYINT(1) DEFAULT 0,
                last_active TIMESTAMP,
                remember_token VARCHAR(100),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    
    public function down() {
    $db = Database::getInstance();
    
    $db->query("DROP TABLE IF EXISTS users");
}
}