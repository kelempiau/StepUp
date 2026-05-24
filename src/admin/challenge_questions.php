<?php
// src/admin/challenge_questions.php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$challenge_id = $_GET['id'] ?? null;
if (!$challenge_id) {
    header("Location: challenges.php");
    exit;
}

// Fetch Admin Data
$stmtHeader = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtHeader->execute([$_SESSION['user_id']]);
$admin_data = $stmtHeader->fetch();

// Fetch Challenge Info
$stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ?");
$stmt->execute([$challenge_id]);
$challenge = $stmt->fetch();

if (!$challenge) {
    header("Location: challenges.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_question') {
            $stmt = $pdo->prepare("INSERT INTO challenge_questions (challenge_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $challenge_id,
                $_POST['question_text'],
                $_POST['option_a'],
                $_POST['option_b'],
                $_POST['option_c'],
                $_POST['option_d'],
                $_POST['correct_option']
            ]);
            header("Location: challenge_questions.php?id=" . $challenge_id . "&status=added");
            exit;
        } elseif ($_POST['action'] === 'edit_question') {
            $stmt = $pdo->prepare("UPDATE challenge_questions SET question_text = ?, option_a = ?, option_b = ?, option_c = ?, option_d = ?, correct_option = ? WHERE id = ?");
            $stmt->execute([
                $_POST['question_text'],
                $_POST['option_a'],
                $_POST['option_b'],
                $_POST['option_c'],
                $_POST['option_d'],
                $_POST['correct_option'],
                $_POST['question_id']
            ]);
            header("Location: challenge_questions.php?id=" . $challenge_id . "&status=updated");
            exit;
        } elseif ($_POST['action'] === 'delete_question') {
            $stmt = $pdo->prepare("DELETE FROM challenge_questions WHERE id = ?");
            $stmt->execute([$_POST['question_id']]);
            header("Location: challenge_questions.php?id=" . $challenge_id . "&status=deleted");
            exit;
        }
    }
}

// Fetch questions
$stmt = $pdo->prepare("SELECT * FROM challenge_questions WHERE challenge_id = ? ORDER BY created_at ASC");
$stmt->execute([$challenge_id]);
$questions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id" class="dark:bg-[#060b1d]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Soal - <?php echo htmlspecialchars($challenge['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
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
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/auth.js"></script>
</head>
<body class="bg-amber-50/30 text-slate-800 dark:bg-[#060b1d] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        
        <!-- Header Section -->
        <header class="p-6 md:p-10 border-b border-amber-100 dark:border-amber-900/10 flex flex-col md:flex-row md:items-center justify-between gap-6 glass-card z-10">
            <div class="flex items-center space-x-6">
                <a href="challenges.php" 
                   class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 flex items-center justify-center text-slate-500 hover:text-amber-600 hover:border-amber-200 transition-all shadow-sm group">
                    <i class="fas fa-arrow-left group-hover:-translate-x-1 transition-transform"></i>
                </a>
                <div>
                    <div class="flex items-center space-x-2 text-[10px] font-black uppercase tracking-widest text-amber-600 dark:text-amber-400 mb-1">
                        <i class="fas fa-trophy mr-1"></i>
                        <span>Weekly Mission</span>
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1">Kelola Soal</h1>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-widest"><?php echo htmlspecialchars($challenge['title']); ?></p>
                </div>
            </div>
            <div class="flex items-center space-x-3">
                <button onclick="openAddModal()" 
                    class="px-8 py-4 bg-amber-600 text-white font-black rounded-2xl hover:bg-amber-700 shadow-xl shadow-amber-500/30 transition transform hover:-translate-y-1 active:scale-95 flex items-center space-x-3 uppercase tracking-widest text-[10px]">
                    <i class="fas fa-plus-circle text-sm"></i>
                    <span>Tambah Soal Baru</span>
                </button>
            </div>
        </header>

        <!-- Questions List -->
        <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar pb-32 animate__animated animate__fadeIn">
            <div class="max-w-5xl mx-auto space-y-8">
                
                <?php if(empty($questions)): ?>
                <div class="glass-card rounded-[3rem] p-24 text-center border-2 border-dashed border-amber-200 dark:border-amber-900/30">
                    <div class="w-24 h-24 bg-white dark:bg-slate-900 rounded-[2.5rem] flex items-center justify-center text-amber-200 dark:text-amber-900 mx-auto mb-8 shadow-sm border border-amber-50 dark:border-amber-800">
                        <i class="fas fa-clipboard-question text-4xl"></i>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 dark:text-slate-200">Belum Ada Soal</h3>
                    <p class="text-sm text-slate-400 dark:text-slate-500 mt-4 max-w-md mx-auto font-medium leading-relaxed">
                        Misi ini belum memiliki pertanyaan kuis. Siswa akan mendapatkan poin setelah berhasil menjawab soal-soal yang Anda buat di sini.
                    </p>
                </div>
                <?php endif; ?>

                <?php foreach($questions as $i => $q): ?>
                <div class="glass-card p-10 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 flex flex-col lg:flex-row justify-between items-start gap-12 group hover:border-amber-500/50 dark:hover:border-amber-800 transition-all duration-500 hover:shadow-2xl hover:shadow-amber-500/[0.05] relative overflow-hidden">
                    <!-- Background Decoration -->
                    <div class="absolute -right-10 -top-10 w-48 h-48 bg-amber-500/[0.03] dark:bg-amber-500/[0.02] rounded-full blur-3xl group-hover:bg-amber-500/10 transition-all duration-700"></div>

                    <div class="flex-1 w-full relative z-10">
                        <div class="flex items-center space-x-3 mb-8">
                            <span class="px-5 py-2 bg-slate-900 dark:bg-amber-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-amber-500/20">
                                Soal #<?php echo $i + 1; ?>
                            </span>
                            <div class="w-1.5 h-1.5 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">ID: <?php echo $q['id']; ?></span>
                        </div>
                        
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-10 leading-relaxed">
                            <?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
                        </h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php foreach(['A', 'B', 'C', 'D'] as $opt): 
                                $is_correct = ($q['correct_option'] === $opt);
                                $opt_val = $q['option_'.strtolower($opt)];
                            ?>
                            <div class="flex items-center space-x-5 p-6 rounded-[1.5rem] transition-all border <?php echo $is_correct ? 'bg-amber-50/50 dark:bg-amber-900/10 border-amber-200 dark:border-amber-900/40 ring-1 ring-amber-500/20' : 'bg-white/40 dark:bg-slate-900/40 border-slate-200 dark:border-slate-800'; ?>">
                                <div class="w-10 h-10 rounded-xl <?php echo $is_correct ? 'bg-amber-500 text-white shadow-lg shadow-amber-500/30' : 'bg-white dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-100 dark:border-slate-700'; ?> flex items-center justify-center text-xs font-black shrink-0 transition-all">
                                    <?php echo $opt; ?>
                                </div>
                                <span class="text-sm <?php echo $is_correct ? 'text-amber-700 dark:text-amber-400 font-extrabold' : 'text-slate-600 dark:text-slate-400 font-bold'; ?>">
                                    <?php echo htmlspecialchars($opt_val); ?>
                                </span>
                                <?php if($is_correct): ?>
                                    <i class="fas fa-check-circle text-amber-500 ml-auto text-lg"></i>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="flex lg:flex-col items-center gap-4 w-full lg:w-auto relative z-10 pt-8 lg:pt-0 border-t lg:border-t-0 lg:border-l border-slate-100 dark:border-slate-800 lg:pl-12">
                        <button onclick='openEditModal(<?php echo json_encode($q); ?>)' 
                            class="flex-1 lg:flex-none w-14 h-14 bg-amber-50 dark:bg-amber-600/20 text-amber-600 dark:text-amber-400 rounded-2xl hover:bg-amber-600 hover:text-white transition-all shadow-sm group/btn flex items-center justify-center">
                            <i class="fas fa-edit group-hover/btn:scale-110 transition-transform"></i>
                        </button>
                        <button onclick="confirmDelete(<?php echo $q['id']; ?>)" 
                            class="flex-1 lg:flex-none w-14 h-14 bg-red-50 dark:bg-red-500/20 text-red-500 rounded-2xl hover:bg-red-500 hover:text-white transition-all shadow-sm group/btn flex items-center justify-center">
                            <i class="fas fa-trash-alt group-hover/btn:rotate-12 transition-transform"></i>
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Question Modal (Add/Edit) -->
    <div id="questionModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" onclick="closeModal()"></div>
        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-10 md:p-14 max-w-3xl w-full shadow-2xl border border-slate-100 dark:border-blue-900/10 relative z-10 animate__animated animate__zoomIn">
            <div class="flex justify-between items-center mb-10">
                <div class="flex items-center space-x-4">
                    <div class="w-14 h-14 bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center shadow-sm">
                        <i class="fas fa-clipboard-question text-2xl" id="modalIcon"></i>
                    </div>
                    <div>
                        <h3 class="text-3xl font-black text-slate-900 dark:text-white leading-tight" id="modalTitle">Tambah Soal</h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Konfigurasi pertanyaan kuis misi</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="w-12 h-12 bg-slate-100 dark:bg-slate-800 text-slate-400 rounded-2xl hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-500 transition-all flex items-center justify-center">
                    <i class="fas fa-times text-lg"></i>
                </button>
            </div>

            <form method="POST" id="questionForm">
                <input type="hidden" name="action" id="formAction" value="add_question">
                <input type="hidden" name="question_id" id="edit_question_id">
                
                <div class="mb-8 space-y-3">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Pertanyaan</label>
                    <textarea name="question_text" id="q_text" rows="4" required 
                        class="w-full px-8 py-6 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-[2rem] focus:ring-2 focus:ring-amber-500 outline-none text-base font-bold dark:text-white transition-all resize-none"
                        placeholder="Apa yang ingin Anda tanyakan?"></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                    <div class="space-y-4">
                        <div class="relative group">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center text-[10px] font-black text-slate-500">A</div>
                            <input type="text" name="option_a" id="opt_a" placeholder="Opsi A" required 
                                class="w-full pl-16 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-amber-500 outline-none transition-all">
                        </div>
                        <div class="relative group">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center text-[10px] font-black text-slate-500">B</div>
                            <input type="text" name="option_b" id="opt_b" placeholder="Opsi B" required 
                                class="w-full pl-16 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-amber-500 outline-none transition-all">
                        </div>
                    </div>
                    <div class="space-y-4">
                        <div class="relative group">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center text-[10px] font-black text-slate-500">C</div>
                            <input type="text" name="option_c" id="opt_c" placeholder="Opsi C" required 
                                class="w-full pl-16 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-amber-500 outline-none transition-all">
                        </div>
                        <div class="relative group">
                            <div class="absolute left-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-lg bg-white dark:bg-slate-800 shadow-sm border border-slate-100 dark:border-slate-700 flex items-center justify-center text-[10px] font-black text-slate-500">D</div>
                            <input type="text" name="option_d" id="opt_d" placeholder="Opsi D" required 
                                class="w-full pl-16 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-bold dark:text-white focus:ring-2 focus:ring-amber-500 outline-none transition-all">
                        </div>
                    </div>
                </div>

                <div class="flex flex-col md:flex-row md:items-center justify-between gap-8 pt-10 border-t border-slate-100 dark:border-slate-800/50 mt-4">
                    <div class="w-full md:max-w-xs space-y-3">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] ml-2">Kunci Jawaban</label>
                        <div class="relative">
                            <select name="correct_option" id="correct_opt" 
                                class="w-full px-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl text-sm font-black text-amber-600 dark:text-amber-400 appearance-none cursor-pointer focus:ring-2 focus:ring-amber-500 outline-none transition-all">
                                <option value="A">Opsi A (Pertama)</option>
                                <option value="B">Opsi B (Kedua)</option>
                                <option value="C">Opsi C (Ketiga)</option>
                                <option value="D">Opsi D (Keempat)</option>
                            </select>
                            <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                <i class="fas fa-chevron-down text-xs"></i>
                            </div>
                        </div>
                    </div>
                    <button type="submit" 
                        class="px-12 py-5 bg-amber-600 text-white font-black rounded-2xl hover:bg-amber-700 shadow-xl shadow-amber-500/30 transition transform hover:-translate-y-1 active:scale-95 flex items-center justify-center space-x-3 uppercase tracking-widest text-[10px]">
                        <i class="fas fa-save text-base"></i>
                        <span id="btnText">Simpan Soal</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <form id="deleteForm" method="POST" class="hidden">
        <input type="hidden" name="action" value="delete_question">
        <input type="hidden" name="question_id" id="delete_id">
    </form>
    
    <script>
        function openAddModal() {
            document.getElementById('modalTitle').innerText = 'Tambah Soal';
            document.getElementById('formAction').value = 'add_question';
            document.getElementById('btnText').innerText = 'Simpan Soal';
            document.getElementById('questionForm').reset();
            document.getElementById('questionModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function openEditModal(q) {
            document.getElementById('modalTitle').innerText = 'Edit Pertanyaan';
            document.getElementById('formAction').value = 'edit_question';
            document.getElementById('btnText').innerText = 'Update Soal';
            
            document.getElementById('edit_question_id').value = q.id;
            document.getElementById('q_text').value = q.question_text;
            document.getElementById('opt_a').value = q.option_a;
            document.getElementById('opt_b').value = q.option_b;
            document.getElementById('opt_c').value = q.option_c;
            document.getElementById('opt_d').value = q.option_d;
            document.getElementById('correct_opt').value = q.correct_option;
            
            document.getElementById('questionModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            document.getElementById('questionModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function confirmDelete(id) {
            confirmModernAlert({
                title: 'HAPUS SOAL?',
                text: 'Pertanyaan ini akan dihapus secara permanen dari misi.',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'YA, HAPUS'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete_id').value = id;
                    document.getElementById('deleteForm').submit();
                }
            });
        }

        // Handle Status Messages
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        
        if (status) {
            let config = { icon: 'success' };
            switch(status) {
                case 'added':
                    config.title = 'BERHASIL!';
                    config.text = 'Soal baru telah ditambahkan ke misi.';
                    break;
                case 'updated':
                    config.title = 'TERUPDATE!';
                    config.text = 'Perubahan soal telah berhasil disimpan.';
                    break;
                case 'deleted':
                    config.title = 'TERHAPUS!';
                    config.text = 'Soal telah dihapus dari sistem.';
                    config.icon = 'info';
                    break;
            }
            showModernAlert(config);
            window.history.replaceState({}, document.title, window.location.pathname + "?id=<?php echo $challenge_id; ?>");
        }
    </script>
</body>
</html>
