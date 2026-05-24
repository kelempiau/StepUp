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
                
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $fileTmpPath);
                finfo_close($finfo);
                
                if (in_array($fileExtension, $allowedExtensions) && strpos($mime, 'image/') === 0) {
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = '../../uploads/profile_pics/';
                    $dest_path = $uploadFileDir . $newFileName;
                    
                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        $profile_pic = $newFileName;
                    }
                } else {
                    $error = "File profil harus berupa gambar yang valid (JPG, PNG, GIF).";
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
            header("Location: settings.php?status=saved");
            exit;
        } catch (PDOException $e) {
            $error = "Gagal memperbarui: " . $e->getMessage();
        }
    }
}

// Re-fetch admin data for the sidebar and header
$stmtHeader = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmtHeader->execute([$_SESSION['user_id']]);
$admin_data = $stmtHeader->fetch();
?>
<!DOCTYPE html>
<html lang="id" class="dark:bg-[#060b1d]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setelan Akun - Admin StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #1e293b; }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .dark .glass-card {
            background: rgba(10, 17, 40, 0.7);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(30, 58, 138, 0.2);
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <script src="../../assets/js/theme.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../../assets/js/auth.js"></script>
</head>
<body class="bg-slate-50/30 text-slate-800 dark:bg-[#060b1d] dark:text-slate-200 transition-colors duration-300 font-sans h-screen flex overflow-hidden">

    <!-- Sidebar -->
    <?php include 'inc_sidebar.template.html'; ?>

    <!-- Main Content -->
    <main class="flex-1 flex flex-col h-full relative overflow-hidden">
        
        <!-- Mobile Header -->
        <header class="md:hidden bg-white/80 dark:bg-[#0a1128]/80 backdrop-blur-xl border-b border-blue-50 dark:border-blue-900/20 p-5 flex justify-between items-center z-30">
            <span class="font-bold text-lg text-blue-600">AdminPanel</span>
            <button onclick="toggleSidebar()" class="text-slate-500 p-2 hover:bg-slate-50 dark:hover:bg-slate-800 rounded-xl transition">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </header>

        <!-- Dynamic Page Header -->
        <div class="p-6 md:p-10 border-b border-slate-100 dark:border-blue-900/10 flex flex-col md:flex-row md:items-center justify-between gap-6 glass-card z-10">
            <div class="flex items-center space-x-6">
                <div class="w-12 h-12 rounded-2xl bg-slate-900 dark:bg-blue-600 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i class="fas fa-gear text-xl"></i>
                </div>
                <div>
                    <div class="flex items-center space-x-2 text-[10px] font-black uppercase tracking-widest text-blue-600 dark:text-blue-400 mb-1">
                        <i class="fas fa-shield-halved mr-1"></i>
                        <span>System Settings</span>
                    </div>
                    <h1 class="text-3xl font-black text-slate-900 dark:text-white tracking-tight leading-none">Setelan Akun</h1>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <div id="realtimeClock" class="hidden md:flex items-center space-x-3 px-5 py-3 glass-card rounded-2xl shadow-sm tracking-widest border-slate-100 dark:border-blue-900/20">
                    <i class="far fa-clock text-blue-600"></i>
                    <span id="clockDisplay" class="text-xs font-black text-slate-600 dark:text-slate-400 tabular-nums uppercase">00:00:00</span>
                </div>
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-6 md:p-10 custom-scrollbar pb-32 animate__animated animate__fadeIn">
            <div class="max-w-6xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-10">
                    
                    <!-- Profile Form Section -->
                    <div class="lg:col-span-8 space-y-10">
                        <div class="glass-card rounded-[3rem] p-10 md:p-12 shadow-2xl shadow-blue-500/[0.03]">
                            <div class="flex items-center space-x-4 mb-12">
                                <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center border border-blue-100 dark:border-blue-900/30">
                                    <i class="fas fa-user-pen"></i>
                                </div>
                                <h2 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight">Informasi Personal</h2>
                            </div>

                            <form method="POST" enctype="multipart/form-data" class="space-y-10">
                                <!-- Photo Upload Area -->
                                <div class="flex flex-col md:flex-row items-center gap-10 p-8 bg-slate-50/50 dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800">
                                    <div class="relative group">
                                        <div class="w-32 h-32 rounded-[2.5rem] overflow-hidden shadow-2xl border-4 border-white dark:border-slate-800 relative z-10 transition-all duration-500 group-hover:scale-105 group-hover:rotate-3" id="profile_preview_container">
                                            <?php if($user['profile_pic'] && file_exists('../../uploads/profile_pics/' . $user['profile_pic'])): ?>
                                                <img src="../../uploads/profile_pics/<?php echo $user['profile_pic']; ?>" class="w-full h-full object-cover" id="profile_preview">
                                            <?php else: ?>
                                                <div class="w-full h-full bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center text-5xl font-black text-white" id="profile_placeholder">
                                                    <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <button type="button" onclick="document.getElementById('profile_pic_input').click()" 
                                            class="absolute -bottom-2 -right-2 w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center shadow-xl border-4 border-white dark:border-[#0a1128] z-20 hover:bg-blue-700 hover:scale-110 transition-all">
                                            <i class="fas fa-camera text-sm"></i>
                                        </button>
                                    </div>
                                    <div class="flex-1 text-center md:text-left">
                                        <h3 class="text-lg font-black text-slate-800 dark:text-white mb-1 uppercase tracking-tight">Foto Profil</h3>
                                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest mb-6">PNG, JPG atau GIF. Maksimal 2MB.</p>
                                        <input type="file" name="profile_pic" id="profile_pic_input" class="hidden" accept="image/*" onchange="previewImage(this)">
                                        <button type="button" onclick="document.getElementById('profile_pic_input').click()" 
                                            class="px-6 py-3 bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 rounded-xl text-[10px] font-black uppercase tracking-widest hover:border-blue-500 transition-all">
                                            Pilih Berkas
                                        </button>
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Nama Lengkap</label>
                                        <div class="relative group">
                                            <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors">
                                                <i class="fas fa-id-card"></i>
                                            </div>
                                            <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required 
                                                class="w-full pl-14 pr-8 py-5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all shadow-inner">
                                        </div>
                                    </div>
                                    <div class="space-y-3">
                                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Alamat Email</label>
                                        <div class="relative group">
                                            <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors">
                                                <i class="fas fa-envelope"></i>
                                            </div>
                                            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required 
                                                class="w-full pl-14 pr-8 py-5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all shadow-inner">
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Ganti Password</label>
                                    <div class="relative group">
                                        <div class="absolute left-6 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors">
                                            <i class="fas fa-lock"></i>
                                        </div>
                                        <input type="password" name="new_password" placeholder="Biarkan kosong jika tidak ingin mengubah password" 
                                            class="w-full pl-14 pr-8 py-5 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all shadow-inner placeholder:text-slate-300 dark:placeholder:text-slate-700 text-sm">
                                    </div>
                                </div>

                                <div class="pt-6 border-t border-slate-100 dark:border-slate-800">
                                    <button type="submit" 
                                        class="w-full py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition transform hover:-translate-y-1 active:scale-95 uppercase tracking-widest text-xs flex items-center justify-center space-x-3">
                                        <i class="fas fa-save text-base"></i>
                                        <span>Simpan Perubahan Profil</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sidebar Info / Personalization -->
                    <div class="lg:col-span-4 space-y-10">
                        <!-- Appearance -->
                        <div class="glass-card rounded-[3rem] p-10 shadow-2xl shadow-blue-500/[0.03]">
                            <div class="flex items-center space-x-4 mb-10">
                                <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center border border-blue-100 dark:border-blue-900/30">
                                    <i class="fas fa-palette"></i>
                                </div>
                                <h2 class="text-xl font-black text-slate-900 dark:text-white tracking-tight">Personalisasi</h2>
                            </div>

                            <div class="space-y-6">
                                <div class="p-6 bg-slate-50/50 dark:bg-slate-900/50 rounded-3xl border border-slate-100 dark:border-slate-800">
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-4 ml-1">Tema Panel</p>
                                    <button onclick="toggleDarkMode()" 
                                        class="w-full flex items-center justify-between p-5 bg-white dark:bg-slate-800 rounded-2xl shadow-sm border border-slate-100 dark:border-slate-700 hover:border-blue-500 transition-all group overflow-hidden relative">
                                        <div class="absolute inset-y-0 left-0 w-1 bg-blue-600 transition-all opacity-0 group-hover:opacity-100"></div>
                                        <div class="flex items-center space-x-4">
                                            <div class="w-10 h-10 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center transition-transform group-hover:scale-110">
                                                <i class="fas fa-moon dark:hidden"></i>
                                                <i class="fas fa-sun hidden dark:block"></i>
                                            </div>
                                            <span class="text-sm font-black text-slate-700 dark:text-white uppercase tracking-widest">Toggle Mode</span>
                                        </div>
                                        <i class="fas fa-chevron-right text-[10px] text-slate-300 group-hover:translate-x-1 transition-transform"></i>
                                    </button>
                                </div>

                                <div class="p-8 bg-blue-50/30 dark:bg-blue-900/10 rounded-3xl border border-blue-50 dark:border-blue-900/20">
                                    <div class="flex items-start space-x-4">
                                        <div class="w-8 h-8 bg-blue-600 text-white rounded-lg flex items-center justify-center shrink-0 mt-1 shadow-lg shadow-blue-500/20">
                                            <i class="fas fa-circle-info text-xs"></i>
                                        </div>
                                        <div>
                                            <h4 class="text-xs font-black text-slate-800 dark:text-white uppercase tracking-widest mb-2">Informasi Keamanan</h4>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-bold leading-relaxed uppercase">Selalu gunakan password yang kuat dan unik untuk menjaga keamanan panel administrasi StepUp.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Account Stats -->
                        <div class="glass-card rounded-[3rem] p-10 shadow-2xl shadow-blue-500/[0.03]">
                             <h4 class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-8 text-center">Status Keanggotaan</h4>
                             <div class="flex flex-col items-center">
                                 <div class="w-20 h-20 bg-emerald-50 dark:bg-emerald-900/20 text-emerald-600 dark:text-emerald-400 rounded-[2rem] flex items-center justify-center text-3xl mb-4 border border-emerald-100 dark:border-emerald-900/30 shadow-inner">
                                     <i class="fas fa-check-shield"></i>
                                 </div>
                                 <h3 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight">Active Admin</h3>
                                 <p class="text-[10px] text-emerald-500 font-black uppercase tracking-[0.2em] mt-1">Full Access Control</p>
                                 
                                 <div class="w-full mt-10 pt-8 border-t border-slate-100 dark:border-slate-800 space-y-4">
                                     <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                                         <span class="text-slate-400">Terakhir Login</span>
                                         <span class="text-slate-600 dark:text-slate-300">Just Now</span>
                                     </div>
                                     <div class="flex justify-between items-center text-[10px] font-black uppercase tracking-widest">
                                         <span class="text-slate-400">IP Address</span>
                                         <span class="text-slate-600 dark:text-slate-300"><?php echo $_SERVER['REMOTE_ADDR']; ?></span>
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        // Live Clock
        function updateClock() {
            const now = new Date();
            const time = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
            const clockEl = document.getElementById('clockDisplay');
            if (clockEl) clockEl.textContent = time;
        }
        setInterval(updateClock, 1000);
        updateClock();

        function previewImage(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const container = document.getElementById('profile_preview_container');
                    let img = document.getElementById('profile_preview');
                    let placeholder = document.getElementById('profile_placeholder');
                    
                    if (placeholder) {
                        placeholder.remove();
                        img = document.createElement('img');
                        img.id = 'profile_preview';
                        img.className = 'w-full h-full object-cover';
                        container.appendChild(img);
                    }
                    
                    img.src = e.target.result;
                    img.classList.add('animate__animated', 'animate__pulse');
                    setTimeout(() => img.classList.remove('animate__pulse'), 1000);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Status Alerts
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status === 'saved') {
            showModernAlert({ 
                title: 'PROFIL TERUPDATE!', 
                text: 'Data personal dan preferensi akun Anda telah berhasil disimpan.', 
                icon: 'success' 
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        <?php if($error): ?>
            showModernAlert({ title: 'GAGAL!', text: '<?php echo $error; ?>', icon: 'error' });
        <?php endif; ?>
    </script>
</body>
</html>
