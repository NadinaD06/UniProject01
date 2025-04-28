<?php
class SettingsController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function index() {
        // Get user information
        $stmt = $this->pdo->prepare("
            SELECT * FROM users
            WHERE id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        require_once __DIR__ . '/../Views/settings/index.php';
    }

    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $bio = $_POST['bio'] ?? '';
            $location = $_POST['location'] ?? '';

            // Handle avatar upload
            $avatar = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../public/uploads/avatars/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = uniqid() . '_' . basename($_FILES['avatar']['name']);
                $uploadFile = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadFile)) {
                    $avatar = '/uploads/avatars/' . $fileName;
                }
            }

            // Update user information
            $stmt = $this->pdo->prepare("
                UPDATE users
                SET username = ?, bio = ?, location = ?
                " . ($avatar ? ", avatar = ?" : "") . "
                WHERE id = ?
            ");

            $params = [$username, $bio, $location];
            if ($avatar) {
                $params[] = $avatar;
            }
            $params[] = $_SESSION['user_id'];

            $stmt->execute($params);

            header('Location: /settings?updated=1');
            exit;
        }
    }

    public function updateAccount() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';

            // Get current user
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();

            $errors = [];

            // Validate current password
            if (!empty($current_password)) {
                if (!password_verify($current_password, $user['password_hash'])) {
                    $errors[] = "Current password is incorrect";
                }
            }

            // Validate new password
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $errors[] = "New password must be at least 6 characters long";
                }
                if ($new_password !== $confirm_password) {
                    $errors[] = "New passwords do not match";
                }
            }

            // Validate email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Invalid email format";
            }

            if (empty($errors)) {
                // Update user information
                $stmt = $this->pdo->prepare("
                    UPDATE users
                    SET email = ?
                    " . (!empty($new_password) ? ", password_hash = ?" : "") . "
                    WHERE id = ?
                ");

                $params = [$email];
                if (!empty($new_password)) {
                    $params[] = password_hash($new_password, PASSWORD_DEFAULT);
                }
                $params[] = $_SESSION['user_id'];

                $stmt->execute($params);

                header('Location: /settings?updated=1');
                exit;
            }
        }
    }

    public function updatePrivacy() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $profile_visibility = $_POST['profile_visibility'] ?? 'public';
            $post_visibility = $_POST['post_visibility'] ?? 'public';
            $allow_tagging = isset($_POST['allow_tagging']) ? 1 : 0;

            $stmt = $this->pdo->prepare("
                UPDATE users
                SET profile_visibility = ?, post_visibility = ?, allow_tagging = ?
                WHERE id = ?
            ");
            $stmt->execute([$profile_visibility, $post_visibility, $allow_tagging, $_SESSION['user_id']]);

            header('Location: /settings?updated=1');
            exit;
        }
    }

    public function updateNotifications() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
            $push_notifications = isset($_POST['push_notifications']) ? 1 : 0;
            $notify_likes = isset($_POST['notify_likes']) ? 1 : 0;
            $notify_comments = isset($_POST['notify_comments']) ? 1 : 0;
            $notify_follows = isset($_POST['notify_follows']) ? 1 : 0;

            $stmt = $this->pdo->prepare("
                UPDATE users
                SET email_notifications = ?, push_notifications = ?,
                    notify_likes = ?, notify_comments = ?, notify_follows = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $email_notifications, $push_notifications,
                $notify_likes, $notify_comments, $notify_follows,
                $_SESSION['user_id']
            ]);

            header('Location: /settings?updated=1');
            exit;
        }
    }
} 