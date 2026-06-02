<?php
/**
 * @var int $orderId
 * @var int $receiverId
 * @var string $receiverRole
 */
?>
<div id="chat-widget" style="position: fixed; bottom: 20px; right: 20px; width: 320px; background: #fff; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.15); z-index: 9999; display: flex; flex-direction: column; overflow: hidden; border: 1px solid var(--border-color);">
    <div style="background: var(--primary); color: #fff; padding: 12px 15px; cursor: pointer; display: flex; justify-content: space-between; align-items: center;" onclick="toggleChat()">
        <div style="display: flex; align-items: center; gap: 8px;">
            <span class="material-symbols-outlined">chat</span>
            <strong id="chat-header-title" style="font-size: 14px;">Chat với <?= app_e($receiverRole) ?></strong>
        </div>
        <span class="material-symbols-outlined" id="chat-toggle-icon">expand_less</span>
    </div>
    
    <div id="chat-body" style="display: none; flex-direction: column; height: 350px;">
        <div id="chat-messages" style="flex: 1; padding: 15px; overflow-y: auto; background: #f8fafc; display: flex; flex-direction: column; gap: 10px;">
        </div>
        <div style="padding: 10px; border-top: 1px solid var(--border-color); background: #fff; display: flex; gap: 8px;">
            <input type="text" id="chat-input" placeholder="Nhập tin nhắn..." autocomplete="off" style="flex: 1; padding: 10px; border: 1px solid var(--border-color); border-radius: 4px; outline: none; font-size: 13px;" onkeypress="if(event.key === 'Enter') sendChatMessage()">
            <button onclick="sendChatMessage()" style="background: var(--primary); color: #fff; border: none; width: 38px; height: 38px; border-radius: 4px; cursor: pointer; display: flex; align-items: center; justify-content: center; flex-shrink: 0;"><span class="material-symbols-outlined" style="font-size: 16px;">send</span></button>
        </div>
    </div>
</div>

<script>
    const chatOrderId = <?= (int) $orderId ?>;
    const chatReceiverId = <?= (int) $receiverId ?>;
    let chatInterval = null;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    }

    function toggleChat() {
        const body = document.getElementById('chat-body');
        const icon = document.getElementById('chat-toggle-icon');
        const title = document.getElementById('chat-header-title');
        
        if (body.style.display === 'none' || body.style.display === '') {
            body.style.display = 'flex';
            icon.textContent = 'expand_more';
            if (title) { title.style.color = '#fff'; title.innerText = 'Chat với <?= app_e($receiverRole) ?>'; }
            loadChatMessages();
            chatInterval = setInterval(loadChatMessages, 3000);
        } else {
            body.style.display = 'none';
            icon.textContent = 'expand_less';
            clearInterval(chatInterval);
        }
    }

    async function loadChatMessages() {
        try {
            const res = await fetch(`/api/chat/${chatOrderId}`);
            const data = await res.json();
            if (data.success) {
                const box = document.getElementById('chat-messages');
                const isAtBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 10;
                let html = '';
                data.messages.forEach(m => {
                    const isMe = Number(m.sender_id) === Number(data.current_user_id);
                    html += `<div style="max-width: 85%; padding: 8px 12px; border-radius: 4px; font-size: 13px; align-self: ${isMe ? 'flex-end' : 'flex-start'}; background: ${isMe ? 'var(--primary)' : '#e2e8f0'}; color: ${isMe ? '#fff' : 'var(--text-main)'}; border-bottom-${isMe ? 'right' : 'left'}-radius: 0;">${escapeHtml(m.message)}</div>`;
                });
                box.innerHTML = html || '<div style="text-align: center; color: var(--text-muted); font-size: 13px; margin: auto;">Chưa có tin nhắn nào.</div>';
                if (isAtBottom) box.scrollTop = box.scrollHeight;
            }
        } catch(e) { console.error("Lỗi tải tin nhắn:", e); }
    }

    async function sendChatMessage() {
        const input = document.getElementById('chat-input');
        const msg = input.value.trim();
        if (!msg) return;
        input.value = '';
        await fetch(`/api/chat/${chatOrderId}`, { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({receiver_id: chatReceiverId, message: msg}) });
        loadChatMessages();
    }
</script>