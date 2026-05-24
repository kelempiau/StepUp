<!-- AI Chat Window -->
<div id="stepup-asst-window">
    <div id="stepup-asst-header">
        <div class="title">
            <i class="fas fa-robot text-xl"></i>
            <div>
                AI StepUp
                <small>Asisten Belajar Pribadi</small>
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
    <div id="stepup-asst-input-area">
        <input type="text" id="stepup-asst-input" placeholder="Tanya sesuatu..." onkeypress="if(event.key==='Enter')sendStepupAsst()">
        <button id="stepup-asst-submit" onclick="sendStepupAsst()"><i class="fas fa-paper-plane"></i></button>
    </div>
</div>

<script>
(function() {
    // ─── Config ───────────────────────────────────────
    const CHAT_API    = '../api/chat.php';      // works from src/views/ and src/admin/
    const GROQ_API    = '../api/groq_proxy.php';
    const SYSTEM_MSG  = { role: 'system', content: 'Kamu adalah asisten AI dari e-learning StepUp. Berikan jawaban yang singkat, ramah, dan sangat membantu. Gunakan bahasa Indonesia. Jangan gunakan markdown rumit karena output ditampilkan langsung sebagai teks biasa.' };

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
            addMsgToDOM('Halo! Saya asisten AI StepUp. Tanyakan apa saja tentang pelajaran, jadwal, atau motivasi belajarmu! 😊', 'bot', false);
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

    // ─── Send message ─────────────────────────────────
    window.sendStepupAsst = async function() {
        const inp = getInp();
        const msg = inp ? inp.value.trim() : '';
        if (!msg) return;

        inp.value = '';
        const btn = getBtn();
        if (btn) btn.disabled = true;

        addMsgToDOM(msg, 'user', true);
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
            const res  = await fetch(GROQ_API, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ messages: chatContext })
            });
            const data = await res.json();

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
            } else if (data && data.choices && data.choices.length > 0) {
                const reply = data.choices[0].message.content;
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

