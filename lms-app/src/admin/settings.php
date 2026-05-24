<?php
// src/admin/settings.php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$success = "";
$error = "";

// 1. Fetch Current Admin Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];

    if (empty($full_name) || empty($email)) {
        $error = "Nama dan Email wajib diisi.";
    } else {
        try {
            // Profile Picture Upload
            $profile_pic = $user['profile_pic'];
            if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
                $fileName = $_FILES['profile_pic']['name'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = '../../uploads/profile_pics/';
                    $dest_path = $uploadFileDir . $newFileName;
                    
                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        $profile_pic = $newFileName;
                    }
                }
            }

            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, profile_pic = ? WHERE id = ?");
                $update->execute([$full_name, $email, $hashed, $profile_pic, $user_id]);
            } else {
                $update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_pic = ? WHERE id = ?");
                $update->execute([$full_name, $email, $profile_pic, $user_id]);
            }
            $_SESSION['full_name'] = $full_name;
            $success = "Profil admin berhasil diperbarui!";
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            header("Location: " . ($_SERVER['HTTP_REFERER'] ?: 'dashboard.php'));
            exit;
        } catch (PDOException $e) {
            $error = "Gagal memperbarui: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setelan Admin - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/auth.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
    <body class="bg-blue-50/50 text-slate-800 dark:bg-[#060b1d] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">
        <!-- Sidebar -->
        <?php include 'inc_sidebar.template.html'; ?>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col h-full relative overflow-hidden">
            <!-- Dynamic Page Header -->
            <div class="p-6 md:p-10 border-b border-blue-50 dark:border-blue-900/10 flex flex-col md:flex-row md:items-center justify-between gap-6 bg-white dark:bg-[#0a1128]/50 backdrop-blur-md">
                <div class="flex items-center space-x-6">
                    <div class="space-y-1">
                        <h2 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none mb-2">Setelan Akun</h2>
                        <p class="text-sm text-slate-400 font-medium">Kelola profil personal dan preferensi panel admin Anda.</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <div id="realtimeClockContainer" class="hidden md:flex items-center space-x-3 px-5 py-2.5 bg-white dark:bg-[#0a1128] border border-blue-100 dark:border-blue-900/20 rounded-2xl shadow-sm tracking-widest">
                        <i class="far fa-clock text-blue-600 animate-pulse"></i>
                        <span id="clockDisplay" class="text-sm font-black text-slate-600 dark:text-slate-400 tabular-nums uppercase">00:00:00</span>
                    </div>
                </div>
            </div>

            <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar pb-32">
                <div class="max-w-6xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-10">
                    <!-- Profile Section -->
                    <div class="lg:col-span-2 space-y-10">
                        <div class="bg-white dark:bg-[#0a1128] p-10 rounded-[3.5rem] shadow-sm border border-slate-50 dark:border-blue-900/10">
                            <h2 class="text-2xl font-black mb-10 text-slate-900 dark:text-white flex items-center tracking-tight">
                                <i class="fas fa-user-circle mr-4 text-blue-600"></i> Informasi Profil
                            </h2>
                            
                            <div class="flex items-center space-x-8 mb-12 p-8 bg-blue-50/50 dark:bg-blue-900/20 rounded-[2.5rem] border border-blue-50 dark:border-blue-900/10">
                                <div class="relative group">
                                    <div class="w-28 h-28 rounded-3xl overflow-hidden shadow-2xl border-4 border-white dark:border-blue-800 relative z-10 transition-transform group-hover:scale-105">
                                        <?php if($user['profile_pic'] && file_exists('../../uploads/profile_pics/' . $user['profile_pic'])): ?>
                                            <img src="../../uploads/profile_pics/<?php echo $user['profile_pic']; ?>" class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-full bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-4xl font-black text-white">
                                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <button onclick="document.getElementById('profile_pic_input').click()" class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                            <i class="fas fa-camera text-white text-2xl"></i>
                                        </button>
                                    </div>
                                    <div class="absolute -bottom-2 -right-2 w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg border-2 border-white dark:border-blue-900 z-20">
                                        <i class="fas fa-pen text-[10px]"></i>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <p class="text-[10px] text-blue-500 font-black uppercase tracking-[0.2em] mb-2">Administrator</p>
                                    <h3 class="text-2xl font-black text-slate-800 dark:text-white mb-2"><?php echo htmlspecialchars($user['full_name']); ?></h3>
                                    <p class="text-xs text-slate-400 font-medium mb-4"><?php echo htmlspecialchars($user['email']); ?></p>
                                    <button type="button" onclick="document.getElementById('profile_pic_input').click()" class="text-[10px] font-black text-white bg-blue-600 px-5 py-2 rounded-lg uppercase tracking-widest hover:bg-blue-700 transition">Ubah Foto Profil</button>
                                </div>
                            </div>

                            <form method="POST" enctype="multipart/form-data" class="space-y-8">
                                <input type="file" name="profile_pic" id="profile_pic_input" class="hidden" accept="image/*" onchange="previewImage(this)">
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-3">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest px-2">Nama Lengkap</label>
                                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required class="w-full px-8 py-5 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all text-lg shadow-inner">
                                    </div>
                                    <div class="space-y-3">
                                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest px-2">Alamat Email</label>
                                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required class="w-full px-8 py-5 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all text-lg shadow-inner">
                                    </div>
                                </div>
                                <div class="space-y-3">
                                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest px-2">Ganti Password (Biarkan kosong jika tidak berubah)</label>
                                    <input type="password" name="new_password" placeholder="••••••••••••" class="w-full px-8 py-5 bg-slate-50 dark:bg-[#060b1d] border-none rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all text-lg shadow-inner placeholder:text-slate-300 dark:placeholder:text-slate-800">
                                </div>
                                <div class="pt-4">
                                    <button type="submit" class="w-full py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 transition shadow-2xl shadow-blue-500/40 uppercase tracking-widest text-xs">Perbarui Informasi Profil</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Appearance Section -->
                    <div class="space-y-10">
                        <div class="bg-white dark:bg-[#0a1128] p-10 rounded-[3.5rem] shadow-sm border border-slate-50 dark:border-blue-900/10">
                            <h2 class="text-2xl font-black mb-10 text-slate-900 dark:text-white flex items-center tracking-tight">
                                <i class="fas fa-palette mr-4 text-blue-600"></i> Personalisasi
                            </h2>
                            <div class="p-8 bg-blue-50/50 dark:bg-blue-600/10 rounded-[2.5rem] border border-blue-50 dark:border-blue-900/10 text-center">
                                <p class="font-black text-slate-800 dark:text-white mb-2 uppercase tracking-widest text-xs">Visual Mode</p>
                                <p class="text-[10px] text-slate-500 dark:text-slate-400 mb-8 uppercase font-bold tracking-widest">Atur kenyamanan mata anda</p>
                                <button onclick="toggleDarkMode()" class="w-full flex items-center justify-between p-6 bg-white dark:bg-[#060b1d] rounded-2xl border border-blue-100 dark:border-blue-900/20 hover:border-blue-600 hover:shadow-xl hover:shadow-blue-500/10 transition-all group overflow-hidden relative">
                                    <div class="absolute inset-y-0 left-0 w-1 bg-blue-600 opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                    <span class="text-sm font-black text-slate-700 dark:text-white uppercase tracking-widest dark:hidden">Terang</span>
                                    <span class="text-sm font-black text-slate-700 dark:text-white uppercase tracking-widest hidden dark:inline">Gelap</span>
                                    <div class="w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center">
                                        <i class="fas fa-moon dark:text-white transition-transform group-hover:rotate-12"></i>
                                    </div>
                                </button>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </main>
    </div>
    </div>
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.querySelector('img.w-28.h-28') || document.querySelector('.w-28.h-28.rounded-3xl');
                    if (img.tagName === 'IMG') {
                        img.src = e.target.result;
                    } else {
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.className = 'w-24 h-24 rounded-2xl object-cover shadow-xl border-2 border-blue-500';
                        img.parentNode.replaceChild(newImg, img);
                    }
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
