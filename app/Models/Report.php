<?php
/**
 * Report Model
 * Handles report-related operations
 */
namespace App\Models;

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
} 