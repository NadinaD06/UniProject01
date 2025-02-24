// post-interactions.js
class PostInteractions {
    constructor() {
        this.bindEvents();
    }

    bindEvents() {
        document.addEventListener('click', (e) => {
            const target = e.target;
            if (target.matches('.like-btn, .like-btn *')) this.handleLike(e);
            if (target.matches('.comment-submit')) this.handleComment(e);
            if (target.matches('.share-btn')) this.handleShare(e);
        });
    }

    async handleLike(e) {
        const button = e.target.closest('.like-btn');
        const postId = button.closest('.post').dataset.postId;
        const icon = button.querySelector('i');

        try {
            const response = await this.sendRequest('like', { postId });
            const likeCount = response.likeCount;
            
            if (response.action === 'liked') {
                icon.classList.remove('far');
                icon.classList.add('fas');
            } else {
                icon.classList.remove('fas');
                icon.classList.add('far');
            }
            
            button.closest('.post').querySelector('.like-count').textContent = 
                `${likeCount} ${likeCount === 1 ? 'like' : 'likes'}`;
        } catch (error) {
            console.error('Error:', error);
            this.showToast('Failed to update like');
        }
    }

    async handleComment(e) {
        e.preventDefault();
        const post = e.target.closest('.post');
        const input = post.querySelector('.comment-input');
        const content = input.value.trim();

        if (!content) return;

        try {
            const response = await this.sendRequest('comment', {
                postId: post.dataset.postId,
                content: content
            });

            if (response) {
                this.addCommentToDOM(post, response);
                input.value = '';
                
                // Update comment count
                const countElem = post.querySelector('.comment-count');
                const currentCount = parseInt(countElem.textContent);
                countElem.textContent = `${currentCount + 1} comments`;
            }
        } catch (error) {
            console.error('Error:', error);
            this.showToast('Failed to post comment');
        }
    }

    handleShare(e) {
        const post = e.target.closest('.post');
        const postUrl = `${window.location.origin}/post/${post.dataset.postId}`;
        
        if (navigator.share) {
            navigator.share({
                title: 'Check out this post on ArtSpace',
                url: postUrl
            }).catch(console.error);
        } else {
            navigator.clipboard.writeText(postUrl)
                .then(() => this.showToast('Link copied to clipboard!'))
                .catch(() => this.showToast('Failed to copy link'));
        }
    }

    async sendRequest(action, data) {
        const response = await fetch('/post_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ action, ...data })
        });

        if (!response.ok) {
            throw new Error('Network response was not ok');
        }

        return response.json();
    }

    addCommentToDOM(post, commentData) {
        const commentsList = post.querySelector('.comments-list');
        const commentElement = document.createElement('div');
        commentElement.className = 'comment';
        commentElement.innerHTML = `
            <span class="comment-username">${commentData.username}</span>
            <span class="comment-text">${commentData.content}</span>
            <span class="comment-time">Just now</span>
        `;
        commentsList.insertBefore(commentElement, commentsList.firstChild);
    }

    showToast(message) {
        const toast = document.createElement('div');
        toast.className = 'toast';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 3000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new PostInteractions();
});