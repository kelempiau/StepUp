<?php
// src/views/terms.php - PREMIUM TERMS & CONDITIONS PAGE
session_start();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syarat & Ketentuan - StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .glass {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }
        .dark .glass {
            background: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .custom-scrollbar::-webkit-scrollbar { width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-[#060b1d] transition-colors duration-300">

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-blue-500/10 rounded-full blur-[100px]"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-indigo-500/10 rounded-full blur-[100px]"></div>
    </div>

    <main class="relative z-10 min-h-screen flex flex-col items-center py-20 px-4">
        <div class="max-w-4xl w-full">
            <!-- Header -->
            <div class="text-center mb-12">
                <div class="w-16 h-16 bg-blue-600 rounded-[2rem] mx-auto flex items-center justify-center text-white shadow-2xl shadow-blue-500/40 mb-8 overflow-hidden relative group">
                    <div class="absolute inset-0 bg-gradient-to-br from-white/20 to-transparent"></div>
                    <i class="fas fa-scroll text-2xl relative z-10 transition-transform group-hover:rotate-12"></i>
                </div>
                <h1 class="text-4xl font-black text-slate-900 dark:text-white tracking-tight mb-4">Syarat & Ketentuan</h1>
                <p class="text-slate-500 dark:text-slate-400 font-medium">Harap baca dengan teliti sebelum menggunakan platform StepUp.</p>
            </div>

            <!-- Content Card -->
            <div class="glass rounded-[3rem] p-10 md:p-16 shadow-2xl overflow-hidden relative">
                <div class="absolute top-0 left-0 w-2 h-full bg-blue-600"></div>
                
                <div class="prose prose-slate dark:prose-invert max-w-none space-y-10 text-slate-600 dark:text-slate-400 leading-relaxed font-medium">
                    <section>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-4 mb-6">
                            <span class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-xl flex items-center justify-center text-sm">01</span>
                            Penerimaan Ketentuan
                        </h2>
                        <p>Dengan mengakses atau menggunakan platform pembelajaran StepUp, Anda setuju untuk terikat oleh Syarat dan Ketentuan ini. Jika Anda tidak menyetujui bagian mana pun dari ketentuan ini, Anda tidak diperbolehkan untuk menggunakan layanan kami.</p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-4 mb-6">
                            <span class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-xl flex items-center justify-center text-sm">02</span>
                            Akun Pengguna
                        </h2>
                        <p>Anda bertanggung jawab untuk menjaga kerahasiaan informasi akun dan kata sandi Anda. Anda setuju untuk menerima tanggung jawab atas semua aktivitas yang terjadi di bawah akun Anda. Satu akun hanya boleh digunakan oleh satu orang yang terdaftar.</p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-4 mb-6">
                            <span class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-xl flex items-center justify-center text-sm">03</span>
                            Hak Kekayaan Intelektual
                        </h2>
                        <p>Semua konten yang tersedia di platform ini, termasuk namun tidak terbatas pada video, materi teks, soal kuis, kode sumber, dan desain grafis adalah milik eksklusif StepUp. Dilarang keras menyalin, mendistribusikan, atau menggunakan kembali materi tanpa izin tertulis.</p>
                    </section>

                    <section>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-4 mb-6">
                            <span class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-xl flex items-center justify-center text-sm">04</span>
                            Kebijakan Progres Belajar
                        </h2>
                        <p>Progres belajar Anda direkam secara real-time. Untuk menyelesaikan sebuah modul, Anda diharuskan mencapai skor minimal yang telah ditentukan (70%) pada kuis terkait. Sertifikat digital akan diterbitkan secara otomatis setelah seluruh kuis dalam satu topik diselesaikan dengan hasil yang memenuhi kriteria lulus.</p>
                    </section> section>

                    <section>
                        <h2 class="text-2xl font-black text-slate-900 dark:text-white flex items-center gap-4 mb-6">
                            <span class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 text-blue-600 rounded-xl flex items-center justify-center text-sm">05</span>
                            Batasan Tanggung Jawab
                        </h2>
                        <p>StepUp tidak menjamin bahwa layanan akan selalu tersedia tanpa gangguan. Kami terus melakukan pemeliharaan server secara berkala untuk memastikan kenyamanan belajar Anda. Kami tidak bertanggung jawab atas kerugian data yang disebabkan oleh kesalahan pengguna atau gangguan jaringan pihak ketiga.</p>
                    </section>
                </div>

                <div class="mt-16 pt-10 border-t border-slate-100 dark:border-slate-800 flex flex-col md:flex-row items-center justify-between gap-8">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest italic">Terakhir diperbarui: 21 Februari 2026</p>
                    <a href="../../index.php" class="px-10 py-4 bg-blue-600 text-white font-black rounded-2xl shadow-xl shadow-blue-500/30 hover:scale-105 active:scale-95 transition-all uppercase tracking-widest text-xs">SAYA MENGERTI</a>
                </div>
            </div>
        </div>
    </main>

    <?php include_once __DIR__ . '/../inc_chat_button.php'; ?>
<?php include_once __DIR__ . '/../inc_chat_window.php'; ?>
</body>
</html>

