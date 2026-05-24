<?php
// src/admin/challenges.php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Fetch Admin Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_data = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $title = $_POST['title'];
        $desc = $_POST['description'];
        $pts = (int)$_POST['points'];
        $diff = $_POST['difficulty'];
        $week = $_POST['week_type'];
        
        $stmt = $pdo->prepare("INSERT INTO challenges (title, description, points, difficulty, week_type, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $stmt->execute([$title, $desc, $pts, $diff, $week]);
        header("Location: challenges.php?msg=" . urlencode("Tantangan berhasil ditambahkan!"));
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM challenges WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: challenges.php?msg=" . urlencode("Tantangan berhasil dihapus!"));
    exit;
}

$stmt = $pdo->query("SELECT * FROM challenges ORDER BY week_type DESC, created_at DESC");
$challenges = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Tantangan - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#f8fafc] text-slate-800 dark:bg-[#020617] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-amber-500/5 dark:bg-amber-500/10 rounded-full blur-[120px] -mr-64 -mt-64 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-blue-500/5 dark:bg-blue-500/10 rounded-full blur-[120px] -ml-64 -mb-64 pointer-events-none"></div>

        <!-- Header -->
        <header class="p-8 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center bg-white/30 dark:bg-[#020617]/30 backdrop-blur-xl border-b border-blue-50/50 dark:border-blue-900/20 z-10">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <span class="px-3 py-1 bg-amber-600 text-[10px] font-black text-white rounded-lg uppercase tracking-widest">Gamification</span>
                    <span class="text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-widest italic">/ Weekly Challenges</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1">Misi Mingguan</h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-xs italic">Atur tantangan untuk meningkatkan engangement siswa.</p>
            </div>
            <div class="flex items-center space-x-4 mt-6 md:mt-0">
                <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="px-8 py-4 bg-amber-600 text-white font-black rounded-2xl hover:bg-amber-700 shadow-xl shadow-amber-500/20 transition transform hover:-translate-y-1 active:scale-95 text-[10px] uppercase tracking-widest flex items-center space-x-3">
                    <i class="fas fa-rocket text-sm"></i>
                    <span>BUAT MISI BARU</span>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar scroll-smooth">
            <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 pb-32">
                <?php foreach($challenges as $ch): ?>
                <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl p-8 rounded-[3.5rem] shadow-sm border border-blue-50/50 dark:border-blue-900/20 flex flex-col justify-between group relative overflow-hidden transition-all duration-700 hover:shadow-2xl hover:shadow-amber-500/10 hover:-translate-y-2">
                    <div class="absolute top-0 right-0 p-6 z-20">
                        <button onclick="deleteChallenge(<?php echo $ch['id']; ?>, '<?php echo htmlspecialchars($ch['title'], ENT_QUOTES); ?>')"
                            class="w-12 h-12 bg-rose-500/10 text-rose-500 rounded-2xl flex items-center justify-center transition-all duration-300 hover:bg-rose-500 hover:text-white shadow-sm opacity-0 group-hover:opacity-100 translate-x-4 group-hover:translate-x-0">
                            <i class="fas fa-trash-alt text-sm"></i>
                        </button>
                    </div>

                    <div class="relative z-10">
                        <div class="flex flex-wrap items-center gap-3 mb-8">
                            <span class="px-4 py-1.5 <?php echo $ch['week_type'] === 'current' ? 'bg-emerald-500/10 text-emerald-600' : 'bg-amber-500/10 text-amber-600' ?> rounded-xl text-[9px] font-black uppercase tracking-widest border border-current opacity-70"><?php echo $ch['week_type'] === 'current' ? 'Live Now' : 'Upcoming' ?></span>
                            <span class="px-4 py-1.5 bg-blue-500/10 text-blue-600 rounded-xl text-[9px] font-black uppercase tracking-widest border border-blue-500/20"><?php echo strtoupper($ch['difficulty']) ?></span>
                            <span class="px-4 py-1.5 bg-blue-500/10 text-blue-600 rounded-xl text-[9px] font-black uppercase tracking-widest border border-blue-500/20">+<?php echo $ch['points'] ?> Points</span>
                        </div>
                        
                        <a href="challenge_questions.php?id=<?php echo $ch['id']; ?>" class="block text-3xl font-black text-slate-900 dark:text-white mb-4 tracking-tighter hover:text-amber-600 transition-colors group/title">
                            <?php echo htmlspecialchars($ch['title']) ?>
                            <i class="fas fa-chevron-right text-xs ml-2 opacity-0 -translate-x-2 group-hover/title:opacity-100 group-hover/title:translate-x-0 transition-all"></i>
                        </a>
                        
                        <div class="bg-slate-50/50 dark:bg-white/5 rounded-3xl p-6 mb-8 border border-blue-50/50 dark:border-white/5">
                            <p class="text-xs font-bold text-slate-500 dark:text-slate-400 leading-relaxed italic">
                                "<?php echo htmlspecialchars($ch['description']); ?>"
                            </p>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-blue-50/50 dark:border-blue-900/10 flex justify-between items-center relative z-10">
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest flex items-center">
                            <i class="far fa-calendar-alt mr-2 text-amber-500"></i>
                            Created <?php echo date('d M Y', strtotime($ch['created_at'])); ?>
                        </span>
                        <a href="challenge_questions.php?id=<?php echo $ch['id']; ?>" class="px-5 py-2.5 bg-amber-600 text-white text-[9px] font-black rounded-xl uppercase tracking-widest shadow-lg shadow-amber-500/20 hover:bg-amber-700 transition-all">
                            Manage Questions
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Add Modal -->
    <div id="addModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-[#060b1d]/80 backdrop-blur-md p-4">
        <div class="bg-white dark:bg-[#0a1128] rounded-[3.5rem] p-12 max-w-xl w-full shadow-2xl border border-blue-50 dark:border-blue-900/30 overflow-y-auto max-h-screen">
            <h2 class="text-3xl font-black mb-8 text-slate-900 dark:text-white uppercase tracking-tight">Misi Baru</h2>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="add">
                
                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Judul Misi</label>
                    <input type="text" name="title" required placeholder="Contoh: Sang Juara Aljabar" class="w-full px-6 py-4 bg-blue-50/50 dark:bg-blue-900/10 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-800 dark:text-white">
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Deskripsi</label>
                    <textarea name="description" rows="3" required placeholder="Apa yang harus dilakukan siswa?" class="w-full px-6 py-4 bg-blue-50/50 dark:bg-blue-900/10 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-800 dark:text-white resize-none"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Hadiah Poin</label>
                        <input type="number" name="points" value="10" required class="w-full px-6 py-4 bg-blue-50/50 dark:bg-blue-900/10 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Kesulitan</label>
                        <select name="difficulty" class="w-full px-6 py-4 bg-blue-50/50 dark:bg-blue-900/10 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-800 dark:text-white">
                            <option value="easy">Mudah</option>
                            <option value="medium">Menengah</option>
                            <option value="hard">Sulit</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2 block">Jadwal Minggu</label>
                    <select name="week_type" class="w-full px-6 py-4 bg-blue-50/50 dark:bg-blue-900/10 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-800 dark:text-white">
                        <option value="current">Minggu Ini</option>
                        <option value="next">Minggu Depan</option>
                    </select>
                </div>

                <div class="mt-10 flex space-x-4 pt-6">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 py-5 text-slate-400 font-bold hover:text-slate-600 transition">Batal</button>
                    <button type="submit" class="flex-1 py-5 bg-blue-600 text-white font-black rounded-[1.5rem] hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition uppercase tracking-widest text-xs">Aktifkan Misi</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function deleteChallenge(id, title) {
            Swal.fire({
                title: '<div class="text-xl font-black text-slate-900 uppercase tracking-tight">Hapus Misi?</div>',
                html: `<div class="text-sm text-slate-400 font-bold">"${title}"</div>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'YA, HAPUS',
                cancelButtonText: 'BATAL',
                customClass: { popup: 'glass-popup border-none shadow-2xl' }
            }).then((result) => {
                if (result.isConfirmed) window.location.href = `?delete=${id}`;
            });
        }
    </script>
</body>
</html>
