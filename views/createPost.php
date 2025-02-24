<!-- create-post.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Post | Social Media</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&libraries=places"></script>
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

    <main class="create-post-container">
        <h1>Create New Post</h1>
        <form id="post-form" enctype="multipart/form-data">
            <div class="form-group">
                <textarea id="content" name="content" placeholder="What's on your mind?"></textarea>
            </div>
            <div class="form-group">
                <label for="image">Add Image:</label>
                <input type="file" id="image" name="image" accept="image/*">
                <div id="image-preview"></div>
            </div>
            <div class="form-group">
                <label for="location">Add Location:</label>
                <input type="text" id="location" name="location" placeholder="Search for a place">
                <div id="map"></div>
            </div>
            <button type="submit">Post</button>
        </form>
    </main>

    <script src="js/create-post.js"></script>
</body>
</html>