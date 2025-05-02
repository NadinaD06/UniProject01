-- Test Users
INSERT INTO users (username, email, password_hash, role, created_at) VALUES
('admin', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NOW()), -- password: password
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW()),
('bob_wilson', 'bob@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user', NOW());

-- Test Posts
INSERT INTO posts (user_id, content, location_lat, location_lng, created_at) VALUES
(2, 'Just finished my first day at the new job! #excited', 51.5074, -0.1278, NOW()),
(3, 'Beautiful day for a walk in the park!', 51.5074, -0.1278, NOW()),
(4, 'Check out this amazing restaurant I found!', 51.5074, -0.1278, NOW());

-- Test Comments
INSERT INTO comments (post_id, user_id, content, created_at) VALUES
(1, 3, 'Congratulations! 🎉', NOW()),
(1, 4, 'Good luck with your new job!', NOW()),
(2, 2, 'The weather is perfect today!', NOW());

-- Test Likes
INSERT INTO likes (post_id, user_id, created_at) VALUES
(1, 3, NOW()),
(1, 4, NOW()),
(2, 2, NOW()),
(2, 4, NOW());

-- Test Friendships
INSERT INTO friendships (user_id, friend_id, created_at) VALUES
(2, 3, NOW()),
(3, 2, NOW()),
(2, 4, NOW()),
(4, 2, NOW());

-- Test Messages
INSERT INTO messages (sender_id, receiver_id, content, created_at) VALUES
(2, 3, 'Hey Jane, how are you?', NOW()),
(3, 2, 'Hi John! I\'m good, thanks for asking!', NOW()),
(2, 4, 'Bob, did you see the new movie?', NOW());

-- Test Reports
INSERT INTO reports (reporter_id, reported_id, reason, status, created_at) VALUES
(2, 4, 'Inappropriate content', 'pending', NOW()),
(3, 4, 'Harassment', 'pending', NOW());

-- Test Blocks
INSERT INTO blocks (user_id, blocked_id, created_at) VALUES
(2, 4, NOW()); 