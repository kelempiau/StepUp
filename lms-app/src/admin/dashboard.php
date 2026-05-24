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

// Real-time Exam Pass Rate
$stmt = $pdo->query("SELECT COUNT(*) FROM final_exam_scores WHERE score >= 60");
$passed_exams = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM final_exam_scores");
$total_exams = $stmt->fetchColumn();

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
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; }
    </style>
</head>
<body class="bg-blue-50/50 text-slate-800 dark:bg-[#060b1d] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        
        <!-- Mobile Header -->
        <header class="md:hidden bg-white dark:bg-[#0a1128] border-b border-blue-50 dark:border-blue-900/20 p-5 flex justify-between items-center z-30">
            <span class="font-bold text-lg text-blue-600">AdminDashboard</span>
            <button onclick="toggleSidebar()" class="text-slate-500 p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </header>

        <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar pb-32">
            <div class="max-w-7xl mx-auto space-y-10">
                
                <!-- Welcome Section -->
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">Dashboard Admin</h2>
                        <p class="text-sm text-slate-400 font-medium italic">Halo, <?php echo htmlspecialchars($admin_data['full_name']); ?>. Selamat mengelola platform StepUp!</p>
                    </div>
                </div>

                <!-- Main Dashboard Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left & Middle Column (Main Content) -->
                    <div class="lg:col-span-2 space-y-8">
                        <!-- Stats Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="bg-white dark:bg-[#0a1128] p-8 rounded-[2.5rem] border border-blue-50 dark:border-blue-900/20 shadow-sm transition-all duration-300 hover:shadow-xl hover:shadow-blue-500/5">
                                <div class="w-12 h-12 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded-2xl flex items-center justify-center mb-6">
                                    <i class="fas fa-users text-xl"></i>
                                </div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Siswa</p>
                                <h4 class="text-3xl font-black text-slate-800 dark:text-white"><?php echo $total_students; ?></h4>
                            </div>
                            <div class="bg-white dark:bg-[#0a1128] p-8 rounded-[2.5rem] border border-blue-50 dark:border-blue-900/20 shadow-sm transition-all duration-300 hover:shadow-xl hover:shadow-blue-500/5">
                                <div class="w-12 h-12 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-2xl flex items-center justify-center mb-6">
                                    <i class="fas fa-check-circle text-xl"></i>
                                </div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Materi Selesai</p>
                                <h4 class="text-3xl font-black text-slate-800 dark:text-white"><?php echo $total_completed; ?></h4>
                            </div>
                            <div class="bg-white dark:bg-[#0a1128] p-8 rounded-[2.5rem] border border-blue-50 dark:border-blue-900/20 shadow-sm transition-all duration-300 hover:shadow-xl hover:shadow-blue-500/5">
                                <div class="w-12 h-12 bg-purple-50 dark:bg-purple-900/20 text-purple-600 rounded-2xl flex items-center justify-center mb-6">
                                    <i class="fas fa-chart-line text-xl"></i>
                                </div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Tingkat Kelulusan</p>
                                <h4 class="text-3xl font-black text-slate-800 dark:text-white"><?php echo $total_exams > 0 ? round(($passed_exams / $total_exams) * 100) : 0; ?>%</h4>
                            </div>
                            <div class="bg-white dark:bg-[#0a1128] p-8 rounded-[2.5rem] border border-blue-50 dark:border-blue-900/20 shadow-sm transition-all duration-300 hover:shadow-xl hover:shadow-blue-500/5">
                                <div class="w-12 h-12 bg-orange-50 dark:bg-orange-900/20 text-orange-600 rounded-2xl flex items-center justify-center mb-6">
                                    <i class="fas fa-bolt text-xl"></i>
                                </div>
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Total Aktivitas</p>
                                <h4 class="text-3xl font-black text-slate-800 dark:text-white"><?php echo $total_activities; ?></h4>
                            </div>
                        </div>

                        <!-- Siswa Terbaru -->
                        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-10 border border-blue-50 dark:border-blue-900/20 shadow-sm">
                            <h3 class="text-xl font-black text-slate-800 dark:text-white mb-8 tracking-tight uppercase tracking-widest text-xs">Siswa Terbaru</h3>
                            <div class="space-y-6">
                                <?php foreach($new_students as $student): ?>
                                    <div class="flex items-center justify-between p-4 hover:bg-slate-50 dark:hover:bg-blue-900/10 rounded-2xl transition duration-300">
                                        <div class="flex items-center space-x-4">
                                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-xl flex items-center justify-center font-black overflow-hidden">
                                                <?php if ($student['profile_pic'] && file_exists('../../uploads/profile_pics/' . $student['profile_pic'])): ?>
                                                    <img src="../../uploads/profile_pics/<?php echo htmlspecialchars($student['profile_pic']); ?>" alt="Profile" class="w-full h-full object-cover">
                                                <?php else: ?>
                                                    <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                                <?php endif; ?>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-800 dark:text-white"><?php echo htmlspecialchars($student['full_name']); ?></p>
                                                <p class="text-[10px] text-slate-400"><?php echo date('d M Y', strtotime($student['created_at'])); ?></p>
                                            </div>
                                        </div>
                                        <span class="px-3 py-1 bg-green-50 dark:bg-green-900/20 text-green-600 rounded-lg text-[10px] font-black">ACTIVE</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Quick Actions / Tips (Replacement for System Status) -->
                        <div class="bg-gradient-to-br from-indigo-600 to-blue-700 rounded-[3rem] p-10 text-white relative overflow-hidden shadow-2xl">
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
                        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-8 border border-blue-50 dark:border-blue-900/20 shadow-sm relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-500/5 rounded-full -mr-16 -mt-16 blur-2xl font-black"></div>
                            <div class="flex flex-col gap-6 mb-8 relative z-10">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight uppercase tracking-widest text-xs">Kalender Kerja</h3>
                                    <div class="flex items-center space-x-2 bg-slate-50 dark:bg-blue-900/20 p-1.5 rounded-xl shadow-inner">
                                        <button onclick="changeMonth(-1)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-blue-600 text-slate-400 dark:text-white hover:text-blue-600 transition shadow-sm"><i class="fas fa-chevron-left text-[10px]"></i></button>
                                        <span id="currentMonthYear" class="text-[10px] font-black text-slate-600 dark:text-blue-100 uppercase tracking-widest px-2 min-w-[100px] text-center">Februari 2026</span>
                                        <button onclick="changeMonth(1)" class="w-8 h-8 flex items-center justify-center rounded-lg bg-white dark:bg-blue-600 text-slate-400 dark:text-white hover:text-blue-600 transition shadow-sm"><i class="fas fa-chevron-right text-[10px]"></i></button>
                                    </div>
                                </div>
                                <div id="calendarGrid" class="grid grid-cols-7 gap-2 text-center font-bold text-[10px] relative z-10 w-full mb-2 opacity-50">
                                    <div class="py-2 text-rose-500">M</div>
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
                        <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-8 border border-blue-50 dark:border-blue-900/20 shadow-sm">
                            <div class="flex justify-between items-center mb-8">
                                <h3 class="text-lg font-black text-slate-800 dark:text-white tracking-tight uppercase tracking-widest text-xs">Rencana Kerja</h3>
                                <button onclick="openAddTodo()" class="w-10 h-10 bg-blue-600 text-white rounded-xl shadow-lg flex items-center justify-center hover:bg-blue-700 transition transform active:scale-95 shadow-blue-500/20"><i class="fas fa-plus text-xs"></i></button>
                            </div>
                            <div class="space-y-4 max-h-[400px] overflow-y-auto pr-1 custom-scrollbar" id="todoList">
                                <?php if (empty($admin_todos)): ?>
                                    <div class="text-center py-10 opacity-30 text-xs text-center border-2 border-dashed border-blue-50 dark:border-blue-900/20 rounded-[2rem]">
                                        <p class="font-bold uppercase tracking-widest italic">Belum ada tugas admin.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($admin_todos as $todo): ?>
                                        <div class="flex items-center space-x-3 p-5 rounded-2xl bg-slate-50 dark:bg-blue-900/10 border border-slate-100 dark:border-transparent group transition hover:border-blue-200" id="todo-<?php echo $todo['id']; ?>">
                                            <button onclick="toggleTodo(<?php echo $todo['id']; ?>)" class="w-6 h-6 rounded-lg border-2 <?php echo $todo['is_completed'] ? 'bg-blue-600 border-blue-600 text-white' : 'border-slate-200 dark:border-blue-900/40'; ?> flex items-center justify-center transition flex-shrink-0">
                                                <?php if ($todo['is_completed']): ?><i class="fas fa-check text-[10px]"></i><?php endif; ?>
                                            </button>
                                            <span class="flex-1 text-xs font-bold <?php echo $todo['is_completed'] ? 'text-slate-400 line-through font-medium' : 'text-slate-700 dark:text-slate-200'; ?> leading-snug"><?php echo htmlspecialchars($todo['task']); ?></span>
                                            <button onclick="deleteTodo(<?php echo $todo['id']; ?>)" class="text-slate-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition flex-shrink-0"><i class="fas fa-trash-alt text-xs"></i></button>
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
            const { value: task } = await promptModernAlert({
                title: 'TUGAS BARU',
                inputPlaceholder: 'Apa yang harus diselesaikan admin?'
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
            const result = await confirmModernAlert({
                title: 'Hapus Tugas?',
                text: "Data tidak bisa dikembalikan!",
                icon: 'warning',
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
</body>
</html>
