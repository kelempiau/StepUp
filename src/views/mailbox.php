<?php
// src/views/mailbox.php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Fetch Messages (Personal Chat)
$messages = [];
try {
    $stmtMsg = $pdo->prepare("
        SELECT m.*, u.full_name as sender_name, u.profile_pic as sender_pic
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmtMsg->execute([$userId]);
    $messages = $stmtMsg->fetchAll();

    // Mark all as read
    $pdo->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ?")->execute([$userId]);
} catch (Exception $e) {}

$fn = explode(' ', $user['full_name'])[0];
$ini = strtoupper(substr($user['full_name'], 0, 1));
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kotak Masuk - StepUp LMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .frost { background: rgba(255, 255, 255, 0.7); backdrop-filter: blur(15px); }
        .dark .frost { background: rgba(15, 23, 42, 0.7); }
    </style>
</head>
<body class="bg-blue-50/30 dark:bg-[#060b1d] text-slate-800 dark:text-slate-200 min-h-screen">

    <div class="max-w-5xl mx-auto py-10 px-6">
        <div class="flex items-center justify-between mb-12">
            <div class="flex items-center gap-6">
                <a href="dashboard.php" class="w-12 h-12 rounded-2xl bg-white dark:bg-slate-800 flex items-center justify-center text-slate-400 hover:text-blue-600 transition shadow-sm border border-slate-100 dark:border-slate-800">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight">Kotak Masuk</h1>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.3em] mt-2">Pesan dan Notifikasi Terbaru</p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                 <div class="px-5 py-2 bg-blue-600 text-white rounded-2xl text-[10px] font-black uppercase tracking-widest shadow-xl shadow-blue-500/20">
                    <?php echo count($messages); ?> PESAN BARU
                 </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4">
            <?php if (empty($messages)): ?>
                <div class="text-center py-32 frost rounded-[3.5rem] border-2 border-dashed border-slate-200 dark:border-slate-800">
                    <div class="w-24 h-24 bg-slate-100 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-8 text-slate-300">
                        <i class="fas fa-envelope-open text-4xl"></i>
                    </div>
                    <h3 class="font-black text-slate-800 dark:text-white text-2xl mb-2">Kotak Masuk Kosong</h3>
                    <p class="text-slate-400 font-bold max-w-xs mx-auto italic uppercase text-[10px] tracking-widest">Belum ada pesan yang masuk untuk saat ini.</p>
                </div>
            <?php else: ?>
                <?php foreach ($messages as $msg): ?>
                    <div class="frost p-8 rounded-[2.5rem] border border-white dark:border-slate-700 shadow-sm hover:shadow-xl transition-all group flex items-start gap-6 relative overflow-hidden">
                        <?php if ($msg['is_read'] == 0): ?>
                            <div class="absolute top-0 left-0 w-2 h-full bg-blue-600"></div>
                        <?php endif; ?>
                        
                        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-700 p-0.5 shadow-lg shrink-0">
                            <div class="w-full h-full rounded-[0.9rem] bg-white dark:bg-slate-800 overflow-hidden flex items-center justify-center font-black text-blue-600">
                                <?php if ($msg['sender_pic']): ?>
                                    <img src="../../<?php echo htmlspecialchars($msg['sender_pic']) ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <?php echo strtoupper(substr($msg['sender_name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="font-black text-slate-900 dark:text-white text-xl"><?php echo htmlspecialchars($msg['sender_name']) ?></h4>
                                <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest"><?php echo date('d M, H:i', strtotime($msg['created_at'])) ?></span>
                            </div>
                            <p class="text-slate-600 dark:text-slate-400 font-medium leading-relaxed mb-4"><?php echo nl2br(htmlspecialchars($msg['message'])) ?></p>
                            <div class="flex items-center gap-3">
                                <button onclick="replyChat(<?php echo $msg['sender_id'] ?>)" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest shadow-lg shadow-blue-500/20 hover:scale-105 transition-all">Balas Pesan</button>
                                <button class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-900/50 flex items-center justify-center text-slate-400 hover:text-red-500 transition-all"><i class="fas fa-trash-alt"></i></button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function replyChat(friendId) {
            Swal.fire({
                title: 'Balas Pesan',
                input: 'textarea',
                inputPlaceholder: 'Ketik pesan balasan...',
                showCancelButton: true,
                confirmButtonText: 'KIRIM',
                cancelButtonText: 'BATAL'
            }).then((res) => {
                if(res.isConfirmed && res.value) {
                    // Send message via AJAX
                    Swal.fire('Terkirim!', 'Pesan balasan telah dikirim.', 'success');
                }
            });
        }
    </script>
</body>
</html>
