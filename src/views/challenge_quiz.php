
<?php
// src/views/challenge_quiz.php
session_start();
require_once '../../config/db.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    header("Location: ../../index.php");
    exit;
}

$ch_id = $_GET['id'] ?? null;
if (!$ch_id) {
    header("Location: dashboard.php");
    exit;
}

// Check if already completed
$stmt = $pdo->prepare("SELECT * FROM user_challenges WHERE user_id = ? AND challenge_id = ?");
$stmt->execute([$userId, $ch_id]);
$uc = $stmt->fetch();
$is_completed = $uc ? ($uc['is_completed'] == 1) : false;

$stmt = $pdo->prepare("SELECT * FROM challenges WHERE id = ?");
$stmt->execute([$ch_id]);
$challenge = $stmt->fetch();

if (!$challenge) {
    header("Location: dashboard.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, question_text, option_a, option_b, option_c, option_d, correct_option FROM challenge_questions WHERE challenge_id = ? ORDER BY created_at ASC");
$stmt->execute([$ch_id]);
$questions = $stmt->fetchAll();

// Handle Submit Answers
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_quiz') {
    $score = 0;
    $total = count($questions);
    
    foreach ($questions as $q) {
        $ans = $_POST['q_' . $q['id']] ?? '';
        if ($ans === $q['correct_option']) {
            $score++;
        }
    }
    
    // Require 100% to pass the challenge
    if ($total > 0 && $score === $total) {
        if (!$is_completed) {
            $pts = $challenge['points'];
            $pdo->prepare("INSERT INTO user_challenges (user_id, challenge_id, is_completed, is_claimed, completed_at) VALUES (?, ?, 1, 0, NOW()) ON DUPLICATE KEY UPDATE is_completed = 1")->execute([$userId, $ch_id]);
            echo json_encode(['success' => true, 'score' => $score, 'total' => $total, 'passed' => true]);
            exit;
        }
    }
    
    echo json_encode(['success' => true, 'score' => $score, 'total' => $total, 'passed' => false]);
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Misi: <?php echo htmlspecialchars($challenge['title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/theme.js"></script>
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-200 transition-colors duration-300 min-h-screen pb-20">

    <div class="max-w-4xl mx-auto px-4 pt-10">
        <a href="dashboard.php" class="inline-flex items-center gap-2 text-slate-500 hover:text-blue-600 transition-colors font-bold text-sm mb-8">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>

        <div class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-[3rem] p-10 md:p-14 text-white shadow-2xl mb-10 relative overflow-hidden">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 mix-blend-overlay"></div>
            <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                <div>
                    <span class="px-4 py-2 bg-white/20 rounded-2xl text-[10px] font-black uppercase tracking-[0.2em] mb-4 inline-block backdrop-blur-sm shadow-inner">MISI TANTANGAN</span>
                    <h1 class="text-4xl md:text-5xl font-black tracking-tight mb-4"><?php echo htmlspecialchars($challenge['title']); ?></h1>
                    <p class="text-blue-100 font-medium text-lg leading-relaxed max-w-xl"><?php echo htmlspecialchars($challenge['description']); ?></p>
                </div>
                <div class="shrink-0 text-center">
                    <div class="w-24 h-24 rounded-[2rem] bg-white/10 backdrop-blur-md flex flex-col items-center justify-center border border-white/20 shadow-xl">
                        <span class="text-xs font-black uppercase tracking-widest text-blue-200 mb-1">HADIAH</span>
                        <span class="text-3xl font-black text-white">+<?php echo $challenge['points']; ?></span>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($is_completed): ?>
        <div class="bg-emerald-50 border border-emerald-200 dark:bg-emerald-900/20 dark:border-emerald-800/50 rounded-3xl p-8 text-center mb-10 shadow-lg">
            <div class="w-20 h-20 bg-emerald-500 text-white rounded-full flex items-center justify-center text-4xl mx-auto mb-4 shadow-xl shadow-emerald-500/30">
                <i class="fas fa-check"></i>
            </div>
            <h2 class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mb-2">Misi Selesai!</h2>
            <p class="text-emerald-700 dark:text-emerald-300 font-bold">Kamu sudah berhasil menyelesaikan tantangan ini. Jangan lupa klaim poinmu di Dashboard!</p>
        </div>
        <?php elseif (empty($questions)): ?>
        <div class="bg-white dark:bg-slate-800 rounded-3xl p-10 text-center shadow-lg border border-slate-100 dark:border-slate-700">
            <i class="fas fa-tools text-5xl text-slate-300 dark:text-slate-600 mb-4 block"></i>
            <h2 class="text-xl font-black text-slate-700 dark:text-slate-300">Belum ada soal</h2>
            <p class="text-slate-500 font-medium mt-2">Admin belum menambahkan soal untuk misi ini.</p>
        </div>
        <?php else: ?>
        <form id="quizForm" class="space-y-8">
            <input type="hidden" name="action" value="submit_quiz">
            
            <?php foreach ($questions as $i => $q): ?>
            <div class="bg-white dark:bg-slate-800 rounded-[2.5rem] p-8 md:p-10 shadow-lg border border-slate-100 dark:border-slate-700/50">
                <h3 class="text-xl font-black text-slate-900 dark:text-white mb-8 leading-relaxed">
                    <span class="text-blue-500 mr-2"><?php echo $i + 1; ?>.</span>
                    <?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach (['A', 'B', 'C', 'D'] as $opt): 
                        $val = $q['option_'.strtolower($opt)];
                        $radId = 'q_'.$q['id'].'_'.$opt;
                    ?>
                    <label for="<?php echo $radId; ?>" class="relative cursor-pointer">
                        <input type="radio" name="q_<?php echo $q['id']; ?>" value="<?php echo $opt; ?>" id="<?php echo $radId; ?>" class="peer sr-only" required>
                        <div class="p-5 rounded-2xl border-2 border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 text-slate-700 dark:text-slate-300 font-bold transition-all hover:bg-slate-100 dark:hover:bg-slate-800 peer-checked:border-blue-500 peer-checked:bg-blue-50 peer-checked:text-blue-700 dark:peer-checked:bg-blue-900/20 dark:peer-checked:text-blue-400 flex items-center gap-4">
                            <div class="w-8 h-8 rounded-xl bg-white dark:bg-slate-700 flex items-center justify-center font-black shadow-sm peer-checked:bg-blue-500 peer-checked:text-white transition-colors">
                                <?php echo $opt; ?>
                            </div>
                            <span class="flex-1"><?php echo htmlspecialchars($val); ?></span>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="sticky bottom-10 z-20 flex justify-center mt-12">
                <button type="submit" id="submitBtn" class="px-12 py-5 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-black rounded-full shadow-2xl shadow-blue-500/30 hover:-translate-y-2 hover:shadow-blue-500/50 transition-all active:scale-95 text-lg uppercase tracking-widest flex items-center gap-3">
                    <i class="fas fa-paper-plane"></i> Kumpulkan Jawaban
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <script>
        document.getElementById('quizForm')?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memeriksa...';
            
            try {
                const fd = new FormData(e.target);
                const res = await fetch('', { method: 'POST', body: fd });
                const data = await res.json();
                
                if (data.success) {
                    if (data.passed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Misi Berhasil!',
                            text: `Luar Biasa! Kamu menjawab benar semua (${data.score}/${data.total}). Poin misi bisa diklaim sekarang.`,
                            confirmButtonText: 'KEMBALI KE DASHBOARD',
                            customClass: {
                                popup: 'rounded-[3rem] p-8 border border-white/20 shadow-2xl',
                                confirmButton: 'px-8 py-3 bg-gradient-to-r from-emerald-500 to-teal-500 text-white rounded-2xl shadow-xl'
                            }
                        }).then(() => {
                            window.location.href = 'dashboard.php';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Belum Sempurna',
                            text: `Kamu menjawab benar ${data.score} dari ${data.total} soal. Kamu harus menjawab benar semua untuk lulus misi ini. Coba lagi!`,
                            confirmButtonText: 'COBA LAGI',
                            customClass: {
                                popup: 'rounded-[3rem] p-8 border border-white/20 shadow-2xl',
                                confirmButton: 'px-8 py-3 bg-slate-200 text-slate-800 rounded-2xl'
                            }
                        }).then(() => {
                            window.location.href = 'dashboard.php';
                        });
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Kumpulkan Jawaban';
                    }
                }
            } catch (err) {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memproses jawaban' });
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-paper-plane"></i> Kumpulkan Jawaban';
            }
        });
    </script>
</body>
</html>
