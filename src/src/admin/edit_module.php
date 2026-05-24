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

// Fetch Module
$stmt = $pdo->prepare("SELECT * FROM modules WHERE id = ?");
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
    if ($_POST['action'] === 'update_content') {
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
    if ($_POST['action'] === 'add_question') {
        $q_text = $_POST['question'];
        $opts = [$_POST['opt0'], $_POST['opt1'], $_POST['opt2'], $_POST['opt3']];
        $ans = (int)$_POST['answer'];
        
        $insQ = $pdo->prepare("INSERT INTO module_questions (module_id, question, options, answer) VALUES (?, ?, ?, ?)");
        $insQ->execute([$id, $q_text, json_encode($opts), $ans]);
        header("Location: edit_module.php?id=$id&tab=quiz");
        exit;
    }

    // Edit Question
    if ($_POST['action'] === 'edit_question') {
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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Modul - <?php echo $module['title']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Quill Editor (No API Key Required) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        #quill_editor { border-radius: 1.5rem; height: 400px; font-size: 16px; border: none !important; }
        .ql-toolbar { border: none !important; border-bottom: 1px solid #f1f5f9 !important; padding: 1rem !important; }
        .dark .ql-toolbar { border-bottom: 1px solid rgba(255,255,255,0.05) !important; color: #cbd5e1 !important; }
        .dark .ql-toolbar .ql-stroke { stroke: #94a3b8 !important; }
        .dark .ql-toolbar .ql-fill { fill: #94a3b8 !important; }
        .dark .ql-toolbar .ql-picker { color: #94a3b8 !important; }
        .ql-container { border: none !important; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; }
        .dark .ql-editor.ql-blank::before { color: #475569 !important; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/auth.js"></script>
</head>
<body class="bg-blue-50/50 text-slate-800 dark:bg-[#060b1d] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        
        <!-- Mobile Header -->
        <header class="md:hidden bg-white dark:bg-[#0a1128] border-b border-blue-50 dark:border-blue-900/20 p-5 flex justify-between items-center z-30">
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

    <div class="max-w-5xl mx-auto mb-20">
        <header class="flex items-center space-x-4 mb-8">
            <a href="modules.php?topic_id=<?php echo $module['topic_id']; ?>" class="p-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl text-slate-500 hover:text-blue-600 transition shadow-sm"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white leading-tight">Manajemen Modul</h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Topik ID: <?php echo $module['topic_id']; ?></p>
            </div>
        </header>

        <!-- Status Alert Logic Removed (Handled by showModernAlert script at bottom) -->

        <!-- Tabs -->
        <div class="flex space-x-1 bg-slate-200 dark:bg-slate-800/50 p-1 rounded-xl mb-8 w-fit shadow-inner" id="tabs">
            <button onclick="switchTab('content')" id="tab-btn-content" class="px-6 py-2.5 rounded-lg font-black text-xs uppercase tracking-widest transition bg-white dark:bg-blue-600 text-blue-600 dark:text-white shadow-sm">Materi & Video</button>
            <button onclick="switchTab('quiz')" id="tab-btn-quiz" class="px-6 py-2.5 rounded-lg font-black text-xs uppercase tracking-widest transition text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-800/50">Bank Soal (<?php echo count($questions); ?>)</button>
        </div>

        <!-- Content Tab -->
        <div id="tab-content" class="space-y-6">
            <div class="bg-white dark:bg-[#0a1128] rounded-[2rem] p-8 md:p-10 shadow-sm border border-slate-100 dark:border-blue-900/10">
                <form method="POST" id="form_update_content">
                    <input type="hidden" name="action" value="update_content">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Judul Modul</label>
                            <input type="text" name="title" value="<?php echo htmlspecialchars($module['title']); ?>" class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-white">
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">URL Video (YouTube)</label>
                            <input type="text" name="video_url" value="<?php echo htmlspecialchars($module['video_url']); ?>" class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-white" placeholder="https://www.youtube.com/embed/...">
                        </div>
                    </div>
                    <div class="mb-10">
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Konten Materi</label>
                        <div class="bg-slate-50 dark:bg-[#060b1d] rounded-[2rem] border border-slate-100 dark:border-blue-900/10 overflow-hidden">
                            <div id="quill_editor" class="dark:text-slate-200"><?php echo $content; ?></div>
                        </div>
                        <input type="hidden" name="content" id="content_hidden">
                        <p class="text-xs text-slate-400 mt-4 font-medium italic">* Gunakan toolbar di atas untuk mengatur format teks. Klik Simpan Materi untuk menyimpan perubahan.</p>
                    </div>
                    <div class="flex justify-end pt-6 border-t border-slate-50">
                        <button type="submit" class="px-10 py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition transform hover:-translate-y-1 active:scale-95">Simpan Materi</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Quiz Tab -->
        <div id="tab-quiz" class="hidden space-y-8">
             <!-- Add Question (Styled Card) -->
             <div class="bg-white dark:bg-[#0a1128] rounded-[2rem] p-8 md:p-10 shadow-sm border border-slate-100 dark:border-blue-900/10">
                <div class="flex items-center space-x-3 mb-8">
                    <div class="w-10 h-10 bg-green-50 dark:bg-green-900/20 text-green-600 dark:text-green-400 rounded-xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-plus"></i>
                    </div>
                    <h3 class="font-black text-xl text-slate-800 dark:text-white">Tambah Pertanyaan</h3>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_question">
                    <div class="mb-6">
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Isi Pertanyaan</label>
                        <textarea name="question" rows="3" required class="w-full px-5 py-4 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-white" placeholder="Tuliskan pertanyaan di sini..."></textarea>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                        <div class="space-y-4">
                            <input type="text" name="opt0" placeholder="Pilihan A" required class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-xl text-sm font-bold dark:text-white">
                            <input type="text" name="opt1" placeholder="Pilihan B" required class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-xl text-sm font-bold dark:text-white">
                        </div>
                        <div class="space-y-4">
                            <input type="text" name="opt2" placeholder="Pilihan C" required class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-xl text-sm font-bold dark:text-white">
                            <input type="text" name="opt3" placeholder="Pilihan D" required class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-xl text-sm font-bold dark:text-white">
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row md:items-end justify-between space-y-4 md:space-y-0 pt-6 border-t border-slate-50 dark:border-blue-900/10">
                        <div class="w-full md:max-w-xs">
                            <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Kunci Jawaban</label>
                            <select name="answer" class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-xl text-sm font-black text-blue-600 dark:text-blue-400 cursor-pointer">
                                <option value="0">PILIHAN A</option>
                                <option value="1">PILIHAN B</option>
                                <option value="2">PILIHAN C</option>
                                <option value="3">PILIHAN D</option>
                            </select>
                        </div>
                        <button type="submit" class="px-8 py-4 bg-green-600 text-white font-black rounded-2xl hover:bg-green-700 shadow-xl shadow-green-500/30 transition transform hover:-translate-y-1 active:scale-95 uppercase tracking-widest text-xs">Simpan Soal</button>
                    </div>
                </form>
            </div>

            <!-- List Questions -->
            <div class="space-y-8">
                <div class="flex items-center justify-between px-2">
                    <div class="flex items-center space-x-3">
                        <div class="w-1.5 h-6 bg-blue-600 rounded-full"></div>
                        <h4 class="text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em]">Bank Soal Tersimpan</h4>
                    </div>
                    <span class="px-3 py-1 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-lg text-[10px] font-black uppercase tracking-widest"><?php echo count($questions); ?> Soal</span>
                </div>

                <?php if (empty($questions)): ?>
                    <div class="bg-slate-50 dark:bg-blue-900/5 border-2 border-dashed border-slate-100 dark:border-blue-900/20 rounded-[2.5rem] p-16 text-center">
                        <div class="w-20 h-20 bg-white dark:bg-[#0a1128] rounded-3xl flex items-center justify-center text-blue-300 dark:text-blue-800 mx-auto mb-6 shadow-sm border border-slate-50 dark:border-blue-900/10">
                            <i class="fas fa-clipboard-question text-3xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-slate-800 dark:text-slate-300">Belum ada soal kuis</h3>
                        <p class="text-sm text-slate-400 dark:text-slate-500 mt-2">Gunakan formulir di atas untuk membuat pertanyaan pertama.</p>
                    </div>
                <?php endif; ?>

                <?php foreach ($questions as $idx => $q): 
                    $opts = json_decode($q['options']);    
                ?>
                    <div class="bg-white dark:bg-[#0a1128] p-10 rounded-[2.5rem] border border-slate-50 dark:border-blue-900/10 flex flex-col md:flex-row justify-between items-start gap-10 group hover:border-blue-300 dark:hover:border-blue-800 transition-all hover:shadow-2xl hover:shadow-blue-500/[0.03] relative">
                        <div class="flex-1 w-full">
                            <div class="flex items-center space-x-3 mb-6">
                                <span class="px-5 py-1.5 bg-slate-900 dark:bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-500/20">SOAL #<?php echo $idx + 1; ?></span>
                            </div>
                            <p class="text-xl font-black text-slate-800 dark:text-white mb-8 leading-relaxed"><?php echo htmlspecialchars($q['question']); ?></p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <?php foreach ($opts as $oi => $opt): 
                                    $isCorrect = ($oi == $q['answer']);
                                ?>
                                    <div class="flex items-center space-x-4 p-5 rounded-3xl transition-all border <?php echo $isCorrect ? 'bg-green-50/50 dark:bg-green-900/10 border-green-100 dark:border-green-900/30' : 'bg-slate-50/50 dark:bg-[#060b1d] border-slate-50 dark:border-blue-900/10'; ?>">
                                        <div class="w-8 h-8 rounded-xl <?php echo $isCorrect ? 'bg-green-500 text-white' : 'bg-white dark:bg-slate-800 text-slate-400 dark:text-slate-500'; ?> flex items-center justify-center text-xs font-black shadow-sm border border-inherit">
                                            <?php echo chr(65 + $oi); ?>
                                        </div>
                                        <span class="text-sm <?php echo $isCorrect ? 'text-green-700 dark:text-green-400 font-extrabold' : 'text-slate-600 dark:text-slate-400 font-bold'; ?>"><?php echo htmlspecialchars($opt); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="flex md:flex-col space-x-2 md:space-x-0 md:space-y-3 w-full md:w-auto">
                            <button onclick="editQuestion(<?php echo $q['id']; ?>)" class="flex-1 md:flex-none p-5 bg-blue-50 dark:bg-blue-600/20 text-blue-600 dark:text-blue-400 rounded-2xl hover:bg-blue-600 hover:text-white transition shadow-sm group/btn">
                                <i class="fas fa-edit group-hover/btn:scale-110 transition-transform"></i>
                            </button>
                            <button onclick="confirmDeleteQ(<?php echo $q['id']; ?>)" class="flex-1 md:flex-none p-5 bg-red-50 dark:bg-red-500/20 text-red-500 rounded-2xl hover:bg-red-500 hover:text-white transition shadow-sm group/btn">
                                <i class="fas fa-trash-alt group-hover/btn:rotate-12 transition-transform"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- Edit Question Modal -->
    <div id="editQuestionModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white dark:bg-[#0a1128] rounded-[2.5rem] p-8 md:p-10 max-w-2xl w-full shadow-2xl border border-slate-100 dark:border-blue-900/10">
            <div class="flex justify-between items-center mb-10">
                <h3 class="text-2xl font-black text-slate-900 dark:text-white leading-tight">Edit Pertanyaan</h3>
                <button onclick="document.getElementById('editQuestionModal').classList.add('hidden')" class="w-10 h-10 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-full hover:bg-slate-200 dark:hover:bg-slate-700 transition">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="edit_question">
                <input type="hidden" name="question_id" id="edit_q_id">
                <div class="mb-6">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Isi Pertanyaan</label>
                    <textarea name="question" id="edit_q_text" rows="3" required class="w-full px-5 py-4 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none text-sm font-bold dark:text-white"></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                    <div class="space-y-4">
                        <input type="text" name="opt0" id="edit_q_opt0" placeholder="Pilihan A" required class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-xl text-sm font-bold dark:text-white">
                        <input type="text" name="opt1" id="edit_q_opt1" placeholder="Pilihan B" required class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-xl text-sm font-bold dark:text-white">
                    </div>
                    <div class="space-y-4">
                        <input type="text" name="opt2" id="edit_q_opt2" placeholder="Pilihan C" required class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-xl text-sm font-bold dark:text-white">
                        <input type="text" name="opt3" id="edit_q_opt3" placeholder="Pilihan D" required class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-xl text-sm font-bold dark:text-white">
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-end justify-between space-y-6 md:space-y-0 pt-6 border-t border-slate-100 dark:border-blue-900/10 mt-10">
                    <div class="w-full md:max-w-xs">
                        <label class="block text-xs font-black text-slate-400 uppercase tracking-widest mb-3">Kunci Jawaban</label>
                        <select name="answer" id="edit_q_ans" class="w-full px-5 py-3 bg-slate-50 dark:bg-[#060b1d] border-none rounded-xl text-sm font-black text-blue-600 dark:text-blue-400">
                            <option value="0">PILIHAN A</option>
                            <option value="1">PILIHAN B</option>
                            <option value="2">PILIHAN C</option>
                            <option value="3">PILIHAN D</option>
                        </select>
                    </div>
                    <button type="submit" class="px-10 py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition transform hover:-translate-y-1 active:scale-95 uppercase tracking-widest text-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>


        </div>
    </main>

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
            document.getElementById('editQuestionModal').classList.remove('hidden');
        }

        function switchTab(tab) {
            document.getElementById('tab-content').classList.toggle('hidden', tab !== 'content');
            document.getElementById('tab-quiz').classList.toggle('hidden', tab !== 'quiz');
            
            const btnContent = document.getElementById('tab-btn-content');
            const btnQuiz = document.getElementById('tab-btn-quiz');
            
            if(tab === 'content') {
                btnContent.className = "px-6 py-2.5 rounded-lg font-black text-xs uppercase tracking-widest transition bg-white dark:bg-blue-600 text-blue-600 dark:text-white shadow-sm";
                btnQuiz.className = "px-6 py-2.5 rounded-lg font-black text-xs uppercase tracking-widest transition text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-800/50";
            } else {
                btnQuiz.className = "px-6 py-2.5 rounded-lg font-black text-xs uppercase tracking-widest transition bg-white dark:bg-blue-600 text-blue-600 dark:text-white shadow-sm";
                btnContent.className = "px-6 py-2.5 rounded-lg font-black text-xs uppercase tracking-widest transition text-slate-500 dark:text-slate-400 hover:bg-white/50 dark:hover:bg-slate-800/50";
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
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'image', 'code-block'],
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
            showModernAlert({ title: 'BERHASIL!', text: 'Perubahan telah disimpan dengan aman.', icon: 'success' });
        }
    </script>
</body>
</html>
