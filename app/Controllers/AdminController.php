<?php
/**
 * AdminController
 * Handles administrative functions
 */
namespace App\Controllers;

use App\Models\User;
use App\Models\Post;
use App\Models\Report;
use App\Models\Block;
use App\Core\Controller;

class AdminController extends Controller {
    private $userModel;
    private $postModel;
    private $reportModel;
    private $blockModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->requireAdmin();
        
        $this->userModel = new User();
        $this->postModel = new Post();
        $this->reportModel = new Report();
        $this->blockModel = new Block();
    }

    /**
     * Show admin dashboard
     */
    public function index() {
        $stats = [
            'users' => $this->userModel->getCount(),
            'reports' => $this->reportModel->getStats(),
            'blocks' => $this->blockModel->getCount()
        ];
        
        $this->render('admin/index', [
            'stats' => $stats
        ]);
    }

    /**
     * Show user management page
     */
    public function users() {
        $users = $this->userModel->getAll();
        
        $this->render('admin/users', [
            'users' => $users
        ]);
    }

    /**
     * Delete user
     */
    public function deleteUser() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }
        
        $userId = $_POST['user_id'] ?? null;
        
        if (!$userId) {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }
        
        $success = $this->userModel->delete($userId);
        
        if ($success) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'User deleted successfully'
            ];
        } else {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Failed to delete user'
            ];
        }
        
        header('Location: /admin/users');
        exit;
    }

    /**
     * Show reports management page
     */
    public function reports() {
        $status = $_GET['status'] ?? null;
        $page = $_GET['page'] ?? 1;
        $limit = 20;
        $offset = ($page - 1) * $limit;
        
        $reports = $this->reportModel->getForAdmin($status, $limit, $offset);
        
        $this->render('admin/reports', [
            'reports' => $reports,
            'status' => $status,
            'page' => $page
        ]);
    }

    /**
     * Handle report action
     */
    public function handleReport() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }
        
        $reportId = $_POST['report_id'] ?? null;
        $action = $_POST['action'] ?? null;
        $notes = $_POST['notes'] ?? null;
        
        if (!$reportId || !$action) {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }
        
        // Get report
        $report = $this->reportModel->find($reportId);
        if (!$report) {
            header('HTTP/1.1 404 Not Found');
            exit;
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

            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Report status updated successfully'
            ];
        } catch (\Exception $e) {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Failed to process report'
            ];
        }
        
        header('Location: /admin/reports');
        exit;
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

    /**
     * Handle user role update
     */
    public function updateUserRole() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('HTTP/1.1 405 Method Not Allowed');
            exit;
        }
        
        $userId = $_POST['user_id'] ?? null;
        $role = $_POST['role'] ?? null;
        
        if (!$userId || !$role) {
            header('HTTP/1.1 400 Bad Request');
            exit;
        }
        
        $success = $this->userModel->updateRole($userId, $role);
        
        if ($success) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'User role updated successfully'
            ];
        } else {
            $_SESSION['flash'] = [
                'type' => 'error',
                'message' => 'Failed to update user role'
            ];
        }
        
        header('Location: /admin/users');
        exit;
    }
} 