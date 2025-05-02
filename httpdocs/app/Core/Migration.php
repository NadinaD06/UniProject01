<?php
/**
* app/Core/Migration.php
**/

namespace App\Core;

use App\Core\Database;

class Migration {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function createMigrationsTable() {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL,
                batch INT NOT NULL
            )
        ");
    }
    
    public function getMigrations() {
        return $this->db->fetchAll("SELECT * FROM migrations");
    }
    
    public function run() {
        $this->createMigrationsTable();
        
        $migrations = $this->getMigrations();
        $migrationFiles = $this->getMigrationFiles();
        
        $completedMigrations = array_column($migrations, 'migration');
        $pendingMigrations = array_diff($migrationFiles, $completedMigrations);
        
        $batch = $this->getNextBatch();
        
        foreach ($pendingMigrations as $migration) {
            $className = 'App\\Migrations\\' . pathinfo($migration, PATHINFO_FILENAME);
            $instance = new $className();
            
            echo "Running migration: {$migration}\n";
            
            $instance->up();
            
            $this->db->insert('migrations', [
                'migration' => $migration,
                'batch' => $batch
            ]);
        }
    }
    
    public function rollback() {
        $this->createMigrationsTable();
        
        $batch = $this->getLastBatch();
        
        $migrations = $this->db->fetchAll(
            "SELECT * FROM migrations WHERE batch = ? ORDER BY id DESC",
            [$batch]
        );
        
        foreach ($migrations as $migration) {
            $className = 'App\\Migrations\\' . pathinfo($migration['migration'], PATHINFO_FILENAME);
            $instance = new $className();
            
            echo "Rolling back: {$migration['migration']}\n";
            
            $instance->down();
            
            $this->db->delete('migrations', 'id = ?', [$migration['id']]);
        }
    }
    
    protected function getMigrationFiles() {
        $files = glob(__DIR__ . '/../Migrations/*.php');
        
        return array_map(function($file) {
            return pathinfo($file, PATHINFO_FILENAME) . '.php';
        }, $files);
    }
    
    protected function getNextBatch() {
        $lastBatch = $this->getLastBatch();
        
        return $lastBatch + 1;
    }
    
    protected function getLastBatch() {
        $result = $this->db->fetch("SELECT MAX(batch) as batch FROM migrations");
        
        return $result['batch'] ?? 0;
    }
}