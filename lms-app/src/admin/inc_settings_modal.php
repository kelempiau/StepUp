<?php
// src/admin/inc_settings_modal.php
$admin_profile_id = $_SESSION['user_id'];
$stmt_admin = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_admin->execute([$admin_profile_id]);
$admin_profile_data = $stmt_admin->fetch();
?>
<!-- Admin Settings Modal -->
<div id="settingsModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white dark:bg-[#0a1128] rounded-[2.5rem] p-10 max-w-2xl w-full shadow-2xl border border-blue-100 dark:border-blue-900/30 overflow-y-auto max-h-[90vh] custom-scrollbar">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h3 class="text-2xl font-black text-slate-800 dark:text-white">Setelan Admin</h3>
                <p class="text-slate-500 dark:text-slate-400 font-medium text-sm">Kelola profil administrator dan preferensi sistem.</p>
            </div>
            <button onclick="closeSettings()" class="w-12 h-12 flex items-center justify-center rounded-2xl bg-slate-50 dark:bg-blue-900/20 text-slate-400 hover:text-red-500 transition">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        
        <form action="settings.php" method="POST" enctype="multipart/form-data">
            <div class="p-8 bg-blue-50/50 dark:bg-blue-900/10 rounded-[2rem] border border-blue-50 dark:border-blue-900/20 flex items-center space-x-6 mb-8">
                <div class="relative group">
                    <?php if($admin_profile_data['profile_pic']): ?>
                        <img src="../../uploads/profile_pics/<?php echo $admin_profile_data['profile_pic']; ?>" class="w-24 h-24 rounded-2xl object-cover shadow-xl border-2 border-blue-500" id="settingAdminPreview">
                    <?php else: ?>
                        <div class="w-24 h-24 rounded-2xl bg-gradient-to-br from-blue-600 to-indigo-600 flex items-center justify-center text-3xl font-bold text-white shadow-xl shadow-blue-500/20" id="settingAdminInitial">
                            <?php echo strtoupper(substr($admin_profile_data['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    <button type="button" onclick="document.getElementById('setting_admin_pic').click()" class="absolute inset-0 flex items-center justify-center bg-black/40 rounded-2xl opacity-0 group-hover:opacity-100 transition duration-200 cursor-pointer">
                        <i class="fas fa-camera text-white text-xl"></i>
                    </button>
                </div>
                <div>
                    <p class="text-blue-500 dark:text-blue-400 text-[10px] font-black uppercase tracking-widest mb-1">Administrator Profile</p>
                    <h3 class="text-xl font-bold text-slate-900 dark:text-white"><?php echo htmlspecialchars($admin_profile_data['full_name']); ?></h3>
                    <button type="button" onclick="document.getElementById('setting_admin_pic').click()" class="mt-2 text-[10px] font-black text-blue-600 uppercase tracking-widest hover:underline">Ubah Foto</button>
                </div>
            </div>

            <input type="file" name="profile_pic" id="setting_admin_pic" class="hidden" accept="image/*" onchange="previewAdminImage(this)">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Nama Lengkap</label>
                    <input type="text" name="full_name" value="<?php echo htmlspecialchars($admin_profile_data['full_name']); ?>" required 
                        class="w-full px-6 py-4 bg-slate-50 dark:bg-blue-950 border border-blue-100/30 dark:border-blue-900/30 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($admin_profile_data['username']); ?>" disabled 
                        class="w-full px-6 py-4 bg-slate-100 dark:bg-blue-900/50 border-none rounded-2xl text-slate-400 font-bold cursor-not-allowed">
                </div>
            </div>

            <div class="mb-8">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Email Administrator</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($admin_profile_data['email']); ?>" required 
                    class="w-full px-6 py-4 bg-slate-50 dark:bg-blue-950 border border-blue-100/30 dark:border-blue-900/30 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-800 dark:text-white">
            </div>

            <div class="mb-8 pt-8 border-t border-blue-50 dark:border-blue-900/20">
                <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 ml-1">Ganti Password (Opsional)</label>
                <input type="password" name="new_password" placeholder="Min. 6 karakter"
                    class="w-full px-6 py-4 bg-slate-50 dark:bg-blue-950 border border-blue-100/30 dark:border-blue-900/30 rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-800 dark:text-white text-sm">
            </div>

            <div class="flex items-center justify-between p-6 bg-blue-50/50 dark:bg-blue-900/20 rounded-3xl mb-10 border border-blue-100 dark:border-blue-900/30">
                <div>
                    <p class="font-bold text-slate-800 dark:text-white text-sm">Mode Gelap (Dark Mode)</p>
                    <p class="text-[10px] text-slate-500 dark:text-slate-400 font-black uppercase tracking-widest mt-1">Gunakan tema gelap</p>
                </div>
                <button type="button" onclick="toggleDarkMode()" class="w-12 h-6 bg-slate-300 rounded-full relative transition duration-200 focus:outline-none dark:bg-blue-600">
                    <div class="absolute left-1 top-1 bg-white w-4 h-4 rounded-full transition-transform duration-200 transform translate-x-0 dark:translate-x-6"></div>
                </button>
            </div>
            
            <div class="flex space-x-4">
                <button type="button" onclick="closeSettings()" class="flex-1 py-4 text-slate-400 hover:text-slate-600 dark:hover:text-white font-bold uppercase tracking-widest text-xs transition">Batal</button>
                <button type="submit" class="flex-1 py-4 bg-blue-600 text-white font-black rounded-2xl hover:bg-blue-700 shadow-xl shadow-blue-500/30 transition uppercase tracking-widest text-xs">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSettings() { document.getElementById('settingsModal').classList.remove('hidden'); }
    function closeSettings() { document.getElementById('settingsModal').classList.add('hidden'); }
    function previewAdminImage(input) {
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const preview = document.getElementById('settingAdminPreview');
                const initial = document.getElementById('settingAdminInitial');
                if (preview) {
                    preview.src = e.target.result;
                } else if (initial) {
                    const newImg = document.createElement('img');
                    newImg.src = e.target.result;
                    newImg.id = 'settingAdminPreview';
                    newImg.className = 'w-24 h-24 rounded-2xl object-cover shadow-xl border-2 border-blue-500';
                    initial.parentNode.replaceChild(newImg, initial);
                }
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>
