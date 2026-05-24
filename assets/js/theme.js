// assets/js/theme.js
(function injectGlobalStyles() {
    const style = document.createElement('style');
    style.innerHTML = `
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        :root {
            --swal-bg: rgba(255, 255, 255, 0.98);
            --swal-border: rgba(255, 255, 255, 0.8);
            --swal-shadow: rgba(2, 6, 23, 0.12);
            --swal-text: #0f172a;
            --swal-accent: #2563eb;
            --swal-success: #10b981;
        }

        .dark {
            --swal-bg: rgba(13, 21, 38, 0.98);
            --swal-border: rgba(255, 255, 255, 0.08);
            --swal-shadow: rgba(0, 0, 0, 0.45);
            --swal-text: #f8fafc;
            --swal-accent: #3b82f6;
            --swal-success: #059669;
        }

        /* ─── Premium Modern UI Overhaul ─── */
        .swal2-container {
            backdrop-filter: blur(12px) !important;
            -webkit-backdrop-filter: blur(12px) !important;
            z-index: 100000 !important;
        }

        .swal2-popup {
            background: var(--swal-bg) !important;
            border: 1px solid var(--swal-border) !important;
            border-radius: 2.5rem !important;
            box-shadow: 0 40px 100px -20px var(--swal-shadow) !important;
            padding: 4.5rem 2rem 2.5rem !important;
            font-family: 'Plus Jakarta Sans', sans-serif !important;
            width: 95% !important;
            max-width: 440px !important;
            overflow: visible !important;
        }

        /* Adjustments for TOAST Popups */
        .swal2-popup.swal2-toast {
            padding: 1rem 1.5rem !important;
            border-radius: 1.5rem !important;
            width: auto !important;
            max-width: 350px !important;
            margin-top: 1rem !important;
            margin-right: 1rem !important;
            overflow: hidden !important;
        }
        .swal2-popup.swal2-toast .swal2-icon {
            width: 32px !important;
            height: 32px !important;
            margin: 0 1rem 0 0 !important;
            background: transparent !important;
            box-shadow: none !important;
            border-radius: 0.5rem !important;
        }
        .swal2-popup.swal2-toast .swal2-title {
            font-size: 1rem !important;
            margin-bottom: 0 !important;
            text-align: left !important;
        }
        .swal2-popup.swal2-toast .swal2-html-container {
            font-size: 0.875rem !important;
            margin: 0.25rem 0 0 !important;
            text-align: left !important;
        }

        /* Animations */
        @keyframes swal-normal-in {
            0% { transform: scale(0.9) translateY(40px); opacity: 0; }
            100% { transform: scale(1) translateY(0); opacity: 1; }
        }
        .swal2-show { animation: swal-normal-in 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards !important; }

        .swal2-title {
            font-weight: 800 !important;
            font-size: 1.6rem !important;
            letter-spacing: -0.04em !important;
            color: var(--swal-text) !important;
            margin-bottom: 0.5rem !important;
        }

        .swal2-html-container {
            font-size: 0.95rem !important;
            font-weight: 500 !important;
            color: #64748b !important;
            line-height: 1.6 !important;
            margin: 1rem 0 !important;
        }
        .dark .swal2-html-container { color: #94a3b8 !important; }

        /* Floating Icon Design */
        .swal2-icon {
            border: none !important;
            width: 80px !important;
            height: 80px !important;
            margin: -3.5rem auto 1.5rem !important;
            padding: 0 !important;
            background: white !important;
            border-radius: 2rem !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1) !important;
            overflow: visible !important;
        }
        .dark .swal2-icon { 
            background: #1e293b !important; 
            box-shadow: 0 20px 40px -10px rgba(0,0,0,0.4) !important; 
        }

        .swal2-icon.swal2-success { color: var(--swal-success) !important; }
        .swal2-icon.swal2-info { color: var(--swal-accent) !important; }
        .swal2-icon.swal2-error { color: #ef4444 !important; }
        .swal2-icon.swal2-warning { color: #f59e0b !important; }

        /* Success Animated Checkmark SVG */
        .swal-success-icon-premium {
            width: 45px;
            height: 45px;
            stroke-width: 5;
            stroke: currentColor;
            stroke-linecap: round;
            stroke-linejoin: round;
            fill: none;
            stroke-dasharray: 100;
            stroke-dashoffset: 100;
            animation: swal-stroke 0.6s ease-out forwards 0.2s;
            display: block;
        }
        @keyframes swal-stroke { to { stroke-dashoffset: 0; } }

        /* Actions Customization */
        .swal2-actions { margin-top: 2rem !important; gap: 12px !important; width: 100% !important; justify-content: center !important; }

        .swal2-confirm, .swal2-cancel {
            border-radius: 1.25rem !important;
            padding: 1rem 2rem !important;
            font-size: 0.8rem !important;
            font-weight: 800 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.1em !important;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1) !important;
            border: none !important;
            flex: 1 !important;
            max-width: 180px !important;
        }

        .swal2-confirm { 
            background: linear-gradient(135deg, var(--swal-accent), #1d4ed8) !important; 
            color: white !important; 
            box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4) !important; 
        }
        .swal2-cancel { background-color: #f1f5f9 !important; color: #64748b !important; }
        .dark .swal2-cancel { background-color: #334155 !important; color: #cbd5e1 !important; }

        .swal2-confirm:hover { transform: translateY(-3px); box-shadow: 0 15px 30px -5px rgba(37, 99, 235, 0.5) !important; }
        .swal2-confirm:active { transform: translateY(0); }
        .swal2-cancel:hover { background-color: #e2e8f0 !important; transform: translateY(-2px); }

        /* Input Custom */
        .swal2-input {
            border-radius: 1rem !important;
            border: 2px solid #e2e8f0 !important;
            font-family: inherit !important;
            font-weight: 700 !important;
            font-size: 0.9rem !important;
            padding: 0.8rem 1.2rem !important;
            transition: all 0.3s !important;
            box-shadow: none !important;
            width: 80% !important;
            margin: 1rem auto !important;
        }
        .dark .swal2-input { background: #1e293b !important; border-color: #334155 !important; color: white !important; }
        .swal2-input:focus { border-color: var(--swal-accent) !important; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1) !important; }
    `;
    document.head.appendChild(style);
})();

if (typeof tailwind !== 'undefined') {
    tailwind.config = { darkMode: 'class' }
}

(function initTheme() {
    const theme = localStorage.getItem('theme') || 'light';
    if (theme === 'dark') document.documentElement.classList.add('dark');
    else document.documentElement.classList.remove('dark');
})();

function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('theme', isDark ? 'dark' : 'light');
    if (window.renderCal) renderCal();
}

// ─── Modern Alert Helpers ───
/** ─── Robust JSON Multi-Platform Parser ─── **/
function parseMagicJSON(text) {
    try {
        if (text.includes("<!--JSON_START-->")) {
            return JSON.parse(text.split("<!--JSON_START-->")[1].split("<!--JSON_END-->")[0].trim());
        }
        const s = text.indexOf('{'), e = text.lastIndexOf('}');
        if (s !== -1 && e !== -1) return JSON.parse(text.substring(s, e + 1).trim());
        return JSON.parse(text.trim());
    } catch (err) {
        console.warn("JSON Parse Error. Full response:", text);
        const snippet = text.length > 100 ? text.substring(0, 100) + '...' : text;
        throw new Error("Respon server tidak valid: " + snippet);
    }
}

const getModernConfig = () => {
    const isDark = document.documentElement.classList.contains('dark');
    return {
        background: 'transparent',
        color: isDark ? '#f8fafc' : '#0f172a',
        buttonsStyling: false,
        padding: '0',
        toast: false,
        position: 'center',
    };
};

const successIconHtml = `
    <svg class="swal-success-icon-premium" viewBox="0 0 50 50">
        <path d="M13 25l8 8l16-16" />
    </svg>
`; /* Mathematically Centerd M13 25 -> M21 33 -> M37 17 */

function showModernAlert(options = {}) {
    const isError = options.icon === 'error';
    const variantHtml = isError
        ? `<div class="text-red-500 scale-110"><i class="fas fa-times-circle"></i></div>`
        : successIconHtml;

    return Swal.fire({
        ...getModernConfig(),
        icon: options.icon || 'success',
        iconHtml: options.iconHtml || variantHtml,
        title: options.title || (isError ? 'Gagal' : 'Berhasil!'),
        confirmButtonText: 'OKE',
        ...options
    });
}

function confirmModernAlert(options = {}) {
    const isWarning = options.icon === 'warning' || !options.icon;
    const variantHtml = isWarning
        ? `<div class="text-amber-500"><i class="fas fa-exclamation-triangle"></i></div>`
        : `<div class="text-blue-500"><i class="fas fa-question-circle"></i></div>`;

    return Swal.fire({
        ...getModernConfig(),
        title: 'Anda Yakin?',
        text: 'Aksi ini tidak dapat dibatalkan!',
        icon: options.icon || 'warning',
        iconHtml: options.iconHtml || variantHtml,
        showCancelButton: true,
        confirmButtonText: 'YA, LANJUTKAN',
        cancelButtonText: 'BATAL',
        ...options
    });
}

function promptModernAlert(options = {}) {
    const variantHtml = `<div class="text-blue-500 scale-110"><i class="fas fa-edit"></i></div>`;

    return Swal.fire({
        ...getModernConfig(),
        title: 'Input Data',
        input: 'text',
        icon: 'question',
        iconHtml: options.iconHtml || variantHtml,
        showCancelButton: true,
        confirmButtonText: 'SIMPAN',
        cancelButtonText: 'BATAL',
        ...options
    });
}
