<?php
// fix_all_progress.php
require_once 'config/db.php';

echo "Memulai sinkronisasi dan perbaikan data...\n<br>";

// 1. Ambil semua progress
$progress = $pdo->query("SELECT * FROM progress")->fetchAll(PDO::FETCH_ASSOC);

// 2. Ambil semua module, topic, subject yang asli
$modules = $pdo->query("SELECT m.id, m.slug as m_slug, t.slug as t_slug, s.slug as s_slug 
                        FROM modules m
                        JOIN topics t ON m.topic_id = t.id
                        JOIN subjects s ON t.subject_id = s.id")->fetchAll(PDO::FETCH_ASSOC);

$updated = 0;
foreach ($progress as $p) {
    // Bersihkan karakter aneh pada progress saat ini
    $c_s = preg_replace('/[^a-z0-9]/', '', strtolower($p['subject_slug']));
    $c_t = preg_replace('/[^a-z0-9]/', '', strtolower($p['topic_slug']));
    $c_m = preg_replace('/[^a-z0-9]/', '', strtolower($p['module_slug']));
    
    // Cari kecocokan di master data module
    $matched = null;
    foreach ($modules as $m) {
        $m_s = preg_replace('/[^a-z0-9]/', '', strtolower($m['s_slug']));
        $m_t = preg_replace('/[^a-z0-9]/', '', strtolower($m['t_slug']));
        $m_m = preg_replace('/[^a-z0-9]/', '', strtolower($m['m_slug']));
        
        if ($m_s === $c_s && $m_t === $c_t && $m_m === $c_m) {
            $matched = $m;
            break;
        }
    }
    
    // Jika cocok namun slug dari progress table tidak sama persis (misal hilang & dsb), kita repair!
    if ($matched && ($p['subject_slug'] !== $matched['s_slug'] || $p['topic_slug'] !== $matched['t_slug'] || $p['module_slug'] !== $matched['m_slug'])) {
        $up = $pdo->prepare("UPDATE progress SET subject_slug = ?, topic_slug = ?, module_slug = ? WHERE id = ?");
        $up->execute([$matched['s_slug'], $matched['t_slug'], $matched['m_slug'], $p['id']]);
        $updated++;
    }
}

echo "Berhasil memperbaiki dan menyinkronkan {$updated} data progress dengan struktur database terbaru!\n<br>";
echo "Silakan kembali ke dashboard.";
