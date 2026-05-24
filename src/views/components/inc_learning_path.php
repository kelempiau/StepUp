<!-- src/views/components/inc_learning_path.php -->
<?php
/**
 * @var array $s Current subject data
 * @var string $sl Current subject slug
 * @var bool $isGuest
 * @var string $quote Motivational quote (from dashboard.php)
 */
?>
<div class="w-full">
    <!-- Subject Header Banner -->
    <div class="relative overflow-hidden rounded-[4rem] p-12 md:p-16 text-white mb-12 shadow-2xl" style="background: linear-gradient(135deg, <?php echo $s['color'] ?> 0%, <?php echo $s['color'] ?>dd 100%);">
        <div class="absolute top-0 right-0 p-10 opacity-10">
            <i class="fas <?php echo htmlspecialchars($s['icon']) ?> text-[10rem]"></i>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center backdrop-blur-md border border-white/30">
                    <i class="fas <?php echo htmlspecialchars($s['icon']) ?> text-sm"></i>
                </div>
                <p class="text-[11px] font-black uppercase tracking-[0.4em] opacity-70">Mata Pelajaran</p>
            </div>
            <h2 class="text-4xl md:text-6xl font-black tracking-tighter uppercase mb-6 leading-none"><?php echo htmlspecialchars($s['title']) ?></h2>
            <div class="flex flex-wrap items-center gap-6">
                <div class="px-6 py-3 bg-white/20 backdrop-blur-md rounded-2xl border border-white/30 flex items-center gap-3">
                    <span class="text-3xl font-black"><?php echo $s['progress'] ?>%</span>
                    <span class="text-[10px] font-black uppercase tracking-widest opacity-60">Progres</span>
                </div>
                <div class="text-sm font-bold opacity-80 bg-black/10 px-4 py-2 rounded-xl backdrop-blur-sm">
                    <i class="fas fa-check-circle mr-2 opacity-60"></i> <?php echo $s['completed_mods'] ?> / <?php echo $s['total_mods'] ?> Modul Selesai
                </div>
            </div>
        </div>
    </div>

    <!-- Two Column Layout: Learning Path (left) + Sidebar (right, scrolls normally) -->
    <div class="flex flex-col xl:flex-row gap-8 items-start">

        <!-- LEFT: Learning Path (narrower) -->
        <div class="flex-1 min-w-0">
            <div class="bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 shadow-xl rounded-[5rem] p-8 md:p-16 mb-12 overflow-hidden relative">
                <div class="text-center mb-24 relative z-10">
                    <div class="inline-block px-6 py-2 bg-blue-50 dark:bg-blue-900/30 text-blue-600 rounded-full text-[10px] font-black uppercase tracking-[0.3em] mb-4">Peta Pembelajaran</div>
                    <h3 class="text-4xl font-black text-slate-900 dark:text-white mb-4 tracking-tight">Rute Belajar</h3>
                    <p class="text-base font-bold text-slate-500 max-w-xl mx-auto">Selesaikan modul satu per satu untuk membuka materi berikutnya dan mengumpulkan poin!</p>
                </div>

                <div class="relative w-full max-w-[900px] mx-auto flex flex-col items-center pb-64">
                    <?php
                    // Winding path that connects levels, starting from the very top
                    $node_sp = 250;
                    $total_items = $s['total_mods'] + count($s['topics']);
                    $path_height = ($total_items * $node_sp) + 100;
                    $start_y = 0; // Start from the very top
                    $path_d = "M400,$start_y ";
                    for ($i = 0; $i < $total_items; $i++) {
                        $y0 = $start_y + ($i * $node_sp);
                        $y1 = $y0 + ($node_sp * 0.5);
                        $y3 = $y0 + $node_sp;
                        if ($i % 2 == 0) {
                            $path_d .= "C 200 $y1, 200 $y1, 400 $y3 ";
                        } else {
                            $path_d .= "C 600 $y1, 600 $y1, 400 $y3 ";
                        }
                    }
                    ?>
                    <!-- Decorative Path SVG -->
                    <div class="absolute inset-0 pointer-events-none flex justify-center z-0" style="top: 0; width: 100%; height: 100%;">
                        <svg class="w-full h-full" viewBox="0 0 800 <?php echo $path_height ?>" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                            <path d="<?php echo $path_d ?>" fill="none" stroke="#94a3b8" class="dark:stroke-slate-600" stroke-linecap="round" stroke-width="12" stroke-dasharray="10 10"></path>
                        </svg>
                    </div>

                    <?php 
                    $global_prev_completed = true;
                    $global_level = 0;
                    $node_counter = 0;
                    $is_first_topic = true;
                    $active_node_found = false;
                    foreach ($s['topics'] as $ts => $top): 
                    ?>
                        <!-- Sub Tema Header - Floating transparent overlay -->
                        <div class="w-full flex flex-col items-center justify-center relative z-40" style="margin-bottom: 3rem; <?php echo !$is_first_topic ? 'margin-top: 6rem;' : '' ?>">
                            <div class="px-6 py-2 bg-white/80 dark:bg-slate-900/80 backdrop-blur-md border border-slate-200/50 dark:border-slate-800/50 rounded-full shadow-sm flex items-center justify-center gap-2.5 transition-all">
                                <i class="fas fa-bookmark text-blue-500 text-[10px]"></i>
                                <span class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.3em]">Sub Tema</span>
                                <span class="text-slate-300 dark:text-slate-700 font-bold">•</span>
                                <span class="font-black text-xs text-slate-700 dark:text-slate-300 tracking-tight uppercase"><?php echo htmlspecialchars($top['title']) ?></span>
                            </div>
                        </div>

                        <?php 
                        $is_first_topic = false;
                        foreach ($top['modules'] as $ms => $mod): 
                            $is_done = $mod['is_completed']; 
                            $is_locked = !$global_prev_completed && !$isGuest; 
                            $global_level++;
                            $node_counter++;
                            $align = ($node_counter % 2 == 1) ? 'start' : 'end';
                            $link = "module.php?subject=".urlencode($sl)."&topic=".urlencode($ts)."&module=".urlencode($ms);
                        ?>
                            <!-- Node wrapper: flex-col so Level label is in normal flow -->
                            <?php
                            $is_active_node = (!$is_done && !$is_locked && !$active_node_found);
                            if ($is_active_node) $active_node_found = true;
                            ?>
                            <div class="flex w-full justify-<?php echo $align ?> <?php echo $align == 'start' ? 'pl-2 md:pl-4' : 'pr-2 md:pr-4' ?> relative z-20 group" style="margin-bottom: 4rem;">
                                <div class="flex flex-col items-center gap-3">
                                    <?php if($is_locked): ?>
                                        <!-- Locked Node -->
                                        <div class="w-20 h-20 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center border-4 border-slate-200 dark:border-slate-700 shadow-[0_8px_0_#e2e8f0] dark:shadow-[0_8px_0_#1e293b] opacity-60">
                                            <i class="fas fa-lock text-slate-400 text-2xl"></i>
                                        </div>
                                    <?php elseif($is_done): ?>
                                        <!-- Completed Node -->
                                        <button onclick="navMod('<?php echo $link ?>')" class="w-20 h-20 bg-emerald-500 rounded-full flex items-center justify-center border-4 border-white dark:border-slate-900 shadow-[0_8px_0_#059669] hover:translate-y-1 hover:shadow-[0_4px_0_#059669] active:translate-y-2 active:shadow-none transition-all outline-none">
                                            <i class="fas fa-check text-white text-3xl"></i>
                                        </button>
                                    <?php else: ?>
                                        <!-- Active Node Button -->
                                        <button onclick="navMod('<?php echo $link ?>')" class="w-24 h-24 bg-blue-600 rounded-full flex items-center justify-center border-4 border-white dark:border-slate-900 shadow-[0_10px_0_#1d4ed8] relative z-20 outline-none hover:translate-y-1 hover:shadow-[0_6px_0_#1d4ed8] active:translate-y-2 active:shadow-none transition-all">
                                            <div class="absolute inset-2 border-4 border-white/30 rounded-full border-dashed animate-spin" style="animation-duration: 6s;"></div>
                                            <i class="fas fa-play text-white text-3xl ml-1.5"></i>
                                            <div class="absolute -top-4 <?php echo $align == 'start' ? 'left-0' : 'right-0' ?> bg-amber-400 text-white text-[10px] font-black px-3 py-1.5 rounded-full border-2 border-white dark:border-slate-900 shadow-lg">+5 PTS</div>
                                        </button>
                                    <?php endif; ?>

                                    <!-- Level & Module Label: NORMAL FLOW -->
                                    <div class="text-center mt-2 max-w-[180px] pointer-events-none">
                                        <span class="font-black text-xs text-slate-700 dark:text-slate-300 block leading-tight mb-0.5"><?php echo htmlspecialchars($mod['title']) ?></span>
                                        <span class="font-black text-[9px] text-slate-400 dark:text-slate-500 uppercase tracking-[0.25em]">Level <?php echo $global_level ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php 
                        $global_prev_completed = $is_done; 
                        endforeach; 
                        ?>
                    <?php endforeach; ?>
                    
                    <!-- Finish Flag -->
                    <div class="flex w-full justify-center relative mt-24">
                        <button onclick="finishPathAlert()" class="group relative">
                            <div class="absolute -inset-4 bg-blue-500/20 rounded-full blur-xl group-hover:bg-blue-500/40 transition-all"></div>
                            <div class="relative w-36 bg-white dark:bg-slate-800 rounded-[2.5rem] border-2 border-dashed border-slate-200 dark:border-slate-700 flex items-center justify-center shadow-xl group-hover:scale-105 transition-all py-5">
                                <i class="fas fa-flag-checkered text-slate-300 dark:text-slate-600 text-3xl group-hover:text-blue-500 transition-all"></i>
                            </div>
                        </button>
                    </div>
                </div> 
            </div>
        </div>

        <!-- RIGHT: Sidebar (normal scroll, not fixed, not sticky) -->
        <div class="w-full xl:w-[350px] flex-shrink-0 space-y-5">

            <!-- Mini Calendar -->
            <div class="bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 rounded-[2rem] shadow-xl p-5 overflow-hidden">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h4 class="font-black text-sm text-slate-900 dark:text-white tracking-tight">Kalender</h4>
                        <p class="text-[8px] font-black text-blue-500 uppercase tracking-[0.2em]">Agenda Belajar</p>
                    </div>
                    <div class="flex items-center gap-1">
                        <button onclick="chMiniCal(-1, '<?php echo $sl ?>')" class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-blue-600 transition-all border border-slate-100 dark:border-slate-700">
                            <i class="fas fa-chevron-left text-[9px]"></i>
                        </button>
                        <button onclick="chMiniCal(1, '<?php echo $sl ?>')" class="w-7 h-7 rounded-lg bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-blue-600 transition-all border border-slate-100 dark:border-slate-700">
                            <i class="fas fa-chevron-right text-[9px]"></i>
                        </button>
                    </div>
                </div>
                <div class="text-center py-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-100 dark:border-blue-900/10 mb-2">
                    <span id="miniCalLbl_<?php echo $sl ?>" class="font-black text-[9px] uppercase tracking-[0.25em] text-blue-600 dark:text-blue-400"></span>
                </div>
                <div class="grid grid-cols-7 mb-1.5 text-center">
                    <?php foreach (['Mg','Sn','Sl','Rb','Km','Jm','Sb'] as $d): ?>
                        <div class="text-[7px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-wide py-0.5"><?php echo $d ?></div>
                    <?php endforeach; ?>
                </div>
                <div id="miniCalGrid_<?php echo $sl ?>" class="grid grid-cols-7 gap-0.5 min-h-[140px]"></div>

                <!-- Add Agenda -->
                <button onclick="addMiniAgenda('<?php echo $sl ?>')" class="w-full mt-2 py-2 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-lg flex items-center justify-center gap-1.5 text-[9px] font-black uppercase tracking-widest border border-blue-100 dark:border-blue-800/50 transition-all active:scale-[0.98]">
                    <i class="fas fa-plus text-[7px]"></i> Agenda
                </button>
                <div id="miniAgendaList_<?php echo $sl ?>" class="space-y-1.5 max-h-[100px] overflow-y-auto custom-scrollbar pr-1 mt-2"></div>
            </div>

            <!-- Motivational Quote -->
            <div class="relative overflow-hidden rounded-[2rem] p-5 shadow-xl bg-gradient-to-br from-blue-50 via-blue-100/80 to-blue-200/60 dark:from-slate-800 dark:via-slate-800/90 dark:to-blue-900/40 border-2 border-blue-100/80 dark:border-blue-900/30">
                <div class="absolute top-[-15%] right-[-10%] w-24 h-24 bg-blue-200/30 dark:bg-blue-500/5 rounded-full blur-2xl"></div>
                
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <i class="fas fa-graduation-cap text-[4rem] text-blue-200/25 dark:text-blue-700/15"></i>
                </div>

                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-7 h-7 rounded-lg bg-blue-500/15 dark:bg-blue-400/20 flex items-center justify-center text-blue-600 dark:text-blue-300">
                            <i class="fas fa-quote-left text-[10px]"></i>
                        </div>
                        <span class="text-[8px] font-black text-blue-500 dark:text-blue-400 uppercase tracking-[0.25em] opacity-80">Daily Boost</span>
                    </div>
                    
                    <p class="text-sm font-black italic leading-snug tracking-tight text-blue-900 dark:text-blue-100">
                        "<?php echo htmlspecialchars($quote ?? 'Belajar hari ini adalah investasi terbaik untuk masa depanmu.') ?>"
                    </p>
                </div>
            </div>

            <!-- Mini Todolist -->
            <div class="bg-white dark:bg-slate-900 border-2 border-slate-100 dark:border-slate-800 shadow-xl rounded-[2rem] p-5 relative overflow-hidden">
                <div class="flex items-center justify-between mb-3 relative z-10">
                    <div>
                        <h4 class="font-black text-sm text-slate-900 dark:text-white tracking-tight">Tugas</h4>
                        <p class="text-[7px] text-slate-400 font-black uppercase tracking-widest">TARGET HARI INI</p>
                    </div>
                    <span class="px-2.5 py-1 bg-blue-600 text-white rounded-lg shadow-lg shadow-blue-500/20 text-[8px] font-black uppercase" id="miniTodoCnt_<?php echo $sl ?>">0</span>
                </div>
                <form onsubmit="addMiniTodo(event, '<?php echo $sl ?>')" class="flex gap-1.5 mb-3 relative z-10">
                    <input id="miniTodoInput_<?php echo $sl ?>" type="text" placeholder="Tugas baru..." class="flex-1 bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700 rounded-lg px-3 py-2 text-[11px] font-bold outline-none focus:border-blue-500 transition-all">
                    <button type="submit" class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center shadow-lg active:scale-95 transition-all">
                        <i class="fas fa-plus text-[10px]"></i>
                    </button>
                </form>
                <div id="miniTodoList_<?php echo $sl ?>" class="space-y-1.5 max-h-[180px] overflow-y-auto custom-scrollbar pr-1 relative z-10"></div>
            </div>

        </div>
    </div>
</div>

<!-- Mini Calendar & Sidebar JS for this subject tab -->
<script>
(function(){
    const slug = '<?php echo $sl ?>';
    let miniCalMonth = new Date().getMonth();
    let miniCalYear = new Date().getFullYear();
    const months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    function renderMiniCal() {
        const lbl = document.getElementById('miniCalLbl_' + slug);
        const grid = document.getElementById('miniCalGrid_' + slug);
        if (!lbl || !grid) return;
        lbl.textContent = months[miniCalMonth] + ' ' + miniCalYear;
        
        const first = new Date(miniCalYear, miniCalMonth, 1).getDay();
        const days = new Date(miniCalYear, miniCalMonth + 1, 0).getDate();
        const today = new Date();
        let html = '';
        
        for (let i = 0; i < first; i++) {
            html += '<div></div>';
        }
        for (let d = 1; d <= days; d++) {
            const isToday = (d === today.getDate() && miniCalMonth === today.getMonth() && miniCalYear === today.getFullYear());
            html += `<div class="w-full aspect-square flex items-center justify-center rounded-md text-[10px] font-bold cursor-pointer transition-all hover:bg-blue-50 dark:hover:bg-blue-900/30 ${isToday ? 'bg-blue-600 text-white shadow-sm hover:bg-blue-700 dark:hover:bg-blue-700' : 'text-slate-600 dark:text-slate-400'}">${d}</div>`;
        }
        grid.innerHTML = html;
    }

    window.chMiniCal = function(dir, s) {
        if (s !== slug) return;
        miniCalMonth += dir;
        if (miniCalMonth > 11) { miniCalMonth = 0; miniCalYear++; }
        if (miniCalMonth < 0) { miniCalMonth = 11; miniCalYear--; }
        renderMiniCal();
    };

    window.addMiniAgenda = function(s) {
        if (s !== slug) return;
        const title = prompt('📅 Judul agenda:');
        if (!title) return;
        const date = prompt('📆 Tanggal (YYYY-MM-DD):');
        if (!date) return;
        
        fetch('../../src/api/todo.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'add_event', title: title, date: date})
        }).then(r => r.json()).then(data => {
            if (data.success) {
                const list = document.getElementById('miniAgendaList_' + slug);
                if (list) {
                    const div = document.createElement('div');
                    div.className = 'flex items-center gap-1.5 p-1.5 bg-blue-50 dark:bg-blue-900/20 rounded-lg';
                    div.innerHTML = `<i class="fas fa-calendar-day text-blue-400 text-[9px]"></i><span class="text-[10px] font-bold text-slate-700 dark:text-slate-300 truncate">${date}: ${title}</span>`;
                    list.appendChild(div);
                }
            }
        }).catch(() => {});
    };

    // Mini Todo functions
    window.addMiniTodo = function(e, s) {
        e.preventDefault();
        if (s !== slug) return;
        const input = document.getElementById('miniTodoInput_' + slug);
        const task = input.value.trim();
        if (!task) return;
        
        fetch('../../src/api/todo.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'add', task: task})
        }).then(r => r.json()).then(data => {
            if (data.success) {
                input.value = '';
                loadMiniTodos();
            }
        }).catch(() => {});
    };

    function loadMiniTodos() {
        fetch('../../src/api/todo.php?action=list')
        .then(r => r.json())
        .then(data => {
            const list = document.getElementById('miniTodoList_' + slug);
            const cnt = document.getElementById('miniTodoCnt_' + slug);
            if (!list) return;
            
            const todos = data.todos || [];
            const pending = todos.filter(t => !t.is_completed);
            if (cnt) cnt.textContent = pending.length;
            
            if (todos.length === 0) {
                list.innerHTML = '<div class="text-center py-4"><p class="text-[10px] font-bold text-slate-400">Belum ada tugas</p></div>';
                return;
            }
            
            list.innerHTML = todos.map(t => `
                <div class="flex items-center gap-1.5 p-2 rounded-lg ${t.is_completed ? 'bg-emerald-50 dark:bg-emerald-900/10' : 'bg-slate-50 dark:bg-slate-800/50'} group transition-all">
                    <button onclick="toggleMiniTodo(${t.id}, '${slug}')" class="w-4 h-4 rounded border-2 ${t.is_completed ? 'bg-emerald-500 border-emerald-500' : 'border-slate-300 dark:border-slate-600'} flex items-center justify-center flex-shrink-0 transition-all">
                        ${t.is_completed ? '<i class="fas fa-check text-white text-[7px]"></i>' : ''}
                    </button>
                    <span class="text-[10px] font-bold ${t.is_completed ? 'line-through text-slate-400' : 'text-slate-700 dark:text-slate-300'} truncate flex-1">${t.task}</span>
                    <button onclick="delMiniTodo(${t.id}, '${slug}')" class="w-4 h-4 rounded text-slate-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all flex-shrink-0">
                        <i class="fas fa-times text-[7px]"></i>
                    </button>
                </div>
            `).join('');
        }).catch(() => {});
    }

    window.toggleMiniTodo = function(id, s) {
        if (s !== slug) return;
        fetch('../../src/api/todo.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'toggle', id: id})
        }).then(() => loadMiniTodos()).catch(() => {});
    };

    window.delMiniTodo = function(id, s) {
        if (s !== slug) return;
        fetch('../../src/api/todo.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', id: id})
        }).then(() => loadMiniTodos()).catch(() => {});
    };

    // Init
    renderMiniCal();
    loadMiniTodos();
})();
</script>
