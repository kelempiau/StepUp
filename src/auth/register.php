<?php
// src/auth/register.php - PREMIUM REGISTER PAGE
session_start();
require_once '../../config/db.php';
require_once '../helpers/mailer.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $username  = trim($_POST['username'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm) {
        $error = "Password tidak cocok dengan konfirmasi!";
    } elseif (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $token = bin2hex(random_bytes(16)); // Generate random token
            
            $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role, is_verified, verification_token) VALUES (?, ?, ?, ?, 'student', 0, ?)");
            $stmt->execute([$full_name, $username, $email, $hashed, $token]);
            
            // Send Real Email
            if (sendVerificationEmail($email, $full_name, $token)) {
                $success = "Registrasi Berhasil! Kami telah mengirimkan link verifikasi ke email <b>$email</b>. Silakan cek Inbox atau folder Spam kamu.";
            } else {
                // If mail fails, still register but tell user to contact admin (or delete the user to retry)
                $success = "Akun berhasil dibuat, namun gagal mengirim email verifikasi ke <b>$email</b>. Silakan hubungi admin atau coba login nanti.";
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Username atau Email sudah digunakan. Coba yang lain!";
            } else {
                $error = "Pendaftaran gagal. Silakan coba lagi.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Akun - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .input-field {
            width: 100%;
            padding: 0.875rem 1.25rem;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 9999px;
            outline: none;
            font-weight: 600;
            color: #0f172a;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        .input-field:focus { border-color: #2563eb; background: white; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }
        .input-field::placeholder { color: #cbd5e1; font-weight: 500; }
        @keyframes fadeInUp { from { opacity:0; transform: translateY(20px); } to { opacity:1; transform: translateY(0); } }
        .card { animation: fadeInUp 0.5s ease; }
    </style>
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4">

    <!-- Background -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-200/30 rounded-full blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-blue-200/30 rounded-full blur-3xl"></div>
    </div>

    <div class="w-full max-w-md relative z-[20] card pointer-events-auto">
        <!-- Card -->
        <div class="bg-white rounded-[3.5rem] shadow-2xl border border-slate-100 p-10 md:p-12">
            
            <!-- Logo & Title inside card -->
            <div class="text-center mb-10">
                <div class="w-14 h-14 bg-blue-600 rounded-2xl mx-auto flex items-center justify-center text-white shadow-xl shadow-blue-500/30 mb-6">
                    <i class="fas fa-bolt text-2xl"></i>
                </div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight mb-2">Buat Akun</h1>
                <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Bergabung bersama StepUp</p>
            </div>

            <?php if ($error): ?>
                <div class="flex items-center space-x-3 bg-red-50 border border-red-200 p-4 rounded-2xl mb-6">
                    <i class="fas fa-exclamation-circle text-red-500 text-lg shrink-0"></i>
                    <p class="text-red-700 text-sm font-bold"><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="text-center space-y-6">
                    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-3xl mx-auto flex items-center justify-center">
                        <i class="fas fa-envelope-open-text text-3xl"></i>
                    </div>
                    <div class="bg-green-50 border border-green-200 p-6 rounded-[2rem]">
                        <p class="text-green-800 text-sm font-bold leading-relaxed"><?php echo $success; ?></p>
                    </div>
                    <a href="../../login.php" class="inline-block text-blue-600 font-black hover:underline">Kembali ke Login</a>
                </div>
            <?php else: ?>
                <form method="POST" class="space-y-5" autocomplete="off" novalidate>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 px-2">Nama Lengkap</label>
                        <input type="text" name="full_name" required autocomplete="off" class="input-field" placeholder="Nama lengkap kamu">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 px-2">Username</label>
                        <input type="text" name="username" required autocomplete="off" class="input-field" placeholder="Buat username unik">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 px-2">Email</label>
                        <input type="email" name="email" required autocomplete="off" class="input-field" placeholder="alamat@email.com">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 px-2">Password</label>
                        <div class="relative">
                            <input type="password" name="password" id="pw1" required autocomplete="new-password" class="input-field" placeholder="Min. 6 karakter">
                            <button type="button" onclick="togglePass('pw1', 'eye1')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-500 transition-colors"><i id="eye1" class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 px-2">Konfirmasi Password</label>
                        <div class="relative">
                            <input type="password" name="confirm_password" id="pw2" required autocomplete="new-password" class="input-field" placeholder="Ulangi password">
                            <button type="button" onclick="togglePass('pw2', 'eye2')" class="absolute right-4 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-500 transition-colors"><i id="eye2" class="fas fa-eye"></i></button>
                        </div>
                    </div>
                    <button type="submit" class="w-full py-5 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-full transition-all shadow-xl shadow-blue-500/30 hover:scale-[1.02] active:scale-95 mt-4 uppercase tracking-widest text-xs">
                        Daftar Sekarang <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </form>
            <?php endif; ?>

            <p class="mt-8 text-center text-xs text-slate-500 font-bold">
                Sudah punya akun? 
                <a href="../../index.php" class="text-blue-600 hover:text-blue-700 font-black decoration-2 underline-offset-4 hover:underline">Login disini</a>
            </p>

            <div class="mt-8 pt-8 border-t border-slate-50 text-center text-[10px] text-slate-400 font-bold leading-relaxed">
                Dengan mendaftar, kamu menyetujui <br>
                <a href="../views/terms.php" class="text-blue-500 hover:text-blue-600 font-black underline decoration-2 underline-offset-4 transition-all">Syarat & Ketentuan</a> StepUp.
            </div>
        </div>
    </div>

    <script>
        function togglePass(id, eyeId) {
            const input = document.getElementById(id);
            const eye = document.getElementById(eyeId);
            if (input.type === 'password') {
                input.type = 'text';
                eye.className = 'fas fa-eye-slash';
            } else {
                input.type = 'password';
                eye.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>
