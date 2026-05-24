<style>
#stepup-asst-bubble-btn {
    position: fixed !important;
    bottom: 35px !important;
    right: 30px !important;
    width: 65px !important;
    height: 65px !important;
    border-radius: 50% !important;
    background: linear-gradient(135deg, #2563eb, #8b5cf6) !important;
    color: white !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 28px !important;
    box-shadow: 0 10px 25px rgba(37,99,235,0.4) !important;
    cursor: pointer !important;
    z-index: 2147483647 !important;
    pointer-events: auto !important;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) !important;
    border: none !important;
    outline: none !important;
    visibility: visible !important;
    opacity: 1 !important;
    transform: none !important;
}
#stepup-asst-bubble-btn:hover {
    transform: scale(1.12) rotate(5deg) !important;
    box-shadow: 0 16px 35px rgba(37,99,235,0.55) !important;
}
#stepup-asst-bubble-btn .chat-notif-dot {
    position: absolute;
    top: 4px;
    right: 4px;
    width: 12px;
    height: 12px;
    background: #ef4444;
    border-radius: 50%;
    border: 2px solid white;
    display: none;
    animation: ping-notif 1.5s ease-in-out infinite;
}
@keyframes ping-notif {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.4); opacity: 0.7; }
}
#stepup-asst-window {
    position: fixed !important;
    bottom: 100px !important;
    right: 25px !important;
    width: 360px !important;
    height: 520px !important;
    max-height: calc(100vh - 140px) !important;
    max-width: calc(100vw - 40px) !important;
    background: #ffffff !important;
    border-radius: 20px !important;
    box-shadow: 0 15px 40px rgba(0,0,0,0.18) !important;
    display: none;
    flex-direction: column;
    z-index: 999999 !important;
    overflow: hidden !important;
    border: 1px solid #e2e8f0;
    font-family: 'Plus Jakarta Sans', sans-serif;
    animation: chat-slide-in 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
}
@keyframes chat-slide-in {
    from { opacity: 0; transform: translateY(20px) scale(0.95); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}
.dark #stepup-asst-window {
    background: #0f172a !important;
    border-color: #1e293b;
    box-shadow: 0 15px 40px rgba(0,0,0,0.6);
}
#stepup-asst-header {
    background: linear-gradient(135deg, #2563eb, #8b5cf6);
    padding: 16px 20px;
    color: white;
    font-weight: 800;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 15px;
    flex-shrink: 0;
}
#stepup-asst-header .title {
    display: flex;
    align-items: center;
    gap: 10px;
}
#stepup-asst-header .title small {
    font-size: 10px;
    font-weight: 600;
    opacity: 0.8;
    display: block;
    letter-spacing: 0.05em;
}
#stepup-asst-close {
    cursor: pointer;
    background: rgba(255,255,255,0.2);
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: 0.2s;
    font-size: 14px;
    border: none;
    color: white;
}
#stepup-asst-close:hover { background: rgba(255,255,255,0.4); }
#stepup-asst-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
    display: flex;
    flex-direction: column;
    gap: 10px;
    scroll-behavior: smooth;
}
#stepup-asst-messages::-webkit-scrollbar { width: 4px; }
#stepup-asst-messages::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
.dark #stepup-asst-messages::-webkit-scrollbar-thumb { background: #334155; }
#stepup-asst-loading-bar {
    width: 100%;
    height: 2px;
    background: rgba(255,255,255,0.3);
    position: relative;
    overflow: hidden;
    display: none;
}
#stepup-asst-loading-bar.active { display: block; }
#stepup-asst-loading-bar::after {
    content: '';
    position: absolute;
    left: -50%;
    width: 50%;
    height: 100%;
    background: white;
    animation: chat-loading 1s linear infinite;
}
@keyframes chat-loading {
    0% { left: -50%; } 100% { left: 150%; }
}
.chat-msg {
    max-width: 88%;
    padding: 11px 15px;
    border-radius: 18px;
    font-size: 13.5px;
    line-height: 1.55;
    word-wrap: break-word;
    font-weight: 500;
}
.chat-msg.bot {
    background: #f1f5f9;
    color: #1e293b;
    border-bottom-left-radius: 4px;
    align-self: flex-start;
}
.dark .chat-msg.bot {
    background: #1e293b;
    color: #f8fafc;
}
.chat-msg.user {
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: white;
    border-bottom-right-radius: 4px;
    align-self: flex-end;
}
.chat-msg .chat-time {
    font-size: 9px;
    opacity: 0.55;
    display: block;
    margin-top: 4px;
    font-weight: 600;
}
#stepup-asst-input-area {
    padding: 12px 16px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    gap: 8px;
    background: #f8fafc;
    flex-shrink: 0;
}
.dark #stepup-asst-input-area {
    border-top-color: #1e293b;
    background: #0a1128;
}
#stepup-asst-input {
    flex: 1;
    padding: 11px 16px;
    border: 1.5px solid #cbd5e1;
    border-radius: 25px;
    outline: none;
    font-size: 13.5px;
    background: white;
    color: #0f172a;
    transition: 0.2s;
    font-family: inherit;
}
.dark #stepup-asst-input {
    background: #0f172a;
    border-color: #334155;
    color: #f8fafc;
}
#stepup-asst-input:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 3px rgba(37,99,235,0.12);
}
#stepup-asst-submit {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #8b5cf6);
    color: white;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
    font-size: 15px;
    flex-shrink: 0;
}
#stepup-asst-submit:hover { transform: scale(1.08); box-shadow: 0 6px 16px rgba(37,99,235,0.4); }
#stepup-asst-submit:disabled { background: #94a3b8; cursor: not-allowed; transform: scale(1); box-shadow: none; }
.typing-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    background: #94a3b8;
    border-radius: 50%;
    margin: 0 2px;
    animation: typing 1.4s infinite ease-in-out both;
}
.typing-dot:nth-child(1) { animation-delay: -0.32s; }
.typing-dot:nth-child(2) { animation-delay: -0.16s; }
@keyframes typing {
    0%, 80%, 100% { transform: scale(0); opacity: 0.4; }
    40% { transform: scale(1); opacity: 1; }
}
#stepup-asst-clear-btn {
    font-size: 10px;
    color: rgba(255,255,255,0.6);
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    transition: color 0.2s;
}
#stepup-asst-clear-btn:hover { color: rgba(255,255,255,1); }
@media print {
    #stepup-asst-bubble-btn, #stepup-asst-window { display: none !important; }
}
</style>

<!-- AI Chat Bubble Button -->
<button id="stepup-asst-bubble-btn" onclick="toggleStepupAsst()" title="AI StepUp - Tanya Apapun!">
    <i class="fas fa-comment-dots"></i>
    <span class="chat-notif-dot" id="chatNotifDot"></span>
</button>


