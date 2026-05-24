<?php
// src/admin/subjects.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Fetch Admin Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_data = $stmt->fetch();

// Add Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $title = $_POST['title'];
    $slug = strtolower(str_replace(' ', '-', $title));
    $description = $_POST['description'];
    $icon = $_POST['icon'];
    
    
    try {
        $stmt = $pdo->prepare("INSERT INTO subjects (title, slug, icon, description) VALUES (?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $icon, $description]);
        header("Location: subjects.php?status=added");
        exit;
    } catch (PDOException $e) {
        header("Location: subjects.php?status=error_duplicate");
        exit;
    }
}

// Edit Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'];
    $title = $_POST['title'];
    $slug = strtolower(str_replace(' ', '-', $title));
    $description = $_POST['description'];
    $icon = $_POST['icon'];
    
    
    $stmt = $pdo->prepare("UPDATE subjects SET title = ?, slug = ?, icon = ?, description = ? WHERE id = ?");
    $stmt->execute([$title, $slug, $icon, $description, $id]);
    header("Location: subjects.php?status=updated");
    exit;
}

// Delete Subject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: subjects.php?status=deleted");
    exit;
}



// Fetch Subjects
$stmt = $pdo->query("SELECT * FROM subjects ORDER BY id ASC");
$subjects = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mapel - Admin StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/auth.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; }
    </style>
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
                    <span class="px-3 py-1 bg-blue-600 text-[10px] font-black text-white rounded-lg uppercase tracking-widest">Curriculum</span>
                    <span class="text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-widest italic">/ Subjects</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1">Kurikulum Utama</h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-xs italic">Kelola struktur pembelajaran dan mata pelajaran.</p>
            </div>
            <div class="flex items-center space-x-4 mt-6 md:mt-0">
                <div class="hidden lg:flex items-center space-x-2 mr-4">
                    <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Status:</span>
                    <span class="px-3 py-1 bg-green-500/10 text-green-500 text-[9px] font-black rounded-lg border border-green-500/20 uppercase">Online</span>
                </div>
                <button onclick="document.getElementById('addSubjectModal').classList.remove('hidden')"
                    class="px-8 py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/20 transition transform hover:-translate-y-1 active:scale-95 text-[10px] uppercase tracking-widest flex items-center space-x-3">
                    <i class="fas fa-plus-circle text-sm"></i>
                    <span>TAMBAH MAPEL</span>
                </button>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar scroll-smooth">
            <div class="max-w-7xl mx-auto space-y-10">
                
                <!-- Welcome Section -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-800 rounded-[3rem] p-10 text-white relative overflow-hidden shadow-2xl shadow-blue-500/20">
                    <div class="absolute top-0 right-0 w-96 h-96 bg-white/10 rounded-full -mr-32 -mt-32 blur-3xl pointer-events-none"></div>
                    <div class="relative z-10">
                        <h2 class="text-2xl font-black mb-4 tracking-tighter uppercase">Statistik Kurikulum</h2>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                            <div class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/10">
                                <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-1">Total Mapel</p>
                                <p class="text-3xl font-black"><?php echo count($subjects); ?></p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/10">
                                <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-1">Video Modul</p>
                                <p class="text-3xl font-black">254</p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/10">
                                <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-1">Total Kuis</p>
                                <p class="text-3xl font-black">1,024</p>
                            </div>
                            <div class="bg-white/10 backdrop-blur-md p-6 rounded-3xl border border-white/10">
                                <p class="text-[10px] font-black uppercase tracking-widest opacity-60 mb-1">Siswa Aktif</p>
                                <p class="text-3xl font-black">892</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8 pb-32">
                    <?php foreach ($subjects as $s): ?>
                        <div class="group relative bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl rounded-[3.5rem] p-8 border border-blue-50/50 dark:border-blue-900/20 hover:border-blue-500/50 transition-all duration-700 shadow-sm hover:shadow-2xl hover:shadow-blue-500/10 flex flex-col h-full overflow-hidden text-center hover:-translate-y-2">
                            <div class="absolute inset-0 bg-gradient-to-br from-blue-600/[0.03] to-blue-700/[0.03] opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none"></div>
                            
                            <!-- Card Header: Icon -->
                            <div class="relative z-10 flex justify-center mb-8">
                                <div class="w-24 h-24 bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-600/10 dark:to-blue-700/10 text-blue-600 dark:text-blue-400 rounded-[2.5rem] flex items-center justify-center text-4xl group-hover:scale-110 group-hover:rotate-12 transition-all duration-700 shadow-inner border border-blue-100/50 dark:border-blue-900/20 relative">
                                    <?php echo $s['icon']; ?>
                                    <div class="absolute -top-2 -right-2 w-8 h-8 bg-blue-600 text-white rounded-xl text-[10px] font-black flex items-center justify-center shadow-lg shadow-blue-500/30">
                                        <?php echo $s['id']; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Card Body -->
                            <div class="relative z-10 flex-1 flex flex-col justify-center mb-10">
                                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter leading-tight mb-3 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($s['title']); ?></h3>
                                <p class="text-[11px] text-slate-400 dark:text-slate-500 font-bold leading-relaxed line-clamp-3 px-2 italic uppercase tracking-wider">
                                    <?php echo htmlspecialchars($s['description']); ?>
                                </p>
                            </div>
                            
                            <!-- Card Footer: Full Bodied Buttons -->
                            <div class="relative z-10 space-y-4 mt-auto">
                                <a href="topics.php?subject_id=<?php echo $s['id']; ?>" class="flex items-center justify-center space-x-3 w-full py-5 bg-slate-900 text-white font-black rounded-[2rem] hover:bg-blue-600 shadow-xl shadow-slate-900/10 hover:shadow-blue-500/20 transition-all transform active:scale-95 uppercase tracking-widest text-[10px] group/btn">
                                    <span>Manage Topics</span>
                                    <i class="fas fa-arrow-right text-[10px] group-hover/btn:translate-x-2 transition-transform"></i>
                                </a>
                                <div class="flex space-x-3">
                                    <button onclick="editSubject(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($s['description'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($s['icon'], ENT_QUOTES); ?>')"
                                        class="flex-1 py-4 bg-white/50 dark:bg-blue-900/20 text-slate-600 dark:text-blue-400 font-black rounded-2xl hover:bg-blue-600 hover:text-white border border-blue-50 dark:border-blue-900/30 transition-all uppercase tracking-widest text-[9px] flex items-center justify-center space-x-2">
                                        <i class="fas fa-pencil-alt"></i>
                                        <span>Edit</span>
                                    </button>
                                    <button onclick="confirmDeleteSubject(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?>')"
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
        </div>
    </main>

    <!-- Modal Add Subject -->
    <div id="addSubjectModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-[#060b1d]/80 backdrop-blur-md p-4 animate__animated animate__fadeIn">
        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-5 md:p-8 md:p-12 max-w-lg w-full shadow-2xl border border-blue-100 dark:border-blue-900/20 animate__animated animate__zoomIn animate__faster max-h-[90vh] overflow-y-auto custom-scrollbar">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-10 tracking-tight uppercase">Tambah Mata Pelajaran</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="action" value="add">
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Nama Mapel</label>
                    <input type="text" name="title" required class="w-full px-6 py-4 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all">
                </div>
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Ikon (FontAwesome / Emoji)</label>
                    <input type="text" name="icon" placeholder="Contoh: <i class='fas fa-book'></i> atau 📖" required class="w-full px-6 py-4 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all">
                </div>
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Deskripsi Singkat</label>
                    <textarea name="description" rows="3" class="w-full px-6 py-4 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all"></textarea>
                </div>

                <div class="flex space-x-3 pt-6">
                    <button type="button" onclick="document.getElementById('addSubjectModal').classList.add('hidden')" class="flex-1 py-4 bg-slate-100 dark:bg-slate-800 text-slate-400 font-bold rounded-2xl transition uppercase tracking-widest text-xs">Batal</button>
                    <button type="submit" class="flex-1 py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition uppercase tracking-widest text-xs">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Edit Subject -->
    <div id="editSubjectModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-[#060b1d]/80 backdrop-blur-md p-4 animate__animated animate__fadeIn">
        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-5 md:p-8 md:p-12 max-w-lg w-full shadow-2xl border border-blue-100 dark:border-blue-900/20 animate__animated animate__zoomIn animate__faster max-h-[90vh] overflow-y-auto custom-scrollbar">
            <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-10 tracking-tight uppercase">Edit Mata Pelajaran</h3>
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Nama Mapel</label>
                    <input type="text" name="title" id="edit_title" required class="w-full px-6 py-4 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all">
                </div>
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Ikon (FontAwesome / Emoji)</label>
                    <input type="text" name="icon" id="edit_icon" required class="w-full px-6 py-4 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all">
                </div>
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Deskripsi Singkat</label>
                    <textarea name="description" id="edit_description" rows="3" class="w-full px-6 py-4 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all"></textarea>
                </div>

                <div class="flex space-x-3 pt-6">
                    <button type="button" onclick="document.getElementById('editSubjectModal').classList.add('hidden')" class="flex-1 py-4 bg-slate-100 dark:bg-slate-800 text-slate-400 font-bold rounded-2xl transition uppercase tracking-widest text-xs">Batal</button>
                    <button type="submit" class="flex-1 py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition uppercase tracking-widest text-xs">Update</button>
                </div>
            </form>
        </div>
    </div>

    <script>

        // Live Clock
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            document.getElementById('clockDisplay').textContent = time;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function editSubject(id, title, description, icon) {
            document.getElementById('edit_id').value = id;
            document.getElementById('edit_title').value = title;
            document.getElementById('edit_description').value = description;
            document.getElementById('edit_icon').value = icon;
            document.getElementById('editSubjectModal').classList.remove('hidden');
        }

        function confirmDeleteSubject(id, title) {
            confirmModernAlert({
                title: 'Hapus Mapel?',
                html: `Anda yakin ingin menghapus <strong>${title}</strong>?<br><span class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-2 block">⚠️ Semua data terkait akan terhapus!</span>`,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'YA, HAPUS'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="${id}">
                    `;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        const status = new URLSearchParams(window.location.search).get('status');
        if (status) {
            if (status === 'added') showModernAlert({ title: 'BERHASIL!', text: 'Mata pelajaran baru telah ditambahkan.', icon: 'success' });
            if (status === 'updated') showModernAlert({ title: 'BERHASIL!', text: 'Perubahan telah disimpan.', icon: 'success' });
            if (status === 'deleted') showModernAlert({ title: 'DIHAPUS!', text: 'Mata pelajaran telah dihapus.', icon: 'success' });
            if (status === 'error_duplicate') showModernAlert({ title: 'GAGAL!', text: 'Nama mata pelajaran sudah ada. Gunakan nama lain.', icon: 'error' });
        }


    </script>
</body>
</html>
