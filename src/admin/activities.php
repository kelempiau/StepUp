<?php
// src/admin/activities.php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
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

// Handle Reset Activities
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_activities') {
    try {
        $pdo->exec("TRUNCATE TABLE quiz_scores");
        $pdo->exec("TRUNCATE TABLE progress");
        $success_msg = "Semua aktifitas dan progres siswa berhasil direset.";
    } catch (PDOException $e) {
        $error_msg = "Gagal mereset aktifitas: " . $e->getMessage();
    }
}

// Fetch All Activities
try {
    $stmt = $pdo->query("
        (SELECT u.full_name, 'Menyelesaikan Kuis' as action, CAST(q.score AS CHAR) as details, q.created_at as time_ref, 'quiz' as type
        FROM quiz_scores q
        JOIN users u ON q.user_id = u.id)
        UNION ALL
        (SELECT u.full_name, 'Membaca Modul' as action, CAST(m.title AS CHAR) as details, COALESCE(p.created_at, NOW()) as time_ref, 'progress' as type
        FROM progress p
        JOIN users u ON p.user_id = u.id
        JOIN modules m ON p.module_slug = m.slug)
        UNION ALL
        (SELECT u.full_name, 'Ujian Akhir' as action, CONCAT(e.subject_slug, ' (', e.score, '%)') as details, e.created_at as time_ref, 'exam' as type
        FROM final_exam_scores e
        JOIN users u ON e.user_id = u.id)
        ORDER BY time_ref DESC
    ");
    $activities = $stmt->fetchAll();
} catch (PDOException $e) {
    $activities = [];
    $error_msg = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktifitas Real-time - StepUp</title>
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
                    <span class="px-3 py-1 bg-blue-600 text-[10px] font-black text-white rounded-lg uppercase tracking-widest">Logs</span>
                    <span class="text-slate-400 dark:text-slate-500 text-[10px] font-black uppercase tracking-widest italic">/ Real-time Activities</span>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-1">Aktifitas Real-time</h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-xs italic">Pantau interaksi dan progres belajar secara langsung.</p>
            </div>
            <div class="flex items-center space-x-4 mt-6 md:mt-0">
                <form method="POST" id="resetForm" class="hidden">
                    <input type="hidden" name="action" value="reset_activities">
                </form>
                <div class="bg-blue-600/10 dark:bg-blue-600/20 p-2 rounded-2xl border border-blue-600/20">
                    <button onclick="confirmReset()" class="bg-red-600 text-white px-5 py-2.5 rounded-xl flex items-center space-x-2 shadow-lg shadow-red-500/20 hover:bg-red-700 transition transform active:scale-95">
                        <i class="fas fa-trash-alt text-xs"></i>
                        <span class="text-[10px] font-black uppercase tracking-widest">RESET LOGS</span>
                    </button>
                </div>
            </div>
        </header>

        <?php if(isset($success_msg)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showModernAlert({
                    title: 'Berhasil',
                    text: '<?php echo $success_msg; ?>',
                    timer: 2000,
                    showConfirmButton: false
                });
            });
        </script>
        <?php endif; ?>

        <?php if(isset($error_msg)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                showModernAlert({
                    icon: 'error',
                    title: 'Gagal',
                    text: '<?php echo $error_msg; ?>'
                });
            });
        </script>
        <?php endif; ?>

        <script>
        function confirmReset() {
            confirmModernAlert({
                title: 'Hapus Semua Aktifitas?',
                text: "Tindakan ini akan menghapus SEMUA log aktifitas, nilai kuis, dan progres belajar siswa secara permanen.",
                icon: 'warning',
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'Ya, Reset Semua',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('resetForm').submit();
                }
            })
        }
        </script>

        <div class="flex-1 overflow-y-auto p-8 custom-scrollbar scroll-smooth">
            <div class="max-w-5xl mx-auto space-y-4 pb-32">
                <?php foreach ($activities as $act):
                    $isQuiz = $act['type'] === 'quiz';
                    $isExam = $act['type'] === 'exam';
                    $colorClass = $isQuiz ? 'amber' : ($isExam ? 'blue' : 'sky');
                ?>
                <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl p-6 rounded-[2.5rem] shadow-sm border border-blue-50/50 dark:border-blue-900/20 flex items-center justify-between group hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-700 hover:-translate-y-1">
                    <div class="flex items-center space-x-6">
                        <div class="w-14 h-14 rounded-2xl bg-<?php echo $colorClass; ?>-500/10 text-<?php echo $colorClass; ?>-600 flex items-center justify-center text-xl shadow-inner group-hover:scale-110 group-hover:rotate-6 transition-all duration-500 border border-<?php echo $colorClass; ?>-500/20">
                            <i class="fas <?php echo $isQuiz ? 'fa-bolt' : ($isExam ? 'fa-award' : 'fa-play-circle'); ?>"></i>
                        </div>
                        <div>
                            <div class="flex items-center space-x-3 mb-1">
                                <p class="text-base font-black text-slate-900 dark:text-white tracking-tight"><?php echo htmlspecialchars($act['full_name']); ?></p>
                                <span class="px-2 py-0.5 bg-<?php echo $colorClass; ?>-500/10 text-<?php echo $colorClass; ?>-600 rounded text-[8px] font-black uppercase tracking-widest border border-<?php echo $colorClass; ?>-500/20"><?php echo strtoupper($act['type']); ?></span>
                            </div>
                            <p class="text-xs text-slate-500 dark:text-slate-400 font-bold italic">
                                <?php echo $act['action']; ?>:
                                <span class="font-black text-slate-900 dark:text-blue-400 not-italic ml-1">
                                    <?php echo htmlspecialchars($act['details']); ?><?php echo $isQuiz ? '%' : ''; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="flex items-center justify-end space-x-2 text-slate-400 dark:text-slate-500 mb-1">
                            <i class="far fa-clock text-[10px]"></i>
                            <span class="text-[9px] font-black uppercase tracking-widest"><?php echo date('H:i', strtotime($act['time_ref'])); ?></span>
                        </div>
                        <p class="text-[10px] font-black text-slate-900 dark:text-white tabular-nums tracking-widest uppercase">
                            <?php echo date('d M Y', strtotime($act['time_ref'])); ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if(empty($activities)): ?>
                <div class="text-center py-20 bg-white dark:bg-[#0a1128] rounded-[3rem] border-2 border-dashed border-blue-100 dark:border-blue-900/20">
                    <div class="w-20 h-20 bg-blue-50 dark:bg-blue-900/20 rounded-full flex items-center justify-center text-blue-400 mx-auto mb-6">
                        <i class="fas fa-history text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-black text-slate-400 dark:text-slate-600">Belum ada aktifitas</h3>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script>
        function updateClock() {
            const clockEl = document.getElementById('clockDisplay');
            if (!clockEl) return;
            const now = new Date();
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false }).replace(/\./g, ':');
            clockEl.innerText = timeStr;
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
