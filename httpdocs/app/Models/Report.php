<?php
/**
 * Report Model
 * Handles report-related operations
 */
namespace App\Models;

use App\Core\Model;

class Report extends Model {
    protected $table = 'reports';
    protected $fillable = ['reporter_id', 'reported_user_id', 'reason', 'status', 'admin_action', 'action_date'];

    /**
     * Create a new report
     * @param int $reporterId User ID of the reporter
     * @param int $reportedUserId User ID of the reported user
     * @param string $reason Reason for the report
     * @return int|bool Report ID or false on failure
     */
    public function createReport($reporterId, $reportedUserId, $reason) {
        try {
            $sql = "INSERT INTO {$this->table} (reporter_id, reported_user_id, reason) VALUES (?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$reporterId, $reportedUserId, $reason]);
            
            return $this->db->lastInsertId();
        } catch (\PDOException $e) {
            error_log("Error creating report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all reports with pagination
     * @param int $page Page number
     * @param int $perPage Reports per page
     * @return array Reports with pagination info
     */
    public function getAllReports($page = 1, $perPage = 10) {
        try {
            $offset = ($page - 1) * $perPage;

            $reports = $this->db->fetchAll(
                "SELECT r.*, 
                        u1.username as reporter_username,
                        u2.username as reported_username
                FROM {$this->table} r
                JOIN users u1 ON r.reporter_id = u1.id
                JOIN users u2 ON r.reported_user_id = u2.id
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?",
                [$perPage, $offset]
            );

            $total = $this->db->fetch(
                "SELECT COUNT(*) as count FROM {$this->table}"
            )['count'];

            return [
                'data' => $reports,
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => ceil($total / $perPage)
            ];
        } catch (\PDOException $e) {
            error_log("Error getting reports: " . $e->getMessage());
            return [
                'data' => [],
                'total' => 0,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => 1
            ];
        }
    }

    /**
     * Update report status and admin action
     * @param int $reportId Report ID
     * @param string $status New status
     * @param string $adminAction Admin's action
     * @return bool Success status
     */
    public function updateReport($reportId, $status, $adminAction = null) {
        try {
            $sql = "UPDATE {$this->table} 
                    SET status = ?, 
                        admin_action = ?, 
                        action_date = CURRENT_TIMESTAMP 
                    WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$status, $adminAction, $reportId]);
        } catch (\PDOException $e) {
            error_log("Error updating report: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reports for a specific user
     * @param int $userId User ID
     * @return array List of reports
     */
    public function getUserReports($userId) {
        try {
            $sql = "SELECT r.*, u.username as reporter_username
                    FROM {$this->table} r
                    JOIN users u ON r.reporter_id = u.id
                    WHERE r.reported_user_id = ?
                    ORDER BY r.created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error getting user reports: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get report statistics
     * @param string $period Period to get stats for (week/month/year)
     * @return array Report statistics
     */
    public function getReportStats($period = 'month') {
        try {
            $dateFormat = '%Y-%m'; // Default to monthly
            switch ($period) {
                case 'week':
                    $dateFormat = '%Y-%u';
                    break;
                case 'year':
                    $dateFormat = '%Y';
                    break;
            }

            $sql = "SELECT 
                        DATE_FORMAT(created_at, ?) as period,
                        COUNT(*) as total_reports,
                        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_reports,
                        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_reports
                    FROM {$this->table}
                    GROUP BY period
                    ORDER BY period DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([$dateFormat]);
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("Error getting report stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create a new report
     */
    public function create($data) {
        $sql = "INSERT INTO reports (reporter_id, reported_id, reason, status, created_at) 
                VALUES (:reporter_id, :reported_id, :reason, 'pending', NOW())";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'reporter_id' => $data['reporter_id'],
            'reported_id' => $data['reported_id'],
            'reason' => $data['reason']
        ]);
    }
    
    /**
     * Get reports for admin
     */
    public function getForAdmin($status = null, $limit = 20, $offset = 0) {
        $sql = "SELECT r.*, 
                u1.username as reporter_username, u1.profile_image as reporter_image,
                u2.username as reported_username, u2.profile_image as reported_image
                FROM reports r
                JOIN users u1 ON r.reporter_id = u1.id
                JOIN users u2 ON r.reported_id = u2.id";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE r.status = :status";
            $params['status'] = $status;
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Update report status
     */
    public function updateStatus($reportId, $status, $adminId) {
        $sql = "UPDATE reports SET status = :status, admin_id = :admin_id, updated_at = NOW() 
                WHERE id = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $reportId,
            'status' => $status,
            'admin_id' => $adminId
        ]);
    }
    
    /**
     * Get report by ID
     */
    public function getById($reportId) {
        $sql = "SELECT r.*, 
                u1.username as reporter_username, u1.profile_image as reporter_image,
                u2.username as reported_username, u2.profile_image as reported_image
                FROM reports r
                JOIN users u1 ON r.reporter_id = u1.id
                JOIN users u2 ON r.reported_id = u2.id
                WHERE r.id = :id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $reportId]);
        return $stmt->fetch();
    }
    
    /**
     * Get reports by user
     */
    public function getByUser($userId, $type = 'reported') {
        $sql = "SELECT r.*, 
                u1.username as reporter_username, u1.profile_image as reporter_image,
                u2.username as reported_username, u2.profile_image as reported_image
                FROM reports r
                JOIN users u1 ON r.reporter_id = u1.id
                JOIN users u2 ON r.reported_id = u2.id
                WHERE r." . ($type === 'reported' ? 'reported_id' : 'reporter_id') . " = :user_id
                ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get report statistics
     */
    public function getStats() {
        $sql = "SELECT 
                status,
                COUNT(*) as count
                FROM reports
                GROUP BY status";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
} 