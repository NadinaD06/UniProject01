<?php
/**
 * Report Model
 * Handles user reports of content
 */
class Report {
    private $conn;
    
    // Report types
    const TYPE_POST = 'post';
    const TYPE_COMMENT = 'comment';
    const TYPE_USER = 'user';
    const TYPE_MESSAGE = 'message';
    
    // Report reasons
    const REASON_INAPPROPRIATE = 'inappropriate';
    const REASON_SPAM = 'spam';
    const REASON_HARASSMENT = 'harassment';
    const REASON_COPYRIGHT = 'copyright';
    const REASON_AI_DISCLOSURE = 'ai_disclosure';
    const REASON_IMPERSONATION = 'impersonation';
    const REASON_OTHER = 'other';
    
    // Report statuses
    const STATUS_PENDING = 'pending';
    const STATUS_REVIEWED = 'reviewed';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_REJECTED = 'rejected';
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     */
    public function __construct($db) {
        $this->conn = $db;
    }
    
    /**
     * Create a new report
     * 
     * @param int $reporter_id User ID who is reporting
     * @param string $report_type Type of content being reported
     * @param int $content_id ID of reported content
     * @param string $reason Reason for reporting
     * @param string $description Additional details
     * @return int|bool Report ID if successful, false otherwise
     */
    public function createReport($reporter_id, $report_type, $content_id, $reason, $description = '') {
        // Check if report already exists
        if ($this->hasReported($reporter_id, $report_type, $content_id)) {
            return false; // User already reported this content
        }
        
        // Validate report type
        if (!$this->isValidReportType($report_type)) {
            return false;
        }
        
        // Validate reason
        if (!$this->isValidReportReason($reason)) {
            return false;
        }
        
        // Insert report
        $stmt = $this->conn->prepare("
            INSERT INTO reports (
                reporter_id, report_type, content_id, reason, 
                description, status, created_at, updated_at
            ) VALUES (
                :reporter_id, :report_type, :content_id, :reason, 
                :description, :status, NOW(), NOW()
            )
        ");
        
        $status = self::STATUS_PENDING;
        
        $stmt->bindParam(':reporter_id', $reporter_id);
        $stmt->bindParam(':report_type', $report_type);
        $stmt->bindParam(':content_id', $content_id);
        $stmt->bindParam(':reason', $reason);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':status', $status);
        
        if ($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Check if user has already reported this content
     * 
     * @param int $reporter_id User ID who is reporting
     * @param string $report_type Type of content being reported
     * @param int $content_id ID of reported content
     * @return bool True if user already reported this content
     */
    public function hasReported($reporter_id, $report_type, $content_id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM reports 
            WHERE reporter_id = :reporter_id 
              AND report_type = :report_type 
              AND content_id = :content_id
        ");
        
        $stmt->bindParam(':reporter_id', $reporter_id);
        $stmt->bindParam(':report_type', $report_type);
        $stmt->bindParam(':content_id', $content_id);
        $stmt->execute();
        
        return $stmt->fetchColumn() > 0;
    }
    
    /**
     * Get a report by ID
     * 
     * @param int $report_id Report ID
     * @return array|bool Report data or false if not found
     */
    public function getReportById($report_id) {
        $stmt = $this->conn->prepare("
            SELECT * 
            FROM reports
            WHERE id = :report_id
        ");
        
        $stmt->bindParam(':report_id', $report_id);
        $stmt->execute();
        
        $report = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($report) {
            return $this->formatReport($report);
        }
        
        return false;
    }
    
    /**
     * Get reports for admin review
     * 
     * @param string $status Filter by status
     * @param int $limit Result limit
     * @param int $offset Result offset
     * @return array List of reports
     */
    public function getReportsForReview($status = self::STATUS_PENDING, $limit = 20, $offset = 0) {
        $query = "
            SELECT 
                r.*,
                u.username as reporter_username
            FROM reports r
            JOIN users u ON r.reporter_id = u.id
        ";
        
        $params = [];
        
        if ($status) {
            $query .= " WHERE r.status = :status";
            $params[':status'] = $status;
        }
        
        $query .= " ORDER BY r.created_at DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format reports
        $formattedReports = [];
        foreach ($reports as $report) {
            $formattedReports[] = $this->formatReport($report);
        }
        
        return $formattedReports;
    }
    
    /**
     * Update the status of a report
     * 
     * @param int $report_id Report ID
     * @param string $status New status
     * @param string $admin_notes Notes from admin (optional)
     * @return bool Success status
     */
    public function updateReportStatus($report_id, $status, $admin_notes = '') {
        // Validate status
        if (!in_array($status, [self::STATUS_REVIEWED, self::STATUS_RESOLVED, self::STATUS_REJECTED])) {
            return false;
        }
        
        $stmt = $this->conn->prepare("
            UPDATE reports
            SET 
                status = :status,
                admin_notes = :admin_notes,
                updated_at = NOW()
            WHERE id = :report_id
        ");
        
        $stmt->bindParam(':report_id', $report_id);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':admin_notes', $admin_notes);
        
        return $stmt->execute();
    }
    
    /**
     * Get count of reports by status
     * 
     * @return array Counts by status
     */
    public function getReportCounts() {
        $stmt = $this->conn->prepare("
            SELECT status, COUNT(*) as count
            FROM reports
            GROUP BY status
        ");
        
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $counts = [
            self::STATUS_PENDING => 0,
            self::STATUS_REVIEWED => 0,
            self::STATUS_RESOLVED => 0,
            self::STATUS_REJECTED => 0
        ];
        
        foreach ($results as $row) {
            $counts[$row['status']] = (int)$row['count'];
        }
        
        return $counts;
    }
    
    /**
     * Get reports against specific content (used for determining if content should be reviewed)
     * 
     * @param string $content_type Content type (post, comment, etc.)
     * @param int $content_id Content ID
     * @return array Reports for this content
     */
    public function getReportsForContent($content_type, $content_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                r.*,
                u.username as reporter_username
            FROM reports r
            JOIN users u ON r.reporter_id = u.id
            WHERE r.report_type = :report_type
              AND r.content_id = :content_id
            ORDER BY r.created_at DESC
        ");
        
        $stmt->bindParam(':report_type', $content_type);
        $stmt->bindParam(':content_id', $content_id);
        $stmt->execute();
        
        $reports = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format reports
        $formattedReports = [];
        foreach ($reports as $report) {
            $formattedReports[] = $this->formatReport($report);
        }
        
        return $formattedReports;
    }
    
    /**
     * Get count of pending reports for a moderator dashboard
     * 
     * @return int Count of pending reports
     */
    public function getPendingReportCount() {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) 
            FROM reports 
            WHERE status = :status
        ");
        
        $status = self::STATUS_PENDING;
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        return $stmt->fetchColumn();
    }
    
    /**
     * Validate report type
     * 
     * @param string $type Report type
     * @return bool True if valid
     */
    private function isValidReportType($type) {
        return in_array($type, [
            self::TYPE_POST,
            self::TYPE_COMMENT,
            self::TYPE_USER,
            self::TYPE_MESSAGE
        ]);
    }
    
    /**
     * Validate report reason
     * 
     * @param string $reason Report reason
     * @return bool True if valid
     */
    private function isValidReportReason($reason) {
        return in_array($reason, [
            self::REASON_INAPPROPRIATE,
            self::REASON_SPAM,
            self::REASON_HARASSMENT,
            self::REASON_COPYRIGHT,
            self::REASON_AI_DISCLOSURE,
            self::REASON_IMPERSONATION,
            self::REASON_OTHER
        ]);
    }
    
    /**
     * Format a report for API response
     * 
     * @param array $report Raw report data
     * @return array Formatted report
     */
    private function formatReport($report) {
        // Get additional details based on report type
        $contentDetails = $this->getContentDetails($report['report_type'], $report['content_id']);
        
        return array_merge($report, [
            'id' => (int)$report['id'],
            'reporter_id' => (int)$report['reporter_id'],
            'content_id' => (int)$report['content_id'],
            'created_at_formatted' => date('M j, Y g:i A', strtotime($report['created_at'])),
            'updated_at_formatted' => date('M j, Y g:i A', strtotime($report['updated_at'])),
            'content_details' => $contentDetails
        ]);
    }
    
    /**
     * Get details about the reported content
     * 
     * @param string $content_type Content type
     * @param int $content_id Content ID
     * @return array|null Content details if available
     */
    private function getContentDetails($content_type, $content_id) {
        switch ($content_type) {
            case self::TYPE_POST:
                return $this->getPostDetails($content_id);
                
            case self::TYPE_COMMENT:
                return $this->getCommentDetails($content_id);
                
            case self::TYPE_USER:
                return $this->getUserDetails($content_id);
                
            case self::TYPE_MESSAGE:
                return $this->getMessageDetails($content_id);
                
            default:
                return null;
        }
    }
    
    /**
     * Get post details
     * 
     * @param int $post_id Post ID
     * @return array|null Post details
     */
    private function getPostDetails($post_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.id, p.user_id, p.title, LEFT(p.description, 100) as preview,
                u.username as author_username
            FROM posts p
            JOIN users u ON p.user_id = u.id
            WHERE p.id = :post_id
        ");
        
        $stmt->bindParam(':post_id', $post_id);
        $stmt->execute();
        
        $post = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($post) {
            // If description is truncated, add ellipsis
            if (strlen($post['preview']) === 100) {
                $post['preview'] .= '...';
            }
            
            return [
                'id' => $post['id'],
                'title' => $post['title'],
                'preview' => $post['preview'],
                'author_id' => $post['user_id'],
                'author_username' => $post['author_username'],
                'url' => '/post.php?id=' . $post['id']
            ];
        }
        
        return null;
    }
    
    /**
     * Get comment details
     * 
     * @param int $comment_id Comment ID
     * @return array|null Comment details
     */
    private function getCommentDetails($comment_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                c.id, c.post_id, c.user_id, LEFT(c.content, 100) as preview,
                u.username as author_username
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.id = :comment_id
        ");
        
        $stmt->bindParam(':comment_id', $comment_id);
        $stmt->execute();
        
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($comment) {
            // If content is truncated, add ellipsis
            if (strlen($comment['preview']) === 100) {
                $comment['preview'] .= '...';
            }
            
            return [
                'id' => $comment['id'],
                'post_id' => $comment['post_id'],
                'preview' => $comment['preview'],
                'author_id' => $comment['user_id'],
                'author_username' => $comment['author_username'],
                'url' => '/post.php?id=' . $comment['post_id'] . '#comment-' . $comment['id']
            ];
        }
        
        return null;
    }
    
    /**
     * Get user details
     * 
     * @param int $user_id User ID
     * @return array|null User details
     */
    private function getUserDetails($user_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                id, username, email, profile_picture, bio, is_verified,
                created_at
            FROM users
            WHERE id = :user_id
        ");
        
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            return [
                'id' => $user['id'],
                'username' => $user['username'],
                'bio' => substr($user['bio'] ?? '', 0, 100) . (strlen($user['bio'] ?? '') > 100 ? '...' : ''),
                'is_verified' => (bool)$user['is_verified'],
                'joined' => date('M j, Y', strtotime($user['created_at'])),
                'url' => '/profile.php?id=' . $user['id']
            ];
        }
        
        return null;
    }
    
    /**
     * Get message details
     * 
     * @param int $message_id Message ID
     * @return array|null Message details
     */
    private function getMessageDetails($message_id) {
        $stmt = $this->conn->prepare("
            SELECT 
                m.id, m.sender_id, m.receiver_id, LEFT(m.content, 100) as preview,
                s.username as sender_username,
                r.username as receiver_username
            FROM messages m
            JOIN users s ON m.sender_id = s.id
            JOIN users r ON m.receiver_id = r.id
            WHERE m.id = :message_id
        ");
        
        $stmt->bindParam(':message_id', $message_id);
        $stmt->execute();
        
        $message = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($message) {
            // If content is truncated, add ellipsis
            if (strlen($message['preview']) === 100) {
                $message['preview'] .= '...';
            }
            
            return [
                'id' => $message['id'],
                'preview' => $message['preview'],
                'sender_id' => $message['sender_id'],
                'sender_username' => $message['sender_username'],
                'receiver_id' => $message['receiver_id'],
                'receiver_username' => $message['receiver_username']
            ];
        }
        
        return null;
    }
    
    /**
     * Get all valid report reasons as an array
     * 
     * @return array List of valid reasons with labels
     */
    public function getReportReasons() {
        return [
            self::REASON_INAPPROPRIATE => 'Inappropriate Content',
            self::REASON_SPAM => 'Spam or Misleading',
            self::REASON_HARASSMENT => 'Harassment or Bullying',
            self::REASON_COPYRIGHT => 'Copyright Infringement',
            self::REASON_AI_DISCLOSURE => 'AI Usage Not Disclosed',
            self::REASON_IMPERSONATION => 'Impersonation',
            self::REASON_OTHER => 'Other'
        ];
    }
    
    /**
     * Get all valid report statuses as an array
     * 
     * @return array List of valid statuses with labels
     */
    public function getReportStatuses() {
        return [
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_REVIEWED => 'Under Review',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_REJECTED => 'Rejected'
        ];
    }
    
    /**
     * Get the label for a specific report reason
     * 
     * @param string $reason Report reason code
     * @return string Human-readable label
     */
    public function getReasonLabel($reason) {
        $reasons = $this->getReportReasons();
        return $reasons[$reason] ?? 'Unknown Reason';
    }
    
    /**
     * Get the label for a specific report status
     * 
     * @param string $status Report status code
     * @return string Human-readable label
     */
    public function getStatusLabel($status) {
        $statuses = $this->getReportStatuses();
        return $statuses[$status] ?? 'Unknown Status';
    } }