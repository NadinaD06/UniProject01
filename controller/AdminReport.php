<?php
/**
 * Admin Report Controller
 * Handles administrative functions for content reports
 */

require_once '../models/Report.php';
require_once '../models/User.php';
require_once '../models/Post.php';
require_once '../models/Comment.php';
require_once '../controllers/BaseController.php';

class AdminReportController extends BaseController {
    private $report;
    private $user;
    private $post;
    private $comment;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Call parent constructor
        parent::__construct();
        
        // Initialize models
        $this->report = new Report($this->conn);
        $this->user = new User($this->conn);
        $this->post = new Post($this->conn);
        $this->comment = new Comment($this->conn);
    }
    
    /**
     * Process admin report requests
     */
    public function processRequest() {
        // Require admin privileges for all actions
        if (!$this->requireAdmin()) {
            return;
        }
        
        // Get action from request
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_reports':
                $this->getReports();
                break;
                
            case 'get_report':
                $this->getReport();
                break;
                
            case 'update_status':
                $this->updateReportStatus();
                break;
                
            case 'get_counts':
                $this->getReportCounts();
                break;
                
            case 'take_action':
                $this->takeAction();
                break;
                
            default:
                $this->respondWithError('Invalid action', 400);
                break;
        }
    }
    
    /**
     * Get a list of reports
     */
    private function getReports() {
        // Get pagination and filter parameters
        $status = $_GET['status'] ?? Report::STATUS_PENDING;
        $limit = isset($_GET['limit']) ? min(intval($_GET['limit']), 50) : 20;
        $offset = isset($_GET['offset']) ? max(intval($_GET['offset']), 0) : 0;
        
        // Get reports
        $reports = $this->report->getReportsForReview($status, $limit, $offset);
        
        // Get total count for pagination
        $counts = $this->report->getReportCounts();
        $totalCount = $counts[$status] ?? 0;
        
        $this->respondWithSuccess([
            'reports' => $reports,
            'pagination' => [
                'total' => $totalCount,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $totalCount
            ],
            'counts' => $counts
        ]);
    }
    
    /**
     * Get a specific report by ID
     */
    private function getReport() {
        // Get report ID
        $report_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if ($report_id <= 0) {
            $this->respondWithError('Invalid report ID', 400);
            return;
        }
        
        // Get report
        $report = $this->report->getReportById($report_id);
        
        if (!$report) {
            $this->respondWithError('Report not found', 404);
            return;
        }
        
        $this->respondWithSuccess([
            'report' => $report
        ]);
    }
    
    /**
     * Update report status
     */
    private function updateReportStatus() {
        // Validate request method
        if (!$this->validateMethod('POST')) {
            return;
        }
        
        // Get input data
        $data = $this->getInputData();
        
        // Validate required parameters
        if (!$this->validateRequiredParams($data, ['report_id', 'status'])) {
            return;
        }
        
        // Extract data
        $report_id = intval($data['report_id']);
        $status = $data['status'];
        $admin_notes = $data['admin_notes'] ?? '';
        
        // Update status
        if ($this->report->updateReportStatus($report_id, $status, $admin_notes)) {
            $this->respondWithSuccess([
                'message' => 'Report status updated successfully'
            ]);
        } else {
            $this->respondWithError('Failed to update report status', 500);
        }
    }
    
report_id = isset($_POST['report_id']) ? intval($_POST['report_id']) : 0;
        $action = $_POST['action'] ?? '';
        $reason = $_POST['reason'] ?? '';
        
        // Validate parameters
        if ($report_id <= 0 || empty($action)) {
            $this->respondWithError('Missing required parameters', 400);
            return;
        }
        
        // Get report
        $report = $this->report->getReportById($report_id);
        if (!$report) {
            $this->respondWithError('Report not found', 404);
            return;
        }
        
        // Take action based on report type and action
        switch ($action) {
            case 'remove_content':
                $success = $this->removeContent($report['report_type'], $report['content_id'], $reason);
                break;
                
            case 'warn_user':
                $success = $this->warnUser($report['report_type'], $report['content_id'], $reason);
                break;
                
            case 'block_user':
                $success = $this->blockUser($report['report_type'], $report['content_id'], $reason);
                break;
                
            case 'no_action':
                $success = true; // No action needed, just update report status
                break;
                
            default:
                $this->respondWithError('Invalid action', 400);
                return;
        }
        
        if ($success) {
            // Update report status to resolved
            $admin_notes = "Action taken: $action. Reason: $reason";
            $this->report->updateReportStatus($report_id, Report::STATUS_RESOLVED, $admin_notes);
            
            $this->respondWithSuccess([
                'message' => 'Action taken successfully'
            ]);
        } else {
            $this->respondWithError('Failed to take action', 500);
        }
    }
    
    /**
     * Remove reported content
     * 
     * @param string $content_type Content type
     * @param int $content_id Content ID
     * @param string $reason Removal reason
     * @return bool Success status
     */
    private function removeContent($content_type, $content_id, $reason) {
        switch ($content_type) {
            case Report::TYPE_POST:
                return $this->post->removePost($content_id, $reason);
                
            case Report::TYPE_COMMENT:
                return $this->comment->removeComment($content_id, $reason);
                
            case Report::TYPE_MESSAGE:
                // This would require a Message model
                return true; // Simplified for this example
                
            case Report::TYPE_USER:
                // Can't remove a user, but can block them
                return $this->blockUser($content_type, $content_id, $reason);
                
            default:
                return false;
        }
    }
    
    /**
     * Warn a user about their content
     * 
     * @param string $content_type Content type
     * @param int $content_id Content ID
     * @param string $reason Warning reason
     * @return bool Success status
     */
    private function warnUser($content_type, $content_id, $reason) {
        $user_id = $this->getContentOwner($content_type, $content_id);
        
        if (!$user_id) {
            return false;
        }
        
        // Send a warning notification
        $message = "Your content has been reported and reviewed. Please ensure you follow our community guidelines. Reason: $reason";
        
        // In a real implementation, you'd use a Notification model
        // For now, we'll assume it's successful
        return true;
    }
    
    /**
     * Block a user (admin function)
     * 
     * @param string $content_type Content type 
     * @param int $content_id Content ID
     * @param string $reason Blocking reason
     * @return bool Success status
     */
    private function blockUser($content_type, $content_id, $reason) {
        $user_id = $this->getContentOwner($content_type, $content_id);
        
        if (!$user_id) {
            return false;
        }
        
        // Block the user (in a real implementation, you'd use a specific method)
        return $this->user->blockUser($user_id, $reason);
    }
    
    /**
     * Get user ID of content owner
     * 
     * @param string $content_type Content type
     * @param int $content_id Content ID
     * @return int|bool User ID or false if not found
     */
    private function getContentOwner($content_type, $content_id) {
        switch ($content_type) {
            case Report::TYPE_POST:
                return $this->post->getPostAuthorId($content_id);
                
            case Report::TYPE_COMMENT:
                return $this->comment->getCommentAuthorId($content_id);
                
            case Report::TYPE_USER:
                return $content_id; // The content is the user
                
            case Report::TYPE_MESSAGE:
                // This would require a Message model
                // For now, return false
                return false;
                
            default:
                return false;
        }
    }
}
}

// Handle the request if this file is accessed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $controller = new AdminReportController();
    $controller->processRequest();
}