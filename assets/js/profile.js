// profile.js
document.addEventListener('DOMContentLoaded', function() {
    fetchUserProfile();
    fetchUserPosts();
});

async function fetchUserProfile() {
    try {
        const response = await fetch('api/get_profile.php');
        const data = await response.json();
        
        document.getElementById('username').textContent = data.username;
        document.getElementById('email').textContent = data.email;
        document.getElementById('created-at').textContent = new Date(data.created_at).toLocaleDateString();
    } catch (error) {
        console.error('Error fetching profile:', error);
    }
}

async function fetchUserPosts() {
    try {
        const response = await fetch('api/get_user_posts.php');
        const posts = await response.json();
        
        const container = document.getElementById('posts-container');
        container.innerHTML = posts.map(post => `
            <div class="post">
                <p>${post.content}</p>
                ${post.image_url ? `<img src="${post.image_url}" alt="Post image">` : ''}
                ${post.location ? `<p class="location">📍 ${post.location}</p>` : ''}
                <p class="timestamp">${new Date(post.created_at).toLocaleString()}</p>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error fetching posts:', error);
    }
}
