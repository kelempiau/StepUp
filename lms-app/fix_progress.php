<?php
// fix_progress.php - Fix progress tracking bug
require_once 'config/db.php';

echo "<pre>";
echo "Fixing progress table...\n";

try {
    // 1. Clean up duplicate progress entries
    // We want to keep the one that is completed (is_completed = 1) if available, 
    // otherwise just keep one.
    
    echo "Cleaning up duplicates...\n";
    
    // This query finds all groups of (user_id, module_slug) that have more than one entry
    // and deletes all but one. We prioritize keeping the completed one.
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS progress_temp AS
        SELECT * FROM progress
        WHERE id IN (
            SELECT MAX(id)
            FROM progress
            GROUP BY user_id, subject_slug, topic_slug, module_slug
        )
    ");
    
    // Actually, a better way to keep the completed one:
    $pdo->exec("TRUNCATE TABLE progress_temp");
    $pdo->exec("
        INSERT INTO progress_temp
        SELECT * FROM progress
        WHERE (user_id, subject_slug, topic_slug, module_slug, is_completed) IN (
            SELECT user_id, subject_slug, topic_slug, module_slug, MAX(is_completed)
            FROM progress
            GROUP BY user_id, subject_slug, topic_slug, module_slug
        )
        GROUP BY user_id, subject_slug, topic_slug, module_slug
    ");
    
    $pdo->exec("TRUNCATE TABLE progress");
    $pdo->exec("INSERT INTO progress SELECT * FROM progress_temp");
    $pdo->exec("DROP TABLE progress_temp");
    
    echo "✅ Duplicates cleaned.\n";

    // 2. Add Unique Index
    echo "Adding unique index...\n";
    try {
        $pdo->exec("ALTER TABLE progress ADD UNIQUE INDEX idx_user_module (user_id, subject_slug, topic_slug, module_slug)");
        echo "✅ Unique index added.\n";
    } catch (Exception $e) {
        echo "⚠️  Unique index might already exist or error: " . $e->getMessage() . "\n";
    }

    echo "\n🚀 Done! Progress tracking should be fixed now.";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
echo "</pre>";
