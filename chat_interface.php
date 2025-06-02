<?php
if (!isset($appointment_id)) {
    die('Appointment ID not set');
}
?>

<div class="chat-container" id="chat-container-<?php echo $appointment_id; ?>">
    <div class="chat-messages" id="chat-messages-<?php echo $appointment_id; ?>">
        <!-- Messages will be loaded here -->
    </div>
    <div class="chat-input">
        <textarea id="message-input-<?php echo $appointment_id; ?>" placeholder="Type your message..."></textarea>
        <button onclick="sendMessage(<?php echo $appointment_id; ?>)">Send</button>
    </div>
</div>

<style>
.chat-container {
    border: 1px solid #ddd;
    border-radius: 8px;
    margin: 10px 0;
    height: 400px;
    display: flex;
    flex-direction: column;
}

.chat-messages {
    flex-grow: 1;
    overflow-y: auto;
    padding: 15px;
    background: #f9f9f9;
}

.chat-message {
    margin-bottom: 10px;
    padding: 8px 12px;
    border-radius: 15px;
    max-width: 70%;
    word-wrap: break-word;
}

.chat-message.sent {
    background-color: #007bff;
    color: white;
    margin-left: auto;
}

.chat-message.received {
    background-color: #e9ecef;
    color: black;
    margin-right: auto;
}

.chat-input {
    display: flex;
    padding: 10px;
    background: white;
    border-top: 1px solid #ddd;
}

.chat-input textarea {
    flex-grow: 1;
    margin-right: 10px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    resize: none;
    height: 40px;
}

.chat-input button {
    padding: 8px 20px;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.chat-input button:hover {
    background: #0056b3;
}

.message-time {
    font-size: 0.8em;
    color: #666;
    margin-top: 4px;
}

.message-sender {
    font-size: 0.9em;
    font-weight: bold;
    margin-bottom: 2px;
}
</style>

<script>
let lastMessageId = 0;

function formatTimestamp(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleString();
}

function createMessageElement(message, isCurrentUser) {
    const messageDiv = document.createElement('div');
    messageDiv.className = `chat-message ${isCurrentUser ? 'sent' : 'received'}`;
    
    const senderDiv = document.createElement('div');
    senderDiv.className = 'message-sender';
    senderDiv.textContent = message.sender_name;
    
    const contentDiv = document.createElement('div');
    contentDiv.className = 'message-content';
    contentDiv.textContent = message.message;
    
    const timeDiv = document.createElement('div');
    timeDiv.className = 'message-time';
    timeDiv.textContent = formatTimestamp(message.timestamp);
    
    messageDiv.appendChild(senderDiv);
    messageDiv.appendChild(contentDiv);
    messageDiv.appendChild(timeDiv);
    
    return messageDiv;
}

function loadMessages(appointmentId) {
    fetch(`get_messages.php?appointment_id=${appointmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                const chatMessages = document.getElementById(`chat-messages-${appointmentId}`);
                const currentUserId = <?php echo $_SESSION['user_id']; ?>;
                
                // Clear existing messages
                chatMessages.innerHTML = '';
                
                data.data.forEach(message => {
                    const isCurrentUser = (message.sender_id === currentUserId);
                    const messageElement = createMessageElement(message, isCurrentUser);
                    chatMessages.appendChild(messageElement);
                    
                    // Update lastMessageId
                    if (message.id > lastMessageId) {
                        lastMessageId = message.id;
                    }
                });
                
                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        })
        .catch(error => console.error('Error loading messages:', error));
}

function sendMessage(appointmentId) {
    const input = document.getElementById(`message-input-${appointmentId}`);
    const message = input.value.trim();
    
    if (!message) return;
    
    const formData = new FormData();
    formData.append('appointment_id', appointmentId);
    formData.append('message', message);
    
    fetch('send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            input.value = '';
            loadMessages(appointmentId);
        }
    })
    .catch(error => console.error('Error sending message:', error));
}

// Initial load of messages
loadMessages(<?php echo $appointment_id; ?>);

// Poll for new messages every 5 seconds
setInterval(() => loadMessages(<?php echo $appointment_id; ?>), 5000);

// Handle Enter key to send message
document.getElementById(`message-input-<?php echo $appointment_id; ?>`).addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage(<?php echo $appointment_id; ?>);
    }
});
</script> 