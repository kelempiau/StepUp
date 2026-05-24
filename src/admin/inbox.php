<?php
// src/admin/inbox.php
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

$msg = $_GET['msg'] ?? '';

// Handle Broadcast
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_broadcast'])) {
    $title = $_POST['title'] ?? '';
    $content = $_POST['content'] ?? '';
    $type = $_POST['type'] ?? 'system';

    if (!empty($title) && !empty($content)) {
        try {
            // Get all student IDs
            $stStudents = $pdo->query("SELECT id FROM users WHERE role = 'student'");
            $students = $stStudents->fetchAll(PDO::FETCH_COLUMN);

            $pdo->beginTransaction();
            $stInsert = $pdo->prepare("INSERT INTO inbox (user_id, title, content, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())");
            
            foreach ($students as $s_id) {
                $stInsert->execute([$s_id, $title, $content, $type]);
            }
            
            $pdo->commit();
            header("Location: inbox.php?msg=" . urlencode("Broadcast berhasil dikirim ke " . count($students) . " siswa."));
            exit;
        } catch (Exception $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $msg = "Gagal mengirim broadcast: " . $e->getMessage();
        }
    } else {
        $msg = "Judul dan konten tidak boleh kosong.";
    }
}

// Fetch recent broadcasts (grouped by title/content/created_at to show unique ones)
// In a real app, you might want a 'broadcasts' table, but for now we query the inbox table
$stRecent = $pdo->query("SELECT title, content, type, COUNT(*) as recipient_count, MAX(created_at) as sent_at 
                         FROM inbox 
                         WHERE type IN ('system', 'community') 
                         GROUP BY title, content, type 
                         ORDER BY sent_at DESC LIMIT 10");
$recent_broadcasts = $stRecent->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inbox Broadcast - Admin StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;900&display=swap" rel="stylesheet">
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#f8fafc] text-slate-800 dark:bg-[#020617] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-500/5 dark:bg-blue-500/10 rounded-full blur-[120px] -mr-64 -mt-64 pointer-events-none"></div>
        
        <!-- Header -->
        <header class="p-8 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center bg-white/30 dark:bg-[#020617]/30 backdrop-blur-xl border-b border-blue-50/50 dark:border-blue-900/20 z-10">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <span class="px-3 py-1 bg-blue-600 text-[10px] font-black text-white rounded-lg uppercase tracking-widest">Communication Hub</span>
                    <span class="text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-widest italic">/ Inbox Broadcast</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1">Broadcast Notifikasi</h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-xs italic">Kirim pesan massal ke seluruh siswa StepUp.</p>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            <div class="max-w-5xl mx-auto space-y-10">
                
                <?php if($msg): ?>
                    <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl text-blue-600 dark:text-blue-400 font-bold text-sm">
                        <i class="fas fa-info-circle mr-2"></i> <?php echo htmlspecialchars($msg); ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <!-- Left: Form -->
                    <div class="lg:col-span-2">
                        <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl p-10 rounded-[3rem] shadow-sm border border-blue-50/50 dark:border-blue-900/20">
                            <h2 class="text-2xl font-black text-slate-900 dark:text-white mb-8 tracking-tight">Kirim Pesan Baru</h2>
                            
                            <form method="POST" class="space-y-6">
                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-2">Judul Broadcast</label>
                                    <input type="text" name="title" class="w-full bg-slate-50 dark:bg-slate-900 border border-blue-50 dark:border-blue-900/30 rounded-2xl p-4 text-sm font-bold outline-none focus:border-blue-500 transition-all" placeholder="Contoh: Pemeliharaan Sistem / Update Materi" required>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-2">Kategori</label>
                                    <div class="relative">
                                        <select name="type" class="appearance-none block w-full bg-slate-50 dark:bg-slate-900 border border-blue-50 dark:border-blue-900/30 rounded-2xl p-4 pr-12 text-sm font-bold outline-none focus:border-blue-500 transition-all cursor-pointer">
                                            <option value="system">Pengumuman Sistem</option>
                                            <option value="points">Event Poin / XP</option>
                                            <option value="info">Informasi Umum</option>
                                        </select>
                                        <div class="absolute right-5 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400">
                                            <i class="fas fa-chevron-down text-xs"></i>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 pl-2">Isi Pesan</label>
                                    <textarea name="content" rows="6" class="w-full bg-slate-50 dark:bg-slate-900 border border-blue-50 dark:border-blue-900/30 rounded-[2rem] p-6 text-sm font-medium outline-none focus:border-blue-500 transition-all leading-relaxed" placeholder="Tuliskan detail pengumuman di sini..." required></textarea>
                                </div>

                                <button type="submit" name="send_broadcast" class="w-full py-5 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-black text-xs uppercase tracking-widest shadow-xl shadow-blue-500/20 transition-all active:scale-95 flex items-center justify-center space-x-3">
                                    <i class="fas fa-paper-plane"></i>
                                    <span>Kirim Broadcast Sekarang</span>
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Right: Info & Recent -->
                    <div class="space-y-8">
                        <div class="bg-gradient-to-br from-blue-600 to-blue-800 p-8 rounded-[3rem] text-white shadow-2xl relative overflow-hidden group">
                            <i class="fas fa-bullhorn absolute -right-4 -bottom-4 text-7xl opacity-10 -rotate-12 group-hover:rotate-0 transition-transform"></i>
                            <h3 class="text-xl font-black mb-2">Tips Broadcast</h3>
                            <p class="text-xs font-medium text-blue-100 leading-relaxed italic">
                                Gunakan broadcast untuk memberi tahu siswa tentang event baru, pemeliharaan sistem, atau pencapaian komunitas mingguan.
                            </p>
                        </div>

                        <div>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-6 px-4">Broadcast Terakhir</p>
                            <div class="space-y-4">
                                <?php if(empty($recent_broadcasts)): ?>
                                    <div class="p-6 text-center bg-slate-50 dark:bg-slate-900/50 rounded-3xl border-2 border-dashed border-slate-100 dark:border-slate-800">
                                        <p class="text-[10px] font-bold text-slate-400 uppercase">Belum ada riwayat</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach($recent_broadcasts as $rb): ?>
                                        <div class="p-6 bg-white dark:bg-[#0a1128] rounded-[2.5rem] border border-blue-50 dark:border-blue-900/20 shadow-sm">
                                            <div class="flex items-center justify-between mb-3">
                                                <span class="px-3 py-1 bg-blue-50 dark:bg-blue-900/30 text-blue-500 text-[8px] font-black uppercase tracking-widest rounded-lg border border-blue-100 dark:border-blue-800">
                                                    <?php echo $rb['type']; ?>
                                                </span>
                                                <span class="text-[8px] font-bold text-slate-400"><?php echo date('d M, H:i', strtotime($rb['sent_at'])); ?></span>
                                            </div>
                                            <h4 class="text-sm font-black text-slate-800 dark:text-white mb-1 truncate"><?php echo htmlspecialchars($rb['title']); ?></h4>
                                            <p class="text-[10px] font-bold text-slate-400 italic"><?php echo number_format($rb['recipient_count']); ?> Siswa Terjangkau</p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pb-20"></div>
            </div>
        </div>
    </main>
</body>
</html>
