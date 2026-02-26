<?php
// Kiểm tra session đã start chưa
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['customer_id']) && !isset($_SESSION['locked_user_id'])) {
    return; // Không hiển thị chat nếu chưa đăng nhập
}

// Đảm bảo có kết nối database
if (!isset($conn)) {
    include __DIR__ . "/../db_conn.php";
}

include __DIR__ . "/func-chat.php";

try {
    $user_id = $_SESSION['customer_id'] ?? $_SESSION['locked_user_id'];
    $unread_count = get_unread_messages_count($conn, $user_id);
} catch (Exception $e) {
    // Nếu có lỗi (ví dụ bảng chưa tồn tại), không hiển thị chat
    return;
}
?>
<!-- Chat Widget -->
<style>
    .chat-widget-toggle {
        position: fixed;
        bottom: 25px;
        right: 25px;
        width: 64px;
        height: 64px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
        cursor: pointer;
        z-index: 10000;
        font-size: 26px;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        animation: pulse 2s infinite;
    }
    .chat-widget-toggle:hover {
        transform: scale(1.15) translateY(-2px);
        box-shadow: 0 8px 30px rgba(102, 126, 234, 0.5);
    }
    .chat-widget-toggle:active {
        transform: scale(1.05);
    }
    @keyframes pulse {
        0%, 100% {
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.4);
        }
        50% {
            box-shadow: 0 6px 25px rgba(102, 126, 234, 0.6), 0 0 0 8px rgba(102, 126, 234, 0.1);
        }
    }
    .chat-widget-toggle .badge {
        position: absolute;
        top: -2px;
        right: -2px;
        background: #ff4757;
        border-radius: 50%;
        width: 22px;
        height: 22px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 600;
        border: 2px solid white;
        animation: bounce 0.5s ease;
    }
    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.2); }
    }
    .chat-widget-container {
        position: fixed;
        bottom: 100px;
        right: 25px;
        width: 380px;
        max-width: calc(100vw - 50px);
        height: 600px;
        max-height: calc(100vh - 150px);
        background: white;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.15);
        display: none;
        flex-direction: column;
        z-index: 9999;
        overflow: hidden;
        animation: slideUp 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .chat-widget-container.active {
        display: flex;
    }
    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    .chat-widget-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 18px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .chat-widget-header > div:first-child {
        flex: 1;
    }
    .chat-widget-header strong {
        display: block;
        font-size: 16px;
        margin-bottom: 4px;
    }
    .chat-widget-header .small {
        font-size: 12px;
        opacity: 0.9;
    }
    .chat-widget-header button {
        background: rgba(255,255,255,0.2);
        border: none;
        color: white;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .chat-widget-header button:hover {
        background: rgba(255,255,255,0.3);
        transform: rotate(90deg);
    }
    .chat-widget-body {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: linear-gradient(to bottom, #f8f9fa 0%, #ffffff 100%);
        scroll-behavior: smooth;
    }
    .chat-widget-body::-webkit-scrollbar {
        width: 6px;
    }
    .chat-widget-body::-webkit-scrollbar-track {
        background: #f1f1f1;
    }
    .chat-widget-body::-webkit-scrollbar-thumb {
        background: #667eea;
        border-radius: 3px;
    }
    .chat-widget-body::-webkit-scrollbar-thumb:hover {
        background: #764ba2;
    }
    .chat-widget-message {
        margin-bottom: 16px;
        display: flex;
        flex-direction: column;
        animation: fadeIn 0.3s ease;
    }
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    .chat-widget-message.user {
        align-items: flex-end;
    }
    .chat-widget-message.admin {
        align-items: flex-start;
    }
    .chat-widget-bubble {
        max-width: 75%;
        padding: 12px 16px;
        border-radius: 20px;
        word-wrap: break-word;
        line-height: 1.5;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        position: relative;
    }
    .chat-widget-bubble.user {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-bottom-right-radius: 4px;
    }
    .chat-widget-bubble.admin {
        background: white;
        color: #333;
        border: 1px solid #e8e8e8;
        border-bottom-left-radius: 4px;
    }
    .chat-widget-time {
        font-size: 11px;
        color: #999;
        margin-top: 6px;
        padding: 0 4px;
    }
    .chat-widget-typing-indicator {
        display: none;
        padding: 12px 16px;
        margin-bottom: 10px;
        align-items: flex-start;
    }
    .chat-widget-typing-indicator.active {
        display: flex;
    }
    .chat-widget-typing-bubble {
        background: white;
        color: #666;
        padding: 10px 15px;
        border-radius: 18px;
        border: 1px solid #e8e8e8;
        border-bottom-left-radius: 4px;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .chat-widget-typing-dots {
        display: flex;
        gap: 4px;
    }
    .chat-widget-typing-dot {
        width: 6px;
        height: 6px;
        background: #999;
        border-radius: 50%;
        animation: typingDot 1.4s infinite;
    }
    .chat-widget-typing-dot:nth-child(2) {
        animation-delay: 0.2s;
    }
    .chat-widget-typing-dot:nth-child(3) {
        animation-delay: 0.4s;
    }
    @keyframes typingDot {
        0%, 60%, 100% {
            transform: translateY(0);
            opacity: 0.7;
        }
        30% {
            transform: translateY(-8px);
            opacity: 1;
        }
    }
    .chat-widget-input-area {
        padding: 16px 20px;
        border-top: 1px solid #e8e8e8;
        background: white;
    }
    .chat-widget-input-group {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .chat-widget-input {
        flex: 1;
        border: 2px solid #e8e8e8;
        border-radius: 24px;
        padding: 12px 18px;
        outline: none;
        font-size: 14px;
        transition: all 0.2s;
    }
    .chat-widget-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
    .chat-widget-send-btn {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        border-radius: 50%;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
    }
    .chat-widget-send-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
    }
    .chat-widget-send-btn:active {
        transform: scale(0.95);
    }
    .chat-widget-send-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
    
    /* Emoji Picker */
    .chat-widget-emoji-btn {
        background: transparent;
        border: none;
        color: #667eea;
        font-size: 20px;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .chat-widget-emoji-btn:hover {
        background: #f0f0f0;
        transform: scale(1.1);
    }
    .chat-widget-emoji-picker {
        position: absolute;
        bottom: 70px;
        left: 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        padding: 12px;
        width: 300px;
        max-height: 250px;
        overflow-y: auto;
        display: none;
        z-index: 10001;
        border: 1px solid #e8e8e8;
    }
    .chat-widget-emoji-picker.active {
        display: block;
        animation: slideUp 0.2s ease;
    }
    .chat-widget-emoji-grid {
        display: grid;
        grid-template-columns: repeat(8, 1fr);
        gap: 8px;
    }
    .chat-widget-emoji-item {
        font-size: 24px;
        cursor: pointer;
        padding: 8px;
        text-align: center;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .chat-widget-emoji-item:hover {
        background: #f0f0f0;
        transform: scale(1.2);
    }
    
    /* Image Upload */
    .chat-widget-image-btn {
        background: transparent;
        border: none;
        color: #667eea;
        font-size: 20px;
        cursor: pointer;
        padding: 8px;
        border-radius: 50%;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }
    .chat-widget-image-btn:hover {
        background: #f0f0f0;
        transform: scale(1.1);
    }
    .chat-widget-image-input {
        display: none;
    }
    .chat-widget-image-preview {
        margin-bottom: 12px;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 12px;
        position: relative;
        display: none;
    }
    .chat-widget-image-preview.active {
        display: block;
    }
    .chat-widget-image-preview img {
        max-width: 100%;
        max-height: 200px;
        border-radius: 8px;
        display: block;
    }
    .chat-widget-image-preview .remove-image {
        position: absolute;
        top: 8px;
        right: 8px;
        background: #ff4757;
        color: white;
        border: none;
        border-radius: 50%;
        width: 24px;
        height: 24px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
    }
    .chat-widget-message-image {
        max-width: 75%;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .chat-widget-message-image img {
        width: 100%;
        height: auto;
        display: block;
        cursor: pointer;
    }
    
    /* Responsive */
    @media (max-width: 480px) {
        .chat-widget-container {
            width: calc(100vw - 20px);
            right: 10px;
            bottom: 90px;
            height: calc(100vh - 120px);
            max-height: calc(100vh - 120px);
        }
        .chat-widget-toggle {
            bottom: 15px;
            right: 15px;
            width: 56px;
            height: 56px;
            font-size: 22px;
        }
    }
</style>

<div class="chat-widget-toggle" id="chatWidgetToggle">
    <i class="fas fa-comments"></i>
    <?php if ($unread_count > 0): ?>
        <span class="badge"><?=$unread_count > 9 ? '9+' : $unread_count?></span>
    <?php endif; ?>
</div>

<div class="chat-widget-container" id="chatWidgetContainer">
    <div class="chat-widget-header">
        <div>
            <strong><i class="fas fa-headset me-2"></i>Hỗ trợ trực tuyến</strong>
            <div class="small">Chúng tôi sẽ phản hồi sớm nhất</div>
        </div>
        <button class="btn btn-sm btn-light" onclick="toggleChatWidget()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="chat-widget-body" id="chatWidgetBody">
        <div id="chatWidgetMessages">
            <div class="text-center text-muted py-4">
                <i class="fas fa-spinner fa-spin"></i> Đang tải...
            </div>
        </div>
        <div class="chat-widget-typing-indicator" id="typingIndicator">
            <div class="chat-widget-typing-bubble">
                <span>Admin đang nhập</span>
                <div class="chat-widget-typing-dots">
                    <div class="chat-widget-typing-dot"></div>
                    <div class="chat-widget-typing-dot"></div>
                    <div class="chat-widget-typing-dot"></div>
                </div>
            </div>
        </div>
    </div>
    <div class="chat-widget-input-area">
        <div class="chat-widget-image-preview" id="chatImagePreview">
            <button type="button" class="remove-image" onclick="removeImagePreview()">×</button>
            <img id="chatImagePreviewImg" src="" alt="Preview">
        </div>
        <form id="chatWidgetForm" onsubmit="sendChatMessage(event)" enctype="multipart/form-data">
            <div class="chat-widget-input-group">
                <button type="button" class="chat-widget-emoji-btn" onclick="toggleEmojiPicker()" title="Emoji">
                    <i class="far fa-smile"></i>
                </button>
                <button type="button" class="chat-widget-image-btn" onclick="document.getElementById('chatImageInput').click()" title="Gửi ảnh">
                    <i class="fas fa-image"></i>
                </button>
                <input type="file" id="chatImageInput" class="chat-widget-image-input" accept="image/*" onchange="handleImageSelect(event)">
                <input type="text" class="chat-widget-input" id="chatWidgetInput" placeholder="Nhập tin nhắn...">
                <button type="submit" class="chat-widget-send-btn">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
        </form>
        <div class="chat-widget-emoji-picker" id="emojiPicker">
            <div class="chat-widget-emoji-grid" id="emojiGrid"></div>
        </div>
    </div>
</div>

<script>
    let chatWidgetOpen = false;
    let chatRefreshInterval = null;
    let typingCheckInterval = null;
    let typingTimeout = null;
    let isTyping = false;

    function toggleChatWidget() {
        chatWidgetOpen = !chatWidgetOpen;
        const container = document.getElementById('chatWidgetContainer');
        
        if (chatWidgetOpen) {
            container.classList.add('active');
            loadChatMessages();
            // Đánh dấu đã đọc
            fetch('php/mark-chat-read.php');
            // Auto refresh
            if (chatRefreshInterval) clearInterval(chatRefreshInterval);
            chatRefreshInterval = setInterval(loadChatMessages, 3000);
            // Kiểm tra typing status
            if (typingCheckInterval) clearInterval(typingCheckInterval);
            typingCheckInterval = setInterval(checkTypingStatus, 1000);
        } else {
            container.classList.remove('active');
            if (chatRefreshInterval) {
                clearInterval(chatRefreshInterval);
                chatRefreshInterval = null;
            }
            if (typingCheckInterval) {
                clearInterval(typingCheckInterval);
                typingCheckInterval = null;
            }
            // Dừng typing khi đóng chat
            sendTypingStatus(false);
        }
    }
    
    function sendTypingStatus(typing) {
        if (isTyping === typing) return;
        isTyping = typing;
        
        const formData = new FormData();
        formData.append('is_typing', typing ? '1' : '0');
        
        fetch('php/set-typing-status.php', {
            method: 'POST',
            body: formData
        }).catch(error => console.error('Error sending typing status:', error));
    }
    
    function checkTypingStatus() {
        fetch('php/get-typing-status.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const indicator = document.getElementById('typingIndicator');
                    if (data.is_typing) {
                        indicator.classList.add('active');
                        const chatBody = document.getElementById('chatWidgetBody');
                        chatBody.scrollTop = chatBody.scrollHeight;
                    } else {
                        indicator.classList.remove('active');
                    }
                }
            })
            .catch(error => console.error('Error checking typing status:', error));
    }

    function loadChatMessages() {
        fetch('php/get-chat-messages.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.messages) {
                    const chatBody = document.getElementById('chatWidgetBody');
                    const messagesContainer = document.getElementById('chatWidgetMessages');
                    if (!messagesContainer) return;
                    
                    messagesContainer.innerHTML = '';
                    
                    if (data.messages.length === 0) {
                        messagesContainer.innerHTML = '<div class="text-center text-muted py-4"><i class="fas fa-comments fa-3x mb-3"></i><p>Chưa có tin nhắn nào. Hãy bắt đầu cuộc trò chuyện!</p></div>';
                    } else {
                        data.messages.forEach(msg => {
                            const isUser = !msg.is_admin;
                            const timeStr = new Date(msg.created_at).toLocaleString('vi-VN', {
                                day: '2-digit',
                                month: '2-digit',
                                year: 'numeric',
                                hour: '2-digit',
                                minute: '2-digit'
                            });
                            
                            const messageDiv = document.createElement('div');
                            messageDiv.className = 'chat-widget-message ' + (isUser ? 'user' : 'admin');
                            
                            let content = '';
                            if (msg.message_type === 'image' && msg.image_url) {
                                content = `
                                    <div class="chat-widget-message-image">
                                        <img src="${msg.image_url}" alt="Image" onclick="window.open('${msg.image_url}', '_blank')">
                                    </div>
                                    ${msg.message ? `<div class="chat-widget-bubble ${isUser ? 'user' : 'admin'}" style="margin-top: 8px;">${msg.message.replace(/\n/g, '<br>')}</div>` : ''}
                                `;
                            } else {
                                content = `
                                    <div class="chat-widget-bubble ${isUser ? 'user' : 'admin'}">
                                        ${msg.message ? msg.message.replace(/\n/g, '<br>') : ''}
                                    </div>
                                `;
                            }
                            
                            messageDiv.innerHTML = content + `<div class="chat-widget-time">${timeStr}</div>`;
                            messagesContainer.appendChild(messageDiv);
                        });
                        chatBody.scrollTop = chatBody.scrollHeight;
                    }
                }
            })
            .catch(error => console.error('Error loading messages:', error));
    }

    let selectedImage = null;
    
    function handleImageSelect(event) {
        const file = event.target.files[0];
        if (file) {
            if (file.size > 5 * 1024 * 1024) {
                alert('Kích thước ảnh không được vượt quá 5MB');
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                selectedImage = file;
                document.getElementById('chatImagePreviewImg').src = e.target.result;
                document.getElementById('chatImagePreview').classList.add('active');
            };
            reader.readAsDataURL(file);
        }
    }
    
    function removeImagePreview() {
        selectedImage = null;
        document.getElementById('chatImageInput').value = '';
        document.getElementById('chatImagePreview').classList.remove('active');
        document.getElementById('chatImagePreviewImg').src = '';
    }
    
    function toggleEmojiPicker() {
        const picker = document.getElementById('emojiPicker');
        picker.classList.toggle('active');
    }
    
    function insertEmoji(emoji) {
        const input = document.getElementById('chatWidgetInput');
        input.value += emoji;
        input.focus();
        document.getElementById('emojiPicker').classList.remove('active');
    }
    
    // Khởi tạo emoji picker
    const emojis = ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾'];
    
    document.addEventListener('DOMContentLoaded', function() {
        const emojiGrid = document.getElementById('emojiGrid');
        emojis.forEach(emoji => {
            const emojiItem = document.createElement('div');
            emojiItem.className = 'chat-widget-emoji-item';
            emojiItem.textContent = emoji;
            emojiItem.onclick = () => insertEmoji(emoji);
            emojiGrid.appendChild(emojiItem);
        });
        
        // Đóng emoji picker khi click bên ngoài
        document.addEventListener('click', function(e) {
            const picker = document.getElementById('emojiPicker');
            const emojiBtn = document.querySelector('.chat-widget-emoji-btn');
            if (picker && !picker.contains(e.target) && !emojiBtn.contains(e.target)) {
                picker.classList.remove('active');
            }
        });
    });
    
    function sendChatMessage(e) {
        e.preventDefault();
        const input = document.getElementById('chatWidgetInput');
        const message = input.value.trim();
        
        if (!message && !selectedImage) {
            return;
        }
        
        const formData = new FormData();
        if (message) {
            formData.append('message', message);
        }
        if (selectedImage) {
            formData.append('image', selectedImage);
        }
        
        input.disabled = true;
        const sendBtn = document.querySelector('.chat-widget-send-btn');
        sendBtn.disabled = true;
        
        fetch('php/send-chat-message.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                removeImagePreview();
                sendTypingStatus(false); // Dừng typing khi gửi tin nhắn
                if (typingTimeout) clearTimeout(typingTimeout);
                loadChatMessages();
            } else {
                alert('Lỗi: ' + (data.message || 'Không thể gửi tin nhắn'));
            }
            input.disabled = false;
            sendBtn.disabled = false;
            input.focus();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Có lỗi xảy ra khi gửi tin nhắn');
            input.disabled = false;
            sendBtn.disabled = false;
        });
    }

    // Track typing khi user gõ
    const chatInput = document.getElementById('chatWidgetInput');
    if (chatInput) {
        chatInput.addEventListener('input', function() {
            sendTypingStatus(true);
            if (typingTimeout) clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                sendTypingStatus(false);
            }, 2000);
        });
        
        chatInput.addEventListener('blur', function() {
            sendTypingStatus(false);
            if (typingTimeout) clearTimeout(typingTimeout);
        });
    }

    document.getElementById('chatWidgetToggle').addEventListener('click', toggleChatWidget);

    let lastUnreadCount = <?=$unread_count?>;
    
    // Auto refresh unread count every 5 seconds
    setInterval(() => {
        if (!chatWidgetOpen) {
            fetch('php/get-unread-count.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success !== undefined) {
                        const badge = document.querySelector('.chat-widget-toggle .badge');
                        if (data.unread_count > 0) {
                            if (!badge) {
                                const toggle = document.getElementById('chatWidgetToggle');
                                const newBadge = document.createElement('span');
                                newBadge.className = 'badge';
                                toggle.appendChild(newBadge);
                            }
                            const badgeEl = document.querySelector('.chat-widget-toggle .badge');
                            badgeEl.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                            
                            // Hiển thị thông báo nếu có tin nhắn mới
                            if (data.unread_count > lastUnreadCount) {
                                showChatNotification(data.unread_count);
                            }
                            lastUnreadCount = data.unread_count;
                        } else {
                            if (badge) {
                                badge.remove();
                            }
                            lastUnreadCount = 0;
                        }
                    }
                })
                .catch(error => console.error('Error fetching unread count:', error));
        }
    }, 5000);

    function showChatNotification(count) {
        // Tạo toast notification
        const toast = document.createElement('div');
        toast.className = 'chat-notification';
        toast.style.cssText = 'position: fixed; top: 20px; right: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 10001; display: flex; align-items: center; gap: 10px; animation: slideIn 0.3s ease-out;';
        toast.innerHTML = `
            <i class="fas fa-comments fa-2x"></i>
            <div>
                <strong>Bạn có ${count} tin nhắn mới!</strong>
                <div class="small">Click vào icon chat để xem</div>
            </div>
            <button onclick="this.parentElement.remove()" style="background: none; border: none; color: white; font-size: 20px; cursor: pointer; margin-left: 10px;">&times;</button>
        `;
        
        // Thêm animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        if (!document.querySelector('style[data-chat-notification]')) {
            style.setAttribute('data-chat-notification', '1');
            document.head.appendChild(style);
        }
        
        document.body.appendChild(toast);
        
        // Tự động ẩn sau 5 giây
        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'slideIn 0.3s ease-out reverse';
                setTimeout(() => toast.remove(), 300);
            }
        }, 5000);
    }
</script>

