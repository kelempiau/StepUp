<!-- src/views/components/inc_tab_challenges.php -->
<?php
/**
 * @var array $db_challenges - Real challenges from DB
 * @var array $subjects
 * @var array $user
 * @var bool $isGuest
 */
?>
<div id="tab-challenges" class="tp pb-20">
    <div class="w-full max-w-6xl mx-auto px-4">
        <div class="mb-10 flex flex-col md:flex-row md:items-end justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 rounded-full mb-4">
                    <i class="fas fa-fire text-white text-xs"></i>
                    <span class="text-[10px] font-black text-white uppercase tracking-[0.2em]">Misi Mingguan</span>
                </div>
                <h3 class="text-4xl font-black text-blue-600 dark:text-blue-400 tracking-tight mb-2">Tantangan Mingguan</h3>
                <p class="text-slate-500 font-bold max-w-xl">Selesaikan misi untuk mendapatkan poin ekstra dan naik level lebih cepat!</p>
            </div>
            <div class="bg-white dark:bg-slate-900 border border-slate-100 dark:border-slate-800 text-slate-900 dark:text-white px-8 py-4 rounded-2xl shadow-sm flex items-center gap-4">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white"><i class="fas fa-star"></i></div>
                <div>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">Total Poin</p>
                    <p class="text-xl font-black"><?php echo number_format($user['total_points'] ?? 0) ?></p>
                </div>
            </div>
        </div>

        <?php if(empty($db_challenges)): ?>
            <div class="bg-white dark:bg-slate-900/80 rounded-[3rem] p-16 text-center border-2 border-dashed border-slate-200 dark:border-slate-800">
                <div class="w-24 h-24 bg-blue-50 dark:bg-blue-900/20 rounded-[2rem] flex items-center justify-center text-5xl mx-auto mb-6">
                    <i class="fas fa-fire text-blue-400"></i>
                </div>
                <h3 class="text-2xl font-black text-slate-900 dark:text-white mb-3">Belum Ada Tantangan</h3>
                <p class="text-slate-500 dark:text-slate-400 font-bold">Tantangan baru akan muncul setiap minggu. Nantikan ya!</p>
            </div>
        <?php else: ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-stretch">
            <?php
            $icons = ['fa-fire', 'fa-brain', 'fa-rocket', 'fa-bolt', 'fa-trophy', 'fa-medal', 'fa-star', 'fa-shield-alt'];
            
            foreach ($db_challenges as $c_idx => $ch):
                $icon = $icons[$c_idx % count($icons)];
                $is_done = ($ch['is_completed'] ?? 0) == 1;
                $is_claimed = ($ch['is_claimed'] ?? 0) == 1;
                
                // Jika sudah diklaim, kita anggap sudah selesai juga untuk tampilan
                if ($is_claimed) $is_done = true;
                
                $quiz_link = "challenge_quiz.php?id=" . $ch['id'];
            ?>
                <div class="group relative">
                    <div class="h-full rounded-[2.5rem] p-8 border border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900/80 flex flex-col transition-all duration-500 hover:-translate-y-1 hover:shadow-xl hover:border-blue-200 dark:hover:border-blue-800 relative overflow-hidden
                        <?php if($is_claimed): ?> !border-emerald-200 dark:!border-emerald-800 <?php elseif($is_done): ?> !border-blue-200 dark:!border-blue-800 <?php endif; ?>">
                        
                        <?php if($is_claimed): ?>
                            <div class="absolute top-6 right-6">
                                <div class="w-10 h-10 bg-emerald-500 text-white rounded-full flex items-center justify-center shadow-lg shadow-emerald-500/30">
                                    <i class="fas fa-check text-sm"></i>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="flex items-center gap-5 mb-6">
                            <div class="w-16 h-16 rounded-2xl <?php echo $is_done ? 'bg-gradient-to-br from-blue-600 to-blue-800 text-white shadow-lg shadow-blue-600/20' : 'bg-blue-600 text-white' ?> flex items-center justify-center text-3xl shrink-0 group-hover:scale-110 transition-transform duration-500">
                                <i class="fas <?php echo $is_done ? 'fa-check' : $icon ?>"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="inline-flex px-3 py-1 bg-blue-600 text-white rounded-lg font-black text-[10px] uppercase tracking-wider mb-2">
                                    +<?php echo $ch['points'] ?> Poin XP
                                </div>
                                <h4 class="text-xl font-black text-slate-900 dark:text-white tracking-tight leading-tight truncate"><?php echo htmlspecialchars($ch['title']) ?></h4>
                            </div>
                        </div>
                        
                        <div class="mb-8 flex-1">
                            <p class="text-slate-500 dark:text-slate-400 font-medium leading-relaxed text-sm line-clamp-3">
                                <?php echo htmlspecialchars($ch['description']) ?>
                            </p>
                        </div>

                        <div class="mt-auto">
                            <?php if($is_claimed): ?>
                                <button disabled class="w-full py-4 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-2xl font-black text-[11px] uppercase tracking-widest flex items-center justify-center gap-2">
                                    <i class="fas fa-check text-xs"></i> Tantangan Selesai
                                </button>
                            <?php elseif($is_done): ?>
                                <button onclick="claimPoints(<?php echo $ch['id'] ?>)" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest shadow-lg shadow-blue-600/20 transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-3">
                                    <i class="fas fa-gift"></i> Klaim Hadiah XP
                                </button>
                            <?php else: ?>
                                <div class="grid grid-cols-2 gap-3">
                                    <button onclick="showChallengeReadyPopup(<?php echo $ch['id'] ?>, '<?php echo addslashes($ch['title']) ?>')" class="py-4 bg-blue-600 text-white rounded-2xl font-black text-[11px] uppercase tracking-widest shadow-lg transition-all hover:scale-[1.02] active:scale-95 flex items-center justify-center gap-2">
                                        Mulai <i class="fas fa-play text-[9px]"></i>
                                    </button>
                                    <button onclick="showChallengeSpoiler('<?php echo addslashes($ch['title']) ?>', '<?php echo addslashes($ch['description']) ?>')" class="py-4 bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-2xl font-black text-[11px] uppercase tracking-widest hover:bg-slate-100 dark:hover:bg-slate-700 transition-all">
                                        Detail
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>

        <!-- Level Up Info -->
        <div class="mt-14 p-10 bg-white dark:bg-slate-900 rounded-[3rem] border border-slate-100 dark:border-slate-800 shadow-sm relative overflow-hidden group">
        <script>
            function showChallengeReadyPopup(challengeId, challengeTitle) {
                if (GUEST_MODE) {
                    showGuestModal('mengikuti tantangan');
                    return;
                }
                confirmModernAlert({
                    title: 'MULAI TANTANGAN?',
                    html: `
                        <div class="py-6 space-y-6">
                            <div class="relative">
                                <div class="absolute inset-0 bg-blue-500/10 blur-2xl rounded-full"></div>
                                <div class="relative w-20 h-20 mx-auto bg-gradient-to-br from-blue-500 to-blue-700 rounded-[2rem] flex items-center justify-center text-white text-4xl shadow-lg shadow-blue-500/30 mb-4">
                                    <i class="fas fa-bullseye"></i>
                                </div>
                            </div>
                            <div>
                                <p class="text-lg font-black text-slate-900 dark:text-white mb-2">${challengeTitle}</p>
                                <p class="text-sm text-slate-600 dark:text-slate-400 font-bold">Pastikan kamu sudah siap menghadapi tantangan ini. Jawab semua soal dengan benar untuk mendapatkan poin!</p>
                            </div>
                            <div class="grid grid-cols-2 gap-3 pt-4">
                                <div class="p-3 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-center">
                                    <i class="fas fa-star text-amber-500 text-lg mb-1"></i>
                                    <p class="text-[10px] font-black text-slate-600 dark:text-slate-300 uppercase">Bonus Poin</p>
                                </div>
                                <div class="p-3 bg-emerald-50 dark:bg-emerald-900/20 rounded-xl text-center">
                                    <i class="fas fa-trophy text-emerald-600 text-lg mb-1"></i>
                                    <p class="text-[10px] font-black text-slate-600 dark:text-slate-300 uppercase">Naik Level</p>
                                </div>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'SIAP, MULAI SEKARANG',
                    cancelButtonText: 'BATAL',
                    icon: 'question'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `challenge_quiz.php?id=${challengeId}`;
                    }
                });
            }

            function showChallengeSpoiler(title, description) {
                showModernAlert({
                    icon: 'info',
                    title: title,
                    text: description,
                    confirmButtonText: 'TUTUP'
                });
            }

            function claimPoints(challengeId) {
                if (GUEST_MODE) {
                    showGuestModal('mengklaim poin');
                    return;
                }
                
                fetch('set_preference.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=claim_challenge&challenge_id=' + challengeId
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        showModernAlert({
                            icon: 'success',
                            title: 'Klaim Berhasil!',
                            text: `Selamat! Kamu mendapatkan +${data.points} Poin XP dan sekarang berada di Level ${data.new_level}!`,
                            confirmButtonText: 'HEBAT'
                        }).then(() => {
                            // Instead of full reload, we can just reload the tab content or the page
                            window.location.reload();
                        });
                    } else {
                        showModernAlert({
                            icon: 'error',
                            title: 'Gagal',
                            text: data.error || 'Gagal mengklaim poin.'
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    showModernAlert({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan sistem.'
                    });
                });
            }
        </script>
            <div class="absolute right-0 top-0 w-64 h-64 bg-blue-50 dark:bg-blue-900/10 rounded-full -mr-24 -mt-24 blur-3xl group-hover:scale-125 transition-transform duration-1000"></div>
            <div class="relative z-10 flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="text-center md:text-left flex-1">
                    <p class="text-[11px] font-black text-blue-600 uppercase tracking-[0.3em] mb-3">Sistem Progres</p>
                    <h3 class="text-3xl font-black text-slate-900 dark:text-white mb-3 tracking-tight">Kumpulkan Poin, Naikkan Level!</h3>
                    <p class="text-slate-500 dark:text-slate-400 font-bold max-w-xl leading-relaxed">Setiap level yang lebih tinggi membutuhkan dedikasi lebih. Kumpulkan poin dari tantangan ini untuk menjadi <span class="text-blue-600 font-black">Legendary Student</span>.</p>
                </div>
                <div class="flex flex-col items-center shrink-0">
                    <div class="w-24 h-24 rounded-[2rem] bg-gradient-to-br from-amber-400 to-orange-600 text-white flex items-center justify-center text-4xl mb-4 shadow-xl shadow-amber-500/20 rotate-3 group-hover:rotate-12 transition-transform duration-700"><i class="fas fa-bolt"></i></div>
                    <div class="px-5 py-1.5 bg-amber-50 dark:bg-amber-900/20 rounded-lg text-[10px] font-black text-amber-600 dark:text-amber-400 uppercase tracking-widest border border-amber-100 dark:border-amber-800">Leveling Progresif</div>
                </div>
            </div>
        </div>
    </div>
</div>
