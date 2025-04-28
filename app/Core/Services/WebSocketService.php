<?php
/**
* app/Services/WebSocketService.php
* Service for WebSocket connections and real-time notifications
**/

namespace App\Services;

class WebSocketService {
    /**
     * WebSocket server URL
     *
     * @var string
     */
    private $serverUrl;
    
    /**
     * API key for WebSocket authentication
     *
     * @var string
     */
    private $apiKey;
    
    /**
     * WebSocket connection status
     *
     * @var bool
     */
    private $isConnected = false;
    
    /**
     * Constructor
     */
    public function __construct() {
        // Get WebSocket configuration from environment or config file
        $this->serverUrl = getenv('WEBSOCKET_URL') ?: 'ws://localhost:8080';
        $this->apiKey = getenv('WEBSOCKET_API_KEY') ?: 'artspace_api_key';
        
        // Check server availability
        $this->checkServerStatus();
    }
    
    /**
     * Check if WebSocket server is available
     *
     * @return bool Server is available
     */
    public function checkServerStatus() {
        // Parse WebSocket URL to get host and port for socket connection
        $parsedUrl = parse_url($this->serverUrl);
        
        if (!$parsedUrl) {
            $this->isConnected = false;
            return false;
        }
        
        $host = $parsedUrl['host'] ?? 'localhost';
        $port = $parsedUrl['port'] ?? 8080;
        
        // Try to establish a TCP connection to check if server is available
        $socket = @fsockopen($host, $port, $errno, $errstr, 1);
        
        if (!$socket) {
            // Server is not available
            $this->isConnected = false;
            return false;
        }
        
        // Server is available
        fclose($socket);
        $this->isConnected = true;
        return true;
    }
    
    /**
     * Send a message to the WebSocket server
     *
     * @param string $channel Channel to send to (e.g., 'user.1')
     * @param string $event Event name
     * @param array $data Message data
     * @return bool Success status
     */
    public function send($channel, $event, $data) {
        // Check if WebSocket server is available
        if (!$this->isConnected) {
            // Try to reconnect
            if (!$this->checkServerStatus()) {
                // Fallback to database notification only
                return false;
            }
        }
        
        // Prepare message
        $message = [
            'channel' => $channel,
            'event' => $event,
            'data' => $data,
            'api_key' => $this->apiKey
        ];
        
        // Convert to REST API URL from WebSocket URL
        $apiUrl = $this->getApiUrl();
        
        // Send using cURL
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2); // 2 seconds connection timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 3); // 3 seconds timeout
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey
        ]);
        
        // Execute request
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // Check if request was successful
        return $httpCode >= 200 && $httpCode < 300;
    }
    
    /**
     * Get the REST API URL from WebSocket URL
     *
     * @return string API URL
     */
    private function getApiUrl() {
        // Replace WebSocket protocol with HTTP
        $apiUrl = str_replace('ws://', 'http://', $this->serverUrl);
        $apiUrl = str_replace('wss://', 'https://', $apiUrl);
        
        // Add API endpoint
        return $apiUrl . '/api/message';
    }
    
    /**
     * Get JavaScript code for WebSocket client
     *
     * @param int $userId Current user ID
     * @return string JavaScript code
     */
    public function getClientScript($userId) {
        // Fallback to AJAX polling if WebSocket is not available
        if (!$this->isConnected) {
            return $this->getPollingScript($userId);
        }
        
        // Generate authentication token
        $token = $this->generateToken($userId);
        
        // Return JavaScript code for WebSocket client
        return <<<JS
        <script>
            // WebSocket Connection
            document.addEventListener('DOMContentLoaded', function() {
                // WebSocket configuration
                const wsUrl = '{$this->serverUrl}';
                const userId = {$userId};
                const authToken = '{$token}';
                
                // Connect to WebSocket server
                let socket;
                let reconnectAttempts = 0;
                let maxReconnectAttempts = 5;
                let reconnectInterval = 3000; // 3 seconds
                
                function connect() {
                    // Create WebSocket connection
                    socket = new WebSocket(wsUrl + '?token=' + authToken);
                    
                    // Connection opened
                    socket.addEventListener('open', function(event) {
                        console.log('Connected to WebSocket server');
                        reconnectAttempts = 0;
                        
                        // Subscribe to user-specific channel
                        socket.send(JSON.stringify({
                            action: 'subscribe',
                            channel: 'user.' + userId
                        }));
                    });
                    
                    // Listen for messages
                    socket.addEventListener('message', function(event) {
                        try {
                            const message = JSON.parse(event.data);
                            
                            // Handle different event types
                            switch (message.event) {
                                case 'notification':
                                    handleNotification(message.data);
                                    break;
                                    
                                case 'message':
                                    handleMessage(message.data);
                                    break;
                                    
                                // Add more event handlers as needed
                            }
                        } catch (error) {
                            console.error('Error processing message:', error);
                        }
                    });
                    
                    // Handle connection errors
                    socket.addEventListener('error', function(event) {
                        console.error('WebSocket error:', event);
                    });
                    
                    // Connection closed
                    socket.addEventListener('close', function(event) {
                        console.log('WebSocket connection closed');
                        
                        // Attempt to reconnect if not closed intentionally
                        if (reconnectAttempts < maxReconnectAttempts) {
                            reconnectAttempts++;
                            setTimeout(connect, reconnectInterval);
                        } else {
                            // Fall back to polling if max reconnect attempts reached
                            startPolling();
                        }
                    });
                }
                
                // Start connection
                connect();
                
                // Handle notification
                function handleNotification(data) {
                    // Update notification counter
                    updateNotificationCounter();
                    
                    // Show notification toast
                    showNotificationToast(data);
                }
                
                // Handle message
                function handleMessage(data) {
                    // Update message counter
                    updateMessageCounter();
                    
                    // Show message notification if not in messages page
                    if (!window.location.pathname.includes('/messages')) {
                        showMessageToast(data);
                    }
                }
                
                // Update notification counter
                function updateNotificationCounter() {
                    // Make AJAX request to get unread count
                    fetch('/notifications/get-unread-count', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const count = data.data.count;
                            const badge = document.querySelector('.notification-badge');
                            
                            if (badge) {
                                if (count > 0) {
                                    badge.textContent = count;
                                    badge.style.display = 'inline-block';
                                } else {
                                    badge.style.display = 'none';
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error updating notification count:', error);
                    });
                }
                
                // Show notification toast
                function showNotificationToast(data) {
                    // Create toast element if not exists
                    let toast = document.querySelector('#notification-toast');
                    
                    if (!toast) {
                        toast = document.createElement('div');
                        toast.id = 'notification-toast';
                        toast.className = 'notification-toast';
                        document.body.appendChild(toast);
                    }
                    
                    // Create toast content
                    const toastContent = document.createElement('div');
                    toastContent.className = 'notification-toast-content';
                    
                    // Add actor image if available
                    if (data.actor) {
                        const img = document.createElement('img');
                        img.src = data.actor.profile_picture;
                        img.alt = data.actor.username;
                        img.className = 'notification-actor-image';
                        toastContent.appendChild(img);
                    }
                    
                    // Add message
                    const message = document.createElement('div');
                    message.className = 'notification-message';
                    message.textContent = data.message;
                    toastContent.appendChild(message);
                    
                    // Add close button
                    const closeBtn = document.createElement('button');
                    closeBtn.className = 'notification-close';
                    closeBtn.innerHTML = '&times;';
                    closeBtn.addEventListener('click', function() {
                        toast.removeChild(toastContent);
                    });
                    toastContent.appendChild(closeBtn);
                    
                    // Add to toast container
                    toast.appendChild(toastContent);
                    
                    // Auto-remove after 5 seconds
                    setTimeout(function() {
                        if (toastContent.parentNode === toast) {
                            toast.removeChild(toastContent);
                        }
                    }, 5000);
                }
                
                // Fallback to polling if WebSocket fails
                function startPolling() {
                    console.log('Falling back to polling for notifications');
                    
                    // Start polling for notifications
                    setInterval(function() {
                        updateNotificationCounter();
                    }, 30000); // Poll every 30 seconds
                }
            });
        </script>
        JS;
    }
    
    /**
     * Get JavaScript code for AJAX polling fallback
     *
     * @param int $userId Current user ID
     * @return string JavaScript code
     */
    private function getPollingScript($userId) {
        return <<<JS
        <script>
            // AJAX Polling for Notifications
            document.addEventListener('DOMContentLoaded', function() {
                const userId = {$userId};
                const pollingInterval = 30000; // 30 seconds
                
                // Start polling
                setInterval(function() {
                    // Poll for notifications
                    fetch('/notifications/get-unread-count', {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({})
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const count = data.data.count;
                            const badge = document.querySelector('.notification-badge');
                            
                            if (badge) {
                                if (count > 0) {
                                    badge.textContent = count;
                                    badge.style.display = 'inline-block';
                                } else {
                                    badge.style.display = 'none';
                                }
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error polling notifications:', error);
                    });
                    
                    // Poll for messages if not in messages page
                    if (!window.location.pathname.includes('/messages')) {
                        fetch('/message/get-unread-count', {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({})
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const count = data.data.count;
                                const messageBadge = document.querySelector('.message-badge');
                                
                                if (messageBadge) {
                                    if (count > 0) {
                                        messageBadge.textContent = count;
                                        messageBadge.style.display = 'inline-block';
                                    } else {
                                        messageBadge.style.display = 'none';
                                    }
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Error polling messages:', error);
                        });
                    }
                }, pollingInterval);
            });
        </script>
        JS;
    }
    
    /**
     * Generate authentication token for WebSocket
     *
     * @param int $userId User ID
     * @return string Authentication token
     */
    private function generateToken($userId) {
        // In a real application, this should use proper JWT or similar
        $timestamp = time();
        $payload = json_encode([
            'user_id' => $userId,
            'timestamp' => $timestamp
        ]);
        
        // Create signature with HMAC
        $signature = hash_hmac('sha256', $payload, $this->apiKey);
        
        // Create token (base64 encoded payload + signature)
        return base64_encode($payload) . '.' . $signature;
    }
}