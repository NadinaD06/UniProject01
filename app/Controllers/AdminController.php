<?php
/**
 * AdminController
 * Handles administrative functions
 */
namespace App\Controllers;

use App\Models\User;
use App\Models\Post;
use App\Models\Report;

class AdminController extends Controller {
    private $userModel;
    private $postModel;
    private $reportModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->requireAdmin();
        
        $this->userModel = new User();
        $this->postModel = new Post();
        $this->reportModel = new Report();
    }

    /**
     * Show admin dashboard
     */
    public function index() {
        // Get statistics
        $userCount = $this->userModel->count();
        $postCount = $this->postModel->count();
        $pendingReports = $this->reportModel->getPendingReports(1, 5);
        $postStats = $this->postModel->getStats('week');

        $this->render('admin/dashboard', [
            'userCount' => $userCount,
            'postCount' => $postCount,
            'pendingReports' => $pendingReports,
            'postStats' => $postStats
        ]);
    }

    /**
     * Show user management page
     */
    public function users() {
        $page = (int) $this->get('page', 1);
        $perPage = 20;

        $users = $this->userModel->paginate($page, $perPage);

        $this->render('admin/users', [
            'users' => $users
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser() {
        if (!$this->post()) {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        $userId = (int) $this->post('user_id');
        if (!$userId) {
            $this->json(['error' => 'Invalid user ID'], 400);
        }

        // Don't allow deleting self
        if ($userId === $this->getCurrentUserId()) {
            $this->json(['error' => 'Cannot delete own account'], 403);
        }

        try {
            $this->userModel->delete($userId);
            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to delete user'], 500);
        }
    }

    /**
     * Show reports management page
     */
    public function reports() {
        $status = $this->get('status', Report::STATUS_PENDING);
        $page = (int) $this->get('page', 1);
        $perPage = 20;

        $reports = $this->reportModel->getReportsByStatus($status, $page, $perPage);

        $this->render('admin/reports', [
            'reports' => $reports,
            'currentStatus' => $status
        ]);
    }

    /**
     * Handle report action
     */
    public function handleReport() {
        if (!$this->post()) {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        $reportId = (int) $this->post('report_id');
        $action = $this->post('action');
        $notes = $this->post('notes');

        if (!$reportId || !$action) {
            $this->json(['error' => 'Missing required fields'], 400);
        }

        // Get report
        $report = $this->reportModel->find($reportId);
        if (!$report) {
            $this->json(['error' => 'Report not found'], 404);
        }

        try {
            // Update report status
            $this->reportModel->updateReport(
                $reportId,
                Report::STATUS_RESOLVED,
                $action,
                $notes
            );

            // Take action based on admin decision
            switch ($action) {
                case Report::ACTION_BLOCK:
                    // Block user for 30 days
                    $this->userModel->blockUser(
                        $report['reported_id'],
                        30 * 24 * 60 * 60 // 30 days in seconds
                    );
                    break;

                case Report::ACTION_DELETE:
                    // Delete user account
                    $this->userModel->delete($report['reported_id']);
                    break;

                case Report::ACTION_REQUEST_ID:
                    // Send notification to user requesting ID verification
                    // This would be implemented in a real system
                    break;
            }

            $this->json(['success' => true]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to process report'], 500);
        }
    }

    /**
     * Show post statistics
     */
    public function postStats() {
        $period = $this->get('period', 'week');
        if (!in_array($period, ['week', 'month', 'year'])) {
            $period = 'week';
        }

        $stats = $this->postModel->getStats($period);

        if ($this->isAjaxRequest()) {
            $this->json($stats);
        }

        $this->render('admin/post-stats', [
            'stats' => $stats,
            'period' => $period
        ]);
    }

    /**
     * Show report statistics
     */
    public function reportStats() {
        $period = $this->get('period', 'week');
        if (!in_array($period, ['week', 'month', 'year'])) {
            $period = 'week';
        }

        $stats = $this->reportModel->getStats($period);

        if ($this->isAjaxRequest()) {
            $this->json($stats);
        }

        $this->render('admin/report-stats', [
            'stats' => $stats,
            'period' => $period
        ]);
    }
} 