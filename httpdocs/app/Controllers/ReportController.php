<?php
/**
 * ReportController
 * Handles report-related actions
 */
namespace App\Controllers;

use App\Models\Report;
use App\Models\User;
use App\Models\Notification;

class ReportController extends Controller {
    private $reportModel;
    private $userModel;
    private $notificationModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->reportModel = new Report();
        $this->userModel = new User();
        $this->notificationModel = new Notification();
    }

    /**
     * Create a new report
     */
    public function create() {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $reporterId = $_SESSION['user_id'];
        $reportedUserId = $_POST['user_id'] ?? null;
        $reason = $_POST['reason'] ?? '';

        if (!$reportedUserId || empty($reason)) {
            $this->jsonResponse(['error' => 'User ID and reason are required'], 400);
            return;
        }

        // Check if user exists
        if (!$this->userModel->getUser($reportedUserId)) {
            $this->jsonResponse(['error' => 'User not found'], 404);
            return;
        }

        // Create report
        $reportId = $this->reportModel->createReport($reporterId, $reportedUserId, $reason);
        if ($reportId) {
            // Notify admins
            $admins = $this->userModel->getAdmins();
            foreach ($admins as $admin) {
                $this->notificationModel->createNotification(
                    $admin['id'],
                    'report',
                    'New user report received',
                    $reportId
                );
            }

            $this->jsonResponse(['message' => 'Report submitted successfully']);
        } else {
            $this->jsonResponse(['error' => 'Failed to submit report'], 500);
        }
    }

    /**
     * Get all reports (admin only)
     */
    public function index() {
        if (!$this->isAdmin()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $page = (int) $_GET['page'] ?? 1;
        $reports = $this->reportModel->getAllReports($page);

        $this->jsonResponse($reports);
    }

    /**
     * Update report status (admin only)
     */
    public function update() {
        if (!$this->isAdmin()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $reportId = $_POST['report_id'] ?? null;
        $status = $_POST['status'] ?? null;
        $adminAction = $_POST['admin_action'] ?? null;

        if (!$reportId || !$status) {
            $this->jsonResponse(['error' => 'Report ID and status are required'], 400);
            return;
        }

        if ($this->reportModel->updateReport($reportId, $status, $adminAction)) {
            // If action is to block user
            if ($adminAction === 'block_user') {
                $report = $this->reportModel->getReport($reportId);
                if ($report) {
                    $this->userModel->blockUser($report['reported_user_id'], 30); // Block for 30 days
                }
            }

            $this->jsonResponse(['message' => 'Report updated successfully']);
        } else {
            $this->jsonResponse(['error' => 'Failed to update report'], 500);
        }
    }

    /**
     * Get report statistics (admin only)
     */
    public function stats() {
        if (!$this->isAdmin()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $period = $_GET['period'] ?? 'month';
        $stats = $this->reportModel->getReportStats($period);

        $this->jsonResponse($stats);
    }

    /**
     * Get reports for a specific user
     */
    public function userReports() {
        if (!$this->isLoggedIn()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $userId = $_GET['user_id'] ?? $_SESSION['user_id'];
        
        // Only allow users to view their own reports unless they're an admin
        if ($userId !== $_SESSION['user_id'] && !$this->isAdmin()) {
            $this->jsonResponse(['error' => 'Unauthorized'], 401);
            return;
        }

        $reports = $this->reportModel->getUserReports($userId);
        $this->jsonResponse($reports);
    }
} 