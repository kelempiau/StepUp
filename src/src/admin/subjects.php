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
    
    $batik_bg = null;
    if (isset($_FILES['batik_bg']) && $_FILES['batik_bg']['error'] === 0) {
        $ext = pathinfo($_FILES['batik_bg']['name'], PATHINFO_EXTENSION);
        $filename = 'batik_' . $slug . '_' . time() . '.' . $ext;
        $target = '../../assets/img/' . $filename;
        if (move_uploaded_file($_FILES['batik_bg']['tmp_name'], $target)) {
            $batik_bg = 'assets/img/' . $filename;
        }
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO subjects (title, slug, icon, description, batik_bg) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $slug, $icon, $description, $batik_bg]);
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
    
    $stmt = $pdo->prepare("SELECT batik_bg FROM subjects WHERE id = ?");
    $stmt->execute([$id]);
    $current = $stmt->fetch();
    $batik_bg = $current['batik_bg'];

    if (isset($_FILES['batik_bg']) && $_FILES['batik_bg']['error'] === 0) {
        $ext = pathinfo($_FILES['batik_bg']['name'], PATHINFO_EXTENSION);
        $filename = 'batik_' . $slug . '_' . time() . '.' . $ext;
        $target = '../../assets/img/' . $filename;
        if (move_uploaded_file($_FILES['batik_bg']['tmp_name'], $target)) {
            // Hapus file lama jika ada
            if ($batik_bg && file_exists('../../' . $batik_bg)) unlink('../../' . $batik_bg);
            $batik_bg = 'assets/img/' . $filename;
        }
    } elseif (isset($_POST['remove_batik']) && $_POST['remove_batik'] === '1') {
        if ($batik_bg && file_exists('../../' . $batik_bg)) unlink('../../' . $batik_bg);
        $batik_bg = null;
    }
    
    $stmt = $pdo->prepare("UPDATE subjects SET title = ?, slug = ?, icon = ?, description = ?, batik_bg = ? WHERE id = ?");
    $stmt->execute([$title, $slug, $icon, $description, $batik_bg, $id]);
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

// Reset All Batik
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_all_batik') {
    $stmt = $pdo->query("SELECT batik_bg FROM subjects WHERE batik_bg IS NOT NULL");
    while($row = $stmt->fetch()){
        if($row['batik_bg'] && file_exists('../../' . $row['batik_bg'])) unlink('../../' . $row['batik_bg']);
    }
    $pdo->query("UPDATE subjects SET batik_bg = NULL");
    header("Location: subjects.php?status=reset_success");
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
<body class="bg-blue-50/50 text-slate-800 dark:bg-[#060b1d] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

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
                <div class="space-y-1">
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-2">Manajemen Mata Pelajaran</h2>
                    <p class="text-sm text-slate-400 font-medium">Susun kurikulum dan kontrol materi pembelajaran dengan mudah.</p>
                </div>
                <span class="px-5 py-2 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-500/20 transition-transform hover:scale-105 cursor-default">
                    <?php echo count($subjects); ?> MAPEL TERDAFTAR
                </span>
            </div>
            <div class="flex items-center space-x-3">
                <div id="realtimeClockContainer" class="hidden md:flex items-center space-x-3 px-5 py-2.5 bg-white dark:bg-[#0a1128] border border-blue-100 dark:border-blue-900/20 rounded-2xl shadow-sm tracking-widest">
                    <i class="far fa-clock text-blue-600"></i>
                    <span id="clockDisplay" class="text-sm font-black text-slate-600 dark:text-slate-400 tabular-nums uppercase">00:00:00</span>
                </div>
                <button onclick="confirmResetAllBatik()" class="px-6 py-3 bg-red-50 text-red-600 font-black rounded-xl hover:bg-red-600 hover:text-white border border-red-100 dark:border-red-900/20 transition transform hover:-translate-y-1 active:scale-95 text-xs uppercase tracking-widest hidden md:flex items-center gap-2">
                    <i class="fas fa-sync-alt"></i> Reset Semua Batik
                </button>
                <button onclick="document.getElementById('addSubjectModal').classList.remove('hidden')" class="px-8 py-3 bg-blue-600 text-white font-black rounded-xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition transform hover:-translate-y-1 active:scale-95 text-xs uppercase tracking-widest">
                    + Mapel Baru
                </button>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar pb-32">
            <div class="max-w-7xl mx-auto">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <?php foreach ($subjects as $s): ?>
                            <div class="group relative bg-white dark:bg-[#0a1128] rounded-[3rem] p-8 border border-slate-50 dark:border-blue-900/10 hover:border-blue-500 transition-all duration-500 shadow-sm hover:shadow-[0_20px_60px_-15px_rgba(37,99,235,0.12)] flex flex-col h-full overflow-hidden text-center">
                                <div class="absolute inset-0 bg-gradient-to-br from-blue-600/[0.02] to-indigo-600/[0.02] opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                
                                <!-- Card Header: Icon -->
                                <div class="relative z-10 flex justify-center mb-6">
                                    <div class="w-20 h-20 bg-blue-50 dark:bg-blue-600/10 text-blue-600 dark:text-blue-400 rounded-3xl flex items-center justify-center text-3xl group-hover:scale-110 group-hover:rotate-6 transition-all duration-500 shadow-inner border border-blue-100/50 dark:border-blue-900/20">
                                        <?php echo $s['icon']; ?>
                                    </div>
                                </div>
                                
                                <!-- Card Body -->
                                <div class="relative z-10 flex-1 flex flex-col justify-center mb-8">
                                    <h3 class="text-xl font-black text-slate-800 dark:text-white tracking-tight leading-tight mb-3"><?php echo htmlspecialchars($s['title']); ?></h3>
                                    <p class="text-[10px] text-slate-400 dark:text-slate-500 font-bold leading-relaxed line-clamp-2 px-4"><?php echo htmlspecialchars($s['description']); ?></p>
                                </div>
                                
                                <!-- Card Footer: Full Bodied Buttons -->
                                <div class="relative z-10 space-y-3 mt-auto">
                                    <a href="topics.php?subject_id=<?php echo $s['id']; ?>" class="flex items-center justify-center space-x-3 w-full py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/20 transition-all transform hover:-translate-y-1 active:scale-95 uppercase tracking-widest text-xs">
                                        <span>Kelola Materi</span>
                                        <i class="fas fa-arrow-right text-[10px] group-hover:translate-x-1 transition-transform"></i>
                                    </a>
                                    <div class="flex space-x-2">
                                        <button onclick="editSubject(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($s['description'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($s['icon'], ENT_QUOTES); ?>')" 
                                            class="flex-1 py-4 bg-slate-50 dark:bg-blue-900/10 text-slate-600 dark:text-blue-400 font-black rounded-2xl hover:bg-white dark:hover:bg-blue-600 hover:text-blue-600 dark:hover:text-white border border-slate-100 dark:border-blue-900/20 transition-all uppercase tracking-widest text-[9px] flex items-center justify-center space-x-2">
                                            <i class="fas fa-edit"></i>
                                            <span>Edit</span>
                                        </button>
                                        <button onclick="confirmDeleteSubject(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['title'], ENT_QUOTES); ?>')" 
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
    </main>

    <!-- Modal Add Subject -->
    <div id="addSubjectModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-[#060b1d]/80 backdrop-blur-md p-4 animate__animated animate__fadeIn">
        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-8 md:p-12 max-w-lg w-full shadow-2xl border border-blue-100 dark:border-blue-900/20 animate__animated animate__zoomIn animate__faster max-h-[90vh] overflow-y-auto custom-scrollbar">
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
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Batik Background (Hanya di Mapel)</label>
                    <input type="file" name="batik_bg" accept="image/*" class="w-full px-6 py-4 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all">
                    <p class="text-[10px] text-slate-400">Jika diisi, halaman mapel ini akan memiliki background batik soft.</p>
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
        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-8 md:p-12 max-w-lg w-full shadow-2xl border border-blue-100 dark:border-blue-900/20 animate__animated animate__zoomIn animate__faster max-h-[90vh] overflow-y-auto custom-scrollbar">
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
                <div class="space-y-4">
                    <label class="block text-xs font-black text-slate-400 uppercase tracking-widest">Batik Background (Ganti)</label>
                    <div class="p-6 bg-slate-50 dark:bg-[#060b1d] rounded-2xl border-2 border-dashed border-slate-100 dark:border-blue-900/10">
                        <input type="file" name="batik_bg" accept="image/*" class="w-full text-xs text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <label class="group flex items-center justify-between p-4 bg-red-50 dark:bg-red-900/10 rounded-2xl cursor-pointer hover:bg-red-100 transition-colors border border-red-100 dark:border-red-900/20">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-trash-alt text-red-500"></i>
                            <span class="text-[10px] font-black text-red-600 uppercase tracking-widest">Hapus Background Batik</span>
                        </div>
                        <input type="checkbox" name="remove_batik" value="1" class="w-5 h-5 rounded-lg border-red-200 text-red-600 focus:ring-red-500">
                    </label>
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
            if (status === 'reset_success') showModernAlert({ title: 'DIRESET!', text: 'Semua background batik telah dihapus.', icon: 'success' });
            if (status === 'error_duplicate') showModernAlert({ title: 'GAGAL!', text: 'Nama mata pelajaran sudah ada. Gunakan nama lain.', icon: 'error' });
        }

        function confirmResetAllBatik() {
            confirmModernAlert({
                title: 'Reset Semua?',
                html: 'Anda yakin ingin menghapus <strong>SEMUA</strong> background batik mapel?<br><span class="text-red-500 text-[10px] font-black uppercase tracking-widest mt-2 block">⚠️ Semua mapel akan kembali ke batik default.</span>',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'YA, RESET SEMUA'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="action" value="reset_all_batik">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>
