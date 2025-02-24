// messages.js
let currentChat = null;

document.addEventListener('DOMContentLoaded', function() {
    fetchContacts();
    initializeMessageForm();
});

async function fetchContacts() {
    try {
        const response = await fetch('api/get_contacts.php');
        const contacts = await response.json();
        
        const container = document.getElementById('contacts');
        container.innerHTML = contacts.map(contact => `
            <div class="contact" onclick="openChat(${contact.id})">
                <h3>${contact.username}</h3>
                <p>${contact.last_message || 'No messages yet'}</p>
            </div>
        `).join('');
    } catch (error) {
        console.error('Error fetching contacts:', error);
    }
}

async function openChat(userId) {
    currentChat = userId;
    try {
        const response = await fetch(`api/get_messages.php?user_id=${userId}`);
        const messages = await response.json();
        
        const container = document.getElementById('messages');
        container.innerHTML = messages.map(message => `
            <div class="message ${message.sender_id === currentChat ? 'received' : 'sent'}">
                <p>${message.content}</p>
                <span class="timestamp">${new Date(message.sent_at).toLocaleString()}</span>
            </div>
        `).join('');
        
        container.scrollTop = container.scrollHeight;
    } catch (error) {
        console.error('Error fetching messages:', error);
    }
}

function initializeMessageForm() {
    const form = document.getElementById('message-form');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (!currentChat) return;

        const content = document.getElementById('message-content').value;
        try {
            await fetch('api/send_message.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    receiver_id: currentChat,
                    content: content
                })
            });
            
            document.getElementById('message-content').value = '';
            openChat(currentChat);
        } catch (error) {
            console.error('Error sending message:', error);
        }
    });
}
