<?php
namespace App\Models;

class Notification {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function create($data) {
        $stmt = $this->pdo->prepare("
            INSERT INTO notifications (user_id, type, related_id, content)
            VALUES (?, ?, ?, ?)
        ");
        
        return $stmt->execute([
            $data['user_id'],
            $data['type'],
            $data['related_id'] ?? null,
            $data['content']
        ]);
    }
    
    public function getForUser($userId, $limit = 20, $offset = 0) {
        $stmt = $this->pdo->prepare("
            SELECT n.*, 
                   CASE 
                       WHEN n.type = 'like' THEN (SELECT username FROM users WHERE id = (SELECT user_id FROM likes WHERE id = n.related_id))
                       WHEN n.type = 'comment' THEN (SELECT username FROM users WHERE id = (SELECT user_id FROM comments WHERE id = n.related_id))
                       WHEN n.type = 'follow' THEN (SELECT username FROM users WHERE id = (SELECT follower_id FROM follows WHERE id = n.related_id))
                       ELSE NULL
                   END as actor_username
            FROM notifications n
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?
        ");
        
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function markAsRead($notificationId) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications 
            SET read_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        
        return $stmt->execute([$notificationId]);
    }
    
    public function markAllAsRead($userId) {
        $stmt = $this->pdo->prepare("
            UPDATE notifications 
            SET read_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND read_at IS NULL
        ");
        
        return $stmt->execute([$userId]);
    }
    
    public function getUnreadCount($userId) {
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as count
            FROM notifications
            WHERE user_id = ? AND read_at IS NULL
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetch()['count'];
    }
    
    public function createLikeNotification($likeId, $postId, $userId) {
        $post = $this->pdo->prepare("SELECT user_id FROM posts WHERE id = ?")->execute([$postId])->fetch();
        if ($post && $post['user_id'] != $userId) {
            return $this->create([
                'user_id' => $post['user_id'],
                'type' => 'like',
                'related_id' => $likeId,
                'content' => 'liked your post'
            ]);
        }
        return false;
    }
    
    public function createCommentNotification($commentId, $postId, $userId) {
        $post = $this->pdo->prepare("SELECT user_id FROM posts WHERE id = ?")->execute([$postId])->fetch();
        if ($post && $post['user_id'] != $userId) {
            return $this->create([
                'user_id' => $post['user_id'],
                'type' => 'comment',
                'related_id' => $commentId,
                'content' => 'commented on your post'
            ]);
        }
        return false;
    }
    
    public function createFollowNotification($followId, $followerId, $followedId) {
        return $this->create([
            'user_id' => $followedId,
            'type' => 'follow',
            'related_id' => $followId,
            'content' => 'started following you'
        ]);
    }
} 