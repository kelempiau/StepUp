<?php
// fix_progress_v3.php - Super Deep Progress Repair
require_once 'config/db.php';

echo "<pre>";
echo "🚀 [PROGRESS REPAIR V3] Starting deep sync...\n";

// 1. Fetch all modules to get "Canonical" slugs
$stmt = $pdo->query("
    SELECT m.slug as m_slug, t.slug as t_slug, s.slug as s_slug, m.id as m_id
    FROM modules m
    JOIN topics t ON m.topic_id = t.id
    JOIN subjects s ON t.subject_id = s.id
");
$canonical_mods = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "📦 Found " . count($canonical_mods) . " canonical modules.\n";

// 2. Fetch all progress entries
$stmt = $pdo->query("SELECT * FROM progress");
$all_progress = $stmt->fetchAll(PDO::FETCH_ASSOC);

$updated_count = 0;
$deleted_duplicates = 0;

foreach ($all_progress as $p) {
    $found = false;
    $p_s_clean = preg_replace('/[^a-z0-9]/', '', strtolower($p['subject_slug']));
    $p_t_clean = preg_replace('/[^a-z0-9]/', '', strtolower($p['topic_slug']));
    $p_m_clean = preg_replace('/[^a-z0-9]/', '', strtolower($p['module_slug']));

    foreach ($canonical_mods as $c) {
        $c_s_clean = preg_replace('/[^a-z0-9]/', '', strtolower($c['s_slug']));
        $c_t_clean = preg_replace('/[^a-z0-9]/', '', strtolower($c['t_slug']));
        $c_m_clean = preg_replace('/[^a-z0-9]/', '', strtolower($c['m_slug']));

        if ($p_s_clean === $c_s_clean && $p_t_clean === $c_t_clean && $p_m_clean === $c_m_clean) {
            // Check if slugs are exactly different
            if ($p['subject_slug'] !== $c['s_slug'] || $p['topic_slug'] !== $c['t_slug'] || $p['module_slug'] !== $c['m_slug']) {
                echo "   ✅ Fixing slugs for Progress ID {$p['id']}: [{$p['module_slug']}] -> [{$c['m_slug']}]\n";
                $upd = $pdo->prepare("UPDATE progress SET subject_slug = ?, topic_slug = ?, module_slug = ? WHERE id = ?");
                $upd->execute([$c['s_slug'], $c['t_slug'], $c['m_slug'], $p['id']]);
                $updated_count++;
            }
            $found = true;
            break;
        }
    }
}

// 3. Merge Duplicates (Same user, same module slugs)
echo "\n🔍 Checking for duplicates after sync...\n";
$stmt = $pdo->query("SELECT user_id, subject_slug, topic_slug, module_slug, COUNT(*) as cnt, MAX(is_completed) as max_comp FROM progress GROUP BY user_id, subject_slug, topic_slug, module_slug HAVING cnt > 1");
$dupes = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($dupes as $d) {
    echo "   🗑️ Merging " . $d['cnt'] . " entries for user {$d['user_id']} module [{$d['module_slug']}] (Keep completed=" . $d['max_comp'] . ")\n";
    // Delete all, then re-insert one canonical one
    $del = $pdo->prepare("DELETE FROM progress WHERE user_id = ? AND subject_slug = ? AND topic_slug = ? AND module_slug = ?");
    $del->execute([$d['user_id'], $d['subject_slug'], $d['topic_slug'], $d['module_slug']]);
    
    $ins = $pdo->prepare("INSERT INTO progress (user_id, subject_slug, topic_slug, module_slug, is_completed, completed_step, created_at) VALUES (?, ?, ?, ?, ?, 1, NOW())");
    $ins->execute([$d['user_id'], $d['subject_slug'], $d['topic_slug'], $d['module_slug'], $d['max_comp']]);
    $deleted_duplicates += ($d['cnt'] - 1);
}

echo "\n✨ Cleanup finished!";
echo "\n   - Updated: $updated_count";
echo "\n   - Merged: $deleted_duplicates";
echo "</pre>";
?>
