<?php
// src/auth/verify.php
session_start();
require_once '../../config/db.php';

$message = "";
$status = "";

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Find user with this token
    $stmt = $pdo->prepare("SELECT id FROM users WHERE verification_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Verify user and clear token
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
        $stmt->execute([$user['id']]);
        $status = "success";
        $message = "Akun kamu berhasil diverifikasi! Silakan login untuk memulai belajar.";
    } else {
        $status = "error";
        $message = "Token verifikasi tidak valid atau sudah kedaluwarsa.";
    }
} else {
    header("Location: ../../login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Akun - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center p-4 text-center">
    <div class="max-w-md w-full bg-white p-10 rounded-[3rem] shadow-2xl border border-slate-100">
        <div class="w-20 h-20 <?php echo $status === 'success' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?> rounded-3xl mx-auto flex items-center justify-center mb-6">
            <i class="fas <?php echo $status === 'success' ? 'fa-check-circle' : 'fa-times-circle'; ?> text-4xl"></i>
        </div>
        <h1 class="text-2xl font-black text-slate-900 mb-4"><?php echo $status === 'success' ? 'Verifikasi Berhasil' : 'Verifikasi Gagal'; ?></h1>
        <p class="text-slate-500 font-bold mb-8 leading-relaxed"><?php echo $message; ?></p>
        <a href="../../login.php" class="inline-block px-8 py-4 bg-blue-600 text-white font-black rounded-2xl shadow-xl shadow-blue-500/25 hover:scale-105 transition-all">
            Ke Halaman Login
        </a>
    </div>
</body>
</html>
