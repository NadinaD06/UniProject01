<?php
/**
 * Report Model
 * Handles user reporting functionality
 */
namespace App\Models;

class Report extends Model {
    protected $table = 'reports';
    protected $fillable = [
        'reporter_id',
        'reported_id',
        'reason',
        'details',
        'status',
        'admin_action',
        'admin_notes'
    ];

    // Report statuses
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_RESOLVED = 'resolved';

    // Admin actions
    const ACTION_BLOCK = 'block_user';
    const ACTION_DELETE = 'delete_user';
    const ACTION_REQUEST_ID = 'request_id';
    const ACTION_NO_ACTION = 'no_action';

    /**
     * Create a new report
     * @param int $reporterId User making the report
     * @param int $reportedId User being reported
     * @param string $reason Report reason
     * @param string $details Additional details
     * @return int|bool Report ID or false on failure
     */
    public function createReport($reporterId, $reportedId, $reason, $details = null) {
        return $this->create([
            'reporter_id' => $reporterId,
            'reported_id' => $reportedId,
            'reason' => $reason,
            'details' => $details,
            'status' => self::STATUS_PENDING
        ]);
    }

    /**
     * Update report status and admin action
     * @param int $reportId Report ID
     * @param string $status New status
     * @param string $action Admin action
     * @param string $notes Admin notes
     * @return bool Success status
     */
    public function updateReport($reportId, $status, $action = null, $notes = null) {
        return (bool) $this->update($reportId, [
            'status' => $status,
            'admin_action' => $action,
            'admin_notes' => $notes
        ]);
    }

    /**
     * Get all pending reports
     * @param int $page Page number
     * @param int $perPage Reports per page
     * @return array
     */
    public function getPendingReports($page = 1, $perPage = 20) {
        return $this->getReportsByStatus(self::STATUS_PENDING, $page, $perPage);
    }

    /**
     * Get reports by status
     * @param string $status Report status
     * @param int $page Page number
     * @param int $perPage Reports per page
     * @return array
     */
    public function getReportsByStatus($status, $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;

        $reports = $this->db->fetchAll(
            "SELECT r.*,
                reporter.username as reporter_username,
                reported.username as reported_username
            FROM {$this->table} r
            JOIN users reporter ON r.reporter_id = reporter.id
            JOIN users reported ON r.reported_id = reported.id
            WHERE r.status = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?",
            [$status, $perPage, $offset]
        );

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM {$this->table} WHERE status = ?",
            [$status]
        )['count'];

        return [
            'data' => $reports,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }

    /**
     * Get reports statistics
     * @param string $period 'week'|'month'|'year'
     * @return array
     */
    public function getStats($period = 'week') {
        $intervals = [
            'week' => '7 DAY',
            'month' => '1 MONTH',
            'year' => '1 YEAR'
        ];

        $interval = $intervals[$period] ?? '7 DAY';

        return $this->db->fetchAll("
            SELECT 
                DATE(created_at) as date,
                COUNT(*) as report_count,
                COUNT(DISTINCT reporter_id) as reporter_count,
                COUNT(DISTINCT reported_id) as reported_count,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved_count
            FROM {$this->table}
            WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL {$interval})
            GROUP BY DATE(created_at)
            ORDER BY date DESC
        ");
    }

    /**
     * Get reports made by a user
     * @param int $userId User ID
     * @return array
     */
    public function getReportsByUser($userId) {
        return $this->db->fetchAll(
            "SELECT r.*, u.username as reported_username
            FROM {$this->table} r
            JOIN users u ON r.reported_id = u.id
            WHERE r.reporter_id = ?
            ORDER BY r.created_at DESC",
            [$userId]
        );
    }

    /**
     * Get reports against a user
     * @param int $userId User ID
     * @return array
     */
    public function getReportsAgainstUser($userId) {
        return $this->db->fetchAll(
            "SELECT r.*, u.username as reporter_username
            FROM {$this->table} r
            JOIN users u ON r.reporter_id = u.id
            WHERE r.reported_id = ?
            ORDER BY r.created_at DESC",
            [$userId]
        );
    }
} 