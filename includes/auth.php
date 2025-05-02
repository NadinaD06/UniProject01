<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Function to require admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current username
function getCurrentUsername() {
    return $_SESSION['username'] ?? null;
}

// Function to check if user is blocked
function isUserBlocked($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT is_blocked, block_until 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if ($user && $user['is_blocked']) {
            if ($user['block_until'] && strtotime($user['block_until']) > time()) {
                return true;
            } else {
                // Unblock user if block period has expired
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET is_blocked = FALSE, block_until = NULL 
                    WHERE id = ?
                ");
                $stmt->execute([$userId]);
                return false;
            }
        }
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to check if user is blocked by another user
function isBlockedByUser($blockerId, $blockedId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT id 
            FROM user_blocks 
            WHERE blocker_id = ? AND blocked_id = ?
        ");
        $stmt->execute([$blockerId, $blockedId]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Function to check if user can interact with another user
function canInteractWithUser($userId1, $userId2) {
    return !isBlockedByUser($userId1, $userId2) && !isBlockedByUser($userId2, $userId1);
}

// Function to logout
function logout() {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Function to create notification
function createNotification($userId, $actorId, $type, $content, $referenceId = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, actor_id, type, content, reference_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        
        return $stmt->execute([$userId, $actorId, $type, $content, $referenceId]);
    } catch (PDOException $e) {
        return false;
    }
}

// Function to get unread notification count
function getUnreadNotificationCount($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM notifications
            WHERE user_id = ? AND is_read = FALSE
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'];
    } catch (PDOException $e) {
        return 0;
    }
}

// Function to mark notifications as read
function markNotificationsAsRead($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = TRUE 
            WHERE user_id = ? AND is_read = FALSE
        ");
        return $stmt->execute([$userId]);
    } catch (PDOException $e) {
        return false;
    }
}
?> 