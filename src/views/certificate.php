<?php
// src/views/certificate.php - PREMIUM DYNAMIC CERTIFICATE
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }

$topic_slug = $_GET['topic'] ?? '';
$user_id = $_SESSION['user_id'];

// 1. Fetch User
$stU = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stU->execute([$user_id]);
$user = $stU->fetch();

// 2. Fetch Topic & Subject with Strong Fuzzy Matching
$clean_t = preg_replace('/[^a-z0-9]/', '', strtolower($topic_slug));
$f_t = "LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(t.slug, '-', ''), ' ', ''), '&', ''), '_', ''), '.', ''), '/', ''))";

$stmt = $pdo->prepare("
    SELECT s.title as subject_title, t.title as title, t.title as topic_title, s.slug as subject_slug, t.slug as topic_slug, t.id as id
    FROM topics t 
    JOIN subjects s ON t.subject_id = s.id 
    WHERE $f_t = ?
");
$stmt->execute([$clean_t]);
$topic = $stmt->fetch();

if (!$topic) die("Topic not found (" . htmlspecialchars($topic_slug) . ").");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sertifikat Kelulusan - <?php echo htmlspecialchars($topic['title']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@0,400..900;1,400..900&display=swap" rel="stylesheet">
    <style>
        @media print { .no-print { display: none; } body { background: white; } }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .cert-font { font-family: 'Playfair Display', serif; }
        .cert-border {
            border: 20px solid #1e293b;
            padding: 40px;
            position: relative;
        }
        .cert-border::after {
            content: '';
            position: absolute;
            inset: 10px;
            border: 2px solid #e2e8f0;
            pointer-events: none;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen flex flex-col items-center justify-center p-4">

    <div class="no-print w-full max-w-[1100px] flex justify-end gap-4 mb-4 mt-6">
        <button onclick="window.print()" class="px-6 py-2.5 bg-blue-600 text-white font-black rounded-xl shadow-md hover:scale-105 transition-all flex items-center gap-2 text-sm z-50">
            <i class="fas fa-print"></i> CETAK
        </button>
        <a href="dashboard.php" class="px-6 py-2.5 bg-white text-slate-900 font-black rounded-xl shadow-md hover:scale-105 transition-all flex items-center gap-2 text-sm z-50">
            KEMBALI KE DASHBOARD
        </a>
    </div>

    <!-- Certificate Container -->
    <div class="bg-white w-[1100px] transform origin-top md:origin-center scale-[0.35] sm:scale-50 md:scale-75 lg:scale-100 xl:scale-100 xl:w-full max-w-[1100px] aspect-[1.414/1] shadow-2xl cert-border flex flex-col items-center justify-center text-center p-20 relative overflow-hidden shrink-0 mt-[-150px] sm:mt-[-100px] md:mt-0">
        
        <!-- Decorations -->
        <div class="absolute top-0 right-0 w-64 h-64 bg-slate-50 rounded-full -mr-32 -mt-32 border border-slate-100"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-slate-50 rounded-full -ml-32 -mb-32 border border-slate-100"></div>
        
        <div class="relative z-10 w-full">
            <div class="mb-10">
                <div class="w-16 h-16 bg-blue-600 rounded-2xl mx-auto flex items-center justify-center text-white mb-6">
                    <i class="fas fa-bolt text-3xl"></i>
                </div>
                <p class="text-blue-600 font-black tracking-[0.5em] uppercase text-sm mb-4">Sertifikat Kelulusan</p>
                <div class="w-24 h-1 bg-blue-600 mx-auto rounded-full"></div>
            </div>

            <p class="text-slate-400 font-bold italic mb-4">Diberikan secara bangga kepada:</p>
            <h1 class="cert-font text-6xl font-black text-slate-900 mb-8"><?php echo htmlspecialchars($user['full_name']) ?></h1>
            
            <p class="max-w-2xl mx-auto text-slate-600 font-medium leading-relaxed mb-10">
                Telah berhasil menyelesaikan seluruh modul pembelajaran dan lulus penilaian kompetensi pada topik:
                <span class="block text-2xl font-black text-slate-900 mt-4 uppercase tracking-tight"><?php echo htmlspecialchars($topic['title']) ?></span>
            </p>

            <div class="flex items-center justify-between w-full max-w-4xl mx-auto mt-20 border-t border-slate-100 pt-10">
                <div class="text-left">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Tanggal Kelulusan</p>
                    <p class="font-bold text-slate-800"><?php echo date('d F Y') ?></p>
                </div>
                <div class="text-center">
                    <div class="w-24 h-24 bg-blue-50 rounded-full mx-auto flex items-center justify-center mb-2 border-4 border-white shadow-lg">
                        <i class="fas fa-award text-4xl text-blue-600"></i>
                    </div>
                    <p class="text-[10px] font-black text-slate-500 uppercase tracking-widest">Verify ID: SUP-<?php echo strtoupper(substr(md5($user_id . $topic_slug),0,8)) ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Instruktur StepUp</p>
                    <p class="font-bold text-slate-800">Admin StepUp Learning</p>
                </div>
            </div>
        </div>

        <!-- Footer watermark -->
        <p class="absolute bottom-10 text-[8px] font-black text-slate-300 uppercase tracking-[1em]">PLATFORM STEPUP • KELAS 12 INTERAKTIF</p>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    
    <?php include_once __DIR__ . '/../inc_chat_button.php'; ?>
<?php include_once __DIR__ . '/../inc_chat_window.php'; ?>
</body>
</html>

