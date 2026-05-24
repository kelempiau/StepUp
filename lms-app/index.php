<?php
// Root index.php — Login page
session_start();
require_once 'config/db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . ($_SESSION['role'] === 'admin' ? "src/admin/dashboard.php" : "src/views/dashboard.php"));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role']      = $user['role'];
        header("Location: " . ($user['role'] === 'admin' ? "src/admin/dashboard.php" : "src/views/dashboard.php"));
        exit;
    } else {
        $error = "Username atau password salah.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background: #f0f4ff; }
        .inp { width:100%; padding:.875rem 1.125rem; border:2px solid #e2e8f0; border-radius:.875rem; background:#f8fafc; color:#0f172a; font-weight:600; font-size:.9rem; outline:none; transition:all .2s; }
        .inp:focus { border-color:#2563eb; background:#fff; box-shadow:0 0 0 4px rgba(37,99,235,.1); }
        .inp::placeholder { color:#cbd5e1; font-weight:500; }
        @keyframes up { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
        .card { animation: up .5s ease; }
        .blob1 { position:absolute; width:500px; height:500px; border-radius:50%; background:radial-gradient(circle,rgba(99,102,241,.18),transparent 70%); top:-150px; right:-100px; animation:float1 8s ease-in-out infinite; pointer-events: none; }
        .blob2 { position:absolute; width:400px; height:400px; border-radius:50%; background:radial-gradient(circle,rgba(59,130,246,.15),transparent 70%); bottom:-100px; left:-80px; animation:float2 10s ease-in-out infinite; pointer-events: none; }
        @keyframes float1 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(30px,-30px) scale(1.05)} }
        @keyframes float2 { 0%,100%{transform:translate(0,0) scale(1)} 50%{transform:translate(-20px,20px) scale(1.05)} }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center relative overflow-hidden">
    <div class="blob1"></div>
    <div class="blob2"></div>

    <div class="relative z-[20] w-full max-w-sm mx-4 card pointer-events-auto">
        <!-- Card -->
        <div class="bg-white/80 backdrop-blur-2xl [ -webkit-backdrop-filter:blur(40px); ] border border-white/70 rounded-3xl shadow-[0_20px_60px_rgba(37,99,235,.12)] overflow-hidden">

            <!-- Top accent -->
            <div class="h-1 bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-500"></div>

            <div class="p-8">
                <!-- Logo -->
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-blue-600 text-white mb-4 shadow-xl shadow-blue-500/30">
                        <i class="fas fa-bolt text-2xl"></i>
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 tracking-tight">StepUp</h1>
                    <p class="text-slate-400 text-sm mt-1 font-medium">Platform Belajar Interaktif</p>
                </div>

                <?php if($error): ?>
                <div class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl mb-6 text-sm font-semibold">
                    <i class="fas fa-exclamation-circle shrink-0"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>
                <?php if(isset($_GET['msg']) && $_GET['msg'] === 'registered'): ?>
                <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-2xl mb-6 text-sm font-semibold">
                    <i class="fas fa-check-circle shrink-0"></i>
                    Akun berhasil dibuat! Silakan login.
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-4" autocomplete="off">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Username</label>
                        <input type="text" name="username" required autocomplete="off" class="inp" placeholder="Masukkan username">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="pw" required autocomplete="new-password" class="inp pr-12" placeholder="Masukkan password">
                            <button type="button" onclick="togglePw()" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-500 transition text-sm">
                                <i class="fas fa-eye" id="eye"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit"
                        class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-2xl transition-all shadow-xl shadow-blue-500/25 hover:scale-[1.02] active:scale-95 uppercase tracking-widest text-sm mt-2">
                        Masuk Sekarang <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>

                <p class="mt-6 text-center text-sm text-slate-400 font-medium">
                    Belum punya akun? <a href="src/auth/register.php" class="text-blue-600 font-black hover:underline">Daftar disini</a>
                </p>
            </div>
        </div>

        <p class="text-center text-[10px] text-slate-400 font-medium mt-4">
            © 2026 StepUp Learning Platform • 
            <a href="src/views/terms.php" class="text-blue-500 font-bold hover:underline transition-all">Syarat & Ketentuan</a>
        </p>
    </div>

    <script>
        function togglePw() {
            const pw = document.getElementById('pw');
            const eye = document.getElementById('eye');
            pw.type = pw.type === 'password' ? 'text' : 'password';
            eye.className = pw.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
        }
    </script>
</body>
</html>
