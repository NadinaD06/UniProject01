<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Load configuration and database connection
require_once 'get_db_connection.php';

// If this is an AJAX request, return JSON data
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        // Get the last notification ID we've seen
        $lastId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;
        
        // Get new notifications
        $stmt = $pdo->prepare("
            SELECT n.*, 
                   actor.username as actor_username
            FROM notifications n
            JOIN users actor ON n.actor_id = actor.id
            WHERE n.id > ?
            ORDER BY n.created_at DESC
        ");
        
        $stmt->execute([$lastId]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get unread count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM notifications
            WHERE is_read = FALSE
        ");
        
        $stmt->execute();
        $unreadCount = $stmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Real-time Notifications Test</title>
    <style>
        .notification {
            padding: 10px;
            margin: 10px 0;
            border-radius: 10px;
            background-color: #e3f2fd;
        }
        .notification.read {
            background-color: #f5f5f5;
        }
        #notification-count {
            position: fixed;
            top: 20px;
            right: 20px;
            background-color: #ff4444;
            color: white;
            padding: 5px 10px;
            border-radius: 50%;
            display: none;
        }
    </style>
</head>
<body>
    <h2>Real-time Notifications Test</h2>
    
    <div id="notification-count"></div>
    <div id="notifications"></div>
    
    <button onclick="createTestNotification()">Create Test Notification</button>
    
    <script>
        let lastNotificationId = 0;
        
        function updateNotifications() {
            fetch('test_realtime_notifications.php?ajax=1&last_id=' + lastNotificationId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update unread count
                        const countElement = document.getElementById('notification-count');
                        if (data.unread_count > 0) {
                            countElement.textContent = data.unread_count;
                            countElement.style.display = 'block';
                        } else {
                            countElement.style.display = 'none';
                        }
                        
                        // Add new notifications
                        data.notifications.forEach(notification => {
                            if (notification.id > lastNotificationId) {
                                lastNotificationId = notification.id;
                                
                                const div = document.createElement('div');
                                div.className = 'notification' + (notification.is_read ? ' read' : '');
                                div.innerHTML = `
                                    <p><strong>${notification.actor_username}</strong> ${notification.content}</p>
                                    <p style="font-size: 0.8em; color: #666;">
                                        Type: ${notification.type} | Created: ${notification.created_at}
                                    </p>
                                `;
                                
                                document.getElementById('notifications').prepend(div);
                            }
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        function createTestNotification() {
            fetch('create_test_notification.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Test notification created');
                    }
                })
                .catch(error => console.error('Error:', error));
        }
        
        // Check for new notifications every 5 seconds
        setInterval(updateNotifications, 5000);
        
        // Initial load
        updateNotifications();
    </script>
</body>
</html> 