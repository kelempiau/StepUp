<?php
// src/admin/dashboard.php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Jakarta');
require_once '../../config/db.php';

// Check Admin Access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

// Fetch Admin Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$admin_data = $stmt->fetch();

// Fetch Stats
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'student'");
$total_students = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM progress WHERE is_completed = 1");
$total_completed = $stmt->fetchColumn();

// Rata-rata Skor Kuis
$stmt = $pdo->query("SELECT AVG(score) FROM quiz_scores");
$avg_quiz_score = round((float)$stmt->fetchColumn());

// Total Activities
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM (
            SELECT id FROM quiz_scores
            UNION ALL
            SELECT id FROM progress WHERE is_completed = 1
            UNION ALL
            SELECT id FROM final_exam_scores
        ) as all_activities
    ");
    $total_activities = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_activities = 0;
}

// Fetch New Students
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5");
$new_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch Admin Todos
$stmt = $pdo->prepare("SELECT * FROM todos WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$admin_todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/auth.js"></script>
    <style>
@media print { .print\:hidden { display: none !important; } body { overflow: auto !important; height: auto !important; } main { overflow: visible !important; } }
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

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        <!-- Background Elements -->
        <div class="absolute top-0 right-0 w-[500px] h-[500px] bg-blue-500/5 dark:bg-blue-500/10 rounded-full blur-[120px] -mr-64 -mt-64 pointer-events-none"></div>
        <div class="absolute bottom-0 left-0 w-[500px] h-[500px] bg-blue-500/5 dark:bg-blue-500/10 rounded-full blur-[120px] -ml-64 -mb-64 pointer-events-none"></div>
        
        <!-- Mobile Header -->
        <header class="md:hidden bg-white dark:bg-[#0a1128] border-b border-blue-50 dark:border-blue-900/20 p-5 flex justify-between items-center z-30">
            <span class="font-bold text-lg text-blue-600">AdminDashboard</span>
            <button onclick="toggleSidebar()" class="text-slate-500 p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-4 md:p-10 custom-scrollbar pb-32">
            <div class="max-w-7xl mx-auto space-y-10">
                
                <!-- Welcome Section -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight mb-3">Dashboard Admin</h2>
                        <p class="text-sm text-slate-400 font-medium italic">Halo, <?php echo htmlspecialchars($admin_data['full_name']); ?>. Selamat mengelola platform StepUp!</p>
                    </div>
                    <button onclick="window.print()" aria-label="Cetak Laporan" class="px-6 py-3 bg-slate-900 text-white rounded-2xl text-xs uppercase font-black print:hidden shadow-lg shadow-slate-900/20 hover:scale-105 active:scale-95 transition-all">
                        <i class="fas fa-print mr-2"></i> Cetak Laporan
                    </button>
                </div>

                <!-- Main Dashboard Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left & Middle Column (Main Content) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl p-5 md:p-8 rounded-[2.5rem] border border-blue-50/50 dark:border-blue-900/20 shadow-sm transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/10 hover:-translate-y-1 group">
                                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-800 text-white rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-blue-500/20 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-users text-2xl"></i>
                                </div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-2">Total Siswa</p>
                                <h4 class="text-4xl font-black text-slate-900 dark:text-white"><?php echo number_format($total_students); ?></h4>
                                <div class="mt-4 flex items-center text-[10px] font-bold text-green-500 bg-green-500/10 w-fit px-2 py-1 rounded-lg">
                                    <i class="fas fa-arrow-up mr-1"></i> +12% MoM
                                </div>
                            </div>
                            <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl p-5 md:p-8 rounded-[2.5rem] border border-blue-50/50 dark:border-blue-900/20 shadow-sm transition-all duration-500 hover:shadow-2xl hover:shadow-green-500/10 hover:-translate-y-1 group">
                                <div class="w-14 h-14 bg-gradient-to-br from-green-500 to-emerald-600 dark:from-green-600 dark:to-emerald-800 text-white rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-green-500/20 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-check-circle text-2xl"></i>
                                </div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-2">Materi Selesai</p>
                                <h4 class="text-4xl font-black text-slate-900 dark:text-white"><?php echo number_format($total_completed); ?></h4>
                                <div class="mt-4 flex items-center text-[10px] font-bold text-blue-500 bg-blue-500/10 w-fit px-2 py-1 rounded-lg">
                                    <i class="fas fa-chart-line mr-1"></i> Target tercapai
                                </div>
                            </div>
                            <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl p-5 md:p-8 rounded-[2.5rem] border border-blue-50/50 dark:border-blue-900/20 shadow-sm transition-all duration-500 hover:shadow-2xl hover:shadow-blue-500/10 hover:-translate-y-1 group">
                                <div class="w-14 h-14 bg-gradient-to-br from-blue-500 to-blue-700 dark:from-blue-600 dark:to-blue-800 text-white rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-blue-500/20 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-star text-2xl"></i>
                                </div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-2">Rata Skor Kuis</p>
                                <h4 class="text-4xl font-black text-slate-900 dark:text-white"><?php echo $avg_quiz_score; ?>/100</h4>
                                <div class="mt-4 flex items-center text-[10px] font-bold text-blue-500 bg-blue-500/10 w-fit px-2 py-1 rounded-lg">
                                    <i class="fas fa-award mr-1"></i> Kualitas Unggul
                                </div>
                            </div>
                            <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl p-5 md:p-8 rounded-[2.5rem] border border-blue-50/50 dark:border-blue-900/20 shadow-sm transition-all duration-500 hover:shadow-2xl hover:shadow-orange-500/10 hover:-translate-y-1 group">
                                <div class="w-14 h-14 bg-gradient-to-br from-orange-500 to-amber-600 dark:from-orange-600 dark:to-amber-800 text-white rounded-2xl flex items-center justify-center mb-6 shadow-lg shadow-orange-500/20 group-hover:scale-110 transition-transform">
                                    <i class="fas fa-bolt text-2xl"></i>
                                </div>
                                <p class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-2">Total Aktivitas</p>
                                <h4 class="text-4xl font-black text-slate-900 dark:text-white"><?php echo number_format($total_activities); ?></h4>
                                <div class="mt-4 flex items-center text-[10px] font-bold text-orange-500 bg-orange-500/10 w-fit px-2 py-1 rounded-lg">
                                    <i class="fas fa-fire mr-1"></i> Sangat Aktif
                                </div>
                            </div>
                        </div>

                        <!-- Siswa Terbaru -->
                        <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl rounded-[3rem] p-5 md:p-10 border border-blue-50/50 dark:border-blue-900/20 shadow-sm">
                            <div class="flex items-center justify-between mb-8">
                                <h3 class="text-[10px] font-black text-slate-400 dark:text-slate-500 tracking-[0.3em] uppercase">Siswa Terbaru</h3>
                                <a href="students.php" class="text-[10px] font-black text-blue-600 hover:text-blue-700 transition-colors uppercase tracking-widest">Lihat Semua</a>
                            </div>
                            <div class="space-y-4">
                                <?php foreach($new_students as $student): ?>
                                    <div class="flex items-center justify-between p-4 hover:bg-white dark:hover:bg-blue-900/20 rounded-3xl transition duration-300 border border-transparent hover:border-blue-100 dark:hover:border-blue-800/30 hover:shadow-xl hover:shadow-blue-500/5 group">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-12 h-12 bg-blue-100/50 dark:bg-blue-900/30 text-blue-600 rounded-2xl flex items-center justify-center font-black overflow-hidden group-hover:scale-105 transition-transform">
                                                <?php if ($student['profile_pic'] && file_exists('../../uploads/profile_pics/' . $student['profile_pic'])): ?>
                                                    <img src="../../uploads/profile_pics/<?php echo htmlspecialchars($student['profile_pic']); ?>" alt="Profile" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-800 dark:text-white"><?php echo htmlspecialchars($student['full_name']); ?></p>
                                                <div class="flex items-center text-[10px] text-slate-400 space-x-2">
                                                    <i class="far fa-calendar-alt"></i>
                                                    <span>Bergabung <?php echo date('d M Y', strtotime($student['created_at'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <span class="px-3 py-1.5 bg-green-500/10 text-green-500 rounded-xl text-[10px] font-black tracking-tighter">STUDENT</span>
                                            <button class="w-8 h-8 rounded-lg bg-slate-50 dark:bg-blue-900/40 text-slate-400 hover:text-blue-600 transition flex items-center justify-center opacity-0 group-hover:opacity-100">
                                                <i class="fas fa-chevron-right text-[10px]"></i>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Quick Actions / Tips -->
                        <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-[3rem] p-5 md:p-10 text-white relative overflow-hidden shadow-2xl">
                            <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-10 rounded-full -mr-20 -mt-20 blur-3xl"></div>
                            <h3 class="text-xl font-black mb-8 tracking-tight uppercase tracking-widest text-xs">Informasi Cepat</h3>
                            <div class="space-y-6 relative z-10">
                                <div class="flex items-center gap-4 bg-white/10 p-5 rounded-2xl backdrop-blur-md">
                                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0"><i class="fas fa-info-circle"></i></div>
                                    <p class="text-xs font-bold leading-relaxed opacity-90">Gunakan menu <b>Siswa</b> untuk melihat detail progress belajar setiap murid secara real-time.</p>
                                </div>
                                <div class="flex items-center gap-4 bg-white/10 p-5 rounded-2xl backdrop-blur-md">
                                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center shrink-0"><i class="fas fa-magic"></i></div>
                                    <p class="text-xs font-bold leading-relaxed opacity-90">Ingin menambah materi? Buka menu <b>Materi</b> lalu pilih sub-menu <b>Modul</b> untuk upload video.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column (Widgets) -->
                    <div class="space-y-8">
                        <!-- Big Calendar Widget -->
                        <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl rounded-[3rem] p-5 md:p-8 border border-blue-50/50 dark:border-blue-900/20 shadow-sm relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/10 rounded-full -mr-16 -mt-16 blur-2xl"></div>
                            <div class="flex flex-col gap-6 mb-8 relative z-10">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-[10px] font-black text-slate-400 dark:text-slate-500 tracking-[0.3em] uppercase">Kalender Kerja</h3>
                                    <div class="flex items-center space-x-2 bg-slate-100/50 dark:bg-blue-900/40 p-1 rounded-xl shadow-inner">
                                        <button onclick="changeMonth(-1)" class="w-7 h-7 flex items-center justify-center rounded-lg bg-white dark:bg-blue-600 text-slate-400 dark:text-white hover:text-blue-600 transition shadow-sm active:scale-90"><i class="fas fa-chevron-left text-[10px]"></i></button>
                                        <span id="currentMonthYear" class="text-[9px] font-black text-slate-600 dark:text-blue-100 uppercase tracking-widest px-2 min-w-[90px] text-center">Mei 2026</span>
                                        <button onclick="changeMonth(1)" class="w-7 h-7 flex items-center justify-center rounded-lg bg-white dark:bg-blue-600 text-slate-400 dark:text-white hover:text-blue-600 transition shadow-sm active:scale-90"><i class="fas fa-chevron-right text-[10px]"></i></button>
                                    </div>
                                </div>
                                <div id="calendarGrid" class="grid grid-cols-7 gap-2 text-center font-black text-[9px] relative z-10 w-full mb-2 text-slate-400 dark:text-slate-600 uppercase tracking-tighter">
                                    <div class="py-2 text-rose-500/70">Mng</div>
                                    <div class="py-2">S</div>
                                    <div class="py-2">S</div>
                                    <div class="py-2">R</div>
                                    <div class="py-2">K</div>
                                    <div class="py-2">J</div>
                                    <div class="py-2">S</div>
                                </div>
                                <div id="calendarDays" class="grid grid-cols-7 gap-2 text-center text-xs font-bold relative z-10 w-full">
                                    <!-- Days will be injected by JS -->
                                </div>
                            </div>
                        </div>

                        <!-- Todolist Widget -->
                        <div class="bg-white/70 dark:bg-[#0a1128]/70 backdrop-blur-xl rounded-[3rem] p-5 md:p-8 border border-blue-50/50 dark:border-blue-900/20 shadow-sm relative overflow-hidden">
                            <div class="absolute bottom-0 left-0 w-32 h-32 bg-blue-500/5 rounded-full -ml-16 -mb-16 blur-2xl"></div>
                            <div class="flex justify-between items-center mb-8 relative z-10">
                                <h3 class="text-[10px] font-black text-slate-400 dark:text-slate-500 tracking-[0.3em] uppercase">Rencana Kerja</h3>
                                <button onclick="openAddTodo()" class="w-9 h-9 bg-blue-600 text-white rounded-xl shadow-lg flex items-center justify-center hover:bg-blue-700 transition transform active:scale-95 shadow-blue-500/20 group">
                                    <i class="fas fa-plus text-xs group-hover:rotate-90 transition-transform"></i>
                                </button>
                            </div>
                            <div class="space-y-3 max-h-[350px] overflow-y-auto pr-1 custom-scrollbar relative z-10" id="todoList">
                                <?php if (empty($admin_todos)): ?>
                                    <div class="text-center py-10 opacity-30 text-[10px] border-2 border-dashed border-blue-50 dark:border-blue-900/10 rounded-[2rem]">
                                        <p class="font-black uppercase tracking-widest italic">Belum ada tugas.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($admin_todos as $todo): ?>
                                        <div class="flex items-center space-x-3 p-4 rounded-2xl bg-white/50 dark:bg-blue-900/10 border border-slate-100/50 dark:border-transparent group transition hover:border-blue-200 dark:hover:border-blue-800/50" id="todo-<?php echo $todo['id']; ?>">
                                            <button onclick="toggleTodo(<?php echo $todo['id']; ?>)" class="w-5 h-5 rounded-lg border-2 <?php echo $todo['is_completed'] ? 'bg-blue-600 border-blue-600 text-white shadow-lg shadow-blue-500/30' : 'border-slate-200 dark:border-blue-900/40'; ?> flex items-center justify-center transition flex-shrink-0">
                                                <?php if ($todo['is_completed']): ?><i class="fas fa-check text-[8px]"></i><?php endif; ?>
                                            </button>
                                            <span class="flex-1 text-[11px] font-bold <?php echo $todo['is_completed'] ? 'text-slate-400 line-through' : 'text-slate-700 dark:text-slate-200'; ?> leading-tight"><?php echo htmlspecialchars($todo['task']); ?></span>
                                            <button onclick="deleteTodo(<?php echo $todo['id']; ?>)" class="text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition flex-shrink-0">
                                                <i class="fas fa-trash-alt text-[10px]"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        // --- NEW CALENDAR LOGIC ---
        let currentMonth = new Date().getMonth();
        let currentYear = new Date().getFullYear();

        const monthNames = ["Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember"
        ];

        function populateCalendar(month, year) {
            const calendarDays = document.getElementById('calendarDays');
            if (!calendarDays) return;
            
            calendarDays.innerHTML = '';
            document.getElementById('currentMonthYear').textContent = `${monthNames[month]} ${year}`;

            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();

            // Padding for empty days at start
            for (let i = 0; i < firstDay; i++) {
                calendarDays.innerHTML += '<div></div>';
            }

            for (let day = 1; day <= daysInMonth; day++) {
                const isToday = today.getDate() === day && today.getMonth() === month && today.getFullYear() === year;
                calendarDays.innerHTML += `
                    <div class="py-3 rounded-xl transition-all ${isToday ? 'bg-blue-600 text-white shadow-lg shadow-blue-500/30 font-black' : 'hover:bg-blue-50 dark:hover:bg-blue-900/40 text-slate-600 dark:text-slate-300'}">
                        ${day}
                    </div>
                `;
            }
        }

        function changeMonth(delta) {
            currentMonth += delta;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            } else if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            populateCalendar(currentMonth, currentYear);
        }

        // --- NEW TODOLIST LOGIC ---
        async function openAddTodo() {
            const { value: task } = await Swal.fire({
                title: 'TUGAS BARU',
                input: 'text',
                inputPlaceholder: 'Apa yang harus diselesaikan admin?',
                showCancelButton: true,
                confirmButtonColor: '#2563eb'
            });

            if (task) {
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('task', task);
                
                const res = await fetch('../api/todo.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) location.reload();
            }
        }

        async function toggleTodo(id) {
            const formData = new FormData();
            formData.append('action', 'toggle');
            formData.append('id', id);
            
            const res = await fetch('../api/todo.php', { method: 'POST', body: formData });
            const data = await res.json();
            if (data.success) {
                const todoEl = document.querySelector(`#todo-${id} span`);
                const checkbox = document.querySelector(`#todo-${id} button`);
                if (todoEl.classList.contains('line-through')) {
                    todoEl.classList.remove('line-through', 'text-slate-400', 'font-medium');
                    todoEl.classList.add('text-slate-700', 'dark:text-slate-200');
                    checkbox.classList.remove('bg-blue-600', 'border-blue-600', 'text-white');
                    checkbox.classList.add('border-slate-200', 'dark:border-blue-900/40');
                    checkbox.innerHTML = '';
                } else {
                    todoEl.classList.add('line-through', 'text-slate-400', 'font-medium');
                    todoEl.classList.remove('text-slate-700', 'dark:text-slate-200');
                    checkbox.classList.add('bg-blue-600', 'border-blue-600', 'text-white');
                    checkbox.classList.remove('border-slate-200', 'dark:border-blue-900/40');
                    checkbox.innerHTML = '<i class="fas fa-check text-[10px]"></i>';
                }
            }
        }

        async function deleteTodo(id) {
            const result = await Swal.fire({
                title: 'Hapus Tugas?',
                text: "Data tidak bisa dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                confirmButtonText: 'YA, HAPUS'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);
                const res = await fetch('../api/todo.php', { method: 'POST', body: formData });
                const data = await res.json();
                if (data.success) document.getElementById(`todo-${id}`).remove();
            }
        }

        // Initialize components
        document.addEventListener('DOMContentLoaded', () => {
            populateCalendar(currentMonth, currentYear);
        });
    </script>
    <?php include_once __DIR__ . '/../inc_chat_button.php'; ?>
<?php include_once __DIR__ . '/../inc_chat_window.php'; ?>
</body>
</html>
