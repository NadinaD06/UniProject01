<?php
namespace App\Models;

use App\Core\Model;

class AuditLog extends Model {
    protected $table = 'audit_logs';
    
    /**
     * Log an admin action
     */
    public function log($adminId, $action, $details = null) {
        $sql = "INSERT INTO audit_logs (admin_id, action, details, created_at) 
                VALUES (:admin_id, :action, :details, NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'admin_id' => $adminId,
            'action' => $action,
            'details' => $details ? json_encode($details) : null
        ]);
    }
    
    /**
     * Get recent audit logs
     */
    public function getRecent($limit = 50) {
        $sql = "SELECT al.*, u.username as admin_username 
                FROM audit_logs al
                JOIN users u ON al.admin_id = u.id
                ORDER BY al.created_at DESC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
} 