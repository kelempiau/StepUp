<?php
// src/views/final_exam.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

// Fetch Current User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user_data = $stmt->fetch();

// Fetch All Subjects for Sidebar
try {
    $stmt = $pdo->query("SELECT * FROM subjects ORDER BY id ASC");
    $subjects = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error: Tabel 'subjects' tidak ditemukan. Pastikan database sudah terpasang dengan benar.");
}

// 1. Get Subject from URL
$subject_slug = $_GET['subject'] ?? 'matematika';

// Fetch Subject Data
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE slug = ?");
$stmt->execute([$subject_slug]);
$subject_info = $stmt->fetch();

if (!$subject_info) {
    die("Mata pelajaran tidak ditemukan.");
}

// 2. Fetch Real Questions from DB for this subject
try {
    $stmt = $pdo->prepare("SELECT * FROM final_exam_questions WHERE subject_slug = ? ORDER BY RAND() LIMIT 50");
    $stmt->execute([$subject_slug]);
    $exam_questions = $stmt->fetchAll();
} catch (Exception $e) {
    die("Error: Tabel 'final_exam_questions' belum tersedia. Silakan hubungi admin (Pastikan db_fix.php sudah dijalankan).");
}

if (!$exam_questions) {
    die("Data ujian belum tersedia untuk mata pelajaran ini. Admin belum menambahkan soal.");
}

$total_q = count($exam_questions);

// 3. Handle Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $score = 0;
    foreach ($exam_questions as $q) {
        $q_id = $q['id'];
        if (isset($_POST["q$q_id"]) && (int)$_POST["q$q_id"] === (int)$q['answer']) {
            $score++;
        }
    }
    
    $final_grade = ($score / $total_q) * 100;
    $passed = $final_grade >= 60 ? 1 : 0;
    
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("INSERT INTO final_exam_scores (user_id, subject_slug, score, passed, created_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $subject_slug, $final_grade, $passed, $now]);
    
    header("Location: certificate.php?score=$final_grade&passed=$passed&subject=$subject_slug");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M13 2L3 14h9l-1 8 10-12h-9l1-8z'/></svg>">
    <title>Ujian Akhir Semester - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/auth.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; }
    </style>
</head>
<body class="bg-blue-50/50 text-slate-800 dark:bg-slate-950 dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <aside id="sidebar" class="w-64 bg-white dark:bg-slate-900 border-r border-blue-100 dark:border-slate-800 hidden md:flex flex-col z-50 shadow-[4px_0_24px_rgba(37,99,235,0.02)] transition-all duration-300">
        <div class="p-6 border-b border-blue-50 dark:border-slate-800 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i class="fas fa-layer-group"></i>
                </div>
                <span class="font-bold text-xl text-slate-800 dark:text-white tracking-tight">StepUp</span>
            </div>
            <button onclick="toggleSidebar()" class="md:hidden text-slate-400">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <nav class="flex-1 overflow-y-auto p-4 space-y-1 custom-scrollbar">
            <div class="px-4 py-2 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-2">Menu Utama</div>
            <a href="dashboard.php" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 group text-slate-500 dark:text-slate-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600">
                <span class="w-6 text-center text-lg"><i class="fas fa-th-large"></i></span>
                <span class="text-sm font-semibold">Dashboard</span>
            </a>

            <div class="mt-6 px-4 py-2 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest leading-none mb-2">Mata Pelajaran</div>
            <?php foreach ($subjects as $s): ?>
                <a href="dashboard.php?tab=<?php echo $s['slug']; ?>" class="w-full flex items-center space-x-3 px-4 py-3 rounded-xl transition-all duration-200 group text-slate-500 dark:text-slate-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600">
                    <span class="w-6 text-center text-lg"><?php echo $s['icon']; ?></span>
                    <span class="text-sm font-semibold"><?php echo htmlspecialchars($s['title']); ?></span>
                </a>
            <?php endforeach; ?>
            
            <div class="px-4 py-2 mt-6 text-xs font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest border-t border-slate-50 dark:border-slate-800 pt-4 leading-none mb-2">Evaluasi</div>
            <a href="javascript:void(0)" class="w-full flex items-center space-x-3 px-4 py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg shadow-blue-500/20">
                <i class="fas fa-graduation-cap w-6 text-center text-lg"></i>
                <span class="text-sm font-black">Ujian Akhir</span>
            </a>
        </nav>

        <div class="p-4 border-t border-blue-50 dark:border-slate-800">
            <div class="bg-blue-50 dark:bg-slate-800/50 rounded-2xl p-4 flex items-center space-x-3 mb-3 border border-transparent dark:border-slate-800">
                <?php if($user_data['profile_pic']): ?>
                    <img src="../../uploads/profile_pics/<?php echo $user_data['profile_pic']; ?>" class="w-10 h-10 rounded-xl object-cover shadow-sm border border-white dark:border-slate-700">
                <?php else: ?>
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-500 to-cyan-500 flex items-center justify-center text-white font-bold shadow-md">
                        <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate"><?php echo htmlspecialchars(explode(' ', $_SESSION['full_name'])[0]); ?></p>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Siswa Kelas 12</p>
                </div>
            </div>
            <a href="javascript:void(0)" onclick="confirmLogout('../auth/logout.php')" class="block w-full text-center py-2.5 rounded-xl text-xs font-black text-red-500 hover:bg-red-50 dark:hover:bg-red-500/10 transition uppercase tracking-widest border border-transparent hover:border-red-100">
                Logout
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        
        <!-- Mobile Header -->
        <header class="md:hidden bg-white dark:bg-slate-900 border-b border-blue-50 dark:border-slate-800 p-5 flex justify-between items-center z-30">
            <div class="flex items-center space-x-3">
                <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i class="fas fa-layer-group"></i>
                </div>
                <span class="font-bold text-lg text-slate-800 dark:text-white leading-none">StepUp</span>
            </div>
            <button onclick="toggleSidebar()" class="text-slate-500 p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-12 scroll-smooth custom-scrollbar">
            <div class="max-w-4xl mx-auto space-y-10 pb-20">
                
                <!-- Welcome Header (Similar to Dashboard) -->
                <div class="mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div class="space-y-1">
                        <h1 class="text-3xl md:text-4xl font-black text-slate-900 dark:text-white leading-tight">Selamat Belajar, <span class="text-blue-600 dark:text-blue-400"><?php echo htmlspecialchars(explode(' ', $user_data['full_name'])[0]); ?>!</span> 🚀</h1>
                        <p class="text-slate-500 dark:text-slate-400 font-medium text-lg">Siap untuk meraih prestasi hari ini?</p>
                    </div>
                </div>

                <!-- Intro Section -->
                <div id="examIntro" class="bg-white dark:bg-slate-900 p-10 md:p-16 rounded-[3.5rem] shadow-2xl border border-blue-50 dark:border-slate-800 text-center relative overflow-hidden group">
                    <div class="absolute top-0 right-0 w-80 h-80 bg-blue-500/5 rounded-full -mr-40 -mt-40 blur-[80px] group-hover:bg-blue-500/10 transition-colors"></div>
                    <div class="relative z-10">
                        <div class="w-24 h-24 bg-blue-600 rounded-[2.5rem] text-white text-4xl flex items-center justify-center mx-auto mb-10 shadow-2xl shadow-blue-500/40 transform group-hover:scale-110 transition-transform duration-500">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h1 class="text-3xl md:text-5xl font-black text-slate-900 dark:text-white mb-3 tracking-tight">Ujian Akhir Semester</h1>
                        <p class="text-blue-600 dark:text-blue-400 font-black uppercase tracking-[0.4em] text-xs mb-12"><?php echo htmlspecialchars($subject_info['title']); ?></p>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-12 text-left">
                            <div class="bg-blue-50/50 dark:bg-slate-950/50 p-8 rounded-[2rem] border border-blue-100 dark:border-slate-800 group/card hover:bg-white dark:hover:bg-slate-800 transition shadow-sm">
                                <p class="text-[10px] text-blue-500 font-black uppercase tracking-widest mb-3">Total Soal</p>
                                <p class="text-3xl font-black text-slate-800 dark:text-white"><?php echo $total_q; ?> <span class="text-sm font-bold text-slate-400">Pertanyaan</span></p>
                            </div>
                            <div class="bg-blue-50/50 dark:bg-slate-950/50 p-8 rounded-[2rem] border border-blue-100 dark:border-slate-800 group/card hover:bg-white dark:hover:bg-slate-800 transition shadow-sm">
                                <p class="text-[10px] text-green-500 font-black uppercase tracking-widest mb-3">Passing Grade</p>
                                <p class="text-3xl font-black text-slate-800 dark:text-white">60% <span class="text-sm font-bold text-slate-400">(Min. <?php echo ceil($total_q * 0.6); ?>)</span></p>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-950/50 border border-blue-100 dark:border-slate-800 p-8 rounded-[2.5rem] mb-14 text-left shadow-inner">
                            <h4 class="font-black text-xs text-blue-600 dark:text-blue-400 mb-6 uppercase tracking-widest flex items-center">
                                <i class="fas fa-info-circle mr-3 text-lg"></i> Instruksi Ujian
                            </h4>
                            <ul class="space-y-4">
                                <li class="flex items-start space-x-4">
                                    <div class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-[10px]"></i></div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 font-bold leading-relaxed">Dilarang menyontek atau membuka tab lain selama ujian berlangsung.</p>
                                </li>
                                <li class="flex items-start space-x-4">
                                    <div class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-[10px]"></i></div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 font-bold leading-relaxed">Pastikan koneksi internet stabil hingga akhir pengiriman jawaban.</p>
                                </li>
                                <li class="flex items-start space-x-4">
                                    <div class="w-6 h-6 rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 flex items-center justify-center flex-shrink-0 mt-0.5"><i class="fas fa-check text-[10px]"></i></div>
                                    <p class="text-sm text-slate-500 dark:text-slate-400 font-bold leading-relaxed">Pengerjaan tidak dibatasi waktu, kerjakan dengan teliti dan jujur.</p>
                                </li>
                            </ul>
                        </div>

                        <button onclick="confirmStart()" class="w-full py-7 bg-blue-600 text-white font-black rounded-[2rem] hover:bg-blue-700 shadow-2xl shadow-blue-500/30 transition transform hover:-translate-y-1 active:scale-95 text-xl tracking-[0.2em] uppercase">
                            MULAI UJIAN SEKARANG
                        </button>
                        <a href="dashboard.php" class="block mt-8 text-slate-400 hover:text-blue-600 font-black transition text-xs uppercase tracking-widest">Batal & Pilih Mapel Lain</a>
                    </div>
                </div>

                <!-- Exam Content (Hidden by Default) -->
                <div id="examContent" class="hidden animate__animated animate__fadeIn">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-12">
                        <div>
                            <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-2"><?php echo htmlspecialchars($subject_info['title']); ?></h1>
                            <div class="flex items-center space-x-3 text-red-500">
                                <span class="w-2.5 h-2.5 bg-red-500 rounded-full animate-ping"></span>
                                <span class="text-[10px] font-black uppercase tracking-[0.3em]">Sesi Ujian Sedang Berjalan</span>
                            </div>
                        </div>
                        <div class="bg-white dark:bg-slate-900 px-8 py-4 rounded-3xl shadow-sm border border-blue-50 dark:border-slate-800 flex items-center space-x-4">
                            <i class="far fa-clock text-blue-600 text-xl"></i>
                            <span id="examTimer" class="text-2xl font-black text-slate-800 dark:text-white tabular-nums tracking-tighter">00:00:00</span>
                        </div>
                    </div>

                    <div class="bg-white dark:bg-slate-900 p-8 md:p-14 rounded-[3.5rem] shadow-2xl border border-blue-50 dark:border-slate-800">
                        <form method="POST" id="examForm" class="space-y-16">
                            <?php foreach ($exam_questions as $idx => $q): 
                                $q_id = $q['id'];
                                $opts = json_decode($q['options']);
                            ?>
                                <div class="question-container group">
                                    <div class="flex flex-col md:flex-row items-start gap-8">
                                        <div class="w-14 h-14 bg-slate-950 dark:bg-slate-800 text-white rounded-3xl flex flex-col items-center justify-center font-black group-focus-within:bg-blue-600 transition-all shadow-xl flex-shrink-0 transform group-hover:scale-110">
                                            <span class="text-[10px] opacity-40 uppercase leading-none mb-1">Q</span>
                                            <span class="text-xl leading-none"><?php echo $idx + 1; ?></span>
                                        </div>
                                        <div class="flex-1 w-full">
                                            <h4 class="text-2xl font-bold text-slate-800 dark:text-white leading-relaxed mb-10"><?php echo htmlspecialchars($q['question']); ?></h4>
                                            <div class="grid grid-cols-1 gap-4 max-w-2xl">
                                                <?php foreach ($opts as $oIdx => $opt): ?>
                                                    <label class="block relative group/opt">
                                                        <input type="radio" name="q<?php echo $q_id; ?>" value="<?php echo $oIdx; ?>" required class="peer hidden">
                                                        <div class="flex items-center p-6 rounded-3xl border-2 border-slate-50 dark:border-slate-800 peer-checked:border-blue-600 dark:peer-checked:border-blue-500 peer-checked:bg-blue-50/30 dark:peer-checked:bg-blue-900/10 hover:bg-slate-50 dark:hover:bg-slate-800/50 cursor-pointer transition-all duration-300">
                                                            <div class="w-10 h-10 rounded-2xl border-2 border-slate-200 dark:border-slate-700 peer-checked:border-blue-600 dark:peer-checked:border-blue-500 peer-checked:bg-blue-600 flex items-center justify-center transition-all bg-white dark:bg-slate-800 shadow-sm mr-6 overflow-hidden">
                                                                <span class="text-sm font-black text-slate-400 dark:text-slate-500 peer-checked:text-white uppercase">
                                                                    <?php echo chr(65 + $oIdx); ?>
                                                                </span>
                                                            </div>
                                                            <span class="text-slate-600 dark:text-slate-400 font-bold text-lg peer-checked:text-blue-950 dark:peer-checked:text-blue-100 transition"><?php echo htmlspecialchars($opt); ?></span>
                                                        </div>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <div class="pt-16 border-t border-slate-100 dark:border-slate-800/50">
                                <button type="button" onclick="confirmSubmit()"
                                    class="w-full py-8 bg-gradient-to-r from-blue-600 to-blue-800 text-white font-black rounded-[2.5rem] shadow-2xl shadow-blue-500/40 transition transform hover:-translate-y-1 text-2xl uppercase tracking-[0.3em] flex items-center justify-center space-x-6 group">
                                    <span>Kirim Jawaban</span>
                                    <i class="fas fa-paper-plane group-hover:translate-x-2 group-hover:-translate-y-2 transition-transform"></i>
                                </button>
                                <p class="text-center text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-[0.4em] mt-8">Jawaban akan diverifikasi secara otomatis oleh sistem kecerdasan buatan</p>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar.classList.contains('hidden')) {
                sidebar.classList.remove('hidden');
                sidebar.classList.add('fixed', 'inset-0', 'flex');
            } else {
                sidebar.classList.add('hidden');
                sidebar.classList.remove('fixed', 'inset-0', 'flex');
            }
        }

        let startTime = null;
        let timerInterval = null;

        function updateTimer() {
            if (!startTime) return;
            const now = new Date();
            const diff = Math.floor((now - startTime) / 1000);
            const h = String(Math.floor(diff / 3600)).padStart(2, '0');
            const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
            const s = String(diff % 60).padStart(2, '0');
            document.getElementById('examTimer').innerText = `${h}:${m}:${s}`;
        }

        function confirmStart() {
            confirmModernAlert({
                title: 'Siap Memulai?',
                text: "Pastikan koneksi internet kamu stabil dan kamu sudah siap untuk mengerjakan soal!",
                confirmButtonText: 'YA, MULAI!',
                cancelButtonText: 'BATAL'
            }).then((result) => {
                if (result.isConfirmed) startExam();
            });
        }

        function startExam() {
            document.getElementById('examIntro').classList.add('hidden');
            document.getElementById('examContent').classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
            startTime = new Date();
            timerInterval = setInterval(updateTimer, 1000);
        }

        function confirmSubmit() {
            const form = document.getElementById('examForm');
            const total = <?php echo $total_q; ?>;
            const checked = form.querySelectorAll('input[type="radio"]:checked').length;
            
            if (checked < total) {
                showModernAlert({
                    title: 'BELUM SELESAI!',
                    text: `Masih ada ${total - checked} pertanyaan yang belum terjawab. Harap lengkapi semua sebelum dikirim.`,
                    icon: 'warning',
                    showClass: { popup: 'animate__animated animate__shakeX animate__faster' }
                });
                return;
            }

            confirmModernAlert({
                title: 'AKHIRI UJIAN?',
                text: "Kamu tidak bisa mengubah jawaban setelah dikirimkan ke sistem!",
                confirmButtonText: 'YA, SELESAI!',
                cancelButtonText: 'CEK DULU'
            }).then((result) => {
                if (result.isConfirmed) form.submit();
            });
        }
    </script>
    <!-- Include Chat Widget -->
    <?php include 'inc_chat_widget.php'; ?>

    <script>
        // Hide chat when exam starts
        const originalStartExam = window.startExam;
        window.startExam = function() {
            if(window.toggleChatVisibility) window.toggleChatVisibility(false);
            if(originalStartExam) originalStartExam();
        }
    </script>
</body>
</html>
