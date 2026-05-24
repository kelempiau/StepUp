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
<body class="bg-blue-50/50 text-slate-800 dark:bg-[#060b1d] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar (Blue Premium Style) -->
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
        
        <!-- Header -->
        <header class="p-8 pb-4 flex justify-between items-center bg-white/50 dark:bg-[#060b1d]/50 backdrop-blur-md">
            <div>
                <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-1">Kelola Siswa</h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Lihat dan kelola akun siswa yang terdaftar.</p>
            </div>
            <div class="flex items-center space-x-6">
                <!-- Clock -->
                <div class="bg-white dark:bg-[#0a1128] px-6 py-3 rounded-2xl shadow-sm border border-blue-50 dark:border-blue-900/30 flex items-center space-x-3 group hover:border-blue-400 transition-colors">
                    <i class="far fa-clock text-blue-600 animate-pulse"></i>
                    <span id="realtimeClock" class="text-xl font-black text-slate-800 dark:text-white tabular-nums tracking-tighter">00:00:00</span>
                </div>
                <!-- Profile Small -->
                <div class="flex items-center space-x-3 bg-blue-600 p-1.5 pr-4 rounded-2xl text-white shadow-xl shadow-blue-500/20">
                    <div class="w-8 h-8 rounded-xl bg-white text-blue-600 flex items-center justify-center font-black text-xs">A</div>
                    <span class="text-xs font-black uppercase tracking-widest">Admin Panel</span>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            <div class="max-w-6xl mx-auto">
                <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] shadow-sm border border-blue-50 dark:border-blue-900/30 overflow-hidden p-8">
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-blue-50 dark:bg-blue-900/20 text-slate-400 dark:text-blue-400/50 text-[10px] font-black uppercase tracking-widest">
                                    <th class="p-6 rounded-l-2xl">Data Siswa</th>
                                    <th class="p-6">Username & Email</th>
                                    <th class="p-6 text-center">Tanggal Bergabung</th>
                                    <th class="p-6 text-right rounded-r-2xl">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-blue-50 dark:divide-blue-900/10">
                                <?php foreach ($students as $s): ?>
                                <tr class="hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition duration-300 group">
                                    <td class="p-6">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-500 to-indigo-600 text-white flex items-center justify-center font-black text-xl shadow-lg shadow-blue-500/20 group-hover:scale-105 transition-transform overflow-hidden">
                                                <?php if ($s['profile_pic'] && file_exists('../../uploads/profile_pics/' . $s['profile_pic'])): ?>
                                                    <img src="../../uploads/profile_pics/<?php echo htmlspecialchars($s['profile_pic']); ?>" alt="Profile" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($s['full_name'], 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-800 dark:text-white"><?php echo htmlspecialchars($s['full_name']); ?></p>
                                                <span class="text-[10px] font-black text-green-500 bg-green-50 dark:bg-green-900/20 px-2.5 py-1 rounded-lg uppercase tracking-widest border border-green-100 dark:border-green-900/30">Active Student</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="p-6">
                                        <p class="font-bold text-slate-700 dark:text-blue-300 text-sm mb-1"><?php echo htmlspecialchars($s['username']); ?></p>
                                        <div class="flex items-center text-xs text-slate-400 dark:text-slate-500">
                                            <i class="far fa-envelope mr-1.5 text-[10px]"></i>
                                            <?php echo htmlspecialchars($s['email']); ?>
                                        </div>
                                    </td>
                                    <td class="p-6 text-center">
                                        <p class="text-sm font-bold text-slate-600 dark:text-slate-400"><?php echo date('d M Y', strtotime($s['created_at'])); ?></p>
                                        <p class="text-[10px] text-blue-500 dark:text-blue-400/40 font-black uppercase tracking-[0.2em] mt-1.5">Established</p>
                                    </td>
                                    <td class="p-6 text-right">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button onclick="confirmResetStudent(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['full_name'], ENT_QUOTES); ?>')" class="w-12 h-12 bg-amber-50 dark:bg-amber-900/20 text-amber-500 dark:text-amber-400 rounded-2xl hover:bg-amber-500 hover:text-white transition group shadow-sm flex items-center justify-center" title="Reset Progres Belajar">
                                                <i class="fas fa-history text-sm"></i>
                                            </button>
                                            <button onclick="confirmDelete(<?php echo $s['id']; ?>)" class="w-12 h-12 bg-red-50 dark:bg-red-900/20 text-red-500 dark:text-red-400 rounded-2xl hover:bg-red-600 hover:text-white transition group shadow-sm flex items-center justify-center" title="Hapus Siswa">
                                                <i class="fas fa-trash-alt text-sm"></i>
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
