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
                    <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-2">Aktifitas Real-time</h2>
                    <p class="text-sm text-slate-400 font-medium">Monitoring log aktifitas belajar siswa secara instan.</p>
                </div>
                <span class="px-5 py-2 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-500/20 transition-transform hover:scale-105 cursor-default">
                    <?php echo count($activities); ?> LOG TERDETEKSI
                </span>
            </div>
            <div class="flex items-center space-x-3">
                <form method="POST" id="resetForm" class="hidden">
                    <input type="hidden" name="action" value="reset_activities">
                </form>
                <div id="realtimeClockContainer" class="hidden md:flex items-center space-x-3 px-5 py-2.5 bg-white dark:bg-[#0a1128] border border-blue-100 dark:border-blue-900/20 rounded-2xl shadow-sm tracking-widest">
                    <i class="far fa-clock text-blue-600 animate-pulse"></i>
                    <span id="clockDisplay" class="text-sm font-black text-slate-600 dark:text-slate-400 tabular-nums uppercase">00:00:00</span>
                </div>
                <button onclick="confirmReset()" class="px-5 py-2.5 bg-red-50 dark:bg-red-900/10 text-red-500 hover:bg-red-500 hover:text-white rounded-2xl text-[10px] font-black uppercase tracking-widest border border-red-100 dark:border-red-900/20 transition-all flex items-center space-x-2 shadow-sm whitespace-nowrap">
                    <i class="fas fa-trash-alt"></i>
                    <span class="hidden sm:inline">Reset Log</span>
                </button>
            </div>
        </div>

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

        <div class="flex-1 overflow-y-auto p-8 scroll-smooth">
            <div class="max-w-4xl mx-auto space-y-6">
                <?php foreach ($activities as $act): 
                    $isQuiz = $act['type'] === 'quiz';
                ?>
                <div class="bg-white dark:bg-[#0a1128] p-8 rounded-[2.5rem] shadow-sm border border-blue-50 dark:border-blue-900/30 flex items-center justify-between group hover:shadow-2xl hover:shadow-blue-500/10 transition-all duration-500 hover:-translate-y-1">
                    <div class="flex items-center space-x-6">
                        <div class="w-16 h-16 rounded-[1.5rem] <?php echo $isQuiz ? 'bg-amber-50 dark:bg-amber-900/20 text-amber-600' : 'bg-blue-50 dark:bg-blue-900/20 text-blue-600'; ?> flex items-center justify-center text-2xl shadow-inner group-hover:scale-110 duration-500 transition-transform">
                            <i class="fas <?php echo $isQuiz ? 'fa-certificate' : 'fa-book-reader'; ?>"></i>
                        </div>
                        <div>
                            <p class="text-lg font-black text-slate-800 dark:text-white mb-1"><?php echo htmlspecialchars($act['full_name']); ?></p>
                            <p class="text-sm text-slate-500 dark:text-blue-400/60 font-medium">
                                <?php echo $act['action']; ?>: 
                                <span class="font-black text-slate-900 dark:text-blue-400 ml-1 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-lg border border-blue-100 dark:border-blue-900/10">
                                    <?php echo htmlspecialchars($act['details']); ?><?php echo $isQuiz ? '%' : ''; ?>
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 dark:text-blue-400/40 uppercase tracking-[0.2em] mb-2 flex items-center justify-end">
                            <i class="far fa-calendar-alt mr-2 text-[8px]"></i>
                            <?php echo date('d M Y', strtotime($act['time_ref'])); ?>
                        </p>
                        <p class="text-2xl font-black text-slate-800 dark:text-white tabular-nums tracking-tighter"><?php echo date('H:i', strtotime($act['time_ref'])); ?></p>
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
