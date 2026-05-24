<!-- AI Chat Window -->
<div id="stepup-asst-window">
    <div id="stepup-asst-header">
        <div class="title">
            <div>
                AI StepUp
            </div>
        </div>
        <div style="display:flex;align-items:center;gap:8px">
            <button id="stepup-asst-close" onclick="toggleStepupAsst()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    <div id="stepup-asst-loading-bar"><div></div></div>
    <div id="stepup-asst-messages">
        <!-- Messages populated by JS -->
    </div>
    <div id="stepup-asst-preview-area" style="display:none; padding: 8px 10px; background: rgba(0,0,0,0.04); border-top: 1px solid rgba(0,0,0,0.08); position:relative; display:none;">
        <div style="position:relative; display:inline-block;">
            <img id="stepup-asst-image-preview" src="" style="max-height: 80px; max-width: 120px; border-radius: 8px; display:block; object-fit:cover; border:2px solid rgba(0,0,0,0.1);">
            <button onclick="clearImagePreview()" style="position:absolute; top:-8px; right:-8px; background:#ef4444; color:white; border:none; border-radius:50%; width:20px; height:20px; cursor:pointer; font-size:12px; line-height:1; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(0,0,0,0.2);">&times;</button>
        </div>
    </div>
    <div id="stepup-asst-input-area">
        <input type="file" id="stepup-asst-file" style="display:none" accept="image/*" onchange="handleFileSelect(event)">
        <button onclick="document.getElementById('stepup-asst-file').click()" title="Lampirkan Gambar">
            <i class="fas fa-image"></i>
        </button>
        <input type="text" id="stepup-asst-input" placeholder="Tanya sesuatu..." onkeypress="if(event.key==='Enter')sendStepupAsst()">
        <button id="stepup-asst-submit" onclick="sendStepupAsst()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
(function() {
    // ─── Config ───────────────────────────────────────
    // Dynamic path for Gemini
    const is_admin_page = window.location.pathname.includes('/admin/');
    const GEMINI_API  = is_admin_page ? '../views/chat_handler.php' : 'chat_handler.php';
    const CHAT_API    = is_admin_page ? '../api/chat.php' : '../api/chat.php'; // already handled relative to root mostly
    const SYSTEM_MSG  = { role: 'system', content: 'Kamu adalah Gemini, asisten cerdas untuk platform LMS StepUp.' };

    let chatContext = [SYSTEM_MSG];
    let chatLoaded  = false;
    let chatOpen    = false;

    // ─── DOM Refs ─────────────────────────────────────
    const getWin  = () => document.getElementById('stepup-asst-window');
    const getMsgs = () => document.getElementById('stepup-asst-messages');
    const getBtn  = () => document.getElementById('stepup-asst-submit');
    const getInp  = () => document.getElementById('stepup-asst-input');
    const getLoad = () => document.getElementById('stepup-asst-loading-bar');

    // ─── Load chat from DB ────────────────────────────
    async function loadChatFromDB() {
        if (chatLoaded) return;
        chatLoaded = true;
        try {
            const r = await fetch(CHAT_API);
            const d = await r.json();
            if (d.success && d.history && d.history.length > 0) {
                getMsgs().innerHTML = '';
                chatContext = [SYSTEM_MSG];
                d.history.forEach(function(row) {
                    addMsgToDOM(row.message, 'user', false, row.created_at);
                    addMsgToDOM(row.response, 'bot',  false, row.created_at);
                    chatContext.push({ role: 'user',      content: row.message  });
                    chatContext.push({ role: 'assistant', content: row.response });
                });
                scrollToBottom();
            } else {
                showWelcome();
            }
        } catch (e) {
            // If DB fails, just show welcome
            showWelcome();
        }
    }

    function showWelcome() {
        const msgsEl = getMsgs();
        if (msgsEl && msgsEl.children.length === 0) {
            addMsgToDOM('Halo saya Gemini, siap membantu kamu sebagai asisten LMS!', 'bot', false);
        }
    }

    // ─── Save exchange to DB ──────────────────────────
    async function saveToDB(userMsg, aiMsg) {
        try {
            await fetch(CHAT_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ message: userMsg, response: aiMsg })
            });
        } catch(e) { /* Silent fail – chat still works */ }
    }

    // ─── Toggle chat open/close ───────────────────────
    window.toggleStepupAsst = function() {
        const w = getWin();
        chatOpen = !chatOpen;
        if (chatOpen) {
            w.style.display = 'flex';
            w.style.animation = 'chat-slide-in 0.3s cubic-bezier(0.175,0.885,0.32,1.275) both';
            // Hide notif dot
            const dot = document.getElementById('chatNotifDot');
            if (dot) dot.style.display = 'none';
            // Load history from DB on first open
            loadChatFromDB();
            setTimeout(function() { const inp = getInp(); if(inp) inp.focus(); }, 150);
        } else {
            w.style.display = 'none';
        }
    };

    // ─── Clear chat ───────────────────────────────────
    window.clearStepupAsst = function() {
        if (!confirm('Hapus semua riwayat chat? Chat di database tetap tersimpan, hanya tampilan yang dibersihkan.')) return;
        chatContext = [SYSTEM_MSG];
        chatLoaded  = false;
        const msgsEl = getMsgs();
        if (msgsEl) msgsEl.innerHTML = '';
        showWelcome();
    };

    let attachedImage = null;

    window.handleFileSelect = function(e) {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(event) {
            attachedImage = event.target.result;
            const previewArea = document.getElementById('stepup-asst-preview-area');
            const previewImg  = document.getElementById('stepup-asst-image-preview');
            previewImg.src = attachedImage;
            previewArea.style.display = 'block';
        };
        reader.readAsDataURL(file);
    };

    window.clearImagePreview = function() {
        attachedImage = null;
        document.getElementById('stepup-asst-preview-area').style.display = 'none';
        document.getElementById('stepup-asst-file').value = '';
    };

    // ─── Send message ─────────────────────────────────
    window.sendStepupAsst = async function() {
        const inp = getInp();
        const msg = inp ? inp.value.trim() : '';
        if (!msg && !attachedImage) return;

        inp.value = '';
        const btn = getBtn();
        if (btn) btn.disabled = true;

        const currentImage = attachedImage; // Capture for sending
        clearImagePreview();

        addMsgWithImage(msg, currentImage);
        chatContext.push({ role: 'user', content: msg });

        // Typing indicator
        const typingEl = document.createElement('div');
        typingEl.className = 'chat-msg bot typing-indicator';
        typingEl.innerHTML = '<span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span>';
        const msgsEl = getMsgs();
        if (msgsEl) msgsEl.appendChild(typingEl);
        scrollToBottom();

        // Loading bar
        const loadBar = getLoad();
        if (loadBar) loadBar.classList.add('active');

        try {
            const formData = new FormData();
            formData.append('message', msg);
            if (currentImage) formData.append('image', currentImage);

            const res  = await fetch(GEMINI_API, {
                method: 'POST',
                body: formData
            });
            const textResponse = await res.text();
            
            // Extract JSON from potential PHP garbage (wrappers like <!--JSON_START-->)
            let data = {};
            try {
                const jsonMatch = textResponse.match(/<!--JSON_START-->(.*)<!--JSON_END-->/);
                if (jsonMatch) {
                    data = JSON.parse(jsonMatch[1]);
                } else {
                    data = JSON.parse(textResponse);
                }
            } catch(e) {
                console.error("JSON Parse Error:", textResponse);
                throw new Error("Format respon server tidak valid.");
            }

            // Remove typing
            const tyEl = document.querySelector('.typing-indicator');
            if (tyEl) tyEl.remove();
            if (loadBar) loadBar.classList.remove('active');

            if (data.error) {
                let errMsg = data.error;
                if (typeof errMsg === 'object' && errMsg.message) errMsg = errMsg.message;
                else if (typeof errMsg === 'object') errMsg = JSON.stringify(errMsg);
                addMsgToDOM('⚠️ ' + errMsg, 'bot', true);
                chatContext.pop();
            } else if (data && data.reply) {
                const reply = data.reply;
                addMsgToDOM(reply, 'bot', true);
                chatContext.push({ role: 'assistant', content: reply });
                // Save to database
                saveToDB(msg, reply);
            } else {
                addMsgToDOM('Maaf, saya sedang mengalami kendala koneksi ke server AI.', 'bot', true);
                chatContext.pop();
            }
        } catch(err) {
            const tyEl = document.querySelector('.typing-indicator');
            if (tyEl) tyEl.remove();
            if (loadBar) loadBar.classList.remove('active');
            addMsgToDOM('Koneksi ke AI gagal. Coba lagi sebentar lagi. 🔌', 'bot', true);
            chatContext.pop();
        }

        if (btn) btn.disabled = false;
        const inp2 = getInp();
        if (inp2) inp2.focus();
    };

    // ─── Add message to DOM ───────────────────────────
    function addMsgToDOM(text, type, shouldScroll, rawTime) {
        const msgsEl = getMsgs();
        if (!msgsEl) return;
        const div = document.createElement('div');
        div.className = 'chat-msg ' + type;
        const escaped = text.replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
        let timeHtml = '';
        if (rawTime) {
            const d = new Date(rawTime);
            const hh = String(d.getHours()).padStart(2,'0');
            const mm = String(d.getMinutes()).padStart(2,'0');
            timeHtml = '<span class="chat-time">' + hh + ':' + mm + '</span>';
        }
        div.innerHTML = escaped + timeHtml;
        msgsEl.appendChild(div);
        if (shouldScroll) scrollToBottom();
    }

    // ─── Add message with optional image thumbnail ────
    function addMsgWithImage(text, imageDataUrl) {
        const msgsEl = getMsgs();
        if (!msgsEl) return;
        const div = document.createElement('div');
        div.className = 'chat-msg user';

        const now = new Date();
        const hh = String(now.getHours()).padStart(2,'0');
        const mm = String(now.getMinutes()).padStart(2,'0');
        const timeHtml = '<span class="chat-time">' + hh + ':' + mm + '</span>';

        let content = '';
        if (text) {
            content += '<span>' + text.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</span>';
        }
        if (imageDataUrl) {
            content += '<div style="margin-top:6px;">'
                + '<img src="' + imageDataUrl + '" style="'
                + 'max-width:120px; max-height:90px; border-radius:8px;'
                + 'object-fit:cover; display:block; border:2px solid rgba(255,255,255,0.3);'
                + '" />'
                + '</div>';
        }
        div.innerHTML = content + timeHtml;
        msgsEl.appendChild(div);
        scrollToBottom();
    }

    function scrollToBottom() {
        const m = getMsgs();
        if (m) m.scrollTop = m.scrollHeight;
    }

    // Show welcome on initial load (DB will override when opened)
    document.addEventListener('DOMContentLoaded', function() {
        showWelcome();
    });
})();
</script>

