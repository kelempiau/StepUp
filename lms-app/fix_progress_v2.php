<?php
// fix_progress_v2.php
require_once 'config/db.php';

try {
    echo "Starting progress repair...\n";

    // 1. Fill in missing subject/topic slugs based on module_slug
    $stmt = $pdo->query("SELECT p.id, p.module_slug, m.topic_id, t.slug as t_slug, s.slug as s_slug 
                         FROM progress p
                         JOIN modules m ON p.module_slug = m.slug
                         JOIN topics t ON m.topic_id = t.id
                         JOIN subjects s ON t.subject_id = s.id
                         WHERE p.subject_slug = '' OR p.topic_slug = ''");
    
    $updates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Found " . count($updates) . " records to repair.\n";

    foreach ($updates as $row) {
        $up = $pdo->prepare("UPDATE progress SET subject_slug = ?, topic_slug = ? WHERE id = ?");
        $up->execute([$row['s_slug'], $row['t_slug'], $row['id']]);
    }

    // 2. Remove duplicates by keeping the one with is_completed = 1
    // We group by user, subject, topic, module and keep the MAX(is_completed)
    // This is a bit complex in one query, so we'll do it carefully.
    
    echo "Consolidating duplicates...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS progress_temp AS 
                SELECT user_id, subject_slug, topic_slug, module_slug, 
                       MAX(is_completed) as is_completed, 
                       MAX(completed_step) as completed_step, 
                       MAX(created_at) as created_at
                FROM progress 
                GROUP BY user_id, subject_slug, topic_slug, module_slug");
    
    $pdo->exec("TRUNCATE TABLE progress");
    $pdo->exec("INSERT INTO progress (user_id, subject_slug, topic_slug, module_slug, is_completed, completed_step, created_at) 
                SELECT * FROM progress_temp");
    $pdo->exec("DROP TABLE progress_temp");

    echo "Repair complete!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
