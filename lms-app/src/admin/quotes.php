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
<body class="bg-blue-50/50 text-slate-800 dark:bg-[#060b1d] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar (Blue Premium Style) -->
    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        
        <!-- Dynamic Page Header -->
        <div class="p-6 md:p-10 border-b border-blue-50 dark:border-blue-900/10 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white dark:bg-[#0a1128]/50 backdrop-blur-md">
            <div class="flex items-center space-x-6">
                <div class="space-y-1">
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-2">Motivasi Siswa</h2>
                    <p class="text-sm text-slate-400 font-medium">Kelola kalimat penyemangat untuk dashboard siswa.</p>
                </div>
                <span class="px-5 py-2 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-500/20 transition-transform hover:scale-105 cursor-default">
                    <?php echo count($quotes); ?> QUOTES AKTIF
                </span>
            </div>
            <div class="flex items-center space-x-3">
                <div id="realtimeClockContainer" class="hidden md:flex items-center space-x-3 px-5 py-2.5 bg-white dark:bg-[#0a1128] border border-blue-100 dark:border-blue-900/20 rounded-2xl shadow-sm tracking-widest">
                    <i class="far fa-clock text-blue-600 animate-pulse"></i>
                    <span id="clockDisplay" class="text-sm font-black text-slate-600 dark:text-slate-400 tabular-nums uppercase">00:00:00</span>
                </div>
                <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-8 py-3 bg-blue-600 text-white font-black rounded-xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition transform hover:-translate-y-1 active:scale-95 text-xs uppercase tracking-widest">
                    + Quote Baru
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach($quotes as $q): ?>
                <div class="bg-white dark:bg-[#0a1128] p-8 rounded-[3rem] shadow-sm border border-blue-50 dark:border-blue-900/30 flex flex-col justify-between group relative overflow-hidden transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/10 hover:-translate-y-2">
                    <div class="absolute top-0 right-0 p-6 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick="deleteQuote(<?php echo $q['id']; ?>, '<?php echo htmlspecialchars(substr($q['text'], 0, 30), ENT_QUOTES); ?>...')" class="w-10 h-10 bg-red-50 dark:bg-red-900/20 text-red-500 rounded-xl flex items-center justify-center transition hover:bg-red-600 hover:text-white">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    </div>
                    <div class="w-14 h-14 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-[1.5rem] flex items-center justify-center text-2xl mb-6 shadow-inner cursor-default transition-transform group-hover:scale-110">
                        <i class="fas fa-quote-left"></i>
                    </div>
                    <p class="text-lg font-bold text-slate-800 dark:text-white leading-relaxed italic mb-8 flex-1">
                        "<?php echo htmlspecialchars($q['text']); ?>"
                    </p>
                    <div class="pt-6 border-t border-blue-50 dark:border-blue-900/20">
                        <span class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest italic flex items-center">
                            <i class="far fa-calendar-alt mr-2"></i>
                            Added on <?php echo date('d M Y', strtotime($q['created_at'])); ?>
                        </span>
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
