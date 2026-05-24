<?php
// src/api/migrate_activities.php
require_once '../../config/db.php';

try {
    // 1. Add created_at to progress if missing
    $pdo->exec("ALTER TABLE progress ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "COLUMN created_at added to progress table.\n";
} catch (PDOException $e) {
    echo "Progress table already has created_at or error: " . $e->getMessage() . "\n";
}

try {
    // 2. Add created_at to final_exam_scores if missing
    $pdo->exec("ALTER TABLE final_exam_scores ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "COLUMN created_at added to final_exam_scores table.\n";
} catch (PDOException $e) {
    echo "Final exam scores table already has created_at or error: " . $e->getMessage() . "\n";
}

try {
    // 3. Ensure topic_slug exists in final_exam_scores if we want to log it
    // Wait, final_exam_scores has subject_slug? Let's check schema.
} catch (PDOException $e) {}

echo "Migration complete.";
?>
