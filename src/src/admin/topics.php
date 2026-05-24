<?php
// src/admin/topics.php
session_start();
date_default_timezone_set('Asia/Jakarta');
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Fetch Admin Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_data = $stmt->fetch();

$subject_id = $_GET['subject_id'] ?? 0;
if (!$subject_id) { header("Location: subjects.php"); exit; }

// Fetch Subject Details
$stmt = $pdo->prepare("SELECT * FROM subjects WHERE id = ?");
$stmt->execute([$subject_id]);
$subject = $stmt->fetch();

if (!$subject) {
    header("Location: subjects.php?status=error_not_found");
    exit;
}

// Add Topic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = $_POST['title'];
    $slug = strtolower(str_replace(' ', '-', $title));
    
    try {
        $stmt = $pdo->prepare("INSERT INTO topics (subject_id, subject_slug, slug, title) VALUES (?, ?, ?, ?)");
        $stmt->execute([$subject_id, $subject['slug'], $slug, $title]);
        header("Location: topics.php?subject_id=$subject_id&status=added");
        exit;
    } catch (PDOException $e) {
        header("Location: topics.php?subject_id=$subject_id&status=error");
        exit;
    }
}

// Edit Topic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $slug = strtolower(str_replace(' ', '-', $title));
    
    $stmt = $pdo->prepare("UPDATE topics SET title = ?, slug = ? WHERE id = ?");
    $stmt->execute([$title, $slug, $id]);
    header("Location: topics.php?subject_id=$subject_id");
    exit;
}

// Delete Topic
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM topics WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: topics.php?subject_id=$subject_id");
    exit;
}

// Fetch Topics
$stmt = $pdo->prepare("SELECT * FROM topics WHERE subject_id = ? ORDER BY id ASC");
$stmt->execute([$subject_id]);
$topics = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Topik - <?php echo $subject['title']; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
                    <a href="subjects.php" class="w-12 h-12 bg-slate-100 dark:bg-blue-900/20 text-slate-500 dark:text-blue-400 rounded-xl flex items-center justify-center hover:bg-blue-600 hover:text-white transition-all transform active:scale-95 shadow-sm">
                        <i class="fas fa-arrow-left text-sm"></i>
                    </a>
                    <div class="space-y-1">
                        <div class="flex items-center space-x-3">
                            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-lg text-[10px] font-black uppercase tracking-widest border border-blue-200 dark:border-blue-800/50">
                                <?php echo htmlspecialchars($subject['title']); ?>
                            </span>
                        </div>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-2">Kelola Topik Belajar</h2>
                        <p class="text-sm text-slate-400 font-medium">Atur urutan materi dan bab pembelajaran siswa.</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div id="realtimeClockContainer" class="hidden md:flex items-center space-x-3 px-5 py-2.5 bg-white dark:bg-[#0a1128] border border-blue-100 dark:border-blue-900/20 rounded-2xl shadow-sm tracking-widest">
                        <i class="far fa-clock text-blue-600"></i>
                        <span id="clockDisplay" class="text-sm font-black text-slate-600 dark:text-slate-400 tabular-nums uppercase">00:00:00</span>
                    </div>
                    <button onclick="document.getElementById('addModal').classList.remove('hidden')" class="px-8 py-3 bg-blue-600 text-white font-black rounded-xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition transform hover:-translate-y-1 active:scale-95 text-xs uppercase tracking-widest">
                        + Topik Baru
                    </button>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar pb-32">
                <div class="max-w-7xl mx-auto">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php if (empty($topics)): ?>
                            <div class="col-span-full py-20 bg-white dark:bg-[#0a1128] rounded-[4rem] text-center border border-dashed border-blue-200 dark:border-blue-900/20 shadow-sm">
                                <div class="w-32 h-32 bg-blue-50 dark:bg-blue-900/20 text-blue-300 rounded-full flex items-center justify-center text-4xl mx-auto mb-10">
                                    <i class="fas fa-layer-group"></i>
                                </div>
                                <h3 class="text-3xl font-black text-slate-800 dark:text-white mb-4">Topik Belum Tersedia</h3>
                                <p class="text-slate-400 font-bold uppercase tracking-widest text-[10px]">Mulai tambahkan topik pembelajaran untuk mata pelajaran ini.</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($topics as $idx => $t): ?>
                            <div class="group relative bg-white dark:bg-[#0a1128] rounded-[3rem] p-8 border border-slate-50 dark:border-blue-900/10 hover:border-blue-500 transition-all duration-500 shadow-sm hover:shadow-2xl hover:shadow-blue-500/[0.05] flex flex-col items-center text-center overflow-hidden h-full">
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-600/[0.02] to-indigo-600/[0.02] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                
                                <div class="w-20 h-20 bg-blue-50 dark:bg-blue-600/10 text-blue-600 dark:text-blue-400 rounded-3xl flex items-center justify-center text-2xl font-black border border-blue-100 dark:border-blue-900/20 transition-transform group-hover:scale-110 mb-6 relative z-10 shadow-inner">
                                    <?php echo $idx + 1; ?>
                                </div>

                                <div class="relative z-10 flex-1 flex flex-col w-full mb-8">
                                    <div class="h-14 flex items-center justify-center mb-2 overflow-hidden">
                                        <h4 class="text-xl font-black text-slate-800 dark:text-white tracking-tight group-hover:text-blue-600 transition-colors line-clamp-2 leading-tight"><?php echo htmlspecialchars($t['title']); ?></h4>
                                    </div>
                                    <div class="flex items-center justify-center space-x-3">
                                        <span class="text-[9px] text-slate-400 dark:text-slate-500 font-bold uppercase tracking-widest">ID: #<?php echo $t['id']; ?></span>
                                        <span class="w-1 h-1 bg-slate-300 dark:bg-slate-700 rounded-full"></span>
                                        <span class="text-[9px] text-blue-500 dark:text-blue-400/60 font-black uppercase tracking-widest">AKTIF</span>
                                    </div>
                                </div>

                                <div class="w-full space-y-3 relative z-10 mt-auto">
                                    <a href="modules.php?topic_id=<?php echo $t['id']; ?>" class="flex items-center justify-center space-x-3 w-full py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/20 transition-all transform hover:-translate-y-1 active:scale-95 uppercase tracking-widest text-[10px]">
                                        <span>Daftar Modul</span>
                                        <i class="fas fa-arrow-right text-[9px]"></i>
                                    </a>
                                    <div class="flex space-x-2">
                                        <button onclick="editTopic(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['title'], ENT_QUOTES); ?>')" 
                                            class="flex-1 py-4 bg-slate-50 dark:bg-blue-900/10 text-slate-600 dark:text-blue-400 font-black rounded-2xl hover:bg-white dark:hover:bg-blue-600 hover:text-blue-600 dark:hover:text-white border border-slate-100 dark:border-blue-900/20 transition-all uppercase tracking-widest text-[9px] flex items-center justify-center space-x-2">
                                            <i class="fas fa-edit"></i>
                                            <span>Edit</span>
                                        </button>
                                        <button onclick="confirmDeleteTopic(<?php echo $t['id']; ?>, '<?php echo htmlspecialchars($t['title'], ENT_QUOTES); ?>')" 
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
            </div>

            <!-- Modal Add Topik -->
            <div id="addModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-[#060b1d]/80 backdrop-blur-md p-4 animate__animated animate__fadeIn">
                <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-8 md:p-12 max-w-lg w-full shadow-2xl border border-blue-100 dark:border-blue-900/20 animate__animated animate__zoomIn animate__faster">
                    <div class="flex items-center space-x-4 mb-10">
                        <div class="w-12 h-12 bg-blue-600/10 text-blue-600 rounded-2xl flex items-center justify-center text-xl">
                            <i class="fas fa-plus"></i>
                        </div>
                        <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase">Tambah Topik Baru</h3>
                    </div>
                    <form method="POST" class="space-y-8">
                        <input type="hidden" name="action" value="add">
                        <div class="space-y-4">
                            <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Nama / Judul Topik</label>
                            <input type="text" name="title" required autofocus placeholder="Contoh: Pengenalan Dasar Aljabar" class="w-full px-8 py-5 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all text-lg placeholder:text-slate-300 dark:placeholder:text-slate-700">
                        </div>
                        <div class="flex space-x-4">
                            <button type="button" onclick="document.getElementById('addModal').classList.add('hidden')" class="flex-1 py-5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold rounded-2xl transition uppercase tracking-widest text-xs">Batalkan</button>
                            <button type="submit" class="flex-1 py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition uppercase tracking-widest text-xs">Simpan Topik</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

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
        function confirmDeleteTopic(id, title) {
            confirmModernAlert({
                title: 'HAPUS TOPIK?',
                html: `Anda yakin ingin menghapus topik <strong>${title}</strong>?<br><span class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-2 block">⚠️ Seluruh modul didalamnya juga akan terhapus!</span>`,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'YA, HAPUS'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `?subject_id=<?php echo $subject_id; ?>&delete=${id}`;
                }
            });
        }


        function editTopic(id, title) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('editModal').classList.remove('hidden');
        }

        const status = new URLSearchParams(window.location.search).get('status');
        if (status) {
            if (status === 'added') showModernAlert({ title: 'BERHASIL!', text: 'Topik baru telah ditambahkan.', icon: 'success' });
            if (status === 'updated') showModernAlert({ title: 'BERHASIL!', text: 'Topik telah diperbarui.', icon: 'success' });
            if (status === 'deleted') showModernAlert({ title: 'DIHAPUS!', text: 'Topik telah dihapus.', icon: 'success' });
            if (status === 'error') showModernAlert({ title: 'GAGAL!', text: 'Terjadi kesalahan saat memproses data.', icon: 'error' });
        }
    </script>

    <!-- Modal Edit Topik -->
    <div id="editModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-[#060b1d]/80 backdrop-blur-md p-4 animate__animated animate__fadeIn">
        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-8 md:p-12 max-w-lg w-full shadow-2xl border border-blue-100 dark:border-blue-900/20 animate__animated animate__zoomIn animate__faster">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-10 tracking-tight uppercase">Edit Topik</h3>
            <form method="POST" class="space-y-8">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest">Nama / Judul Topik</label>
                    <input type="text" name="title" id="edit_title" required class="w-full px-8 py-5 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all text-lg placeholder:text-slate-300 dark:placeholder:text-slate-700">
                </div>
                <div class="flex space-x-4">
                    <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="flex-1 py-5 bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 font-bold rounded-2xl transition uppercase tracking-widest text-xs">Batalkan</button>
                    <button type="submit" class="flex-1 py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition uppercase tracking-widest text-xs">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
