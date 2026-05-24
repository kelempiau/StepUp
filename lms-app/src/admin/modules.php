<?php
// src/admin/modules.php
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

$topic_id = $_GET['topic_id'] ?? 0;
if (!$topic_id) { header("Location: subjects.php"); exit; }

// Fetch Topic Details
$stmt = $pdo->prepare("SELECT * FROM topics WHERE id = ?");
$stmt->execute([$topic_id]);
$topic = $stmt->fetch();

if (!$topic) {
    header("Location: subjects.php?status=error_topic_not_found");
    exit;
}

// Add Module
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = $_POST['title'];
    $slug = strtolower(str_replace(' ', '-', $title));
    $video = $_POST['video_url'];
    
    try {
        $stmt = $pdo->prepare("INSERT INTO modules (topic_id, topic_slug, slug, title, video_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$topic_id, $topic['slug'], $slug, $title, $video]);
        header("Location: modules.php?topic_id=$topic_id&status=added");
        exit;
    } catch (PDOException $e) {
        header("Location: modules.php?topic_id=$topic_id&status=error");
        exit;
    }
}

// Edit Module Info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_info') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $video_url = $_POST['video_url'];
    $slug = strtolower(str_replace(' ', '-', $title));
    
    $stmt = $pdo->prepare("UPDATE modules SET title = ?, slug = ?, video_url = ? WHERE id = ?");
    $stmt->execute([$title, $slug, $video_url, $id]);
    header("Location: modules.php?topic_id=$topic_id");
    exit;
}

// Delete Module
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: modules.php?topic_id=$topic_id");
    exit;
}

// Fetch Modules
$stmt = $pdo->prepare("SELECT * FROM modules WHERE topic_id = ? ORDER BY id ASC");
$stmt->execute([$topic_id]);
$modules = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Modul - <?php echo $topic['title']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/auth.js"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
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
            <!-- Dynamic Page Header -->
            <div class="p-6 md:p-10 border-b border-blue-50 dark:border-blue-900/10 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white dark:bg-[#0a1128]/50 backdrop-blur-md">
                <div class="flex items-center space-x-6">
                    <a href="topics.php?subject_id=<?php echo $topic['subject_id']; ?>" class="w-12 h-12 bg-slate-100 dark:bg-blue-900/20 text-slate-500 dark:text-blue-400 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all transform active:scale-95 shadow-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                    </a>
                    <div class="space-y-1">
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg text-[10px] font-black uppercase tracking-widest border border-green-200 dark:border-green-800/50">
                                <?php echo htmlspecialchars($topic['title']); ?>
                            </span>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-2">Manajemen Modul</h2>
                        <p class="text-sm text-slate-400 font-medium">Kelola isi materi video dan soal latihan kuis.</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div id="realtimeClockContainer" class="hidden md:flex items-center space-x-3 px-5 py-2.5 bg-white dark:bg-[#0a1128] border border-blue-100 dark:border-blue-900/20 rounded-2xl shadow-sm tracking-widest">
                        <i class="far fa-clock text-blue-600"></i>
                        <span id="clockDisplay" class="text-sm font-black text-slate-600 dark:text-slate-400 tabular-nums uppercase">00:00:00</span>
                    </div>
                    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-8 py-3 bg-blue-600 text-white font-black rounded-xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition transform hover:-translate-y-1 active:scale-95 text-xs uppercase tracking-widest">
                        + Modul Baru
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar pb-32">
                <div class="max-w-7xl mx-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                        <?php if (empty($modules)): ?>
                            <div class="col-span-full py-20 bg-white dark:bg-[#0a1128] rounded-[4rem] text-center border border-dashed border-blue-200 dark:border-blue-900/20 shadow-sm animate-pulse">
                                <div class="w-32 h-32 bg-blue-50 dark:bg-blue-900/20 text-blue-300 rounded-full flex items-center justify-center text-4xl mx-auto mb-10">
                                    <i class="fas fa-play-circle"></i>
                                </div>
                                <h3 class="text-3xl font-black text-slate-800 dark:text-white mb-4">Modul Kosong</h3>
                                <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px] mb-12">Isi materi video dan kuis untuk topik ini.</p>
                                <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-12 py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-2xl shadow-blue-500/40">Tambah Modul Pertama</button>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($modules as $idx => $m): ?>
                            <div class="group relative bg-white dark:bg-[#0a1128] rounded-[2.5rem] p-8 border border-slate-50 dark:border-blue-900/10 hover:border-blue-500 transition-all duration-300 shadow-sm hover:shadow-[0_20px_60px_-15px_rgba(37,99,235,0.05)] overflow-hidden flex flex-col h-full">
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-600/[0.01] to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                
                                <div class="relative z-10 flex justify-center mb-6">
                                    <div class="w-20 h-20 bg-blue-50 dark:bg-blue-600/10 text-blue-600 dark:text-blue-400 rounded-3xl flex items-center justify-center text-3xl group-hover:scale-110 group-hover:rotate-6 transition-all duration-500 shadow-inner border border-blue-100/50 dark:border-blue-900/20">
                                        <i class="fas fa-video"></i>
                                    </div>
                                </div>

                                <div class="relative z-10 flex-1 flex flex-col justify-center mb-8 text-center">
                                    <div class="flex items-center justify-center space-x-2 mb-3">
                                        <span class="px-3 py-1 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 rounded-lg text-[10px] font-black tracking-widest uppercase">MODUL #<?php echo $idx + 1; ?></span>
                                    </div>
                                    <h4 class="text-xl font-black text-slate-800 dark:text-white tracking-tight leading-tight line-clamp-2 px-2 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($m['title']); ?></h4>
                                </div>

                                <div class="relative z-10 space-y-3 mt-auto">
                                    <a href="edit_module.php?id=<?php echo $m['id']; ?>" class="flex items-center justify-center space-x-3 w-full py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/20 transition-all transform hover:-translate-y-1 active:scale-95 uppercase tracking-widest text-[10px]">
                                        <span>Edit Konten & Kuis</span>
                                        <i class="fas fa-pen text-[9px]"></i>
                                    </a>
                                    <div class="flex space-x-2">
                                        <button onclick="editModule(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars($m['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($m['video_url'], ENT_QUOTES); ?>')" 
                                            class="flex-1 py-4 bg-slate-50 dark:bg-blue-900/10 text-slate-600 dark:text-blue-400 font-black rounded-2xl hover:bg-white dark:hover:bg-blue-600 hover:text-blue-600 dark:hover:text-white border border-slate-100 dark:border-blue-900/20 transition-all uppercase tracking-widest text-[9px] flex items-center justify-center space-x-2">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Info</span>
                                        </button>
                                        <button onclick="confirmDeleteModule(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars($m['title'], ENT_QUOTES); ?>')" 
                                            class="flex-1 py-4 bg-red-50 dark:bg-red-900/10 text-red-500 dark:text-red-400 font-black rounded-2xl hover:bg-red-600 hover:text-white transition-all uppercase tracking-widest text-[9px] flex items-center justify-center space-x-2">
                                            <i class="fas fa-trash-alt"></i>
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                </div>
            </div>

            <!-- Modal Add Modul -->
            <div id="addModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-[#060b1d]/80 backdrop-blur-md p-4 animate__animated animate__fadeIn">
                <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-8 md:p-12 max-w-lg w-full shadow-2xl border border-blue-100 dark:border-blue-900/20 animate__animated animate__zoomIn animate__faster">
                    <div class="flex items-center space-x-4 mb-10">
                        <div class="w-12 h-12 bg-blue-600/10 text-blue-600 rounded-2xl flex items-center justify-center text-xl">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Tambah Modul Baru</h3>
                    </div>
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="action" value="add">
                        <div class="space-y-4">
                            <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest px-2">Judul Modul</label>
                            <input type="text" name="title" required placeholder="Contoh: Operasi Penjumlahan & Pengurangan" class="w-full px-8 py-5 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all text-lg placeholder:text-slate-300 dark:placeholder:text-slate-700">
                        </div>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between px-2">
                                <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Link Video (Embed)</label>
                                <span class="text-[9px] text-blue-500 font-bold uppercase tracking-widest">YouTube Preferred</span>
                            </div>
                            <input type="text" name="video_url" placeholder="https://www.youtube.com/embed/XXXXXX" class="w-full px-8 py-5 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all placeholder:text-slate-300 dark:placeholder:text-slate-700">
                        </div>
                        <div class="flex space-x-4 pt-4">
                            <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 py-5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold rounded-2xl transition uppercase tracking-widest text-xs">Batalkan</button>
                            <button type="submit" class="flex-1 py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition uppercase tracking-widest text-xs">Simpan Modul</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }).replace(/\./g, ':');
            const display = document.getElementById('clockDisplay');
            if (display) display.textContent = time;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>

    <script>
        function confirmDeleteModule(id, title) {
            confirmModernAlert({
                title: 'HAPUS MODUL?',
                html: `Anda yakin ingin menghapus modul <strong>${title}</strong>?<br><span class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-2 block">⚠️ Materi di dalamnya juga akan terhapus!</span>`,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'YA, HAPUS'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?topic_id=<?php echo $topic_id; ?>&delete=${id}`;
                }
            });
        }



        function editModule(id, title, video) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_video').value = video;
            document.getElementById('editModal').classList.remove('hidden');
        }

        const status = new URLSearchParams(window.location.search).get('status');
        if (status) {
            if (status === 'added') showModernAlert({ title: 'BERHASIL!', text: 'Modul baru telah ditambahkan.', icon: 'success' });
            if (status === 'updated') showModernAlert({ title: 'BERHASIL!', text: 'Modul telah diperbarui.', icon: 'success' });
            if (status === 'deleted') showModernAlert({ title: 'DIHAPUS!', text: 'Modul telah dihapus.', icon: 'success' });
            if (status === 'error') showModernAlert({ title: 'GAGAL!', text: 'Terjadi kesalahan saat memproses data.', icon: 'error' });
        }
    </script>

    <!-- Modal Edit Modul -->
    <div id="editModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-[#060b1d]/80 backdrop-blur-md p-4 animate__animated animate__fadeIn">
        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-8 md:p-12 max-w-lg w-full shadow-2xl border border-blue-100 dark:border-blue-900/20 animate__animated animate__zoomIn animate__faster">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-10 tracking-tight uppercase">Edit Info Modul</h3>
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="edit_info">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest px-2">Judul Modul</label>
                    <input type="text" name="title" id="edit_title" required class="w-full px-8 py-5 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all text-lg placeholder:text-slate-300 dark:placeholder:text-slate-700">
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between px-2">
                        <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Link Video (Embed)</label>
                        <span class="text-[9px] text-blue-500 font-bold uppercase tracking-widest">YouTube Preferred</span>
                    </div>
                    <input type="text" name="video_url" id="edit_video" class="w-full px-8 py-5 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all placeholder:text-slate-300 dark:placeholder:text-slate-700">
                </div>
                <div class="flex space-x-4 pt-4">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 py-5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold rounded-2xl transition uppercase tracking-widest text-xs">Batalkan</button>
                    <button type="submit" class="flex-1 py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition uppercase tracking-widest text-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
