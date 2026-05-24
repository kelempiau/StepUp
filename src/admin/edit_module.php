<?php
// src/admin/edit_module.php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$id = $_GET['id'] ?? 0;
if (!$id) { header("Location: subjects.php"); exit; }

// Fetch Admin Data
$stmtHeader = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtHeader->execute([$_SESSION['user_id']]);
$admin_data = $stmtHeader->fetch();

// Fetch Module with Topic & Subject info
$stmt = $pdo->prepare("
    SELECT m.*, t.title as topic_title, s.title as subject_title, s.id as subject_id 
    FROM modules m
    JOIN topics t ON m.topic_id = t.id
    JOIN subjects s ON t.subject_id = s.id
    WHERE m.id = ?
");
$stmt->execute([$id]);
$module = $stmt->fetch();
if (!$module) die("Module not found");

// Fetch Material (Assume single material for now)
$stmt = $pdo->prepare("SELECT * FROM module_materials WHERE module_id = ?");
$stmt->execute([$id]);
$materials = $stmt->fetch();
$content = $materials ? $materials['content'] : '';

// Fetch Questions
$stmt = $pdo->prepare("SELECT * FROM module_questions WHERE module_id = ? ORDER BY id ASC");
$stmt->execute([$id]);
$questions = $stmt->fetchAll();

// Handle Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_content') {
        $title = $_POST['title'];
        $video = $_POST['video_url'];
        $new_content = $_POST['content'];
        
        // Update Module
        $upd = $pdo->prepare("UPDATE modules SET title = ?, video_url = ? WHERE id = ?");
        $upd->execute([$title, $video, $id]);
        
        // Update Material (Upsert)
        if ($materials) {
            $updMat = $pdo->prepare("UPDATE module_materials SET content = ? WHERE id = ?");
            $updMat->execute([$new_content, $materials['id']]);
        } else {
            $insMat = $pdo->prepare("INSERT INTO module_materials (module_id, title, content) VALUES (?, 'Materi Utama', ?)");
            $insMat->execute([$id, $new_content]);
        }
        
        header("Location: edit_module.php?id=$id&status=saved");
        exit;
    }
    // Add Question
    if (isset($_POST['action']) && $_POST['action'] === 'add_question') {
        $q_text = $_POST['question'];
        $opts = [$_POST['opt0'], $_POST['opt1'], $_POST['opt2'], $_POST['opt3']];
        $ans = (int)$_POST['answer'];
        
        $insQ = $pdo->prepare("INSERT INTO module_questions (module_id, question, options, answer) VALUES (?, ?, ?, ?)");
        $insQ->execute([$id, $q_text, json_encode($opts), $ans]);
        header("Location: edit_module.php?id=$id&tab=quiz");
        exit;
    }

    // Edit Question
    if (isset($_POST['action']) && $_POST['action'] === 'edit_question') {
        $qid = $_POST['question_id'];
        $q_text = $_POST['question'];
        $opts = [$_POST['opt0'], $_POST['opt1'], $_POST['opt2'], $_POST['opt3']];
        $ans = (int)$_POST['answer'];
        
        $updQ = $pdo->prepare("UPDATE module_questions SET question = ?, options = ?, answer = ? WHERE id = ?");
        $updQ->execute([$q_text, json_encode($opts), $ans, $qid]);
        header("Location: edit_module.php?id=$id&tab=quiz&status=saved");
        exit;
    }
}

// Delete Question logic separate GET
if (isset($_GET['delete_q'])) {
    $qid = $_GET['delete_q'];
    $del = $pdo->prepare("DELETE FROM module_questions WHERE id = ?");
    $del->execute([$qid]);
    header("Location: edit_module.php?id=$id&tab=quiz");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id" class="dark:bg-[#060b1d]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Modul - <?php echo $module['title']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Quill Editor -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        #quill_editor { border-radius: 1.5rem; height: 500px; font-size: 16px; border: none !important; }
        .ql-toolbar { border: none !important; border-bottom: 1px solid #f1f5f9 !important; padding: 1.25rem !important; background: #f8fafc; border-radius: 1.5rem 1.5rem 0 0; }
        .dark .ql-toolbar { border-bottom: 1px solid rgba(255,255,255,0.05) !important; background: rgba(15, 23, 42, 0.5) !important; color: #cbd5e1 !important; }
        .dark .ql-toolbar .ql-stroke { stroke: #94a3b8 !important; }
        .dark .ql-toolbar .ql-fill { fill: #94a3b8 !important; }
        .dark .ql-toolbar .ql-picker { color: #94a3b8 !important; }
        .ql-container { border: none !important; background: white; border-radius: 0 0 1.5rem 1.5rem; }
        .dark .ql-container { background: rgba(6, 11, 29, 0.4) !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; }
        .dark .ql-editor.ql-blank::before { color: #475569 !important; }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .dark .glass-card {
            background: rgba(10, 17, 40, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(30, 58, 138, 0.2);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/auth.js"></script>
</head>
<body class="bg-blue-50/30 text-slate-800 dark:bg-[#060b1d] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        
        <!-- Mobile Header -->
        <header class="md:hidden bg-white/80 dark:bg-[#0a1128]/80 backdrop-blur-xl border-b border-blue-50 dark:border-blue-900/20 p-5 flex justify-between items-center z-30">
            <span class="font-bold text-lg text-blue-600">AdminPanel</span>
            <button onclick="toggleSidebar()" class="text-slate-500 p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar pb-32">
            <?php
            // Prepare question data for JS editing
            $q_data_js = [];
            foreach($questions as $q) {
                $q_data_js[$q['id']] = [
                    'question' => $q['question'],
                    'options' => json_decode($q['options']),
                    'answer' => $q['answer']
                ];
            }
            ?>

            <div class="max-w-5xl mx-auto">
                <header class="flex flex-col md:flex-row md:items-center justify-between gap-6 mb-10">
                    <div class="flex items-center space-x-5">
                        <a href="modules.php?topic_id=<?php echo $module['topic_id']; ?>" 
                           class="w-12 h-12 flex items-center justify-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-500 hover:text-emerald-600 hover:border-emerald-200 dark:hover:border-emerald-900/30 transition-all shadow-sm group">
                            <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                        </a>
                        <div>
                            <div class="flex items-center space-x-2 text-[10px] font-black uppercase tracking-widest text-slate-400 mb-1">
                                <span><?php echo htmlspecialchars($module['subject_title']); ?></span>
                                <i class="fas fa-chevron-right text-[8px]"></i>
                                <span><?php echo htmlspecialchars($module['topic_title']); ?></span>
                            </div>
                            <h1 class="text-3xl font-black text-slate-900 dark:text-white leading-tight">Edit Modul</h1>
                        </div>
                    </div>

                    <div class="flex items-center space-x-2 bg-emerald-50 dark:bg-emerald-900/20 px-4 py-2 rounded-2xl border border-emerald-100 dark:border-emerald-900/30">
                        <div class="w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></div>
                        <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-widest">Editing Mode</span>
                    </div>
                </header>

                <!-- Navigation Tabs -->
                <div class="flex space-x-2 bg-slate-200/50 dark:bg-slate-800/40 p-1.5 rounded-2xl mb-10 w-fit glass-card" id="tabs">
                    <button onclick="switchTab('content')" id="tab-btn-content" 
                        class="px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all duration-300 flex items-center space-x-3 bg-emerald-600 text-white shadow-lg shadow-emerald-500/30">
                        <i class="fas fa-file-lines"></i>
                        <span>Materi & Video</span>
                    </button>
                    <button onclick="switchTab('quiz')" id="tab-btn-quiz" 
                        class="px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all duration-300 flex items-center space-x-3 text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-800/50">
                        <i class="fas fa-clipboard-question"></i>
                        <span>Bank Soal (<?php echo count($questions); ?>)</span>
                    </button>
                </div>

                <!-- Content Tab -->
                <div id="tab-content" class="space-y-8 animate__animated animate__fadeIn">
                    <div class="glass-card rounded-[2.5rem] p-8 md:p-12 shadow-2xl shadow-blue-500/[0.03]">
                        <form method="POST" id="form_update_content">
                            <input type="hidden" name="action" value="update_content">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-10">
                                <div class="space-y-3">
                                    <label class="flex items-center space-x-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">
                                        <i class="fas fa-heading text-emerald-500"></i>
                                        <span>Judul Modul</span>
                                    </label>
                                    <input type="text" name="title" value="<?php echo htmlspecialchars($module['title']); ?>" 
                                        class="w-full px-6 py-4 bg-white/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm font-bold dark:text-white transition-all"
                                        placeholder="Masukkan judul modul...">
                                </div>
                                <div class="space-y-3">
                                    <label class="flex items-center space-x-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">
                                        <i class="fab fa-youtube text-red-500"></i>
                                        <span>URL Video (YouTube)</span>
                                    </label>
                                    <input type="text" name="video_url" value="<?php echo htmlspecialchars($module['video_url']); ?>" 
                                        class="w-full px-6 py-4 bg-white/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 outline-none text-sm font-bold dark:text-white transition-all" 
                                        placeholder="https://www.youtube.com/embed/...">
                                </div>
                            </div>

                            <div class="mb-10">
                                <label class="flex items-center space-x-2 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-4 ml-2">
                                    <i class="fas fa-paragraph text-emerald-500"></i>
                                    <span>Konten Materi</span>
                                </label>
                                <div class="rounded-[2rem] border border-slate-200 dark:border-slate-800 overflow-hidden shadow-inner">
                                    <div id="quill_editor" class="dark:text-slate-200"><?php echo $content; ?></div>
                                </div>
                                <input type="hidden" name="content" id="content_hidden">
                                <div class="mt-6 flex items-start space-x-3 px-4">
                                    <i class="fas fa-circle-info text-emerald-500 mt-0.5 text-xs"></i>
                                    <p class="text-xs text-slate-400 font-medium leading-relaxed">
                                        Gunakan toolbar di atas untuk mengatur format teks, menyisipkan gambar, atau kode. 
                                        Klik <span class="font-bold text-emerald-600 dark:text-emerald-400">Simpan Materi</span> di bawah untuk menerapkan perubahan secara permanen.
                                    </p>
                                </div>
                            </div>

                            <div class="flex justify-end pt-8 border-t border-slate-100 dark:border-slate-800/50">
                                <button type="submit" 
                                    class="px-12 py-5 bg-emerald-600 text-white font-black rounded-2xl hover:bg-emerald-700 shadow-xl shadow-emerald-500/30 transition transform hover:-translate-y-1 active:scale-95 flex items-center space-x-3 uppercase tracking-widest text-xs">
                                    <i class="fas fa-cloud-arrow-up"></i>
                                    <span>Simpan Materi</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Quiz Tab -->
                <div id="tab-quiz" class="hidden space-y-12 animate__animated animate__fadeIn">
                     <!-- Add Question Card -->
                     <div class="glass-card rounded-[2.5rem] p-8 md:p-12 shadow-2xl shadow-blue-500/[0.03]">
                        <div class="flex items-center space-x-4 mb-10">
                            <div class="w-14 h-14 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center shadow-sm border border-emerald-100 dark:border-emerald-900/30">
                                <i class="fas fa-plus-circle text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="font-black text-2xl text-slate-800 dark:text-white">Buat Pertanyaan Baru</h3>
                                <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mt-1">Tambahkan kuis untuk menguji pemahaman siswa</p>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="action" value="add_question">
                            <div class="mb-8 space-y-3">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Pertanyaan</label>
                                <textarea name="question" rows="4" required 
                                    class="w-full px-6 py-5 bg-white/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-[1.5rem] focus:ring-2 focus:ring-emerald-500 outline-none text-sm font-bold dark:text-white transition-all" 
                                    placeholder="Apa yang ingin Anda tanyakan?"></textarea>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                                <div class="space-y-4">
                                    <div class="relative group">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500">A</div>
                                        <input type="text" name="opt0" placeholder="Pilihan Jawaban A" required 
                                            class="w-full pl-16 pr-6 py-4 bg-white/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                                    </div>
                                    <div class="relative group">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500">B</div>
                                        <input type="text" name="opt1" placeholder="Pilihan Jawaban B" required 
                                            class="w-full pl-16 pr-6 py-4 bg-white/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                                    </div>
                                </div>
                                <div class="space-y-4">
                                    <div class="relative group">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500">C</div>
                                        <input type="text" name="opt2" placeholder="Pilihan Jawaban C" required 
                                            class="w-full pl-16 pr-6 py-4 bg-white/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                                    </div>
                                    <div class="relative group">
                                        <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-[10px] font-black text-slate-500">D</div>
                                        <input type="text" name="opt3" placeholder="Pilihan Jawaban D" required 
                                            class="w-full pl-16 pr-6 py-4 bg-white/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 pt-10 border-t border-slate-100 dark:border-slate-800/50">
                                <div class="w-full md:max-w-xs space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Kunci Jawaban</label>
                                    <div class="relative">
                                        <select name="answer" class="w-full px-6 py-4 bg-white/50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-black text-emerald-600 dark:text-emerald-400 appearance-none cursor-pointer focus:ring-2 focus:ring-emerald-500 outline-none">
                                            <option value="0">Opsi A (Pilihan Pertama)</option>
                                            <option value="1">Opsi B (Pilihan Kedua)</option>
                                            <option value="2">Opsi C (Pilihan Ketiga)</option>
                                            <option value="3">Opsi D (Pilihan Keempat)</option>
                                        </select>
                                        <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                            <i class="fas fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" 
                                    class="px-10 py-5 bg-emerald-600 text-white font-black rounded-2xl hover:bg-emerald-700 shadow-xl shadow-emerald-500/30 transition transform hover:-translate-y-1 active:scale-95 flex items-center justify-center space-x-3 uppercase tracking-widest text-xs">
                                    <i class="fas fa-save"></i>
                                    <span>Simpan Soal</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- List Questions -->
                    <div class="space-y-8 pb-10">
                        <div class="flex items-center justify-between px-4">
                            <div class="flex items-center space-x-4">
                                <div class="w-1 h-8 bg-emerald-500 rounded-full"></div>
                                <div>
                                    <h4 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em]">Bank Soal Tersimpan</h4>
                                    <p class="text-[10px] text-slate-400 font-bold mt-1 uppercase">Daftar pertanyaan yang telah dibuat</p>
                                </div>
                            </div>
                            <div class="px-5 py-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-100 dark:border-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-xl text-[10px] font-black uppercase tracking-widest">
                                <?php echo count($questions); ?> Total Soal
                            </div>
                        </div>

                        <?php if (empty($questions)): ?>
                            <div class="glass-card rounded-[3rem] p-20 text-center border-2 border-dashed border-slate-200 dark:border-slate-800/50">
                                <div class="w-24 h-24 bg-white dark:bg-slate-900 rounded-[2rem] flex items-center justify-center text-emerald-200 dark:text-emerald-900 mx-auto mb-8 shadow-sm border border-slate-100 dark:border-slate-800">
                                    <i class="fas fa-layer-group text-4xl"></i>
                                </div>
                                <h3 class="text-2xl font-black text-slate-800 dark:text-slate-200">Bank Soal Kosong</h3>
                                <p class="text-sm text-slate-400 dark:text-slate-500 mt-4 max-w-md mx-auto font-medium">Anda belum menambahkan pertanyaan untuk modul ini. Silakan gunakan formulir di atas untuk mulai membuat soal kuis.</p>
                            </div>
                        <?php endif; ?>

                        <div class="grid grid-cols-1 gap-8">
                            <?php foreach ($questions as $idx => $q): 
                                $opts = json_decode($q['options']);    
                            ?>
                                <div class="glass-card p-10 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 flex flex-col lg:flex-row justify-between items-start gap-12 group hover:border-emerald-500 dark:hover:border-emerald-800 transition-all duration-500 hover:shadow-2xl hover:shadow-emerald-500/[0.05] relative overflow-hidden">
                                    <!-- Decoration -->
                                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-emerald-500/[0.03] dark:bg-emerald-500/[0.02] rounded-full blur-3xl group-hover:bg-emerald-500/10 transition-all duration-700"></div>

                                    <div class="flex-1 w-full relative z-10">
                                        <div class="flex items-center space-x-3 mb-8">
                                            <span class="px-5 py-2 bg-slate-900 dark:bg-emerald-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-emerald-500/20">
                                                Pertanyaan #<?php echo $idx + 1; ?>
                                            </span>
                                        </div>
                                        <p class="text-2xl font-black text-slate-800 dark:text-white mb-10 leading-relaxed"><?php echo htmlspecialchars($q['question']); ?></p>
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <?php foreach ($opts as $oi => $opt): 
                                                $isCorrect = ($oi == $q['answer']);
                                            ?>
                                                <div class="flex items-center space-x-5 p-6 rounded-[1.5rem] transition-all border <?php echo $isCorrect ? 'bg-emerald-50/50 dark:bg-emerald-900/10 border-emerald-200 dark:border-emerald-900/40 ring-1 ring-emerald-500/20' : 'bg-white/40 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800'; ?>">
                                                    <div class="w-10 h-10 rounded-xl <?php echo $isCorrect ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/30' : 'bg-white dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-100 dark:border-slate-700'; ?> flex items-center justify-center text-xs font-black shrink-0 transition-all">
                                                        <?php echo chr(65 + $oi); ?>
                                                    </div>
                                                    <span class="text-sm <?php echo $isCorrect ? 'text-emerald-700 dark:text-emerald-400 font-extrabold' : 'text-slate-600 dark:text-slate-400 font-bold'; ?>"><?php echo htmlspecialchars($opt); ?></span>
                                                    <?php if($isCorrect): ?>
                                                        <i class="fas fa-check-circle text-emerald-500 ml-auto"></i>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="flex lg:flex-col items-center gap-4 w-full lg:w-auto relative z-10 pt-8 lg:pt-0 border-t lg:border-t-0 lg:border-l border-slate-100 dark:border-slate-800 lg:pl-12">
                                        <button onclick="editQuestion(<?php echo $q['id']; ?>)" 
                                            class="flex-1 lg:flex-none w-14 h-14 bg-emerald-50 dark:bg-emerald-600/20 text-emerald-600 dark:text-emerald-400 rounded-2xl hover:bg-emerald-600 hover:text-white transition-all shadow-sm group/btn flex items-center justify-center">
                                            <i class="fas fa-edit group-hover/btn:scale-110 transition-transform"></i>
                                        </button>
                                        <button onclick="confirmDeleteQ(<?php echo $q['id']; ?>)" 
                                            class="flex-1 lg:flex-none w-14 h-14 bg-red-50 dark:bg-red-500/20 text-red-500 rounded-2xl hover:bg-red-500 hover:text-white transition-all shadow-sm group/btn flex items-center justify-center">
                                            <i class="fas fa-trash-alt group-hover/btn:rotate-12 transition-transform"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Question Modal -->
    <div id="editQuestionModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 animate__animated animate__fadeIn">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" onclick="closeEditModal()"></div>
        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-10 md:p-14 max-w-3xl w-full shadow-2xl border border-slate-100 dark:border-blue-900/10 relative z-10 animate__animated animate__zoomIn">
            <div class="flex justify-between items-center mb-12">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-edit text-xl"></i>
                    </div>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white leading-tight">Edit Pertanyaan</h3>
                </div>
                <button onclick="closeEditModal()" class="w-12 h-12 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-2xl hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-500 transition-all flex items-center justify-center">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form method="POST">
                <input type="hidden" name="action" value="edit_question">
                <input type="hidden" name="question_id" id="edit_q_id">
                
                <div class="mb-8 space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Pertanyaan</label>
                    <textarea name="question" id="edit_q_text" rows="4" required 
                        class="w-full px-8 py-6 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-[2rem] focus:ring-2 focus:ring-emerald-500 outline-none text-base font-bold dark:text-white transition-all"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                    <div class="space-y-4">
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center text-[10px] font-black text-slate-500">A</div>
                            <input type="text" name="opt0" id="edit_q_opt0" placeholder="Pilihan A" required 
                                class="w-full pl-16 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                        </div>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center text-[10px] font-black text-slate-500">B</div>
                            <input type="text" name="opt1" id="edit_q_opt1" placeholder="Pilihan B" required 
                                class="w-full pl-16 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center text-[10px] font-black text-slate-500">C</div>
                            <input type="text" name="opt2" id="edit_q_opt2" placeholder="Pilihan C" required 
                                class="w-full pl-16 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                        </div>
                        <div class="relative">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center text-[10px] font-black text-slate-500">D</div>
                            <input type="text" name="opt3" id="edit_q_opt3" placeholder="Pilihan D" required 
                                class="w-full pl-16 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all">
                        </div>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 pt-12 border-t border-slate-100 dark:border-slate-800 mt-10">
                    <div class="w-full md:max-w-xs space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Kunci Jawaban</label>
                        <div class="relative">
                            <select name="answer" id="edit_q_ans" 
                                class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-black text-emerald-600 dark:text-emerald-400 appearance-none cursor-pointer focus:ring-2 focus:ring-emerald-500 outline-none">
                                <option value="0">Opsi A</option>
                                <option value="1">Opsi B</option>
                                <option value="2">Opsi C</option>
                                <option value="3">Opsi D</option>
                            </select>
                            <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>
                    <button type="submit" 
                        class="px-12 py-5 bg-emerald-600 text-white font-black rounded-2xl hover:bg-emerald-700 shadow-xl shadow-emerald-500/30 transition transform hover:-translate-y-1 active:scale-95 flex items-center justify-center space-x-3 uppercase tracking-widest text-xs">
                        <i class="fas fa-check-double"></i>
                        <span>Simpan Perubahan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>


    <script>
        const qData = <?php echo json_encode($q_data_js); ?>;

        function editQuestion(id) {
            const q = qData[id];
            document.getElementById('edit_q_id').value = id;
            document.getElementById('edit_q_text').value = q.question;
            document.getElementById('edit_q_opt0').value = q.options[0];
            document.getElementById('edit_q_opt1').value = q.options[1];
            document.getElementById('edit_q_opt2').value = q.options[2];
            document.getElementById('edit_q_opt3').value = q.options[3];
            document.getElementById('edit_q_ans').value = q.answer;
            
            const modal = document.getElementById('editQuestionModal');
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeEditModal() {
            const modal = document.getElementById('editQuestionModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function switchTab(tab) {
            document.getElementById('tab-content').classList.toggle('hidden', tab !== 'content');
            document.getElementById('tab-quiz').classList.toggle('hidden', tab !== 'quiz');
            
            const btnContent = document.getElementById('tab-btn-content');
            const btnQuiz = document.getElementById('tab-btn-quiz');
            
            if(tab === 'content') {
                btnContent.className = "px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all duration-300 flex items-center space-x-3 bg-emerald-600 text-white shadow-lg shadow-emerald-500/30";
                btnQuiz.className = "px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all duration-300 flex items-center space-x-3 text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-800/50";
            } else {
                btnQuiz.className = "px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all duration-300 flex items-center space-x-3 bg-emerald-600 text-white shadow-lg shadow-emerald-500/30";
                btnContent.className = "px-8 py-3 rounded-xl font-black text-xs uppercase tracking-widest transition-all duration-300 flex items-center space-x-3 text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-800/50";
            }
        }
        
        function confirmDeleteQ(qid) {
            confirmModernAlert({
                title: 'HAPUS PERTANYAAN?',
                text: 'Data yang dihapus tidak dapat dikembalikan!',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'YA, HAPUS'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?id=<?php echo $id; ?>&tab=quiz&delete_q=${qid}`;
                }
            });
        }

        const quill = new Quill('#quill_editor', {
            theme: 'snow',
            placeholder: 'Tuliskan materi pembelajaran di sini...',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['link', 'image', 'video', 'code-block'],
                    ['clean']
                ]
            }
        });

        // Set form content before submit
        document.getElementById('form_update_content').onsubmit = function() {
            document.getElementById('content_hidden').value = quill.root.innerHTML;
        };

        const urlParams = new URLSearchParams(window.location.search);
        if(urlParams.get('tab') === 'quiz') {
            switchTab('quiz');
        }

        const status = urlParams.get('status');
        if (status === 'saved') {
            showModernAlert({ 
                title: 'BERHASIL!', 
                text: 'Perubahan telah disimpan dengan aman ke sistem.', 
                icon: 'success' 
            });
        }
    </script>
</body>
</html>
