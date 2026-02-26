<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_email'])) {
    header("Location: adminlogin.php");
    exit;
}

include "db_conn.php";
include "php/func-chat.php";
include "php/func-user.php";

$selected_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

if ($selected_user_id) {
    $messages = get_chat_messages($conn, $selected_user_id, null, 200);
    $user = get_user_by_id($conn, $selected_user_id);
    // Đánh dấu tin nhắn từ user này là đã đọc (cho admin)
    $admin_id = $_SESSION['user_id'];
    mark_messages_as_read($conn, null, $admin_id, $selected_user_id);
}

// Lấy danh sách chat sau khi đánh dấu đã đọc để cập nhật badge
$active_chats = get_active_chats($conn);

if (!$selected_user_id) {
    $messages = 0;
    $user = null;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Chat - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .chat-container {
            height: calc(100vh - 200px);
            display: flex;
            gap: 20px;
        }
        .chat-list {
            width: 300px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            background: white;
        }
        .chat-list-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            font-weight: bold;
        }
        .chat-item {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            cursor: pointer;
            transition: background 0.2s;
        }
        .chat-item:hover {
            background: #f8f9fa;
        }
        .chat-item.active {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
        }
        .chat-item-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .chat-item-email {
            font-size: 12px;
            color: #666;
        }
        .chat-item-time {
            font-size: 11px;
            color: #999;
        }
        .chat-item-badge {
            float: right;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
        }
        .chat-messages {
            flex: 1;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            display: flex;
            flex-direction: column;
            background: white;
        }
        .chat-messages-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px;
            border-radius: 10px 10px 0 0;
        }
        .chat-messages-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            background: #f8f9fa;
        }
        .chat-message {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }
        .chat-message.user {
            align-items: flex-start;
        }
        .chat-message.admin {
            align-items: flex-end;
        }
        .message-bubble {
            max-width: 70%;
            padding: 10px 15px;
            border-radius: 18px;
            word-wrap: break-word;
        }
        .message-bubble.user {
            background: white;
            color: #333;
            border: 1px solid #e0e0e0;
        }
        .message-bubble.admin {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .message-time {
            font-size: 11px;
            color: #999;
            margin-top: 5px;
        }
        .typing-indicator {
            display: none;
            padding: 10px 15px;
            margin-bottom: 10px;
            align-items: flex-start;
        }
        .typing-indicator.active {
            display: flex;
        }
        .typing-bubble {
            background: white;
            color: #666;
            padding: 10px 15px;
            border-radius: 18px;
            border: 1px solid #e0e0e0;
            border-bottom-left-radius: 4px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .typing-dots {
            display: flex;
            gap: 4px;
        }
        .typing-dot {
            width: 6px;
            height: 6px;
            background: #999;
            border-radius: 50%;
            animation: typingDot 1.4s infinite;
        }
        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }
        .typing-dot:nth-child(3) {
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
        .message-image {
            max-width: 70%;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 8px;
        }
        .message-image img {
            width: 100%;
            height: auto;
            display: block;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .message-image img:hover {
            transform: scale(1.02);
        }
        .chat-input-area {
            padding: 15px;
            border-top: 1px solid #e0e0e0;
            background: white;
            border-radius: 0 0 10px 10px;
            position: relative;
        }
        .chat-image-preview {
            margin-bottom: 12px;
            padding: 12px;
            background: #f8f9fa;
            border-radius: 12px;
            position: relative;
            display: none;
        }
        .chat-image-preview.active {
            display: block;
        }
        .chat-image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: 8px;
            display: block;
        }
        .chat-image-preview .remove-image {
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
        .chat-emoji-btn, .chat-image-btn {
            background: transparent;
            border: none;
            color: #667eea;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 50%;
            transition: all 0.2s;
        }
        .chat-emoji-btn:hover, .chat-image-btn:hover {
            background: #f0f0f0;
            transform: scale(1.1);
        }
        .chat-emoji-picker {
            position: absolute;
            bottom: 70px;
            left: 15px;
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
        .chat-emoji-picker.active {
            display: block;
            animation: slideUp 0.2s ease;
        }
        .chat-emoji-grid {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 8px;
        }
        .chat-emoji-item {
            font-size: 24px;
            cursor: pointer;
            padding: 8px;
            text-align: center;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .chat-emoji-item:hover {
            background: #f0f0f0;
            transform: scale(1.2);
        }
        .chat-image-input {
            display: none;
        }
        .no-chat-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #999;
        }
    </style>
</head>
<body>
    <?php include "php/admin-nav.php"; ?>

    <div class="container py-4">
        <h2 class="mb-4"><i class="fas fa-comments text-primary me-2"></i>Quản lý Chat</h2>

        <div class="chat-container">
            <!-- Chat List -->
            <div class="chat-list">
                <div class="chat-list-header">
                    <i class="fas fa-users me-2"></i>Danh sách chat
                </div>
                <div class="p-3 border-bottom">
                    <div class="input-group">
                        <input type="text" class="form-control form-control-sm" id="searchUserInput" placeholder="Tìm user..." onkeyup="searchUsers(this.value)">
                        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="showAllUsers()">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <div id="searchResults" class="mt-2" style="display: none;"></div>
                </div>
                <div style="max-height: calc(100vh - 320px); overflow-y: auto;" id="chatListContent">
                    <?php if ($active_chats != 0): ?>
                        <?php foreach ($active_chats as $chat): ?>
                            <div class="chat-item <?=$selected_user_id == $chat['user_id'] ? 'active' : ''?>" 
                                 onclick="window.location.href='admin-chat.php?user_id=<?=$chat['user_id']?>'">
                                <div class="chat-item-name">
                                    <?=htmlspecialchars($chat['full_name'])?>
                                    <?php 
                                    // Chỉ hiển thị badge nếu có tin nhắn chưa đọc và không phải user đang được chọn
                                    if ($chat['unread_count'] > 0 && $selected_user_id != $chat['user_id']): ?>
                                        <span class="chat-item-badge"><?=$chat['unread_count'] > 9 ? '9+' : $chat['unread_count']?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="chat-item-email"><?=htmlspecialchars($chat['email'])?></div>
                                <div class="chat-item-time">
                                    <?=date('d/m/Y H:i', strtotime($chat['last_message_time']))?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-comments fa-3x mb-3"></i>
                            <p>Chưa có cuộc trò chuyện nào</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chat Messages -->
            <div class="chat-messages">
                <?php if ($selected_user_id && $user): ?>
                    <div class="chat-messages-header">
                        <strong><i class="fas fa-user me-2"></i><?=htmlspecialchars($user['full_name'])?></strong>
                        <div class="small"><?=htmlspecialchars($user['email'])?></div>
                    </div>
                    <div class="chat-messages-body" id="chatBody">
                        <?php if ($messages != 0): ?>
                            <?php foreach ($messages as $msg): ?>
                                <div class="chat-message <?=$msg['is_admin'] ? 'admin' : 'user'?>">
                                    <?php if ($msg['message_type'] === 'image' && !empty($msg['image_url'])): ?>
                                        <div class="message-image">
                                            <img src="<?=htmlspecialchars($msg['image_url'])?>" alt="Image" onclick="window.open('<?=htmlspecialchars($msg['image_url'])?>', '_blank')">
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($msg['message'])): ?>
                                        <div class="message-bubble <?=$msg['is_admin'] ? 'admin' : 'user'?>" style="<?=$msg['message_type'] === 'image' && !empty($msg['image_url']) ? 'margin-top: 8px;' : ''?>">
                                            <?=htmlspecialchars($msg['message'])?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="message-time">
                                        <?=date('d/m/Y H:i', strtotime($msg['created_at']))?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-comments fa-3x mb-3"></i>
                                <p>Chưa có tin nhắn nào</p>
                            </div>
                        <?php endif; ?>
                        <div class="typing-indicator" id="typingIndicator">
                            <div class="typing-bubble">
                                <span><?=htmlspecialchars($user['full_name'])?> đang nhập</span>
                                <div class="typing-dots">
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                    <div class="typing-dot"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="chat-input-area">
                        <div class="chat-image-preview" id="chatImagePreview">
                            <button type="button" class="remove-image" onclick="removeImagePreview()">×</button>
                            <img id="chatImagePreviewImg" src="" alt="Preview">
                        </div>
                        <form id="chatForm" onsubmit="sendMessage(event)" enctype="multipart/form-data">
                            <div class="input-group">
                                <button type="button" class="chat-emoji-btn" onclick="toggleEmojiPicker()" title="Emoji">
                                    <i class="far fa-smile"></i>
                                </button>
                                <button type="button" class="chat-image-btn" onclick="document.getElementById('chatImageInput').click()" title="Gửi ảnh">
                                    <i class="fas fa-image"></i>
                                </button>
                                <input type="file" id="chatImageInput" class="chat-image-input" accept="image/*" onchange="handleImageSelect(event)">
                                <input type="text" class="form-control" id="messageInput" placeholder="Nhập tin nhắn...">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Gửi
                                </button>
                            </div>
                        </form>
                        <div class="chat-emoji-picker" id="emojiPicker">
                            <div class="chat-emoji-grid" id="emojiGrid"></div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-chat-selected">
                        <div class="text-center">
                            <i class="fas fa-comments fa-4x mb-3 text-muted"></i>
                            <p class="text-muted">Chọn một cuộc trò chuyện để bắt đầu</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        <?php if ($selected_user_id): ?>
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
            const input = document.getElementById('messageInput');
            input.value += emoji;
            input.focus();
            document.getElementById('emojiPicker').classList.remove('active');
        }
        
        // Khởi tạo emoji picker
        const emojis = ['😀', '😃', '😄', '😁', '😆', '😅', '😂', '🤣', '😊', '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘', '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪', '🤨', '🧐', '🤓', '😎', '🤩', '🥳', '😏', '😒', '😞', '😔', '😟', '😕', '🙁', '☹️', '😣', '😖', '😫', '😩', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '🤯', '😳', '🥵', '🥶', '😱', '😨', '😰', '😥', '😓', '🤗', '🤔', '🤭', '🤫', '🤥', '😶', '😐', '😑', '😬', '🙄', '😯', '😦', '😧', '😮', '😲', '🥱', '😴', '🤤', '😪', '😵', '🤐', '🥴', '🤢', '🤮', '🤧', '😷', '🤒', '🤕', '🤑', '🤠', '😈', '👿', '👹', '👺', '🤡', '💩', '👻', '💀', '☠️', '👽', '👾', '🤖', '🎃', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾'];
        
        document.addEventListener('DOMContentLoaded', function() {
            const emojiGrid = document.getElementById('emojiGrid');
            if (emojiGrid) {
                emojis.forEach(emoji => {
                    const emojiItem = document.createElement('div');
                    emojiItem.className = 'chat-emoji-item';
                    emojiItem.textContent = emoji;
                    emojiItem.onclick = () => insertEmoji(emoji);
                    emojiGrid.appendChild(emojiItem);
                });
            }
            
            // Đóng emoji picker khi click bên ngoài
            document.addEventListener('click', function(e) {
                const picker = document.getElementById('emojiPicker');
                const emojiBtn = document.querySelector('.chat-emoji-btn');
                if (picker && emojiBtn && !picker.contains(e.target) && !emojiBtn.contains(e.target)) {
                    picker.classList.remove('active');
                }
            });
        });
        
        function sendMessage(e) {
            e.preventDefault();
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message && !selectedImage) {
                return;
            }
            
            const formData = new FormData();
            formData.append('user_id', <?=$selected_user_id?>);
            if (message) {
                formData.append('message', message);
            }
            if (selectedImage) {
                formData.append('image', selectedImage);
            }
            
            input.disabled = true;
            const sendBtn = document.querySelector('#chatForm button[type="submit"]');
            sendBtn.disabled = true;
            
            fetch('php/send-admin-chat-message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addMessageToChat(message, true, selectedImage ? 'image' : 'text', data.image_url || null);
                    input.value = '';
                    removeImagePreview();
                    sendTypingStatus(false); // Dừng typing khi gửi tin nhắn
                    if (typingTimeout) clearTimeout(typingTimeout);
                    input.disabled = false;
                    sendBtn.disabled = false;
                    input.focus();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể gửi tin nhắn'));
                    input.disabled = false;
                    sendBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi gửi tin nhắn');
                input.disabled = false;
                sendBtn.disabled = false;
            });
        }

        function addMessageToChat(message, isAdmin, messageType = 'text', imageUrl = null) {
            const chatBody = document.getElementById('chatBody');
            const now = new Date();
            const timeStr = now.toLocaleString('vi-VN', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            const messageDiv = document.createElement('div');
            messageDiv.className = 'chat-message ' + (isAdmin ? 'admin' : 'user');
            
            let content = '';
            if (messageType === 'image' && imageUrl) {
                content = `
                    <div class="message-image">
                        <img src="${imageUrl}" alt="Image" onclick="window.open('${imageUrl}', '_blank')">
                    </div>
                `;
            }
            if (message) {
                content += `
                    <div class="message-bubble ${isAdmin ? 'admin' : 'user'}" style="${messageType === 'image' && imageUrl ? 'margin-top: 8px;' : ''}">
                        ${message.replace(/\n/g, '<br>')}
                    </div>
                `;
            }
            
            messageDiv.innerHTML = content + `<div class="message-time">${timeStr}</div>`;
            
            chatBody.appendChild(messageDiv);
            chatBody.scrollTop = chatBody.scrollHeight;
        }

        let typingCheckInterval = null;
        let typingTimeout = null;
        let isTyping = false;
        
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
            fetch('php/get-typing-status.php?user_id=<?=$selected_user_id?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const indicator = document.getElementById('typingIndicator');
                        if (data.is_typing) {
                            indicator.classList.add('active');
                            const chatBody = document.getElementById('chatBody');
                            chatBody.scrollTop = chatBody.scrollHeight;
                        } else {
                            indicator.classList.remove('active');
                        }
                    }
                })
                .catch(error => console.error('Error checking typing status:', error));
        }
        
        // Track typing khi admin gõ
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                sendTypingStatus(true);
                // Clear timeout cũ
                if (typingTimeout) clearTimeout(typingTimeout);
                // Tự động dừng typing sau 2 giây không gõ
                typingTimeout = setTimeout(() => {
                    sendTypingStatus(false);
                }, 2000);
            });
            
            messageInput.addEventListener('blur', function() {
                sendTypingStatus(false);
                if (typingTimeout) clearTimeout(typingTimeout);
            });
        }
        
        // Kiểm tra typing status mỗi giây
        typingCheckInterval = setInterval(checkTypingStatus, 1000);
        
        // Auto refresh messages every 3 seconds
        setInterval(() => {
            fetch('php/get-admin-chat-messages.php?user_id=<?=$selected_user_id?>')
                .then(response => response.json())
            .then(data => {
                if (data.success && data.messages) {
                    const chatBody = document.getElementById('chatBody');
                    const currentMessageCount = chatBody.querySelectorAll('.chat-message').length;
                    
                    if (data.messages.length > currentMessageCount) {
                        // Reload để hiển thị tin nhắn mới (bao gồm ảnh)
                        location.reload();
                    }
                }
            })
                .catch(error => console.error('Error fetching messages:', error));
        }, 3000);

        // Scroll to bottom on load
        window.addEventListener('load', () => {
            const chatBody = document.getElementById('chatBody');
            if (chatBody) {
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        });
        <?php endif; ?>

        function searchUsers(keyword) {
            const searchResults = document.getElementById('searchResults');
            const chatListContent = document.getElementById('chatListContent');
            
            if (keyword.trim().length < 2) {
                searchResults.style.display = 'none';
                chatListContent.style.display = 'block';
                return;
            }
            
            fetch('php/search-users.php?keyword=' + encodeURIComponent(keyword))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users && data.users.length > 0) {
                        let html = '<div class="list-group list-group-flush">';
                        data.users.forEach(user => {
                            html += `<a href="admin-chat.php?user_id=${user.id}" class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <div>
                                        <h6 class="mb-1">${user.full_name}</h6>
                                        <small class="text-muted">${user.email}</small>
                                    </div>
                                </div>
                            </a>`;
                        });
                        html += '</div>';
                        searchResults.innerHTML = html;
                        searchResults.style.display = 'block';
                        chatListContent.style.display = 'none';
                    } else {
                        searchResults.innerHTML = '<div class="text-muted text-center py-2"><small>Không tìm thấy user</small></div>';
                        searchResults.style.display = 'block';
                        chatListContent.style.display = 'none';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        }

        function showAllUsers() {
            const searchInput = document.getElementById('searchUserInput');
            const searchResults = document.getElementById('searchResults');
            const chatListContent = document.getElementById('chatListContent');
            
            searchInput.value = '';
            searchResults.style.display = 'none';
            chatListContent.style.display = 'block';
        }
    </script>
</body>
</html>

