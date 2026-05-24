<!-- src/views/components/inc_tab_leaderboard.php -->
<?php
/**
 * @var array $leaderboard - Real data from DB with fields: id, full_name, username, profile_pic, points, level
 */
?>
<div id="tab-leaderboard" class="tp pb-20">
    <div class="w-full max-w-5xl mx-auto px-4">
        <!-- Header -->
        <div class="relative mb-12 text-center">
            <div class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 rounded-full mb-4">
                <i class="fas fa-trophy text-amber-500 text-xs"></i>
                <span class="text-[10px] font-black text-amber-600 uppercase tracking-[0.2em]">Kompetisi</span>
            </div>
            <h2 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-3">Peringkat Global</h2>
            <p class="text-slate-500 dark:text-slate-400 font-medium max-w-lg mx-auto">Jadilah yang terbaik di antara ribuan pelajar hebat lainnya!</p>
        </div>
        
        <?php if(empty($leaderboard)): ?>
            <div class="bg-white dark:bg-slate-900/80 rounded-[3rem] p-16 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 shadow-sm">
                <div class="w-24 h-24 bg-amber-50 dark:bg-amber-900/20 rounded-[2rem] flex items-center justify-center text-5xl mx-auto mb-6">
                    <i class="fas fa-trophy text-amber-400 animate-bounce"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-3">Belum Ada Kompetisi</h3>
                <p class="text-slate-500 dark:text-slate-400 font-bold">Mulai belajar sekarang untuk mengamankan posisimu!</p>
            </div>
        <?php else: ?>
        <!-- Top 3 Podium -->
        <div class="flex items-end justify-center gap-4 md:gap-6 mb-14 px-4">
            <?php 
            $top3 = array_slice($leaderboard, 0, 3);
            $rank1 = $top3[0] ?? null;
            $rank2 = $top3[1] ?? null;
            $rank3 = $top3[2] ?? null;
            ?>
            
            <!-- Rank 2 (Silver) -->
            <?php if($rank2): ?>
            <div class="flex flex-col items-center w-28 md:w-36 group">
                <div class="relative mb-3">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-full p-1 bg-gradient-to-br from-slate-300 via-slate-100 to-slate-400 shadow-lg transition-transform duration-500 group-hover:scale-110">
                        <div class="w-full h-full rounded-full bg-white dark:bg-slate-800 overflow-hidden flex items-center justify-center text-2xl font-black text-slate-400 border-2 border-white dark:border-slate-700">
                            <?php if(!empty($rank2['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($rank2['profile_pic']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?php echo strtoupper(substr($rank2['username'],0,1)) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="absolute -top-1 -right-1 w-7 h-7 bg-slate-200 dark:bg-slate-700 rounded-full border-2 border-white shadow-md flex items-center justify-center font-black text-slate-500 text-[10px]">2</div>
                </div>
                <div class="h-28 md:h-36 w-full bg-white dark:bg-slate-800/80 rounded-t-[2rem] border-x border-t border-slate-100 dark:border-slate-700 flex flex-col items-center pt-5 shadow-lg">
                    <span class="font-bold text-slate-800 dark:text-white text-sm truncate w-full px-3 text-center"><?php echo htmlspecialchars($rank2['username']) ?></span>
                    <div class="mt-2 px-3 py-1 bg-slate-50 dark:bg-slate-700 rounded-full">
                        <span class="text-[10px] font-black text-slate-500"><?php echo number_format($rank2['points'] ?? 0) ?> PTS</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Rank 1 (Gold) -->
            <?php if($rank1): ?>
            <div class="flex flex-col items-center w-36 md:w-48 group">
                <div class="relative mb-4">
                    <div class="absolute -inset-2 bg-amber-400/20 blur-xl rounded-full group-hover:bg-amber-400/40 transition-all duration-700"></div>
                    <div class="relative w-28 h-28 md:w-32 md:h-32 rounded-full p-1.5 bg-gradient-to-br from-amber-400 via-yellow-200 to-amber-600 shadow-2xl shadow-amber-500/20 transition-transform duration-500 group-hover:scale-110">
                        <div class="w-full h-full rounded-full bg-white dark:bg-slate-800 overflow-hidden flex items-center justify-center text-4xl font-black text-amber-500 border-4 border-white dark:border-slate-700">
                            <?php if(!empty($rank1['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($rank1['profile_pic']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?php echo strtoupper(substr($rank1['username'],0,1)) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="absolute -top-3 -right-1 w-10 h-10 bg-amber-400 rounded-full border-3 border-white shadow-xl flex items-center justify-center font-black text-white">
                        <i class="fas fa-crown text-sm"></i>
                    </div>
                </div>
                <div class="h-40 md:h-48 w-full bg-gradient-to-b from-amber-50 to-white dark:from-amber-900/20 dark:to-slate-800/80 rounded-t-[2.5rem] border-x border-t border-amber-100 dark:border-amber-900/30 flex flex-col items-center pt-6 shadow-xl">
                    <span class="font-black text-slate-900 dark:text-white text-base truncate w-full px-4 text-center"><?php echo htmlspecialchars($rank1['username']) ?></span>
                    <div class="mt-3 px-4 py-1.5 bg-amber-400 rounded-full shadow-lg shadow-amber-400/30">
                        <span class="text-xs font-black text-white"><?php echo number_format($rank1['points'] ?? 0) ?> PTS</span>
                    </div>
                    <div class="mt-2 text-[10px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-widest">Master of StepUp</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Rank 3 (Bronze) -->
            <?php if($rank3): ?>
            <div class="flex flex-col items-center w-28 md:w-36 group">
                <div class="relative mb-3">
                    <div class="w-20 h-20 md:w-24 md:h-24 rounded-full p-1 bg-gradient-to-br from-amber-800 via-amber-600 to-amber-900 shadow-lg transition-transform duration-500 group-hover:scale-110">
                        <div class="w-full h-full rounded-full bg-white dark:bg-slate-800 overflow-hidden flex items-center justify-center text-2xl font-black text-amber-800 border-2 border-white dark:border-slate-700">
                            <?php if(!empty($rank3['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($rank3['profile_pic']) ?>" class="w-full h-full object-cover">
                            <?php else: ?>
                                <?php echo strtoupper(substr($rank3['username'],0,1)) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="absolute -top-1 -right-1 w-7 h-7 bg-amber-700 rounded-full border-2 border-white shadow-md flex items-center justify-center font-black text-white text-[10px]">3</div>
                </div>
                <div class="h-24 md:h-32 w-full bg-white dark:bg-slate-800/80 rounded-t-[2rem] border-x border-t border-slate-100 dark:border-slate-700 flex flex-col items-center pt-5 shadow-lg">
                    <span class="font-bold text-slate-800 dark:text-white text-sm truncate w-full px-3 text-center"><?php echo htmlspecialchars($rank3['username']) ?></span>
                    <div class="mt-2 px-3 py-1 bg-amber-50 dark:bg-amber-900/10 rounded-full">
                        <span class="text-[10px] font-black text-amber-700 dark:text-amber-500"><?php echo number_format($rank3['points'] ?? 0) ?> PTS</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Full List -->
        <div class="bg-white dark:bg-slate-900/80 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-lg overflow-hidden">
            <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h4 class="text-xs font-black text-slate-400 uppercase tracking-[0.2em]">Peringkat Lengkap</h4>
                <span class="text-[10px] font-bold text-slate-400"><?php echo count($leaderboard) ?> Siswa</span>
            </div>
            <div class="divide-y divide-slate-50 dark:divide-slate-800/50">
            <?php
            $rest = array_slice($leaderboard, 3);
            foreach($rest as $idx => $lb_user):
                $rank = $idx + 4;
            ?>
            <div class="group flex items-center gap-4 px-6 py-4 hover:bg-blue-50/50 dark:hover:bg-blue-900/10 transition-all duration-300">
                <div class="w-10 font-black text-slate-300 dark:text-slate-600 text-base text-center group-hover:text-blue-500 transition-colors tabular-nums">
                    #<?php echo $rank ?>
                </div>
                
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-700 flex items-center justify-center overflow-hidden border border-slate-100 dark:border-slate-700 shrink-0">
                    <?php if(!empty($lb_user['profile_pic'])): ?>
                        <img src="<?php echo htmlspecialchars($lb_user['profile_pic']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="font-black text-blue-600 dark:text-blue-400 text-lg"><?php echo strtoupper(substr($lb_user['username'],0,1)) ?></span>
                    <?php endif; ?>
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <h4 class="font-bold text-slate-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors truncate text-sm">
                            <?php echo htmlspecialchars($lb_user['username']) ?>
                        </h4>
                        <span class="px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 rounded text-[9px] font-black text-blue-500 uppercase shrink-0">Lvl <?php echo $lb_user['level'] ?></span>
                    </div>
                </div>

                <div class="text-right shrink-0">
                    <div class="flex items-center justify-end gap-1.5 text-slate-900 dark:text-white">
                        <i class="fas fa-bolt text-amber-400 text-[10px]"></i>
                        <span class="font-black text-base tabular-nums"><?php echo number_format($lb_user['points'] ?? 0) ?></span>
                    </div>
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Points</p>
                </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
