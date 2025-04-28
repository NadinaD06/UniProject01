<?php
/**
 * Report Controller
 * Handles content reporting operations
 */

require_once '../models/Report.php';
require_once '../models/User.php';
require_once '../models/Post.php';
require_once '../models/Comment.php';
require_once '../controllers/BaseController.php';

class ReportController extends BaseController {
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
     * Process report requests
     */
    public function processRequest() {
        // Require authentication for all report actions
        if (!$this->requireAuth()) {
            return;
        }
        
        // Get action from request
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $this->createReport();
                break;
                
            case 'get_reasons':
                $this->getReportReasons();
                break;
                
            case 'get_status':
                $this->getReportStatus();
                break;
                
            default:
                $this->respondWithError('Invalid action', 400);
                break;
        }
    }
    
    /**
     * Create a new report
     */
    private function createReport() {
        // Validate request method
        if (!$this->validateMethod('POST')) {
            return;
        }
        
        // Get input data
        $data = $this->getInputData();
        
        // Validate required parameters
        if (!$this->validateRequiredParams($data, ['report_type', 'content_id', 'reason'])) {
            return;
        }
        
        // Extract data
        $report_type = $data['report_type'];
        $content_id = intval($data['content_id']);
        $reason = $data['reason'];
        $description = $data['description'] ?? '';
        
        // Validate content exists based on report type
        if (!$this->validateContentExists($report_type, $content_id)) {
            $this->respondWithError('Content not found', 404);
            return;
        }
        
        // Check if user has already reported this content
        if ($this->report->hasReported($this->current_user_id, $report_type, $content_id)) {
            $this->respondWithError('You have already reported this content', 400);
            return;
        }
        
        // Create the report
        $report_id = $this->report->createReport($this->current_user_id, $report_type, $content_id, $reason, $description);
        
        if ($report_id) {
            $this->respondWithSuccess([
                'message' => 'Report submitted successfully',
                'report_id' => $report_id
            ]);
        } else {
            $this->respondWithError('Failed to submit report', 500);
        }
    }
    
    /**
     * Get available report reasons
     */
    private function getReportReasons() {
        $reasons = $this->report->getReportReasons();
        
        $this->respondWithSuccess([
            'reasons' => $reasons
        ]);
    }
    
    /**
     * Get report status for a piece of content
     */
    private function getReportStatus() {
        // Get parameters
        $report_type = $_GET['report_type'] ?? '';
        $content_id = isset($_GET['content_id']) ? intval($_GET['content_id']) : 0;
        
        // Validate parameters
        if (empty($report_type) || $content_id <= 0) {
            $this->respondWithError('Missing required parameters', 400);
            return;
        }
        
        // Check if user has reported this content
        $hasReported = $this->report->hasReported($this->current_user_id, $report_type, $content_id);
        
        $this->respondWithSuccess([
            'has_reported' => $hasReported
        ]);
    }
    
    /**
     * Validate content exists based on report type
     * 
     * @param string $report_type Report type
     * @param int $content_id Content ID
     * @return bool True if content exists
     */
    private function validateContentExists($report_type, $content_id) {
        switch ($report_type) {
            case Report::TYPE_POST:
                return $this->post->postExists($content_id);
                
            case Report::TYPE_COMMENT:
                return $this->comment->commentExists($content_id);
                
            case Report::TYPE_USER:
                return $this->user->userExists($content_id);
                
            case Report::TYPE_MESSAGE:
                // For messages, we should check if the user is part of the conversation
                // This would require a Message model, which we'll assume exists
                return true; // Simplified for this example
                
            default:
                return false;
        }
    }
}

// Handle the request if this file is accessed directly
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $controller = new ReportController();
    $controller->processRequest();
}