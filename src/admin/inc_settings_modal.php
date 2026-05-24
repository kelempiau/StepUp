<?php
// src/admin/inc_settings_modal.php
$admin_profile_id = $_SESSION['user_id'];
$stmt_admin = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_admin->execute([$admin_profile_id]);
$admin_profile_data = $stmt_admin->fetch();
?>
<!-- Admin Settings Modal (Premium Glassmorphism) -->
<div id="settingsModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 animate__animated animate__fadeIn">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-md" onclick="closeSettings()"></div>
    <div class="bg-white dark:bg-[#0a1128] rounded-[3rem] p-10 md:p-12 max-w-2xl w-full shadow-2xl border border-blue-100 dark:border-blue-900/10 relative z-10 animate__animated animate__zoomIn animate__faster max-h-[90vh] overflow-y-auto custom-scrollbar">
        
        <div class="flex items-center justify-between mb-10">
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-2xl flex items-center justify-center">
                    <i class="fas fa-user-gear text-xl"></i>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight uppercase leading-none">Setelan Admin</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-2">Personalize your admin experience</p>
                </div>
            </div>
            <button onclick="closeSettings()" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-slate-100 dark:bg-slate-800 text-slate-400 hover:text-red-500 transition-all">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form action="settings.php" method="POST" enctype="multipart/form-data" class="space-y-8">
            <div class="p-8 bg-slate-50/50 dark:bg-slate-900/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 flex items-center space-x-6 mb-8 relative overflow-hidden group">
                <div class="absolute -right-10 -top-10 w-32 h-32 bg-blue-500/[0.03] rounded-full blur-2xl"></div>
                
                <div class="relative group/avatar shrink-0">
                    <div class="w-24 h-24 rounded-2xl overflow-hidden shadow-xl border-4 border-white dark:border-slate-800 relative z-10 transition-transform group-hover/avatar:scale-105" id="modal_avatar_container">
                        <?php if($admin_profile_data['profile_pic'] && file_exists('../../uploads/profile_pics/' . $admin_profile_data['profile_pic'])): ?>
                            <img src="../../uploads/profile_pics/<?php echo $admin_profile_data['profile_pic']; ?>" class="w-full h-full object-cover" id="settingAdminPreview">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-blue-600 to-blue-700 flex items-center justify-center text-3xl font-black text-white" id="settingAdminInitial">
                                <?php echo strtoupper(substr($admin_profile_data['full_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="button" onclick="document.getElementById('setting_admin_pic').click()" 
                        class="absolute -bottom-2 -right-2 w-10 h-10 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg border-2 border-white dark:border-slate-800 z-20 hover:scale-110 transition-all">
                        <i class="fas fa-camera text-xs"></i>
                    </button>
                </div>
                
                <div class="relative z-10">
                    <p class="text-blue-500 dark:text-blue-400 text-[10px] font-black uppercase tracking-widest mb-1">Authenticated Admin</p>
                    <h3 class="text-xl font-black text-slate-900 dark:text-white leading-tight mb-2"><?php echo htmlspecialchars($admin_profile_data['full_name']); ?></h3>
                    <div class="flex items-center space-x-2 px-3 py-1 bg-white/50 dark:bg-slate-800 rounded-lg border border-slate-200/50 dark:border-slate-700 w-fit">
                        <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                        <span class="text-[9px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-widest">Active System Control</span>
                    </div>
                </div>
            </div>

            <input type="file" name="profile_pic" id="setting_admin_pic" class="hidden" accept="image/*" onchange="previewAdminImage(this)">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Nama Lengkap</label>
                    <div class="relative group">
                        <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin_profile_data['full_name']); ?>" required 
                            class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all shadow-inner">
                    </div>
                </div>
                <div class="space-y-2">
                    <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Username</label>
                    <div class="relative group opacity-60">
                        <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400">
                            <i class="fas fa-at text-sm"></i>
                        </div>
                        <input type="text" value="<?php echo htmlspecialchars($admin_profile_data['username']); ?>" disabled 
                            class="w-full pl-12 pr-6 py-4 bg-slate-100 dark:bg-slate-800/50 border border-slate-200 dark:border-slate-700 rounded-2xl text-slate-400 font-bold cursor-not-allowed">
                    </div>
                </div>
            </div>

            <div class="space-y-2">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Email Administrator</label>
                <div class="relative group">
                    <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors">
                        <i class="fas fa-envelope text-sm"></i>
                    </div>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($admin_profile_data['email']); ?>" required 
                        class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all shadow-inner">
                </div>
            </div>

            <div class="space-y-2 pt-6 border-t border-slate-100 dark:border-slate-800/50">
                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-2">Ganti Password (Opsional)</label>
                <div class="relative group">
                    <div class="absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 group-focus-within:text-blue-500 transition-colors">
                        <i class="fas fa-lock text-sm"></i>
                    </div>
                    <input type="password" name="new_password" placeholder="Biarkan kosong jika tidak berubah"
                        class="w-full pl-12 pr-6 py-4 bg-slate-50 dark:bg-slate-900/50 border border-slate-200 dark:border-slate-800 rounded-2xl outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 dark:text-white font-bold transition-all text-sm shadow-inner placeholder:text-slate-300 dark:placeholder:text-slate-700">
                </div>
            </div>

            <div class="p-6 bg-blue-50/30 dark:bg-blue-900/10 rounded-3xl border border-blue-100/50 dark:border-blue-900/20 flex items-center justify-between group/toggle">
                <div class="flex items-center space-x-4">
                    <div class="w-10 h-10 bg-white dark:bg-slate-800 text-blue-600 dark:text-blue-400 rounded-xl flex items-center justify-center shadow-sm border border-slate-100 dark:border-slate-700">
                        <i class="fas fa-moon dark:hidden"></i>
                        <i class="fas fa-sun hidden dark:block"></i>
                    </div>
                    <div>
                        <p class="font-black text-slate-800 dark:text-white text-xs uppercase tracking-tight leading-none">Visual Mode</p>
                        <p class="text-[9px] text-slate-500 dark:text-slate-400 font-bold uppercase tracking-widest mt-1">Dark/Light toggle</p>
                    </div>
                </div>
                <button type="button" onclick="toggleDarkMode()" 
                    class="w-14 h-8 bg-slate-200 dark:bg-blue-600 rounded-full relative p-1 transition-all duration-300 focus:outline-none">
                    <div class="absolute left-1 top-1 bg-white w-6 h-6 rounded-full shadow-md transition-all duration-300 transform translate-x-0 dark:translate-x-6 flex items-center justify-center overflow-hidden">
                        <div class="w-1 h-1 bg-slate-200 dark:bg-blue-200 rounded-full animate-pulse"></div>
                    </div>
                </button>
            </div>
            
            <div class="flex space-x-4 pt-6 border-t border-slate-100 dark:border-slate-800/50">
                <button type="button" onclick="closeSettings()" 
                    class="flex-1 py-5 text-slate-400 hover:text-slate-600 dark:hover:text-white font-black uppercase tracking-[0.2em] text-[10px] transition-all">
                    Batal
                </button>
                <button type="submit" 
                    class="flex-1 py-5 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition transform hover:-translate-y-1 active:scale-95 uppercase tracking-[0.2em] text-[10px] flex items-center justify-center space-x-3">
                    <i class="fas fa-save text-xs"></i>
                    <span>Simpan</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSettings() { 
        document.getElementById('settingsModal').classList.remove('hidden'); 
        document.body.style.overflow = 'hidden';
    }
    function closeSettings() { 
        const modal = document.getElementById('settingsModal');
        modal.classList.add('animate__zoomOut');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('animate__zoomOut');
            document.body.style.overflow = 'auto';
        }, 300);
    }
    function previewAdminImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const container = document.getElementById('modal_avatar_container');
                let preview = document.getElementById('settingAdminPreview');
                let initial = document.getElementById('settingAdminInitial');
                
                if (initial) {
                    initial.remove();
                    preview = document.createElement('img');
                    preview.id = 'settingAdminPreview';
                    preview.className = 'w-full h-full object-cover';
                    container.appendChild(preview);
                }
                
                if (preview) {
                    preview.src = e.target.result;
                    preview.classList.add('animate__animated', 'animate__pulse');
                    setTimeout(() => preview.classList.remove('animate__pulse'), 1000);
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
