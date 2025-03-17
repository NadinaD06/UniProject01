<?php
// Include database connection
require_once '../../config/database.php';
require_once '../../utils/auth.php';

// Check if the user is logged in
$user_id = Auth::getCurrentUserId();
$logged_in = !empty($user_id);

// Initialize response array
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

// Get action from request
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Process different actions
switch ($action) {
    case 'get_tags':
        getTrendingTags();
        break;
    
    case 'get_artworks':
        getArtworks();
        break;
    
    case 'get_artwork_detail':
        getArtworkDetail();
        break;
    
    case 'get_artist_preview':
        getArtistPreview();
        break;
    
    case 'like_artwork':
        likeArtwork();
        break;
    
    case 'save_artwork':
        saveArtwork();
        break;
    
    case 'follow_artist':
        followArtist();
        break;
    
    case 'add_comment':
        addComment();
        break;
    
    default:
        $response['message'] = 'Invalid action';
        break;
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;

// Function to get trending tags
function getTrendingTags() {
    global $conn, $response;
    
    try {
        // Get trending tags with count
        $sql = "SELECT tag_name as name, COUNT(*) as count 
                FROM artwork_tags 
                GROUP BY tag_name 
                ORDER BY count DESC, tag_name 
                LIMIT 20";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        
        $tags = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tags[] = [
                'name' => $row['name'],
                'count' => $row['count']
            ];
        }
        
        $response['success'] = true;
        $response['data'] = $tags;
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

// Function to get artworks with filters
function getArtworks() {
    global $conn, $response, $user_id, $logged_in;
    
    try {
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $limit = 12; // Items per page
        $offset = ($page - 1) * $limit;
        
        $filter = isset($_GET['filter']) ? $_GET['filter'] : 'trending';
        $category = isset($_GET['category']) ? $_GET['category'] : '';
        $tag = isset($_GET['tag']) ? $_GET['tag'] : '';
        $search = isset($_GET['q']) ? $_GET['q'] : '';
        
        // Build query based on filters
        $sql = "SELECT a.*, u.username as artist_username, u.id as artist_id
                FROM artworks a
                JOIN users u ON a.user_id = u.id
                WHERE a.status = 'published'";
        
        $params = [];
        
        // Apply category filter
        if (!empty($category)) {
            $sql .= " AND a.category = :category";
            $params[':category'] = $category;
        }
        
        // Apply tag filter if specified
        if (!empty($tag)) {
            $sql .= " AND EXISTS (
                SELECT 1 FROM artwork_tags at 
                WHERE at.artwork_id = a.id AND at.tag_name = :tag
            )";
            $params[':tag'] = $tag;
        }
        
        // Apply search query if specified
        if (!empty($search)) {
            $sql .= " AND (a.title LIKE :search OR a.description LIKE :search)";
            $params[':search'] = "%$search%";
        }
        
        // Apply ordering based on filter
        switch ($filter) {
            case 'latest':
                $sql .= " ORDER BY a.created_at DESC";
                break;
            case 'popular':
                $sql .= " ORDER BY (a.likes_count + a.views_count) DESC, a.created_at DESC";
                break;
            case 'trending':
                // Trending can be a weighted formula based on recent popularity
                $sql .= " ORDER BY (a.likes_count * 2 + a.views_count + (a.comments_count * 3)) DESC, a.created_at DESC";
                break;
            default:
                $sql .= " ORDER BY a.created_at DESC";
        }
        
        // Apply pagination
        $sql .= " LIMIT :limit OFFSET :offset";
        $params[':limit'] = $limit;
        $params[':offset'] = $offset;
        
        // Execute query
        $stmt = $conn->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        $artworks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Check if user has liked this artwork
            $user_liked = false;
            if ($logged_in) {
                $like_stmt = $conn->prepare("SELECT 1 FROM artwork_likes WHERE artwork_id = :artwork_id AND user_id = :user_id");
                $like_stmt->bindValue(':artwork_id', $row['id']);
                $like_stmt->bindValue(':user_id', $user_id);
                $like_stmt->execute();
                $user_liked = $like_stmt->fetch() !== false;
            }
            
            // Get AI tools used
            $ai_tools = '';
            if ($row['used_ai']) {
                $ai_stmt = $conn->prepare("SELECT tool_name FROM artwork_ai_tools WHERE artwork_id = :artwork_id");
                $ai_stmt->bindValue(':artwork_id', $row['id']);
                $ai_stmt->execute();
                $tools = $ai_stmt->fetchAll(PDO::FETCH_COLUMN);
                $ai_tools = implode(', ', $tools);
            }
            
            $artworks[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'image_url' => $row['image_url'],
                'likes_count' => $row['likes_count'],
                'views_count' => $row['views_count'],
                'comments_count' => $row['comments_count'],
                'used_ai' => (bool)$row['used_ai'],
                'ai_tools' => $ai_tools,
                'user_liked' => $user_liked,
                'artist' => [
                    'id' => $row['artist_id'],
                    'username' => $row['artist_username']
                ]
            ];
        }
        
        // Check if there are more artworks
        $count_sql = "SELECT COUNT(*) FROM artworks a JOIN users u ON a.user_id = u.id WHERE a.status = 'published'";
        
        // Apply same filters to count query
        $count_params = [];
        if (!empty($category)) {
            $count_sql .= " AND a.category = :category";
            $count_params[':category'] = $category;
        }
        if (!empty($tag)) {
            $count_sql .= " AND EXISTS (
                SELECT 1 FROM artwork_tags at 
                WHERE at.artwork_id = a.id AND at.tag_name = :tag
            )";
            $count_params[':tag'] = $tag;
        }
        if (!empty($search)) {
            $count_sql .= " AND (a.title LIKE :search OR a.description LIKE :search)";
            $count_params[':search'] = "%$search%";
        }
        
        $count_stmt = $conn->prepare($count_sql);
        foreach ($count_params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }
        $count_stmt->execute();
        $total_count = $count_stmt->fetchColumn();
        
        $has_more = ($offset + $limit) < $total_count;
        
        $response['success'] = true;
        $response['data'] = [
            'artworks' => $artworks,
            'has_more' => $has_more,
            'total_count' => $total_count
        ];
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

// Function to get artwork detail
function getArtworkDetail() {
    global $conn, $response, $user_id, $logged_in;
    
    try {
        $artwork_id = isset($_GET['artwork_id']) ? intval($_GET['artwork_id']) : 0;
        
        if (!$artwork_id) {
            $response['message'] = 'Artwork ID is required';
            return;
        }
        
        // Get artwork details
        $sql = "SELECT a.*, u.username as artist_username, u.id as artist_id, u.bio as artist_bio
                FROM artworks a
                JOIN users u ON a.user_id = u.id
                WHERE a.id = :artwork_id AND a.status = 'published'";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':artwork_id', $artwork_id);
        $stmt->execute();
        
        $artwork = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$artwork) {
            $response['message'] = 'Artwork not found';
            return;
        }
        
        // Increment view count
        $view_sql = "UPDATE artworks SET views_count = views_count + 1 WHERE id = :artwork_id";
        $view_stmt = $conn->prepare($view_sql);
        $view_stmt->bindValue(':artwork_id', $artwork_id);
        $view_stmt->execute();
        
        // Check if user has liked this artwork
        $user_liked = false;
        if ($logged_in) {
            $like_stmt = $conn->prepare("SELECT 1 FROM artwork_likes WHERE artwork_id = :artwork_id AND user_id = :user_id");
            $like_stmt->bindValue(':artwork_id', $artwork_id);
            $like_stmt->bindValue(':user_id', $user_id);
            $like_stmt->execute();
            $user_liked = $like_stmt->fetch() !== false;
        }
        
        // Check if user has saved this artwork
        $user_saved = false;
        if ($logged_in) {
            $save_stmt = $conn->prepare("SELECT 1 FROM saved_artworks WHERE artwork_id = :artwork_id AND user_id = :user_id");
            $save_stmt->bindValue(':artwork_id', $artwork_id);
            $save_stmt->bindValue(':user_id', $user_id);
            $save_stmt->execute();
            $user_saved = $save_stmt->fetch() !== false;
        }
        
        // Get AI tools used
        $ai_tools = '';
        if ($artwork['used_ai']) {
            $ai_stmt = $conn->prepare("SELECT tool_name FROM artwork_ai_tools WHERE artwork_id = :artwork_id");
            $ai_stmt->bindValue(':artwork_id', $artwork_id);
            $ai_stmt->execute();
            $tools = $ai_stmt->fetchAll(PDO::FETCH_COLUMN);
            $ai_tools = implode(', ', $tools);
        }
        
        // Get tags
        $tags_stmt = $conn->prepare("SELECT tag_name FROM artwork_tags WHERE artwork_id = :artwork_id");
        $tags_stmt->bindValue(':artwork_id', $artwork_id);
        $tags_stmt->execute();
        $tags = $tags_stmt->fetchAll(PDO::FETCH_COLUMN);
        $tags_string = implode(', ', $tags);
        
        // Get comments
        $comments_sql = "SELECT c.*, u.id as user_id, u.username
                       FROM comments c
                       JOIN users u ON c.user_id = u.id
                       WHERE c.artwork_id = :artwork_id
                       ORDER BY c.created_at DESC";
        
        $comments_stmt = $conn->prepare($comments_sql);
        $comments_stmt->bindValue(':artwork_id', $artwork_id);
        $comments_stmt->execute();
        
        $comments = [];
        while ($comment = $comments_stmt->fetch(PDO::FETCH_ASSOC)) {
            $comments[] = [
                'id' => $comment['id'],
                'content' => $comment['content'],
                'created_at' => $comment['created_at'],
                'created_at_formatted' => date('M j, Y g:i A', strtotime($comment['created_at'])),
                'user' => [
                    'id' => $comment['user_id'],
                    'username' => $comment['username']
                ]
            ];
        }
        
        // Format created_at date
        $created_at_formatted = date('M j, Y', strtotime($artwork['created_at']));
        
        $artwork_data = [
            'id' => $artwork['id'],
            'title' => $artwork['title'],
            'description' => $artwork['description'],
            'image_url' => $artwork['image_url'],
            'category' => $artwork['category'],
            'created_at' => $artwork['created_at'],
            'created_at_formatted' => $created_at_formatted,
            'likes_count' => $artwork['likes_count'],
            'views_count' => $artwork['views_count'] + 1, // Include the current view
            'comments_count' => $artwork['comments_count'],
            'used_ai' => (bool)$artwork['used_ai'],
            'ai_tools' => $ai_tools,
            'tags' => $tags_string,
            'user_liked' => $user_liked,
            'user_saved' => $user_saved,
            'artist' => [
                'id' => $artwork['artist_id'],
                'username' => $artwork['artist_username'],
                'bio' => $artwork['artist_bio']
            ],
            'comments' => $comments
        ];
        
        $response['success'] = true;
        $response['data'] = $artwork_data;
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

// Function to get artist preview
function getArtistPreview() {
    global $conn, $response, $user_id, $logged_in;
    
    try {
        $artist_id = isset($_GET['artist_id']) ? intval($_GET['artist_id']) : 0;
        
        if (!$artist_id) {
            $response['message'] = 'Artist ID is required';
            return;
        }
        
        // Get artist details
        $sql = "SELECT u.id, u.username, u.bio
                FROM users u
                WHERE u.id = :artist_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':artist_id', $artist_id);
        $stmt->execute();
        
        $artist = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$artist) {
            $response['message'] = 'Artist not found';
            return;
        }
        
        // Get follower count
        $follower_sql = "SELECT COUNT(*) FROM followers WHERE followed_id = :artist_id";
        $follower_stmt = $conn->prepare($follower_sql);
        $follower_stmt->bindValue(':artist_id', $artist_id);
        $follower_stmt->execute();
        $follower_count = $follower_stmt->fetchColumn();
        
        // Get post count
        $post_sql = "SELECT COUNT(*) FROM artworks WHERE user_id = :artist_id AND status = 'published'";
        $post_stmt = $conn->prepare($post_sql);
        $post_stmt->bindValue(':artist_id', $artist_id);
        $post_stmt->execute();
        $post_count = $post_stmt->fetchColumn();
        
        // Check if user is following this artist
        $is_following = false;
        if ($logged_in) {
            $following_stmt = $conn->prepare("SELECT 1 FROM followers WHERE follower_id = :user_id AND followed_id = :artist_id");
            $following_stmt->bindValue(':user_id', $user_id);
            $following_stmt->bindValue(':artist_id', $artist_id);
            $following_stmt->execute();
            $is_following = $following_stmt->fetch() !== false;
        }
        
        // Get recent works
        $works_sql = "SELECT id, title, image_url
                     FROM artworks
                     WHERE user_id = :artist_id AND status = 'published'
                     ORDER BY created_at DESC
                     LIMIT 6";
        
        $works_stmt = $conn->prepare($works_sql);
        $works_stmt->bindValue(':artist_id', $artist_id);
        $works_stmt->execute();
        
        $recent_works = [];
        while ($work = $works_stmt->fetch(PDO::FETCH_ASSOC)) {
            $recent_works[] = [
                'id' => $work['id'],
                'title' => $work['title'],
                'image_url' => $work['image_url']
            ];
        }
        
        $artist_data = [
            'id' => $artist['id'],
            'username' => $artist['username'],
            'bio' => $artist['bio'],
            'follower_count' => $follower_count,
            'post_count' => $post_count,
            'is_following' => $is_following,
            'recent_works' => $recent_works
        ];
        
        $response['success'] = true;
        $response['data'] = $artist_data;
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

// Function to like/unlike an artwork
function likeArtwork() {
    global $conn, $response, $user_id, $logged_in;
    
    // Require login
    if (!$logged_in) {
        $response['message'] = 'Please login to like artworks';
        return;
    }
    
    try {
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        
        if (!$artwork_id) {
            $response['message'] = 'Artwork ID is required';
            return;
        }
        
        // Check if artwork exists
        $check_sql = "SELECT 1 FROM artworks WHERE id = :artwork_id AND status = 'published'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindValue(':artwork_id', $artwork_id);
        $check_stmt->execute();
        
        if (!$check_stmt->fetch()) {
            $response['message'] = 'Artwork not found';
            return;
        }
        
        // Check if already liked
        $like_check_sql = "SELECT 1 FROM artwork_likes WHERE artwork_id = :artwork_id AND user_id = :user_id";
        $like_check_stmt = $conn->prepare($like_check_sql);
        $like_check_stmt->bindValue(':artwork_id', $artwork_id);
        $like_check_stmt->bindValue(':user_id', $user_id);
        $like_check_stmt->execute();
        $already_liked = $like_check_stmt->fetch() !== false;
        
        // Begin transaction
        $conn->beginTransaction();
        
        if ($already_liked) {
            // Unlike
            $unlike_sql = "DELETE FROM artwork_likes WHERE artwork_id = :artwork_id AND user_id = :user_id";
            $unlike_stmt = $conn->prepare($unlike_sql);
            $unlike_stmt->bindValue(':artwork_id', $artwork_id);
            $unlike_stmt->bindValue(':user_id', $user_id);
            $unlike_stmt->execute();
            
            // Decrement likes count
            $update_sql = "UPDATE artworks SET likes_count = likes_count - 1 WHERE id = :artwork_id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindValue(':artwork_id', $artwork_id);
            $update_stmt->execute();
            
            $action = 'unliked';
        } else {
            // Like
            $like_sql = "INSERT INTO artwork_likes (artwork_id, user_id, created_at) VALUES (:artwork_id, :user_id, NOW())";
            $like_stmt = $conn->prepare($like_sql);
            $like_stmt->bindValue(':artwork_id', $artwork_id);
            $like_stmt->bindValue(':user_id', $user_id);
            $like_stmt->execute();
            
            // Increment likes count
            $update_sql = "UPDATE artworks SET likes_count = likes_count + 1 WHERE id = :artwork_id";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bindValue(':artwork_id', $artwork_id);
            $update_stmt->execute();
            
            $action = 'liked';
        }
        
        // Get updated likes count
        $count_sql = "SELECT likes_count FROM artworks WHERE id = :artwork_id";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bindValue(':artwork_id', $artwork_id);
        $count_stmt->execute();
        $likes_count = $count_stmt->fetchColumn();
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['data'] = [
            'action' => $action,
            'likes_count' => $likes_count
        ];
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

// Function to save/unsave an artwork
function saveArtwork() {
    global $conn, $response, $user_id, $logged_in;
    
    // Require login
    if (!$logged_in) {
        $response['message'] = 'Please login to save artworks';
        return;
    }
    
    try {
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        
        if (!$artwork_id) {
            $response['message'] = 'Artwork ID is required';
            return;
        }
        
        // Check if artwork exists
        $check_sql = "SELECT 1 FROM artworks WHERE id = :artwork_id AND status = 'published'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindValue(':artwork_id', $artwork_id);
        $check_stmt->execute();
        
        if (!$check_stmt->fetch()) {
            $response['message'] = 'Artwork not found';
            return;
        }
        
        // Check if already saved
        $save_check_sql = "SELECT 1 FROM saved_artworks WHERE artwork_id = :artwork_id AND user_id = :user_id";
        $save_check_stmt = $conn->prepare($save_check_sql);
        $save_check_stmt->bindValue(':artwork_id', $artwork_id);
        $save_check_stmt->bindValue(':user_id', $user_id);
        $save_check_stmt->execute();
        $already_saved = $save_check_stmt->fetch() !== false;
        
        if ($already_saved) {
            // Unsave
            $unsave_sql = "DELETE FROM saved_artworks WHERE artwork_id = :artwork_id AND user_id = :user_id";
            $unsave_stmt = $conn->prepare($unsave_sql);
            $unsave_stmt->bindValue(':artwork_id', $artwork_id);
            $unsave_stmt->bindValue(':user_id', $user_id);
            $unsave_stmt->execute();
            
            $action = 'unsaved';
        } else {
            // Save
            $save_sql = "INSERT INTO saved_artworks (artwork_id, user_id, created_at) VALUES (:artwork_id, :user_id, NOW())";
            $save_stmt = $conn->prepare($save_sql);
            $save_stmt->bindValue(':artwork_id', $artwork_id);
            $save_stmt->bindValue(':user_id', $user_id);
            $save_stmt->execute();
            
            $action = 'saved';
        }
        
        $response['success'] = true;
        $response['data'] = [
            'action' => $action,
        ];
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

// Function to follow/unfollow an artist
function followArtist() {
    global $conn, $response, $user_id, $logged_in;
    
    // Require login
    if (!$logged_in) {
        $response['message'] = 'Please login to follow artists';
        return;
    }
    
    try {
        $artist_id = isset($_POST['artist_id']) ? intval($_POST['artist_id']) : 0;
        
        if (!$artist_id) {
            $response['message'] = 'Artist ID is required';
            return;
        }
        
        // Prevent self-following
        if ($artist_id == $user_id) {
            $response['message'] = 'You cannot follow yourself';
            return;
        }
        
        // Check if artist exists
        $check_sql = "SELECT 1 FROM users WHERE id = :artist_id";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindValue(':artist_id', $artist_id);
        $check_stmt->execute();
        
        if (!$check_stmt->fetch()) {
            $response['message'] = 'Artist not found';
            return;
        }
        
        // Check if already following
        $follow_check_sql = "SELECT 1 FROM followers WHERE follower_id = :user_id AND followed_id = :artist_id";
        $follow_check_stmt = $conn->prepare($follow_check_sql);
        $follow_check_stmt->bindValue(':user_id', $user_id);
        $follow_check_stmt->bindValue(':artist_id', $artist_id);
        $follow_check_stmt->execute();
        $already_following = $follow_check_stmt->fetch() !== false;
        
        if ($already_following) {
            // Unfollow
            $unfollow_sql = "DELETE FROM followers WHERE follower_id = :user_id AND followed_id = :artist_id";
            $unfollow_stmt = $conn->prepare($unfollow_sql);
            $unfollow_stmt->bindValue(':user_id', $user_id);
            $unfollow_stmt->bindValue(':artist_id', $artist_id);
            $unfollow_stmt->execute();
            
            $action = 'unfollowed';
        } else {
            // Follow
            $follow_sql = "INSERT INTO followers (follower_id, followed_id, created_at) VALUES (:user_id, :artist_id, NOW())";
            $follow_stmt = $conn->prepare($follow_sql);
            $follow_stmt->bindValue(':user_id', $user_id);
            $follow_stmt->bindValue(':artist_id', $artist_id);
            $follow_stmt->execute();
            
            $action = 'followed';
        }
        
        // Get updated follower count
        $count_sql = "SELECT COUNT(*) FROM followers WHERE followed_id = :artist_id";
        $count_stmt = $conn->prepare($count_sql);
        $count_stmt->bindValue(':artist_id', $artist_id);
        $count_stmt->execute();
        $follower_count = $count_stmt->fetchColumn();
        
        $response['success'] = true;
        $response['data'] = [
            'action' => $action,
            'follower_count' => $follower_count
        ];
    } catch (PDOException $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}

// Function to add a comment to an artwork
function addComment() {
    global $conn, $response, $user_id, $logged_in;
    
    // Require login
    if (!$logged_in) {
        $response['message'] = 'Please login to comment on artworks';
        return;
    }
    
    try {
        $artwork_id = isset($_POST['artwork_id']) ? intval($_POST['artwork_id']) : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
        
        if (!$artwork_id) {
            $response['message'] = 'Artwork ID is required';
            return;
        }
        
        if (empty($comment)) {
            $response['message'] = 'Comment cannot be empty';
            return;
        }
        
        // Check if artwork exists
        $check_sql = "SELECT 1 FROM artworks WHERE id = :artwork_id AND status = 'published'";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bindValue(':artwork_id', $artwork_id);
        $check_stmt->execute();
        
        if (!$check_stmt->fetch()) {
            $response['message'] = 'Artwork not found';
            return;
        }
        
        // Begin transaction
        $conn->beginTransaction();
        
        // Add comment
        $comment_sql = "INSERT INTO comments (artwork_id, user_id, content, created_at) 
                       VALUES (:artwork_id, :user_id, :content, NOW())";
        $comment_stmt = $conn->prepare($comment_sql);
        $comment_stmt->bindValue(':artwork_id', $artwork_id);
        $comment_stmt->bindValue(':user_id', $user_id);
        $comment_stmt->bindValue(':content', $comment);
        $comment_stmt->execute();
        
        $comment_id = $conn->lastInsertId();
        
        // Increment comment count
        $update_sql = "UPDATE artworks SET comments_count = comments_count + 1 WHERE id = :artwork_id";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bindValue(':artwork_id', $artwork_id);
        $update_stmt->execute();
        
        // Get user info
        $user_sql = "SELECT username FROM users WHERE id = :user_id";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bindValue(':user_id', $user_id);
        $user_stmt->execute();
        $username = $user_stmt->fetchColumn();
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['data'] = [
            'id' => $comment_id,
            'content' => $comment,
            'created_at' => date('Y-m-d H:i:s'),
            'created_at_formatted' => date('M j, Y g:i A'),
            'user' => [
                'id' => $user_id,
                'username' => $username
            ]
        ];
    } catch (PDOException $e) {
        // Rollback on error
        $conn->rollBack();
        $response['message'] = 'Database error: ' . $e->getMessage();
    }
}