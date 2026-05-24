<?php
// src/admin/students.php
session_start();
require_once '../../config/db.php';

// Check Admin Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Delete Student
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = $_POST['id'];
    
    // Get profile pic to delete file
    $stmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if ($user && $user['profile_pic']) {
        $file_path = '../../uploads/profile_pics/' . $user['profile_pic'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: students.php?status=deleted");
    exit;
}

// Reset Student Progress
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_student') {
    $id = $_POST['id'];
    try {
        $pdo->beginTransaction();
        $pdo->prepare("DELETE FROM quiz_scores WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM progress WHERE user_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM user_challenges WHERE user_id = ?")->execute([$id]);
        
        // Reset points and level
        $pdo->prepare("UPDATE users SET total_points = 0, current_level = 1 WHERE id = ?")->execute([$id]);
        
        // Send notification to inbox
        $pdo->prepare("INSERT INTO inbox (user_id, title, content, type, is_read, created_at) VALUES (?, ?, ?, ?, 0, NOW())")
            ->execute([$id, 'Akun Anda Telah Direset', 'Admin telah mereset progres belajar, poin, dan level akun Anda.', 'system']);
            
        $pdo->commit();
        header("Location: students.php?status=reset");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
        exit;
    }
}

// Fetch Students
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC");
$students = $stmt->fetchAll();

// Fetch Admin Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_data = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Siswa - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/auth.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 dark:bg-[#020617] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-500/5 dark:bg-blue-500/10 rounded-full blur-[120px] -mr-64 -mt-64 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-blue-500/5 dark:bg-blue-500/10 rounded-full blur-[120px] -ml-64 -mb-64 pointer-events-none"></div>
        
        <!-- Header -->
        <header class="p-8 pb-4 flex flex-col md:flex-row justify-between items-start md:items-center bg-white/30 dark:bg-[#020617]/30 backdrop-blur-xl border-b border-blue-50/50 dark:border-blue-900/20 z-10">
            <div>
                <div class="flex items-center space-x-3 mb-2">
                    <span class="px-3 py-1 bg-blue-600 text-[10px] font-black text-white rounded-lg uppercase tracking-widest">System</span>
                    <span class="text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-widest italic">/ Students Management</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1">Kelola Siswa</h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-xs italic">Direktori utama seluruh murid StepUp LMS.</p>
            </div>
            <div class="flex items-center space-x-4 mt-6 md:mt-0">
                <!-- Clock -->
                <div class="hidden lg:flex bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-md px-6 py-3 rounded-2xl shadow-sm border border-blue-50/50 dark:border-blue-900/30 items-center space-x-3 group hover:border-blue-400/50 transition-colors">
                    <i class="far fa-clock text-blue-600 animate-pulse"></i>
                    <span id="realtimeClock" class="text-xl font-black text-slate-800 dark:text-white tabular-nums tracking-tighter">00:00:00</span>
                </div>
                <div class="bg-blue-600/10 dark:bg-blue-600/20 p-2 rounded-2xl border border-blue-600/20">
                    <div class="bg-blue-600 text-white px-4 py-2 rounded-xl flex items-center space-x-2 shadow-lg shadow-blue-500/20">
                        <i class="fas fa-users-cog text-xs"></i>
                        <span class="text-[10px] font-black uppercase tracking-widest">Database</span>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth custom-scrollbar">
            <div class="max-w-7xl mx-auto space-y-8">
                <!-- Toolbar -->
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white/50 dark:bg-[#0a1128]/50 backdrop-blur-xl p-4 rounded-[2rem] border border-blue-50/50 dark:border-blue-900/20">
                    <div class="relative w-full md:w-96 group">
                        <i class="fas fa-search absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors"></i>
                        <input type="text" id="studentSearch" placeholder="Cari nama, username atau email..."
                            class="w-full bg-white dark:bg-[#020617] pl-12 pr-6 py-4 rounded-2xl border-none ring-1 ring-blue-100 dark:ring-blue-900/30 focus:ring-2 focus:ring-blue-500 outline-none transition-all text-sm font-bold">
                    </div>
                    <div class="flex items-center space-x-2">
                        <button class="px-6 py-4 bg-white dark:bg-[#0a1128] rounded-2xl text-[10px] font-black uppercase tracking-widest text-slate-500 border border-blue-50 dark:border-blue-900/30 hover:bg-blue-50 transition-all flex items-center">
                            <i class="fas fa-filter mr-2"></i> Filter
                        </button>
                        <button class="px-6 py-4 bg-slate-900 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-slate-900/20 hover:scale-105 active:scale-95 transition-all flex items-center">
                            <i class="fas fa-download mr-2"></i> Export CSV
                        </button>
                    </div>
                </div>

                <!-- Table Card -->
                <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl rounded-[3rem] shadow-2xl shadow-blue-500/5 border border-blue-50/50 dark:border-blue-900/20 overflow-hidden">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="text-slate-400 dark:text-blue-400/50 text-[10px] font-black uppercase tracking-[0.2em] border-b border-blue-50 dark:border-blue-900/10">
                                    <th class="p-8">Data Siswa</th>
                                    <th class="p-8">Kontak & Keamanan</th>
                                    <th class="p-8 text-center">Status & Join</th>
                                    <th class="p-8 text-right">Manajemen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-50/50 dark:divide-blue-900/10" id="studentTableBody">
                                <?php foreach ($students as $s): ?>
                                <tr class="hover:bg-blue-50/30 dark:hover:bg-blue-900/10 transition duration-500 group student-row"
                                    data-search="<?php echo htmlspecialchars(strtolower($s['full_name'] . ' ' . $s['username'] . ' ' . $s['email'])); ?>">
                                    <td class="p-8">
                                        <div class="flex items-center space-x-5">
                                            <div class="relative">
                                                <div class="w-16 h-16 rounded-[1.5rem] bg-gradient-to-tr from-blue-500 to-blue-700 p-[2px] shadow-lg shadow-blue-500/20 group-hover:rotate-6 transition-transform">
                                                    <div class="w-full h-full rounded-[1.4rem] bg-white dark:bg-[#0a1128] overflow-hidden">
                                                        <?php if ($s['profile_pic'] && file_exists('../../uploads/profile_pics/' . $s['profile_pic'])): ?>
                                                            <img src="../../uploads/profile_pics/<?php echo htmlspecialchars($s['profile_pic']); ?>" alt="Profile" class="w-full h-full object-cover">
                                                        <?php else: ?>
                                                            <div class="w-full h-full flex items-center justify-center font-black text-2xl text-blue-600 bg-blue-50 dark:bg-blue-900/20">
                                                                <?php echo strtoupper(substr($s['full_name'], 0, 1)); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-green-500 border-4 border-white dark:border-[#0a1128] rounded-full"></div>
                                            </div>
                                            <div>
                                                <p class="font-black text-slate-900 dark:text-white text-lg tracking-tight mb-0.5"><?php echo htmlspecialchars($s['full_name']); ?></p>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-[9px] font-black text-blue-600 bg-blue-500/10 px-2 py-0.5 rounded-md uppercase tracking-widest">ID: #<?php echo str_pad($s['id'], 4, '0', STR_PAD_LEFT); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-8">
                                        <div class="space-y-2">
                                            <div class="flex items-center text-sm font-bold text-slate-700 dark:text-blue-300">
                                                <i class="fas fa-at w-5 text-blue-500/50"></i>
                                                <?php echo htmlspecialchars($s['username']); ?>
                                            </div>
                                            <div class="flex items-center text-xs font-medium text-slate-400 dark:text-slate-500 italic">
                                                <i class="fas fa-envelope w-5 text-slate-300"></i>
                                                <?php echo htmlspecialchars($s['email']); ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-8 text-center">
                                        <p class="text-sm font-black text-slate-700 dark:text-slate-300 mb-1"><?php echo date('d M Y', strtotime($s['created_at'])); ?></p>
                                        <span class="inline-flex items-center px-3 py-1 bg-emerald-500/10 text-emerald-500 rounded-full text-[9px] font-black uppercase tracking-widest border border-emerald-500/20">
                                            <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                                            Verified
                                        </span>
                                    </td>
                                    <td class="p-8 text-right">
                                        <div class="flex items-center justify-end space-x-3">
                                            <button onclick="confirmResetStudent(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['full_name'], ENT_QUOTES); ?>')"
                                                class="w-12 h-12 bg-amber-500/10 text-amber-500 rounded-2xl hover:bg-amber-500 hover:text-white transition-all duration-300 flex items-center justify-center group/btn shadow-sm" title="Reset Progres Belajar">
                                                <i class="fas fa-sync-alt text-sm group-hover/btn:rotate-180 transition-transform duration-500"></i>
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $s['id']; ?>)"
                                                class="w-12 h-12 bg-rose-500/10 text-rose-500 rounded-2xl hover:bg-rose-500 hover:text-white transition-all duration-300 flex items-center justify-center group/btn shadow-sm" title="Hapus Siswa">
                                                <i class="fas fa-trash-alt text-sm group-hover/btn:scale-110 transition-transform"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function updateClock() {
            const clockEl = document.getElementById('realtimeClock');
            if (!clockEl) return;
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':');
            clockEl.innerText = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Search Filter
        const searchInput = document.getElementById('studentSearch');
        const tableRows = document.querySelectorAll('.student-row');

        searchInput.addEventListener('input', (e) => {
            const term = e.target.value.toLowerCase();
            tableRows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                if (searchData.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        function confirmResetStudent(id, name) {
            confirmModernAlert({
                title: 'Reset Progres?',
                html: `Anda akan mereset semua nilai dan progres belajar siswa <strong>${name}</strong>?`,
                icon: 'warning',
                confirmButtonColor: '#f59e0b',
                confirmButtonText: 'Ya, Reset',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="action" value="reset_student"><input type="hidden" name="id" value="${id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            })
        }

        function confirmDelete(id) {
            confirmModernAlert({
                title: 'Hapus Siswa?',
                text: "Data siswa dan riwayat belajarnya akan dihapus permanen.",
                icon: 'warning',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="${id}">`;
                    document.body.appendChild(form);
                    form.submit();
                }
            })
        }

        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status === 'deleted') {
            showModernAlert({
                title: 'Terhapus!',
                text: 'Data siswa berhasil dihapus.',
                timer: 1500,
                showConfirmButton: false
            });
        } else if (status === 'reset') {
             showModernAlert({
                title: 'Direset!',
                text: 'Progres belajar siswa berhasil direset.',
                timer: 1500,
                showConfirmButton: false
            });
        }
    </script>
</body>
</html>
