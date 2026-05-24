<!-- src/views/components/inc_tab_home.php -->
<?php
/**
 * @var string $fn User full name
 * @var array $user User data
 * @var string $ini User initials
 * @var string $quote Daily quote
 * @var int $current_streak
 * @var int $total_progress
 * @var array $user_activities
 * @var array $subjects
 */
?>
<div id="tab-home" class="tp on">
    <!-- Header row: greeting + clock -->
    <div class="flex flex-col md:flex-row md:items-end justify-between mb-10 gap-6">
        <div>
            <h1 class="text-4xl md:text-5xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-3">Halo, <span class="text-blue-600"><?php echo htmlspecialchars(explode(' ', $fn)[0]) ?>!</span></h1>
            <div class="flex items-center gap-6">
                <div class="text-left">
                    <p class="text-2xl font-black tracking-tighter tabular-nums text-slate-900 dark:text-white" id="liveClock">00:00:00</p>
                    <p class="text-[9px] text-slate-400 font-black uppercase tracking-widest mt-1"><?php echo date('l, d F Y') ?></p>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <!-- Stats Badges (Keep in Top Bar as requested) -->
            <div class="flex items-center gap-3">
                <div onclick="showPointHistory()" class="flex items-center gap-3 px-5 py-3 bg-white dark:bg-slate-800 border-2 border-amber-100 dark:border-amber-900/30 rounded-[1.5rem] shadow-xl shadow-amber-500/5 cursor-pointer hover:scale-105 hover:border-amber-500 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center shadow-lg shadow-amber-500/20 group-hover:rotate-12 transition-transform">
                        <i class="fas fa-star text-sm"></i>
                    </div>
                    <div class="text-left">
                        <p class="text-[8px] font-black text-amber-600/60 uppercase tracking-[0.2em] leading-none mb-1">Poin</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white leading-none"><?php echo number_format($user['total_points'] ?? 0) ?></p>
                    </div>
                </div>
                <div onclick="showLevelProgress()" class="flex items-center gap-3 px-5 py-3 bg-white dark:bg-slate-800 border-2 border-blue-100 dark:border-blue-900/30 rounded-[1.5rem] shadow-xl shadow-blue-500/5 cursor-pointer hover:scale-105 hover:border-blue-500 transition-all group">
                    <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shadow-lg shadow-blue-500/20 group-hover:-rotate-12 transition-transform">
                        <i class="fas fa-medal text-sm"></i>
                    </div>
                    <div class="text-left">
                        <p class="text-[8px] font-black text-blue-600/60 uppercase tracking-[0.2em] leading-none mb-1">Level</p>
                        <p class="text-lg font-black text-slate-900 dark:text-white leading-none"><?php echo $user['current_level'] ?? 1 ?></p>
                    </div>
                </div>
            </div>
            <!-- Desktop Action Bar -->
            <div class="flex items-center gap-2">
                <button onclick="toggleDark()" class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-500 hover:text-blue-500 shadow-lg hover:shadow-blue-500/10 transition-all"><i class="fas fa-moon"></i></button>
                <div class="relative">
                    <button onclick="toggleInbox()" class="w-14 h-14 rounded-2xl bg-white dark:bg-slate-800 border-2 border-slate-100 dark:border-slate-700 flex items-center justify-center text-slate-500 hover:text-blue-500 shadow-lg hover:shadow-blue-500/10 transition-all relative group">
                        <i class="fas fa-envelope text-lg group-hover:scale-110 transition-transform"></i>
                        <?php if (($inbox_count ?? 0) > 0): ?>
                            <span class="absolute top-3 right-3 w-4 h-4 bg-red-500 border-2 border-white dark:border-slate-800 rounded-full flex items-center justify-center text-[8px] font-black text-white"><?php echo $inbox_count ?></span>
                        <?php endif; ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-[1fr_400px] gap-8 items-stretch">
        <!-- LEFT COLUMN: Calendar -->
        <div class="min-w-0 flex flex-col">
            <div class="frost cal-card rounded-[3.5rem] overflow-hidden border border-white/60 dark:border-slate-800 shadow-xl flex flex-col flex-1 bg-white/40 dark:bg-slate-900/40 backdrop-blur-2xl">
                <div class="p-8 pb-4">
                    <!-- Title row with nav arrows on the right -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter leading-none">Kalender Akademik</h3>
                            <p class="text-[10px] font-black text-blue-500 uppercase tracking-[0.2em] mt-2">Jadwal & Agenda Belajar</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button onclick="chM(-1)" class="w-10 h-10 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-blue-600 transition-all border border-slate-100 dark:border-slate-700 shadow-sm"><i class="fas fa-chevron-left text-xs"></i></button>
                            <button onclick="chM(1)" class="w-10 h-10 rounded-2xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-blue-600 transition-all border border-slate-100 dark:border-slate-700 shadow-sm"><i class="fas fa-chevron-right text-xs"></i></button>
                        </div>
                    </div>
                    <div class="text-center py-3 bg-blue-50 dark:bg-blue-900/20 rounded-2xl border border-blue-100 dark:border-blue-900/10 mb-6">
                        <span id="calLbl" class="font-black text-[12px] uppercase tracking-[0.3em] text-blue-600 dark:text-blue-400"></span>
                    </div>
                    
                    <div class="grid grid-cols-7 mb-4 text-center">
                        <?php foreach (['MG','SN','SL','RB','KM','JM','SB'] as $d): ?>
                            <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest"><?php echo $d ?></div>
                        <?php endforeach; ?>
                    </div>
                    <div id="calG" class="grid grid-cols-7 gap-2 min-h-[300px]"></div>

                    <!-- Add Agenda button below calendar grid -->
                    <button onclick="addAgenda()" class="w-full mt-6 py-3.5 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center gap-2 text-xs font-black uppercase tracking-widest border border-blue-100 dark:border-blue-800/50 transition-all active:scale-[0.98]">
                        <i class="fas fa-plus text-[10px]"></i> Tambah Agenda
                    </button>
                </div>
                
                <!-- Agenda list: scrollable, max-height -->
                <div class="px-8 pb-8 bg-slate-50/30 dark:bg-slate-900/30 border-t border-white/40 dark:border-slate-800/50">
                    <div id="agendaList" class="space-y-3 max-h-[200px] overflow-y-auto custom-scrollbar pr-1 mt-4"></div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Motivation + Stats -->
        <div class="space-y-6 flex flex-col">
            <!-- Motivation Card -->
            <!-- Daily Boost Card (Solid Blue) -->
            <div class="relative overflow-hidden rounded-[3rem] p-10 shadow-2xl flex flex-col justify-center min-h-[220px] group bg-gradient-to-br from-blue-50 via-blue-100/80 to-blue-200/60 dark:from-slate-800 dark:via-slate-800/90 dark:to-blue-900/40 border-2 border-blue-100/80 dark:border-blue-900/30">
                <div class="absolute top-[-20%] right-[-10%] w-64 h-64 bg-blue-200/30 dark:bg-blue-500/5 rounded-full blur-3xl group-hover:bg-blue-300/40 transition-all duration-700"></div>
                <div class="absolute bottom-[-20%] left-[-10%] w-48 h-48 bg-blue-300/20 dark:bg-blue-800/10 rounded-full blur-3xl"></div>
                
                <!-- Centered toga icon watermark -->
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <i class="fas fa-graduation-cap text-[8rem] text-blue-200/40 dark:text-blue-700/15 group-hover:scale-110 transition-transform duration-700"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-blue-500/15 dark:bg-blue-400/20 backdrop-blur-lg flex items-center justify-center text-blue-600 dark:text-blue-300 shadow-inner">
                            <i class="fas fa-quote-left text-sm"></i>
                        </div>
                        <span class="text-[10px] font-black text-blue-500 dark:text-blue-400 uppercase tracking-[0.3em] opacity-80">Daily Boost</span>
                    </div>
                    
                    <h2 class="text-2xl md:text-3xl font-black italic leading-tight tracking-tight text-blue-900 dark:text-blue-100 drop-shadow-sm">
                        "<?php echo htmlspecialchars($quote) ?>"
                    </h2>
                </div>
            </div>

            <div class="flex flex-col gap-6 flex-1">
                <!-- Streak Card -->
                <div onclick="showStreakDetails()" class="relative bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-[3rem] p-8 shadow-xl flex items-center justify-between group hover:-translate-y-1 transition-all overflow-hidden cursor-pointer">
                    <div class="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none group-hover:scale-110 transition-transform duration-1000">
                        <i class="fas fa-fire text-[8rem] text-orange-500"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Hari Streak</p>
                        <h4 class="text-5xl font-black text-slate-900 dark:text-white tracking-tighter"><?php echo $current_streak ?></h4>
                        <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Hari Bertahan</p>
                    </div>
                    <div class="relative z-10 w-16 h-16 bg-orange-100 dark:bg-orange-900/30 text-orange-500 rounded-2xl flex items-center justify-center text-2xl shadow-inner group-hover:scale-110 transition-transform">
                        <i class="fas fa-fire"></i>
                    </div>
                </div>

                <!-- Progress Card -->
                <div onclick="showProgressDetails()" class="relative bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-[3rem] p-8 shadow-xl flex items-center justify-between group hover:-translate-y-1 transition-all overflow-hidden cursor-pointer">
                    <div class="absolute inset-0 flex items-center justify-center opacity-10 pointer-events-none group-hover:scale-110 transition-transform duration-1000">
                        <i class="fas fa-chart-line text-[8rem] text-blue-500"></i>
                    </div>
                    <div class="relative z-10">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Penguasaan</p>
                        <h4 class="text-5xl font-black text-slate-900 dark:text-white tracking-tighter"><?php echo $total_progress ?><span class="text-2xl opacity-30">%</span></h4>
                        <p class="text-[10px] font-bold text-slate-400 uppercase mt-1">Materi Dikuasai</p>
                    </div>
                    <div class="relative z-10 w-16 h-16 bg-blue-100 dark:bg-blue-900/30 text-blue-500 rounded-2xl flex items-center justify-center text-2xl shadow-inner group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-line"></i>
                    </div>
                </div>

                <!-- Todolist Card -->
                <div class="bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 shadow-xl rounded-[3rem] p-8 relative overflow-hidden group flex-1 flex flex-col">
                    <div class="flex items-center justify-between mb-6 relative z-10">
                        <div>
                            <h3 class="font-black text-slate-900 dark:text-white text-lg tracking-tight">Tugas</h3>
                            <p class="text-[9px] text-slate-400 font-black uppercase tracking-widest">TARGET HARI INI</p>
                        </div>
                        <span class="px-4 py-1.5 bg-blue-600 text-white rounded-xl shadow-lg shadow-blue-500/20 text-[9px] font-black uppercase" id="todoCnt">0</span>
                    </div>
                    <form onsubmit="addTodo(event)" class="flex gap-3 mb-6 relative z-10">
                        <input id="todoInput" type="text" placeholder="Tugas baru..." class="flex-1 bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 rounded-xl px-5 py-3 text-xs font-bold outline-none focus:border-blue-500 transition-all">
                        <button type="submit" class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg active:scale-95 transition-all">
                            <i class="fas fa-plus text-sm"></i>
                        </button>
                    </form>
                    <div id="todoList" class="space-y-2 flex-1 overflow-y-auto custom-scrollbar pr-1 relative z-10 min-h-[200px]"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity History (Middle) -->
    <div class="mt-8 frost rounded-[4rem] border border-white/70 dark:border-slate-800 shadow-md p-10 bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl">
        <div class="mb-8 flex flex-col sm:flex-row sm:items-end justify-between gap-6">
            <div>
                <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-3">📚 Riwayat Belajar</h3>
                <p class="text-sm font-semibold text-slate-400">Jejak aktivitas dan pencapaian belajarmu.</p>
            </div>
        </div>
        <?php $top_activities = array_slice($user_activities, 0, 6); if (empty($top_activities)): ?>
            <div class="w-full p-16 bg-white/60 dark:bg-slate-800/30 rounded-[3.5rem] border-2 border-dashed border-slate-200 dark:border-slate-800 flex flex-col items-center justify-center gap-5 text-center">
                <div class="w-24 h-24 bg-white dark:bg-slate-800 rounded-[2.5rem] flex items-center justify-center shadow-2xl text-slate-200 dark:text-slate-700"><i class="fas fa-history text-4xl"></i></div>
                <div class="max-w-md"><h3 class="font-black text-slate-800 dark:text-white text-xl tracking-tight mb-3">Belum Ada Jejak Belajar</h3><p class="text-sm font-bold text-slate-400 leading-relaxed">Ayo mulai belajar sekarang untuk mencatat progres pertamamu!</p></div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($top_activities as $act): 
                    $isQuiz = $act['type'] === 'quiz'; 
                    $icon = $isQuiz ? 'fa-certificate' : 'fa-book-reader'; 
                    $color = $isQuiz ? 'amber' : 'blue'; 
                ?>
                    <div class="bg-white/90 dark:bg-slate-800/60 p-6 rounded-[2.5rem] shadow-sm border border-white dark:border-slate-700 flex flex-col justify-between hover:shadow-xl hover:-translate-y-2 transition-all duration-300 group">
                        <div class="w-14 h-14 rounded-2xl bg-<?php echo $color ?>-50 dark:bg-<?php echo $color ?>-900/20 text-<?php echo $color ?>-600 flex items-center justify-center text-2xl shadow-inner mb-6 group-hover:scale-110 transition-transform"><i class="fas <?php echo $icon ?>"></i></div>
                        <div>
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-[0.3em] mb-2"><?php echo $act['action'] ?></p>
                            <h4 class="font-black text-slate-900 dark:text-white text-base leading-tight line-clamp-2 mb-4 h-10"><?php echo htmlspecialchars($act['mod_name']) ?></h4>
                            <div class="flex items-center justify-between mt-auto pt-4 border-t border-slate-100 dark:border-slate-700/50">
                                <span class="text-[10px] font-black text-slate-400 uppercase"><?php echo date('d M Y', strtotime($act['time_ref'] ?? 'now')) ?></span>
                                <span class="text-xs font-black text-slate-900 dark:text-white tabular-nums"><?php echo date('H:i', strtotime($act['time_ref'] ?? 'now')) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Subjects Grid (Moved to Bottom as requested) -->
    <div class="mt-12 mb-12">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Mata Pelajaran</h3>
                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">PILIH JALUR BELAJARMU</p>
            </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php 
            $card_colors = ['from-blue-500 to-blue-700', 'from-blue-600 to-blue-800', 'from-emerald-500 to-teal-600', 'from-orange-500 to-amber-600', 'from-rose-500 to-red-600', 'from-cyan-500 to-blue-600'];
            $i = 0;
            foreach ($subjects as $sl => $s): 
                $grad = $card_colors[$i % count($card_colors)];
                $i++;
            ?>
                <div onclick="sw('<?php echo htmlspecialchars($sl) ?>')" class="group relative bg-white dark:bg-slate-900 rounded-[3rem] p-8 shadow-xl border-2 border-slate-50 dark:border-slate-800 hover:border-blue-500 dark:hover:border-blue-500 transition-all cursor-pointer overflow-hidden">
                    <div class="absolute top-0 right-0 p-6 opacity-10 group-hover:scale-125 transition-transform duration-700">
                        <i class="fas <?php echo htmlspecialchars($s['icon']) ?> text-7xl"></i>
                    </div>
                    
                    <div class="relative z-10">
                        <div class="w-14 h-14 rounded-2xl bg-gradient-to-br <?php echo $grad ?> flex items-center justify-center text-white text-2xl shadow-lg mb-6 group-hover:scale-110 transition-transform">
                            <i class="fas <?php echo htmlspecialchars($s['icon']) ?>"></i>
                        </div>
                        
                        <h4 class="text-xl font-black text-slate-900 dark:text-white mb-2 leading-tight uppercase"><?php echo htmlspecialchars($s['title']) ?></h4>
                        <div class="flex items-center gap-4 mb-6">
                            <div class="flex-1 h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r <?php echo $grad ?> transition-all duration-1000" style="width: <?php echo $s['progress'] ?>%"></div>
                            </div>
                            <span class="text-xs font-black text-slate-400 tabular-nums"><?php echo $s['progress'] ?>%</span>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest"><?php echo $s['completed_mods'] ?>/<?php echo $s['total_mods'] ?> MODUL</span>
                            <div class="w-8 h-8 rounded-full bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 group-hover:bg-blue-600 group-hover:text-white transition-all">
                                <i class="fas fa-arrow-right text-[10px]"></i>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
