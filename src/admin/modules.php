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
<body class="bg-[#f8fafc] text-slate-800 dark:bg-[#020617] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">
    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-green-500/5 dark:bg-green-500/10 rounded-full blur-[120px] -mr-64 -mt-64 pointer-events-none"></div>

        <!-- Header -->
        <header class="p-8 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center bg-white/30 dark:bg-[#020617]/30 backdrop-blur-xl border-b border-blue-50/50 dark:border-blue-900/20 z-10">
            <div class="flex items-center space-x-6">
                <a href="topics.php?subject_id=<?php echo $topic['subject_id']; ?>" class="w-12 h-12 bg-white/50 dark:bg-blue-900/20 text-slate-500 dark:text-blue-400 rounded-2xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all transform active:scale-95 border border-blue-100 dark:border-blue-900/30">
                    <i class="fas fa-chevron-left text-sm"></i>
                </a>
                <div>
                    <div class="flex items-center space-x-3 mb-2">
                        <span class="px-3 py-1 bg-emerald-600 text-[10px] font-black text-white rounded-lg uppercase tracking-widest"><?php echo htmlspecialchars($topic['title']); ?></span>
                        <span class="text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-widest italic">/ Modules Directory</span>
                    </div>
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1">Manajemen Modul</h1>
                    <p class="text-slate-500 dark:text-slate-400 font-medium text-xs italic">Kelola konten video dan bank soal kuis.</p>
                </div>
            </div>
            <div class="flex items-center space-x-4 mt-6 md:mt-0">
                <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                    class="px-8 py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/20 transition transform hover:-translate-y-1 active:scale-95 text-[10px] uppercase tracking-widest flex items-center space-x-3">
                    <i class="fas fa-plus-circle text-sm"></i>
                    <span>TAMBAH MODUL</span>
                </button>
            </div>
        </header>

            <div class="flex-1 overflow-y-auto p-8 custom-scrollbar scroll-smooth">
                <div class="max-w-7xl mx-auto space-y-8 pb-32">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
                        <?php if (empty($modules)): ?>
                            <div class="col-span-full py-32 bg-white/50 dark:bg-[#0a1128]/50 backdrop-blur-xl rounded-[4rem] text-center border border-dashed border-blue-200 dark:border-blue-900/20 shadow-sm animate-pulse">
                                <div class="w-32 h-32 bg-blue-50 dark:bg-blue-900/20 text-blue-300 rounded-[2.5rem] flex items-center justify-center text-4xl mx-auto mb-8 shadow-inner">
                                    <i class="fas fa-play"></i>
                                </div>
                                <h3 class="text-3xl font-black text-slate-800 dark:text-white mb-4 tracking-tighter">Materi Kosong</h3>
                                <p class="text-slate-400 font-bold uppercase tracking-[0.2em] text-[10px] mb-12">Belum ada modul video atau kuis yang tersedia.</p>
                                <button onclick="document.getElementById('addModal').classList.remove('hidden')"
                                    class="px-10 py-5 bg-blue-600 text-white font-black rounded-[1.5rem] hover:bg-blue-700 shadow-2xl shadow-blue-500/40 transition-transform active:scale-95 uppercase tracking-widest text-xs">
                                    Create First Module
                                </button>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($modules as $idx => $m): ?>
                            <div class="group relative bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl rounded-[3.5rem] p-8 border border-blue-50/50 dark:border-blue-900/20 hover:border-blue-500/50 transition-all duration-700 shadow-sm hover:shadow-2xl hover:shadow-blue-500/10 flex flex-col h-full overflow-hidden hover:-translate-y-2">
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-600/[0.03] to-transparent opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                                
                                <div class="relative z-10 flex justify-center mb-8">
                                    <div class="w-24 h-24 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-600/10 dark:to-blue-600/20 text-blue-600 dark:text-blue-400 rounded-[2.5rem] flex items-center justify-center text-4xl group-hover:scale-110 group-hover:rotate-12 transition-all duration-700 shadow-inner border border-blue-100/50 dark:border-blue-900/20">
                                        <i class="fas fa-play-circle"></i>
                                    </div>
                                </div>

                                <div class="relative z-10 flex-1 flex flex-col mb-10 text-center">
                                    <div class="flex items-center justify-center space-x-2 mb-3">
                                        <span class="px-2.5 py-1 bg-slate-900 text-white dark:bg-blue-600 text-[8px] font-black tracking-[0.2em] uppercase rounded-lg">M#<?php echo $idx + 1; ?></span>
                                        <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest italic">Video Content</span>
                                    </div>
                                    <h4 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter leading-tight line-clamp-2 px-2 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($m['title']); ?></h4>
                                </div>

                                <div class="relative z-10 space-y-4 mt-auto">
                                    <a href="edit_module.php?id=<?php echo $m['id']; ?>" class="flex items-center justify-center space-x-3 w-full py-5 bg-slate-900 text-white font-black rounded-[2rem] hover:bg-blue-600 shadow-xl shadow-slate-900/10 hover:shadow-blue-500/20 transition-all transform active:scale-95 uppercase tracking-widest text-[10px] group/btn">
                                        <span>Editor & Quiz</span>
                                        <i class="fas fa-pencil-alt text-[9px] group-hover/btn:translate-x-1 transition-transform"></i>
                                    </a>
                                    <div class="flex space-x-3">
                                        <button onclick="editModule(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars($m['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($m['video_url'], ENT_QUOTES); ?>')"
                                            class="flex-1 py-4 bg-white/50 dark:bg-blue-900/20 text-slate-600 dark:text-blue-400 font-black rounded-2xl hover:bg-blue-600 hover:text-white border border-blue-50 dark:border-blue-900/30 transition-all uppercase tracking-widest text-[9px] flex items-center justify-center space-x-2">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Info</span>
                                        </button>
                                        <button onclick="confirmDeleteModule(<?php echo $m['id']; ?>, '<?php echo htmlspecialchars($m['title'], ENT_QUOTES); ?>')"
                                            class="flex-1 py-4 bg-rose-500/10 text-rose-500 font-black rounded-2xl hover:bg-rose-500 hover:text-white transition-all uppercase tracking-widest text-[9px] flex items-center justify-center space-x-2">
                                            <i class="fas fa-trash-alt"></i>
                                            <span>Delete</span>
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
