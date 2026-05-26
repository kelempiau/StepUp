<?php
// src/views/topic_certificate.php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$subject_slug = $_GET['subject'] ?? '';
$topic_slug = $_GET['topic'] ?? '';

if (!$subject_slug || !$topic_slug) {
    die("Data tidak lengkap.");
}

// 1. Initial Robust Hierarchical Search (Strong Fuzzy)
$clean_s = preg_replace('/[^a-z0-9]/', '', strtolower($subject_slug));
$clean_t = preg_replace('/[^a-z0-9]/', '', strtolower($topic_slug));

$f_s = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(s.slug, '-', ''), ' ', ''), '&', ''), '_', ''), '.', ''), '/', ''))";
$f_t = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(t.slug, '-', ''), ' ', ''), '&', ''), '_', ''), '.', ''), '/', ''))";

$stmt = $pdo->prepare("
    SELECT s.title as subject_title, t.title as title, t.title as topic_title, s.slug as subject_slug, t.slug as topic_slug
    FROM subjects s 
    JOIN topics t ON s.id = t.subject_id 
    WHERE $f_s = ? AND $f_t = ?
");
$stmt->execute([$clean_s, $clean_t]);
$info = $stmt->fetch();

if (!$info) {
    die("Topik tidak ditemukan (" . htmlspecialchars($topic_slug) . ").");
}

$user_name = $_SESSION['full_name'];
$date = date('d F Y');
$cert_id = "SU-" . strtoupper(substr($topic_slug, 0, 3)) . "-" . rand(1000, 9999);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M13 2L3 14h9l-1 8 10-12h-9l1-8z'/></svg>">
    <title>Sertifikat Kelulusan - <?php echo htmlspecialchars($info['topic_title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=Great+Vibes&family=Cinzel:wght@700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Dancing+Script:wght@700&display=swap');
        
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f1f5f9; margin: 0; padding: 0; }
        .cursive { font-family: 'Great Vibes', cursive; }
        .cinzel { font-family: 'Cinzel', serif; }
        .signature-font { font-family: 'Dancing Script', cursive; }
        
        /* Certificate Container Optimization for Print */
        .cert-wrapper {
            width: 100%;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px;
        }

        .cert-card {
            width: 100%;
            max-width: 1123px; /* A4 Landscape Width in px at 96dpi */
            aspect-ratio: 1.414 / 1;
            background: white;
            position: relative;
            box-shadow: 0 50px 100px -20px rgba(0,0,0,0.15);
            padding: 60px;
            box-sizing: border-box;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #e2e8f0;
        }

        /* Ornate Corners */
        .corner {
            position: absolute;
            width: 180px;
            height: 180px;
            border-style: solid;
            pointer-events: none;
        }
        .corner-tl { top: 30px; left: 30px; border-width: 15px 0 0 15px; border-color: #1e40af; }
        .corner-tr { top: 30px; right: 30px; border-width: 15px 15px 0 0; border-color: #1e40af; }
        .corner-bl { bottom: 30px; left: 30px; border-width: 0 0 15px 15px; border-color: #1e40af; }
        .corner-br { bottom: 30px; right: 30px; border-width: 0 15px 15px 0; border-color: #1e40af; }

        .inner-border {
            position: absolute;
            inset: 60px;
            border: 2px solid #3b82f6;
            opacity: 0.3;
            pointer-events: none;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            body { background: white; padding: 0; }
            .cert-wrapper { padding: 0; }
            .cert-card { 
                box-shadow: none; 
                border: none; 
                max-width: none;
                width: 100%;
            }
            .no-print { display: none !important; }
            /* Force background colors/images on print */
            body { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
        }
    </style>
</head>
<body>

    <!-- Controls -->
    <div class="no-print fixed top-6 right-6 z-50 flex space-x-3">
        <button onclick="window.print()" class="px-8 py-3 bg-blue-700 text-white font-bold rounded-2xl shadow-xl hover:bg-blue-800 transition flex items-center">
            <i class="fas fa-print mr-2"></i> Cetak Sertifikat
        </button>
        <button onclick="window.close()" class="px-8 py-3 bg-white text-slate-700 font-bold rounded-2xl shadow-xl hover:bg-slate-50 transition border border-slate-200">
            Tutup
        </button>
    </div>

    <div class="cert-wrapper">
        <div class="cert-card">
            <!-- Decorations -->
            <div class="corner corner-tl"></div>
            <div class="corner corner-tr"></div>
            <div class="corner corner-bl"></div>
            <div class="corner corner-br"></div>
            <div class="inner-border"></div>

            <!-- Header Section -->
            <div class="text-center relative z-10 pt-4">
                <div class="w-20 h-20 bg-blue-600 rounded-3xl flex items-center justify-center text-white text-3xl shadow-xl mx-auto mb-6">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h1 class="cinzel text-5xl text-blue-900 font-bold tracking-[0.2em] mb-4">SERTIFIKAT KELULUSAN</h1>
                <div class="w-48 h-1 bg-yellow-400 mx-auto rounded-full mb-8"></div>
                <p class="text-slate-400 font-bold uppercase tracking-[0.4em] text-xs">Diberikan Dengan Bangga Kepada:</p>
            </div>

            <!-- Name Section -->
            <div class="text-center relative z-10 py-4">
                <h2 class="cursive text-[80px] text-slate-800 leading-none mb-4"><?php echo htmlspecialchars($user_name); ?></h2>
                <div class="max-w-2xl mx-auto border-t border-slate-100 mb-8 pt-8">
                    <p class="text-slate-600 text-lg leading-relaxed">
                        Telah berhasil menyelesaikan seluruh rangkaian modul pembelajaran pada topik
                        <span class="block text-blue-700 font-black text-2xl mt-4 uppercase tracking-tight italic">
                            "<?php echo htmlspecialchars($info['topic_title']); ?>"
                        </span>
                        <span class="block text-slate-400 text-sm font-bold uppercase tracking-widest mt-4">
                            Mata Pelajaran: <?php echo htmlspecialchars($info['subject_title']); ?>
                        </span>
                    </p>
                </div>
            </div>

            <!-- Footer Section -->
            <div class="grid grid-cols-3 items-end relative z-10 px-10 pb-4">
                <!-- Left: Signature -->
                <div class="text-left">
                    <div class="signature-font text-3xl text-slate-700 mb-1 opacity-90">StepUp Learning</div>
                    <div class="w-40 h-[1.5px] bg-slate-900 mb-2"></div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Kepala Pembelajaran</p>
                </div>

                <!-- Center: StepUp Logo as Medallion -->
                <div class="flex flex-col items-center justify-center -mb-8">
                    <div class="w-24 h-24 bg-yellow-400 rounded-full border-8 border-white shadow-2xl flex items-center justify-center relative">
                        <div class="w-10 h-10 bg-blue-600 rounded-2xl flex items-center justify-center text-white text-xl">
                            <i class="fas fa-bolt"></i>
                        </div>
                    </div>
                </div>

                <!-- Right: ID & Date -->
                <div class="text-right">
                    <div class="mb-4">
                        <i class="fas fa-qrcode text-slate-100 text-4xl"></i>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">ID Sertifikat:</p>
                        <p class="text-sm font-black text-slate-800 tracking-tight"><?php echo $cert_id; ?></p>
                        <p class="text-[10px] font-bold text-slate-400 mt-2"><?php echo $date; ?></p>
                    </div>
                </div>
            </div>

            <!-- Watermark Background -->
            <i class="fas fa-certificate absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 text-slate-50 text-[500px] -z-10 rotate-12 opacity-80"></i>
        </div>
    </div>

    <?php include_once __DIR__ . '/../inc_chat_button.php'; ?>
<?php include_once __DIR__ . '/../inc_chat_window.php'; ?>
</body>
</html>

