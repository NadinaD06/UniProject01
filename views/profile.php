<!-- profile.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | Social Media</title>
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

    <main class="profile-container">
        <section class="profile-info">
            <h1>Your Profile</h1>
            <div class="user-details">
                <p>Username: <span id="username"></span></p>
                <p>Email: <span id="email"></span></p>
                <p>Member since: <span id="created-at"></span></p>
            </div>
        </section>

        <section class="user-posts">
            <h2>Your Posts</h2>
            <div id="posts-container"></div>
        </section>
    </main>

    <script src="js/profile.js"></script>
</body>
</html>