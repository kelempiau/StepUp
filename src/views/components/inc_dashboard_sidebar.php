<!-- src/views/components/inc_dashboard_sidebar.php -->
<div id="rightSidebar" class="w-full lg:w-[350px] shrink-0 sticky top-0 flex flex-col gap-6 lg:ml-4">
    <!-- Calendar Card -->
    <div class="frost cal-card rounded-[2.5rem] overflow-hidden border border-slate-100 dark:border-slate-800 shadow-sm flex flex-col bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl">
        <div class="p-6 border-b border-slate-50 dark:border-slate-800/50">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="font-black text-slate-900 dark:text-white text-base tracking-tight">Kalender Akademik</h2>
                    <p class="text-[10px] text-slate-400 font-black uppercase tracking-widest mt-0.5">Jadwal & Agenda</p>
                </div>
                <div class="flex items-center gap-1 bg-slate-50 dark:bg-slate-900/50 p-1 rounded-xl border border-slate-100 dark:border-slate-800">
                    <button onclick="chM(-1)" class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 tc flex items-center justify-center text-slate-400 hover:text-blue-600 shadow-sm transition-all"><i class="fas fa-chevron-left text-xs"></i></button>
                    <button onclick="chM(1)" class="w-8 h-8 rounded-lg bg-white dark:bg-slate-800 tc flex items-center justify-center text-slate-400 hover:text-blue-600 shadow-sm transition-all"><i class="fas fa-chevron-right text-xs"></i></button>
                </div>
            </div>
            <div class="text-center py-2 bg-blue-50 dark:bg-blue-900/20 rounded-xl border border-blue-100 dark:border-blue-900/10">
                <span id="calLbl" class="font-black text-[10px] uppercase tracking-[0.2em] text-blue-600 dark:text-blue-400"></span>
            </div>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-7 mb-3 text-center">
                <?php foreach (['MG','SN','SL','RB','KM','JM','SB'] as $d): ?><div class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest"><?php echo $d ?></div><?php endforeach; ?>
            </div>
            <div id="calG" class="grid grid-cols-7 gap-1"></div>
        </div>
        <div class="p-4 bg-slate-50/50 dark:bg-slate-900/30 border-t border-slate-100 dark:border-slate-800/50">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-[9px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">Agenda</h3>
                <button onclick="addAgenda()" class="w-7 h-7 bg-blue-600 text-white rounded-lg flex items-center justify-center shadow-lg shadow-blue-500/20 transition-all active:scale-90"><i class="fas fa-plus text-[10px]"></i></button>
            </div>
            <div id="agendaList" class="space-y-2 max-h-[120px] overflow-y-auto custom-scrollbar pr-1"></div>
        </div>
    </div>

    <!-- Todolist Card -->
    <div class="frost rounded-[2rem] p-6 border border-slate-100 dark:border-slate-800 shadow-sm bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl">
        <div class="flex items-center justify-between mb-5">
            <h3 class="font-black text-slate-900 dark:text-white text-sm tracking-tight">Todolist</h3>
            <span class="px-3 py-1 bg-blue-100 dark:bg-blue-900/40 text-blue-600 dark:text-blue-400 rounded-full text-[9px] font-black" id="todoCnt">0</span>
        </div>
        <form onsubmit="addTodo(event)" class="flex gap-2 mb-4">
            <input id="todoInput" type="text" placeholder="Target baru..." class="flex-1 bg-white dark:bg-slate-900/60 border border-slate-200 dark:border-slate-800 rounded-xl px-4 py-2.5 text-xs font-bold outline-none focus:ring-2 focus:ring-blue-500 transition-all">
            <button type="submit" class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg active:scale-90 transition-all hover:bg-blue-700"><i class="fas fa-plus text-xs"></i></button>
        </form>
        <div id="todoList" class="space-y-2 max-h-48 overflow-y-auto custom-scrollbar"></div>
    </div>

    <!-- Mailbox shortcut Card -->
    <button onclick="<?php echo $isGuest ? 'if(typeof showGuestModal===\'function\')showGuestModal(\'Kotak Masuk\')' : 'markAllAsRead(); window.location.href=\'mailbox.php\'' ?>" class="w-full flex items-center gap-4 p-4 frost rounded-[1.5rem] border border-white/60 dark:border-slate-800 shadow-sm hover:shadow-lg transition-all group bg-white/40 dark:bg-slate-900/40 backdrop-blur-xl">
        <div class="w-10 h-10 rounded-xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 relative">
            <i class="fas fa-envelope"></i>
            <?php if (!$isGuest && $unread_inbox_count > 0): ?><span id="inbox_badge" class="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full border-2 border-white dark:border-slate-900"></span><?php endif; ?>
        </div>
        <div class="text-left"><p class="font-black text-slate-900 dark:text-white text-sm">Kotak Masuk</p><p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Mail & Pesan</p></div>
        <i class="fas fa-chevron-right text-slate-300 ml-auto group-hover:translate-x-1 transition-transform"></i>
    </button>
</div>
