<?php
// public/index.php (acts as Login page if not authenticated)
session_start();
require_once '../config/db.php';

if (isset($_SESSION['user_id'])) {
    if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
        header("Location: ../src/admin/dashboard.php");
    } else {
        header("Location: ../src/views/dashboard.php");
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role']; // Store role in session

        if ($user['role'] === 'admin') {
            header("Location: ../src/admin/dashboard.php");
        } else {
            header("Location: ../src/views/dashboard.php");
        }
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
    <title>Login - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-blue-50 flex items-center justify-center min-h-screen relative overflow-hidden">
    <!-- Background Accents -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0">
        <div class="absolute -top-[10%] -left-[10%] w-[40%] h-[40%] bg-blue-400/20 rounded-full blur-3xl animate-pulse"></div>
        <div class="absolute bottom-[10%] right-[10%] w-[30%] h-[30%] bg-cyan-400/20 rounded-full blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
    </div>

    <div class="bg-white/80 backdrop-blur-xl border border-white/50 p-8 rounded-3xl shadow-[0_8px_30px_rgb(0,0,0,0.04)] w-full max-w-md relative z-10 mx-4">
        <div class="text-center mb-10">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-blue-600 text-white mb-4 shadow-lg shadow-blue-500/30">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            </div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight">StepUp</h1>
            <p class="text-slate-500 text-sm mt-2 font-medium">Platform Belajar Interaktif Kelas 12</p>
        </div>
        
        <?php if(isset($error)) echo "<div class='bg-red-50 text-red-600 px-4 py-3 rounded-xl mb-6 text-sm flex items-center border border-red-100'><svg class='w-5 h-5 mr-2 shrink-0' fill='none' stroke='currentColor' viewBox='0 0 24 24'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'></path></svg>$error</div>"; ?>
        <?php if(isset($_GET['msg']) && $_GET['msg'] == 'registered') echo "<div class='bg-green-50 text-green-600 px-4 py-3 rounded-xl mb-6 text-sm text-center border border-green-100 font-medium'>Registrasi berhasil! Silakan login.</div>"; ?>

        <form method="POST" class="space-y-5" autocomplete="off">
            <div>
                <label class="block text-slate-700 text-sm font-semibold mb-2 ml-1">Username</label>
                <input type="text" name="username" required autocomplete="off" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-800 placeholder-slate-400 transition font-medium" placeholder="Masukkan username">
            </div>
            <div>
                <label class="block text-slate-700 text-sm font-semibold mb-2 ml-1">Password</label>
                <input type="password" name="password" required autocomplete="new-password" class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-slate-800 placeholder-slate-400 transition font-medium" placeholder="Masukkan password">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3.5 rounded-xl transition duration-300 shadow-lg shadow-blue-500/25 transform hover:-translate-y-0.5 mt-2">Masuk Sekarang</button>
        </form>
        <p class="mt-8 text-center text-sm text-slate-500 font-medium">Belum punya akun? <a href="../src/auth/register.php" class="text-blue-600 hover:text-blue-700 hover:underline transition">Daftar disini</a></p>
    </div>
</body>
</html>
