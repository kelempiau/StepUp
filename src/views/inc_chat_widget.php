<?php
// src/views/inc_chat_widget.php
?>
<!-- Floating Chat Bubble -->
<div id="aiChatBubble" class="fixed bottom-6 right-6 z-[9990] animate__animated animate__bounceIn">
    <button onclick="toggleChatPopup()" class="w-16 h-16 bg-gradient-to-tr from-blue-600 to-blue-800 rounded-full shadow-2xl shadow-blue-500/40 text-white flex items-center justify-center text-3xl hover:scale-110 transition-transform duration-300 relative group">
        <i class="fas fa-robot"></i>
        <div class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 rounded-full border-2 border-white"></div>
        <!-- Tooltip -->
        <div class="absolute right-20 bg-white dark:bg-slate-800 text-slate-800 dark:text-white px-4 py-2 rounded-xl shadow-xl text-xs font-bold w-max opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none transform translate-x-2 group-hover:translate-x-0">
            Tanya Asisten Belajar
            <div class="absolute top-1/2 -right-1 w-2 h-2 bg-white dark:bg-slate-800 transform -translate-y-1/2 rotate-45"></div>
        </div>
    </button>
</div>

<!-- Chat Popup Interface -->
<div id="aiChatPopup" class="fixed bottom-24 right-6 w-[90vw] md:w-[400px] h-[600px] max-h-[80vh] bg-white dark:bg-slate-900 rounded-[2rem] shadow-2xl border border-slate-100 dark:border-slate-800 z-[9999] hidden flex flex-col overflow-hidden animate__animated animate__fadeInUp origin-bottom-right">
    
    <!-- Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-6 flex justify-between items-center shrink-0">
        <div class="flex items-center space-x-4">
            <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-white text-xl backdrop-blur-sm">
                <i class="fas fa-robot"></i>
            </div>
            <div>
                <h3 class="font-black text-white text-lg leading-none">Gemini AI</h3>
                <p class="text-blue-100 text-xs font-medium mt-1">Asisten LMS 24/7</p>
            </div>
        </div>
        <button onclick="toggleChatPopup()" class="text-white/80 hover:text-white transition transform hover:rotate-90">
            <i class="fas fa-times text-xl"></i>
        </button>
    </div>

    <!-- Chat Area -->
    <div id="chatMessages" class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50 dark:bg-slate-950 scroll-smooth custom-scrollbar">
        <!-- Welcome Message -->
        <div class="flex items-start space-x-3">
            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-xs shrink-0">
                <i class="fas fa-robot"></i>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 rounded-2xl rounded-tl-none shadow-sm max-w-[85%]">
                <p class="text-sm text-slate-700 dark:text-slate-300 leading-relaxed">
                    Halo saya Gemini, siap membantu kamu sebagai asisten LMS!
                </p>
            </div>
        </div>
    </div>

    <!-- Input Area -->
    <div class="p-4 bg-white dark:bg-slate-900 border-t border-slate-100 dark:border-slate-800 shrink-0">
        <form id="chatForm" onsubmit="handleChatSubmit(event)" class="relative">
            <input type="text" id="chatInput" placeholder="Ketik pertanyaanmu..." autocomplete="off"
                class="w-full pl-5 pr-14 py-4 bg-slate-50 dark:bg-slate-800 rounded-2xl border-none focus:ring-2 focus:ring-blue-500 text-slate-800 dark:text-white placeholder-slate-400 font-medium">
            <button type="submit" class="absolute right-2 top-2 bottom-2 w-10 bg-blue-600 text-white rounded-xl flex items-center justify-center hover:bg-blue-700 transition shadow-lg shadow-blue-500/20">
                <i class="fas fa-paper-plane text-xs"></i>
            </button>
        </form>
        <p class="text-[10px] text-center text-slate-400 dark:text-slate-500 mt-3 font-medium">
            AI by StepUp
        </p>
    </div>
</div>

<script>
    const SYSTEM_INSTRUCTION = "Kamu adalah Gemini, asisten cerdas untuk platform LMS. Jawablah pertanyaan terkait materi pelajaran dan penggunaan sistem LMS dengan ramah, suportif, dan mudah dimengerti. Gunakan Bahasa Indonesia. Jawaban singkat dan jelas.";

    // Global function to control visibility
    window.toggleChatVisibility = function(show) {
        const bubble = document.getElementById('aiChatBubble');
        const popup = document.getElementById('aiChatPopup');
        
        if (show) {
            bubble.classList.remove('hidden');
        } else {
            bubble.classList.add('hidden');
            popup.classList.add('hidden');
        }
    };

    function toggleChatPopup() {
        const popup = document.getElementById('aiChatPopup');
        popup.classList.toggle('hidden');
        
        if (!popup.classList.contains('hidden')) {
            document.getElementById('chatInput').focus();
            scrollToBottom();
        }
    }

    function scrollToBottom() {
        const chatMsgs = document.getElementById('chatMessages');
        chatMsgs.scrollTop = chatMsgs.scrollHeight;
    }

    async function handleChatSubmit(e) {
        e.preventDefault();
        const input = document.getElementById('chatInput');
        const message = input.value.trim();
        if (!message) return;

        // Add User Message
        appendMessage('user', message);
        input.value = '';

        // Add Loading Indicator
        const loadingId = appendLoading();
        scrollToBottom();

        try {
            const apiPath = 'chat_handler.php'; 

            const formData = new FormData();
            formData.append('message', message);

            const response = await fetch(apiPath, {
                method: 'POST',
                body: formData
            });

            const rawText = await response.text();
            console.log("Raw Response:", rawText);
            
            // Handle magic markers to bypass free hosting ads/scripts
            let jsonText = "";
            if (rawText.includes("<!--JSON_START-->") && rawText.includes("<!--JSON_END-->")) {
                jsonText = rawText.split("<!--JSON_START-->")[1].split("<!--JSON_END-->")[0];
            } else {
                // Try to find the first '{' and last '}' as a fallback
                const start = rawText.indexOf('{');
                const end = rawText.lastIndexOf('}');
                if (start !== -1 && end !== -1 && end > start) {
                    jsonText = rawText.substring(start, end + 1);
                } else {
                    throw new Error("Format respon server tidak valid");
                }
            }

            const data = JSON.parse(jsonText.trim());
            
            // Remove Loading
            const loadingEl = document.getElementById(loadingId);
            if (loadingEl) loadingEl.remove();

            if (data.success) {
                appendMessage('ai', data.reply || data.response);
            } else {
                appendMessage('ai', data.reply || data.error || "Maaf, terjadi kesalahan sistem.");
            }

        } catch (error) {
            console.error("Chat Error:", error);
            const loadingEl = document.getElementById(loadingId);
            if (loadingEl) loadingEl.remove();
            appendMessage('ai', `Maaf, sepertinya ada sedikit kendala teknis. Harap pastikan koneksi internet Anda stabil atau coba refresh halaman. Jika masalah berlanjut, hubungi administrator.`);
        }

        scrollToBottom();
    }

    function appendMessage(sender, text) {
        const container = document.getElementById('chatMessages');
        const isUser = sender === 'user';
        
        const div = document.createElement('div');
        div.className = `flex items-end space-x-3 ${isUser ? 'justify-end' : ''} animate__animated animate__fadeIn`;
        
        const avatarHtml = isUser 
            ? `` 
            : `<div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-xs shrink-0"><i class="fas fa-robot"></i></div>`;

        div.innerHTML = `
            ${!isUser ? avatarHtml : ''}
            <div class="${isUser ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-slate-700 dark:text-slate-300 rounded-tl-none'} p-4 rounded-2xl shadow-sm max-w-[85%] text-sm leading-relaxed">
                ${text.replace(/\n/g, '<br>')}
            </div>
            ${isUser ? '<div class="w-8 h-8 rounded-full bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-xs shrink-0 text-slate-500"><i class="fas fa-user"></i></div>' : ''}
        `;
        
        container.appendChild(div);
    }

    function appendLoading() {
        const container = document.getElementById('chatMessages');
        const id = 'loading-' + Date.now();
        
        const div = document.createElement('div');
        div.id = id;
        div.className = "flex items-start space-x-3 animate__animated animate__fadeIn";
        div.innerHTML = `
            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center text-xs shrink-0">
                <i class="fas fa-robot"></i>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 p-4 rounded-2xl rounded-tl-none shadow-sm">
                <div class="flex space-x-1">
                    <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce"></div>
                    <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.1s"></div>
                    <div class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay: 0.2s"></div>
                </div>
            </div>
        `;
        
        container.appendChild(div);
        return id;
    }

    // Load History on Start
    async function loadChatHistory() {
        try {
            const apiPath = 'content_fetcher.php';
            const response = await fetch(apiPath);
            if (response.ok) {
                const rawText = await response.text();
                
                let jsonText = rawText;
                if (rawText.includes("<!--JSON_START-->")) {
                    jsonText = rawText.split("<!--JSON_START-->")[1].split("<!--JSON_END-->")[0];
                }

                const data = JSON.parse(jsonText);
                if (data.success && data.history) {
                    data.history.forEach(item => {
                        if (item.message) appendMessage('user', item.message);
                        if (item.response) appendMessage('ai', item.response);
                    });
                    scrollToBottom();
                }
            }
        } catch (e) {
            console.warn("Failed to load history:", e);
        }
    }

    // Initialize
    loadChatHistory();
</script>
