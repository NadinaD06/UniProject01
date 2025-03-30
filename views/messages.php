<!-- messages.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages | Social Media</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <nav>
            <div class="nav-left">
                <a href="feed.php">Home</a>
                <a href="profile.php" class="active">Profile</a>
                <a href="messages.php">Messages</a>
            </div>
            <div class="nav-right">
                <a href="logout.php">Logout</a>
            </div>
        </nav>
    </header>

    <main class="messages-container">
        <div class="contacts-list">
            <h2>Conversations</h2>
            <div id="contacts"></div>
        </div>
        <div class="message-area">
            <div id="message-header"></div>
            <div id="messages"></div>
            <form id="message-form" class="message-input">
                <input type="text" id="message-content" placeholder="Type a message...">
                <button type="submit">Send</button>
            </form>
        </div>
    </main>

    <script src="js/messages.js"></script>
</body>
</html>
