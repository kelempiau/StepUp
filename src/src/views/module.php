<?php
// src/views/module.php
session_start();
require_once '../../config/db.php';
require_once '../../src/helpers/youtube.php'; // YouTube URL helper

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$subject_slug = trim($_GET['subject'] ?? '');
$topic_slug = trim($_GET['topic'] ?? '');
$module_slug = trim($_GET['module'] ?? '');

// 1. Initial Robust Hierarchical Search
$stmt = $pdo->prepare("
    SELECT m.*, t.title as topic_title, s.title as subject_title, s.slug as subject_slug, t.slug as topic_slug
    FROM modules m
    JOIN topics t ON m.topic_id = t.id
    JOIN subjects s ON t.subject_id = s.id
    WHERE s.slug = ? AND t.slug = ? AND m.slug = ?
");
$stmt->execute([$subject_slug, $topic_slug, $module_slug]);
$module = $stmt->fetch();

if (!$module) {
    // FALLBACK: INDESTRUCTIBLE FUZZY SEARCH
    // Matches regardless of spaces vs hyphens in ANY level of the hierarchy
    $clean_s = str_replace(['-', ' '], '', $subject_slug);
    $clean_t = str_replace(['-', ' '], '', $topic_slug);
    $clean_m = str_replace(['-', ' '], '', $module_slug);
    
    $stFuzzy = $pdo->prepare("
        SELECT m.*, t.title as topic_title, s.title as subject_title, s.slug as subject_slug, t.slug as topic_slug
        FROM modules m
        JOIN topics t ON m.topic_id = t.id
        JOIN subjects s ON t.subject_id = s.id
        WHERE REPLACE(REPLACE(s.slug, '-', ''), ' ', '') = ?
          AND REPLACE(REPLACE(t.slug, '-', ''), ' ', '') = ?
          AND REPLACE(REPLACE(m.slug, '-', ''), ' ', '') = ?
    ");
    $stFuzzy->execute([$clean_s, $clean_t, $clean_m]);
    $module = $stFuzzy->fetch();
}

if (!$module) {
    // FINAL DEEP SEARCH (Search JUST by module name anywhere if hierarchy is broken)
    $clean_m = str_replace(['-', ' '], '', $module_slug);
    $stDeep = $pdo->prepare("
        SELECT m.*, t.title as topic_title, s.title as subject_title, s.slug as subject_slug, t.slug as topic_slug
        FROM modules m
        JOIN topics t ON m.topic_id = t.id
        JOIN subjects s ON t.subject_id = s.id
        WHERE REPLACE(REPLACE(m.slug, '-', ''), ' ', '') = ?
    ");
    $stDeep->execute([$clean_m]);
    $matches = $stDeep->fetchAll();
    
    if (count($matches) === 1) {
        $module = $matches[0];
    } elseif (count($matches) > 1) {
        foreach ($matches as $match) {
            if (str_replace(['-',' '],'',$match['subject_slug']) === str_replace(['-',' '],'',$subject_slug)) {
                $module = $match;
                break;
            }
        }
    }
}

if (!$module) {
    // Enhanced error page with better debugging
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Modul Tidak Ditemukan</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    </head>
    <body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
    <?php 
    $btn = __DIR__ . '/../inc_chat_button.php'; 
    $win = __DIR__ . '/../inc_chat_window.php'; 
    if(file_exists($btn) && file_exists($win)) { 
        include_once $btn; 
        include_once $win; 
    } else { 
        echo "<script>alert('ERROR BROWSER/HOSTING: File inc_chat_button.php atau inc_chat_window.php tidak ditemukan di folder src/.');</script>"; 
    } 
    ?>
        <div class="max-w-2xl w-full">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">
                <div class="bg-red-500 p-8 text-white text-center">
                    <div class="w-20 h-20 bg-white/20 rounded-full flex items-center justify-center text-4xl mx-auto mb-4">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h1 class="text-3xl font-black">Modul Tidak Ditemukan</h1>
                    <p class="text-red-100 mt-2">URL yang Anda akses tidak valid atau data modul tidak ada di database.</p>
                </div>
                
                <div class="p-8">
                    <div class="bg-slate-50 rounded-2xl p-6 mb-6">
                        <h3 class="text-sm font-black text-slate-400 uppercase tracking-wider mb-4">Debug Information:</h3>
                        <div class="space-y-2 font-mono text-sm">
                            <div class="flex justify-between border-b border-slate-200 pb-2">
                                <span class="text-slate-500">Subject Slug:</span>
                                <span class="font-bold text-blue-600"><?php echo htmlspecialchars($subject_slug); ?></span>
                            </div>
                            <div class="flex justify-between border-b border-slate-200 pb-2">
                                <span class="text-slate-500">Topic Slug:</span>
                                <span class="font-bold text-purple-600"><?php echo htmlspecialchars($topic_slug); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Module Slug:</span>
                                <span class="font-bold text-red-600"><?php echo htmlspecialchars($module_slug); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
                        <h3 class="font-black text-blue-900 mb-2 flex items-center">
                            <i class="fas fa-lightbulb mr-2"></i> Kemungkinan Penyebab:
                        </h3>
                        <ul class="space-y-2 text-sm text-blue-800">
                            <li class="flex items-start">
                                <i class="fas fa-circle text-xs mt-1 mr-2"></i>
                                <span>Data modul dengan slug <strong>"<?php echo htmlspecialchars($module_slug); ?>"</strong> belum ada di database</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-circle text-xs mt-1 mr-2"></i>
                                <span>Topic slug salah atau tidak sesuai dengan database</span>
                            </li>
                            <li class="flex items-start">
                                <i class="fas fa-circle text-xs mt-1 mr-2"></i>
                                <span>Modul dihapus atau dipindahkan ke topic lain</span>
                            </li>
                        </ul>
                    </div>
                    
                    <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg mb-8">
                        <h3 class="font-black text-green-900 mb-3 flex items-center">
                            <i class="fas fa-tools mr-2"></i> Cara Memperbaiki:
                        </h3>
                        <ol class="space-y-3 text-sm text-green-800">
                            <li class="flex items-start">
                                <span class="font-black mr-2">1.</span>
                                <div>
                                    <strong>Cek Database:</strong> Upload file <code class="bg-green-200 px-2 py-1 rounded">check_modules.php</code> ke root folder, lalu buka di browser untuk melihat semua modul yang tersedia.
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="font-black mr-2">2.</span>
                                <div>
                                    <strong>Tambah Data Modul:</strong> Jika modul memang tidak ada, tambahkan melalui phpMyAdmin atau admin panel LMS.
                                </div>
                            </li>
                            <li class="flex items-start">
                                <span class="font-black mr-2">3.</span>
                                <div>
                                    <strong>Perbaiki Link:</strong> Pastikan link yang diklik sudah benar sesuai dengan data yang ada di database.
                                </div>
                            </li>
                        </ol>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-3">
                        <a href="dashboard.php" class="flex-1 py-4 bg-blue-600 text-white font-black rounded-xl text-center hover:bg-blue-700 transition">
                            <i class="fas fa-home mr-2"></i> Kembali ke Dashboard
                        </a>
                        <a href="../../check_modules.php" class="flex-1 py-4 bg-slate-600 text-white font-black rounded-xl text-center hover:bg-slate-700 transition">
                            <i class="fas fa-database mr-2"></i> Cek Database
                        </a>
                    </div>
                </div>
            </div>
            
            <p class="text-center text-slate-400 text-xs mt-6">
                <i class="fas fa-code mr-1"></i> StepUp LMS - Module Error Handler v2.0
            </p>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// 2. Fetch Material
$stmt = $pdo->prepare("SELECT content FROM module_materials WHERE module_id = ?");
$stmt->execute([$module['id']]);
$material = $stmt->fetch();
$content_html = $material ? $material['content'] : '<p class="text-slate-500 italic">Belum ada materi untuk modul ini.</p>';

// 3. Fetch Questions (Randomize if needed, but for now fixed order)
$stmt = $pdo->prepare("SELECT * FROM module_questions WHERE module_id = ? ORDER BY id ASC");
$stmt->execute([$module['id']]);
$questions = $stmt->fetchAll();

// 4. Check Progress & Record Activity (if first time) - USING STRONG FUZZY MATCH
$c_s = preg_replace('/[^a-z0-9]/', '', strtolower($subject_slug));
$c_t = preg_replace('/[^a-z0-9]/', '', strtolower($topic_slug));
$c_m = preg_replace('/[^a-z0-9]/', '', strtolower($module_slug));

$stmtAllP = $pdo->prepare("SELECT * FROM progress WHERE user_id = ?");
$stmtAllP->execute([$_SESSION['user_id']]);
$progress = null;
foreach($stmtAllP->fetchAll(PDO::FETCH_ASSOC) as $row) {
    if (
        preg_replace('/[^a-z0-9]/', '', strtolower($row['subject_slug'])) === $c_s &&
        preg_replace('/[^a-z0-9]/', '', strtolower($row['topic_slug'])) === $c_t &&
        preg_replace('/[^a-z0-9]/', '', strtolower($row['module_slug'])) === $c_m
    ) {
        $progress = $row;
        break;
    }
}

if (!$progress) {
    // Record basic activity entry
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO progress (user_id, subject_slug, topic_slug, module_slug, completed_step, is_completed, created_at) VALUES (?, ?, ?, ?, 0, 0, ?)");
    $stmt->execute([$_SESSION['user_id'], $subject_slug, $topic_slug, $module_slug, $now]);
}

$is_completed = $progress && $progress['is_completed'];

// 5. Next Module Logic
// Get all modules in this topic to find next
$stmt = $pdo->prepare("SELECT slug FROM modules WHERE topic_id = ? ORDER BY id ASC");
$stmt->execute([$module['topic_id']]);
$all_mods = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Robust fuzzy search for current index
$current_index = false;
$clean_current_m = str_replace(['-', ' '], '', $module_slug);
foreach ($all_mods as $idx => $m_slug) {
    if (str_replace(['-', ' '], '', $m_slug) === $clean_current_m) {
        $current_index = $idx;
        break;
    }
}

$next_module_slug = ($current_index !== false && isset($all_mods[$current_index + 1])) ? $all_mods[$current_index + 1] : null;

// Prepare Quiz Data for JS
$quiz_data = [];
foreach ($questions as $q) {
    $quiz_data[] = [
        'q' => $q['question'],
        'options' => json_decode($q['options']),
        'answer' => $q['answer']
    ];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($module['title']); ?> - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
    <script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/theme.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .prose p { margin-bottom: 1rem; }
        .prose ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 1rem; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 dark:bg-slate-950 dark:text-slate-200 transition-colors duration-300">
    <?php 
    if(file_exists($btn) && file_exists($win)) { 
        include_once $btn; 
        include_once $win; 
    } else { 
        echo "<script>alert('ERROR BROWSER/HOSTING: File inc_chat_button.php atau inc_chat_window.php tidak ditemukan di folder src/.');</script>"; 
    } 
    ?>

    <!-- Top Navigation -->
    <nav class="bg-white dark:bg-slate-900 border-b border-blue-100 dark:border-slate-800 px-6 py-4 sticky top-0 z-50 shadow-sm flex items-center justify-between transition-colors">
        <div class="flex items-center space-x-4">
            <a href="dashboard.php" class="text-slate-500 hover:text-blue-600 dark:text-slate-400 dark:hover:text-blue-400 transition">
                <i class="fas fa-arrow-left text-lg"></i>
            </a>
            <div>
                 <h1 class="text-lg font-bold text-slate-800 dark:text-white leading-tight"><?php echo htmlspecialchars($module['title']); ?></h1>
                 <p class="text-xs text-slate-500 dark:text-slate-400 font-medium">
                    <?php echo htmlspecialchars($module['subject_title']); ?> &rsaquo; <?php echo htmlspecialchars($module['topic_title']); ?>
                 </p>
            </div>
        </div>
        <div class="hidden md:flex items-center space-x-3">
             <div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 rounded-lg text-sm font-semibold">
                Modul <?php echo ($current_index + 1); ?> / <?php echo count($all_mods); ?>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto p-6 md:p-10">
        
        <!-- Video Section -->
        <?php if (!empty($module['video_url'])): ?>
            <div class="mb-10 rounded-2xl overflow-hidden shadow-2xl shadow-blue-900/10 border border-slate-200 dark:border-slate-800">
                <iframe class="w-full aspect-video" 
                        src="<?php echo htmlspecialchars(convertToEmbedURL($module['video_url'])); ?>" 
                        title="Video Pembelajaran" 
                        frameborder="0" 
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
                        allowfullscreen
                        loading="lazy">
                </iframe>
            </div>
        <?php endif; ?>

        <!-- Materials Content -->
        <div class="bg-white dark:bg-slate-900 p-8 md:p-12 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 dark:border-slate-800 mb-10 prose prose-slate dark:prose-invert max-w-none prose-lg">
            <?php 
            // Strip iframes from content to prevent double video if it's already in the top section
            $clean_content = preg_replace('/<iframe.*?<\/iframe>/is', '', $content_html);
            echo $clean_content; 
            ?>
            
            <!-- Start Quiz Button -->
            <?php if (count($questions) > 0): ?>
                <div class="mt-12 pt-8 border-t border-slate-100 dark:border-slate-800 flex flex-col items-center text-center">
                    <div class="mb-6">
                        <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">Materi Selesai Dibaca?</h3>
                        <p class="text-slate-500 dark:text-slate-400">Uji pemahamanmu dengan menjawab kuis singkat untuk modul ini.</p>
                    </div>
                    <div id="quizStateContainer">
                        <?php if ($is_completed): ?>
                            <div class="flex flex-col space-y-3 w-full max-w-xs">
                                 <button onclick="actuallyOpenQuizModal()" class="w-full py-4 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold rounded-2xl hover:bg-slate-200 dark:hover:bg-slate-700 transition flex items-center justify-center space-x-2">
                                    <i class="fas fa-redo-alt"></i>
                                    <span>Ulangi Kuis</span>
                                </button>
                            </div>
                        <?php else: ?>
                            <button onclick="actuallyOpenQuizModal()" class="w-full max-w-md py-5 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold rounded-2xl shadow-xl shadow-blue-500/30 hover:shadow-blue-500/50 transition transform hover:-translate-y-1 flex items-center justify-center space-x-3 text-lg">
                                <i class="fas fa-rocket"></i>
                                <span>Selesaikan & Mulai Kuis</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="mt-12 pt-8 border-t border-slate-100 dark:border-slate-800 flex flex-col items-center text-center">
                    <div class="mb-6">
                        <div class="w-16 h-16 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-white">Modul Selesai Dipelajari?</h3>
                        <p class="text-slate-500 dark:text-slate-400">Tekan tombol di bawah untuk menandai modul ini sebagai selesai.</p>
                    </div>
                    <?php if ($is_completed): ?>
                        <div class="px-6 py-3 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-bold rounded-xl flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span>Sudah Selesai</span>
                        </div>
                    <?php else: ?>
                        <button onclick="completeWithoutQuiz()" class="w-full max-w-md py-5 bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold rounded-2xl shadow-xl shadow-emerald-500/30 hover:shadow-emerald-500/50 transition transform hover:-translate-y-1 flex items-center justify-center space-x-3 text-lg">
                            <i class="fas fa-check-circle"></i>
                            <span>Selesaikan Modul Sekarang</span>
                        </button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Next Navigation (Hidden until quiz done or if already done) -->
        <div class="flex justify-center mt-8">
            <?php if ($next_module_slug): 
                 $nextModUrl = "module.php?subject=".urlencode($subject_slug)."&topic=".urlencode($topic_slug)."&module=".urlencode($next_module_slug);
            ?>
                <a href="<?php echo $nextModUrl; ?>" id="nextBtn" class="<?php echo $is_completed ? '' : 'hidden'; ?> px-10 py-4 bg-white dark:bg-slate-900 border-2 border-green-500 text-green-600 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/10 font-extrabold rounded-2xl shadow-sm transition flex items-center space-x-2">
                    <span>Lanjut ke Modul Berikutnya</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            <?php endif; ?>
        </div>

    </div>

    <!-- Custom Modals removed in favor of Unified Modern Swal -->

    <!-- Start Quiz Confirmation Modal -->
    <div id="startQuizModal" class="hidden fixed inset-0 z-[55] flex items-center justify-center bg-slate-900/80 backdrop-blur-md">
        <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] p-10 max-w-md w-full mx-4 shadow-2xl text-center">
            <div class="w-24 h-24 bg-gradient-to-tr from-blue-500 to-indigo-600 text-white rounded-[2rem] flex items-center justify-center text-4xl mx-auto mb-8 rotate-12 shadow-xl shadow-blue-500/20">
                <i class="fas fa-play"></i>
            </div>
            <h2 class="text-3xl font-black text-slate-900 dark:text-white mb-4">Siap Memulai Kuis?</h2>
            <div class="space-y-4 mb-10 text-left bg-slate-50 dark:bg-slate-950/50 p-6 rounded-2xl border border-slate-100 dark:border-slate-800">
                <div class="flex items-center space-x-3 text-slate-600 dark:text-slate-400">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <span class="font-medium text-sm">Total Soal: <strong><?php echo count($questions); ?> Butir</strong></span>
                </div>
                <div class="flex items-center space-x-3 text-slate-600 dark:text-slate-400">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <span class="font-medium text-sm">Target Skor: <strong>Min. 70%</strong></span>
                </div>
                <div class="flex items-center space-x-3 text-slate-600 dark:text-slate-400">
                    <i class="fas fa-check-circle text-green-500"></i>
                    <span class="font-medium text-sm">Waktu: <strong>Tidak Terbatas</strong></span>
                </div>
            </div>
            <div class="flex flex-col space-y-3">
                <button onclick="realOpenQuiz()" class="w-full py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/40 transition transform hover:-translate-y-1 active:scale-95 text-lg">
                    Ya, Saya Siap!
                </button>
                <button onclick="document.getElementById('startQuizModal').classList.add('hidden')" class="w-full py-4 bg-white dark:bg-slate-800 text-slate-400 dark:text-slate-500 font-bold rounded-2xl hover:text-slate-600 dark:hover:text-slate-300 transition">
                    Nanti Saja
                </button>
            </div>
        </div>
    </div>

    <!-- Quiz Modal (Redesigned Exam Style) -->
    <div id="quizModal" class="hidden fixed inset-0 z-[60] bg-slate-100 dark:bg-slate-950 flex flex-col overflow-hidden transition-colors">
        <!-- Persistent Header -->
        <header class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 px-8 py-5 flex justify-between items-center shadow-sm">
            <div class="flex items-center space-x-3 md:space-x-6">
                <div class="w-10 h-10 md:w-12 md:h-12 bg-blue-600 text-white rounded-xl md:rounded-2xl flex items-center justify-center text-lg md:text-xl shadow-lg shadow-blue-500/20">
                    <i class="fas fa-file-signature"></i>
                </div>
                <div class="min-w-0">
                    <h2 class="text-sm md:text-xl font-extrabold text-slate-900 dark:text-white uppercase tracking-tight truncate">Lembar Jawaban</h2>
                    <p class="text-[9px] md:text-xs text-slate-500 dark:text-slate-400 font-bold tracking-widest uppercase truncate"><?php echo htmlspecialchars($module['title']); ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="hidden md:block text-right mr-4 border-r border-slate-100 dark:border-slate-800 pr-6">
                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold uppercase mb-0.5">Progress Pengerjaan</p>
                    <p class="text-sm font-black text-blue-600 dark:text-blue-400" id="quizProgressText">0 / <?php echo count($questions); ?> Soal</p>
                </div>
                <button onclick="closeQuizModal()" class="px-6 py-2.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-bold rounded-xl hover:bg-red-50 dark:hover:bg-red-900/40 hover:text-red-500 dark:hover:text-red-400 transition-all flex items-center space-x-2 border border-transparent hover:border-red-100 dark:hover:border-red-900">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Tutup Kuis</span>
                </button>
            </div>
        </header>

        <!-- Main Workspace -->
        <div class="flex-1 overflow-y-auto bg-slate-50/50 dark:bg-slate-950/50 scroll-smooth">
            <div class="max-w-4xl mx-auto py-12 px-6">
                
                <div class="bg-white dark:bg-slate-900 rounded-[2.5rem] shadow-2xl shadow-blue-900/5 border border-slate-200 dark:border-slate-800 overflow-hidden relative">
                    <!-- Paper Design Accent -->
                    <div class="absolute top-0 left-0 w-2 h-full bg-blue-600"></div>
                    
                    <div class="p-10 md:p-14">
                        <form id="quizForm" class="space-y-16">
                            <?php foreach ($quiz_data as $idx => $q): ?>
                                <div class="quiz-item group" data-index="<?php echo $idx; ?>">
                                    <div class="flex items-start space-x-6 mb-8">
                                        <div class="flex-shrink-0 w-10 h-10 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-slate-900 dark:bg-slate-800 text-white flex flex-col items-center justify-center font-black group-focus-within:bg-blue-600 transition-colors shadow-lg">
                                            <span class="text-[8px] md:text-[10px] opacity-40 uppercase leading-none mb-0.5 md:mb-1">Q</span>
                                            <span class="leading-none text-sm md:text-base"><?php echo $idx + 1; ?></span>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-lg md:text-2xl font-bold text-slate-800 dark:text-white leading-relaxed mb-6 md:mb-8"><?php echo htmlspecialchars($q['q']); ?></h4>
                                            
                                            <div class="grid grid-cols-1 gap-4 max-w-2xl">
                                                <?php foreach ($q['options'] as $oIdx => $opt): ?>
                                                    <label class="block relative group/opt">
                                                        <input type="radio" name="q<?php echo $idx; ?>" value="<?php echo $oIdx; ?>" onchange="updateProgress()" class="peer hidden">
                                                        <div class="flex items-center p-5 rounded-2xl border-2 border-slate-100 dark:border-slate-800 peer-checked:border-blue-600 dark:peer-checked:border-blue-500 peer-checked:bg-blue-50/50 dark:peer-checked:bg-blue-900/20 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition-all duration-200 group relative">
                                                            <div class="w-8 h-8 rounded-xl border-2 border-slate-200 dark:border-slate-700 peer-checked:border-blue-600 dark:peer-checked:border-blue-500 peer-checked:bg-blue-600 dark:peer-checked:bg-blue-600 flex items-center justify-center transition-all bg-white dark:bg-slate-800 shadow-sm mr-5 overflow-hidden">
                                                                <span class="text-xs font-black text-slate-400 dark:text-slate-500 peer-checked:text-white uppercase transition-colors">
                                                                    <?php echo chr(65 + $oIdx); ?>
                                                                </span>
                                                                <i class="fas fa-check text-white text-xs scale-0 peer-checked:scale-100 transition-transform absolute"></i>
                                                            </div>
                                                            <span class="text-slate-600 dark:text-slate-400 font-bold text-lg peer-checked:text-blue-900 dark:peer-checked:text-blue-300 transition flex-1"><?php echo htmlspecialchars($opt); ?></span>
                                                        </div>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </form>
                    </div>
                </div>

                <!-- Submission Footer In-Page -->
                <div class="mt-12 flex flex-col items-center">
                    <div class="mb-8 text-center text-slate-400 max-w-sm">
                        <i class="fas fa-info-circle mb-2 block opacity-50"></i>
                        <p class="text-xs font-medium leading-relaxed">Pastikan Anda telah memeriksa semua jawaban sebelum menekan tombol kirim di bawah ini.</p>
                    </div>
                    <button type="button" onclick="submitQuiz()" class="w-full max-w-md py-6 bg-gradient-to-r from-blue-600 to-indigo-700 text-white font-black rounded-3xl shadow-2xl shadow-blue-500/40 hover:shadow-blue-500/60 transition transform hover:-translate-y-1 active:scale-95 text-xl flex items-center justify-center space-x-4">
                        <i class="fas fa-paper-plane mr-2"></i>
                        <span>Selesai & Kumpulkan Jawaban</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom result modal removed as we now use modern SweetAlert2 -->

    <script>
        const subjectSlug = '<?php echo $module['subject_slug']; ?>';
        const topicSlug = '<?php echo $module['topic_slug']; ?>';
        const moduleSlug = '<?php echo $module['slug']; ?>';
        const correctAnswers = <?php echo json_encode(array_column($quiz_data, 'answer')); ?>;
        
        let confirmAction = null;

        // Custom Modal UI (Redirected to Modern Swal)
        function customAlert(title, desc) {
            showModernAlert({ title, text: desc, icon: 'info' });
        }

        function customConfirm(title, desc, onConfirm) {
            confirmModernAlert({ title, text: desc }).then(res => {
                if (res.isConfirmed && onConfirm) onConfirm();
            });
        }

        // Quiz Logic
        function showStartConfirm() {
            document.getElementById('startQuizModal').classList.remove('hidden');
        }

        function updateProgress() {
            const answered = document.querySelectorAll('input[type="radio"]:checked').length;
            document.getElementById('quizProgressText').innerText = `${answered} / ${correctAnswers.length} Soal`;
        }

        function actuallyOpenQuizModal() {
            showStartConfirm();
        }

        function realOpenQuiz() {
            document.getElementById('startQuizModal').classList.add('hidden');
            document.getElementById('quizModal').classList.remove('hidden');
            document.getElementById('quizForm').reset(); // Reset form for fresh start
            document.body.classList.add('overflow-hidden');
            updateProgress();
        }

        function closeQuizModal() {
            confirmModernAlert({
                title: 'Keluar dari Kuis?',
                text: 'Progress pengerjaan Anda tidak akan tersimpan jika keluar sekarang.',
                icon: 'warning',
                confirmButtonText: 'YA, KELUAR',
                cancelButtonText: 'TETAP DISINI'
            }).then(r => {
                if(r.isConfirmed) {
                    document.getElementById('quizModal').classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });
        }

        function completeWithoutQuiz() {
            confirmModernAlert({
                title: 'Tandai Selesai?',
                text: 'Apakah kamu sudah benar-benar memahami materi ini?',
                icon: 'question'
            }).then(r => {
                if(!r.isConfirmed) return;
                const btn = document.querySelector('button[onclick="completeWithoutQuiz()"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Memproses...</span>';
                }

                fetch('../api/submit_quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        subject: String(subjectSlug),
                        topic: String(topicSlug),
                        module: String(moduleSlug),
                        score: 100 // Auto-pass
                    })
                })
                .then(res => res.text())
                .then(text => {
                    const data = parseMagicJSON(text);
                    if (data.success) {
                        if(typeof confetti === 'function') confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 } });
                        showModernAlert({
                            title: 'MODUL SELESAI!',
                            text: 'Selamat! Modul ini telah ditandai sebagai selesai.',
                            confirmButtonText: 'OKE'
                        }).then(() => {
                            window.location.assign('dashboard.php?tab=' + subjectSlug);
                        });
                    } else {
                        throw new Error(data.error || "Gagal menyimpan progress.");
                    }
                })
                .catch(err => {
                    console.error("Error:", err);
                    if (btn) {
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-check-circle"></i><span>Selesaikan Modul Sekarang</span>';
                    }
                    showModernAlert({ icon: 'error', title: 'Gagal', text: err.message });
                });
            });
        }

        function submitQuiz() {
            let score = 0;
            let answered = 0;
            const total = correctAnswers.length;

            correctAnswers.forEach((ans, idx) => {
                const selected = document.querySelector(`input[name="q${idx}"]:checked`);
                if (selected) {
                    answered++;
                    if (parseInt(selected.value) === ans) score++;
                }
            });

            if (answered < total) {
                showModernAlert({
                    icon: 'warning',
                    title: 'Ada Soal Kosong!',
                    text: 'Pastikan kamu sudah menjawab semua pertanyaan kuis sebelum mengumpulkan.'
                });
                return;
            }

            confirmModernAlert({
                title: 'Kumpulkan Jawaban?',
                text: 'Pastikan semua jawaban sudah benar. Kuis akan segera dinilai.',
                icon: 'question'
            }).then(r => {
                if(!r.isConfirmed) return;
                
                const finalScore = Math.round((score / total) * 100);
                const btn = document.querySelector('button[onclick="submitQuiz()"]');
                const originalHtml = btn ? btn.innerHTML : '';
                
                if (btn) {
                    btn.disabled = true;
                    btn.classList.add('opacity-50', 'cursor-not-allowed');
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><span>Mengirim...</span>';
                }

                console.log("Submitting quiz:", { subjectSlug, topicSlug, moduleSlug, finalScore });

                fetch('../api/submit_quiz.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        subject: String(subjectSlug),
                        topic: String(topicSlug),
                        module: String(moduleSlug),
                        score: Number(finalScore)
                    })
                })
                .then(res => res.text())
                .then(text => {
                    console.log("Server response:", text);
                    const data = parseMagicJSON(text);
                    if (data.success) {
                        if (Number(finalScore) >= 70) {
                            if(typeof confetti === 'function') confetti({ particleCount: 150, spread: 70, origin: { y: 0.6 } });
                            
                            showModernAlert({
                                title: 'HASIL: ' + finalScore + '%',
                                text: 'Selamat! Kamu telah menguasai modul ini dengan sangat baik.',
                                confirmButtonText: 'LANJUTKAN'
                            }).then(() => {
                                 // Close kuis modal explicitly
                                 document.getElementById('quizModal')?.classList.add('hidden');
                                 document.body.classList.remove('overflow-hidden');

                                 const nextBtnEl = document.getElementById('nextBtn');
                                 if(nextBtnEl && !nextBtnEl.classList.contains('hidden')) {
                                    confirmModernAlert({
                                        title: 'Lanjut Belajar?',
                                        text: 'Ingin langsung menuju ke modul berikutnya?',
                                        icon: 'success',
                                        confirmButtonText: 'YA, GAS!',
                                        cancelButtonText: 'KEMBALI KE MAPEL'
                                    }).then(r => { 
                                        const target = (r.isConfirmed && nextBtnEl.href) ? nextBtnEl.href : 'dashboard.php?tab=' + subjectSlug;
                                        window.location.assign(target);
                                        setTimeout(() => { window.location.href = target; }, 2000);
                                    });
                                 } else {
                                     const target = 'dashboard.php?tab=' + subjectSlug;
                                     window.location.assign(target);
                                     setTimeout(() => { window.location.href = target; }, 2000);
                                 }
                            });
                        } else {
                            showModernAlert({
                                title: 'Skor: ' + finalScore + '%',
                                text: 'Yah, skor kamu belum mencapai batas minimal (70%). Jangan menyerah, ayo pelajari lagi!',
                                icon: 'warning',
                                confirmButtonText: 'COBA LAGI'
                            }).then(() => location.reload());
                        }
                    } else {
                        throw new Error(data.error || "Gagal menyimpan skor.");
                    }
                })
                .catch(err => {
                    console.error("Submission error:", err);
                    if (btn) {
                        btn.disabled = false;
                        btn.classList.remove('opacity-50', 'cursor-not-allowed');
                        btn.innerHTML = originalHtml;
                    }
                    showModernAlert({ icon: 'error', title: 'Gagal', text: err.message });
                });
            });
        }


    </script>

    
</body>
</html>


