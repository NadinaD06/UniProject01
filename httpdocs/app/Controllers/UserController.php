<?php
/**
 * UserController
 * Handles user-related actions
 */
namespace App\Controllers;

use App\Models\User;
use App\Models\Block;
use App\Models\Report;

class UserController extends Controller {
    private $userModel;
    private $blockModel;
    private $reportModel;

    /**
     * Constructor
     */
    public function __construct() {
        parent::__construct();
        $this->userModel = new User();
        $this->blockModel = new Block();
        $this->reportModel = new Report();
    }

    /**
     * Show user profile
     */
    public function profile() {
        $this->requireLogin();

        $userId = (int) $this->get('id');
        if (!$userId) {
            $userId = $this->getCurrentUserId();
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            $this->setFlash('error', 'User not found.');
            $this->redirect('/index.php');
        }

        // Check if user is blocked
        $isBlocked = false;
        $currentUserId = $this->getCurrentUserId();
        if ($currentUserId !== $userId) {
            $isBlocked = $this->blockModel->isBlocked($currentUserId, $userId);
        }

        // Get user's posts
        $page = (int) $this->get('page', 1);
        $posts = $this->userModel->getPosts($userId, $page);

        $this->render('users/profile', [
            'user' => $user,
            'posts' => $posts,
            'isBlocked' => $isBlocked
        ]);
    }

    /**
     * Block a user
     */
    public function block() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        $userId = (int) $this->post('user_id');
        if (!$userId) {
            $this->json(['error' => 'Invalid user ID'], 400);
        }

        // Can't block self
        if ($userId === $this->getCurrentUserId()) {
            $this->json(['error' => 'Cannot block yourself'], 400);
        }

        try {
            $success = $this->blockModel->blockUser(
                $this->getCurrentUserId(),
                $userId
            );

            if ($success) {
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'User is already blocked'], 400);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to block user'], 500);
        }
    }

    /**
     * Unblock a user
     */
    public function unblock() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        $userId = (int) $this->post('user_id');
        if (!$userId) {
            $this->json(['error' => 'Invalid user ID'], 400);
        }

        try {
            $success = $this->blockModel->unblockUser(
                $this->getCurrentUserId(),
                $userId
            );

            $this->json(['success' => $success]);
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to unblock user'], 500);
        }
    }

    /**
     * Report a user
     */
    public function report() {
        $this->requireLogin();

        if (!$this->post()) {
            $this->json(['error' => 'Invalid request method'], 405);
        }

        $userId = (int) $this->post('user_id');
        $reason = $this->post('reason');
        $details = $this->post('details');

        if (!$userId || !$reason) {
            $this->json(['error' => 'Missing required fields'], 400);
        }

        // Can't report self
        if ($userId === $this->getCurrentUserId()) {
            $this->json(['error' => 'Cannot report yourself'], 400);
        }

        try {
            $reportId = $this->reportModel->createReport(
                $this->getCurrentUserId(),
                $userId,
                $reason,
                $details
            );

            if ($reportId) {
                $this->json(['success' => true]);
            } else {
                $this->json(['error' => 'Failed to create report'], 500);
            }
        } catch (\Exception $e) {
            $this->json(['error' => 'Failed to report user'], 500);
        }
    }

    /**
     * Show blocked users list
     */
    public function blockedUsers() {
        $this->requireLogin();

        $blockedUsers = $this->blockModel->getBlockedUsers($this->getCurrentUserId());

        $this->render('users/blocked', [
            'blockedUsers' => $blockedUsers
        ]);
    }

    /**
     * Show user's reports
     */
    public function reports() {
        $this->requireLogin();

        $madeReports = $this->reportModel->getReportsByUser($this->getCurrentUserId());
        $receivedReports = $this->reportModel->getReportsAgainstUser($this->getCurrentUserId());

        $this->render('users/reports', [
            'madeReports' => $madeReports,
            'receivedReports' => $receivedReports
        ]);
    }
} 