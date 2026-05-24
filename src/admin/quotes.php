<?php
// src/admin/quotes.php
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
        $text = $_POST['text'];
        $stmt = $pdo->prepare("INSERT INTO motivational_quotes (text) VALUES (?)");
        $stmt->execute([$text]);
        header("Location: quotes.php?msg=" . urlencode("Quote berhasil ditambahkan!"));
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM motivational_quotes WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: quotes.php?msg=" . urlencode("Quote berhasil dihapus!"));
    exit;
}

$stmt = $pdo->query("SELECT * FROM motivational_quotes ORDER BY id DESC");
$quotes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Motivasi - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/auth.js"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#f8fafc] text-slate-800 dark:bg-[#020617] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-500/5 dark:bg-blue-500/10 rounded-full blur-[120px] -mr-64 -mt-64 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-blue-500/5 dark:bg-blue-500/10 rounded-full blur-[120px] -ml-64 -mb-64 pointer-events-none"></div>

        <!-- Header -->
        <header class="p-8 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center bg-white/30 dark:bg-[#020617]/30 backdrop-blur-xl border-b border-blue-50/50 dark:border-blue-900/20 z-10">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <span class="px-3 py-1 bg-blue-600 text-[10px] font-black text-white rounded-lg uppercase tracking-widest">Growth</span>
                    <span class="text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-widest italic">/ Motivational Quotes</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1">Motivasi Siswa</h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-xs italic">Kelola kalimat inspiratif untuk mendorong semangat belajar.</p>
            </div>
            <div class="flex items-center space-x-4 mt-6 md:mt-0">
                <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="px-8 py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/20 transition transform hover:-translate-y-1 active:scale-95 text-[10px] uppercase tracking-widest flex items-center space-x-3">
                    <i class="fas fa-feather-pointed text-sm"></i>
                    <span>NEW INSPIRATION</span>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar scroll-smooth">
            <div class="max-w-7xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 pb-32">
                <?php foreach($quotes as $q): ?>
                <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl p-8 rounded-[3.5rem] shadow-sm border border-blue-50/50 dark:border-blue-900/20 flex flex-col group relative overflow-hidden transition-all duration-700 hover:shadow-2xl hover:shadow-blue-500/10 hover:-translate-y-2 min-h-[320px]">
                    <div class="absolute top-0 right-0 p-6 z-20">
                        <button onclick="deleteQuote(<?php echo $q['id']; ?>, '<?php echo htmlspecialchars(substr($q['text'], 0, 30), ENT_QUOTES); ?>...')"
                            class="w-12 h-12 bg-rose-500/10 text-rose-500 rounded-2xl flex items-center justify-center transition-all duration-300 hover:bg-rose-500 hover:text-white shadow-sm opacity-0 group-hover:opacity-100 translate-x-4 group-hover:translate-x-0">
                            <i class="fas fa-trash-alt text-sm"></i>
                        </button>
                    </div>

                    <div class="relative z-10 flex-1 flex flex-col">
                        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-blue-700 text-white rounded-[1.5rem] flex items-center justify-center text-xl mb-8 shadow-lg shadow-blue-500/20 group-hover:scale-110 group-hover:rotate-6 transition-all duration-500">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        
                        <div class="bg-blue-50/50 dark:bg-white/5 rounded-[2rem] p-6 border border-blue-100/50 dark:border-white/5 flex-1 flex items-center justify-center">
                            <p class="text-base font-bold text-slate-700 dark:text-slate-300 leading-relaxed italic text-center">
                                "<?php echo htmlspecialchars($q['text']); ?>"
                            </p>
                        </div>
                    </div>

                    <div class="mt-8 pt-6 border-t border-blue-50/50 dark:border-blue-900/10 flex justify-between items-center relative z-10">
                        <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest flex items-center italic">
                            <i class="far fa-calendar-alt mr-2 text-blue-500"></i>
                            Created <?php echo date('d M Y', strtotime($q['created_at'])); ?>
                        </span>
                        <div class="flex -space-x-2">
                            <div class="w-6 h-6 rounded-full bg-blue-500 border-2 border-white dark:border-[#0a1128]"></div>
                            <div class="w-6 h-6 rounded-full bg-blue-600 border-2 border-white dark:border-[#0a1128]"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>

    <!-- Add Modal -->
    <div id="addModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-[#060b1d]/80 backdrop-blur-md p-4">
        <div class="bg-white dark:bg-[#0a1128] rounded-[3.5rem] p-12 max-w-lg w-full shadow-2xl border border-blue-50 dark:border-blue-900/30">
            <h2 class="text-3xl font-black mb-10 text-slate-900 dark:text-white uppercase tracking-tight">New Quote</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <textarea name="text" rows="5" required placeholder="Tuliskan kalimat penyemangat..." class="w-full px-8 py-6 bg-blue-50/50 dark:bg-blue-900/10 border-none rounded-[2rem] focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-800 dark:text-white leading-relaxed resize-none"></textarea>
                <div class="mt-12 flex space-x-4">
                    <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 py-5 text-slate-400 font-bold hover:text-slate-600 transition">Batal</button>
                    <button type="submit" class="flex-1 py-5 bg-blue-600 text-white font-black rounded-[1.5rem] hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition uppercase tracking-widest text-xs">Simpan Quote</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function deleteQuote(id, preview) {
            Swal.fire({
                title: '<div class="text-xl font-black text-slate-900 uppercase tracking-tight">Hapus Quote?</div>',
                html: `<div class="text-sm text-slate-400 font-bold">"${preview}"</div>`,
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

        function updateClock() {
            const now = new Date();
            const el = document.getElementById('clockDisplay');
            if (el) el.textContent = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':');
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
