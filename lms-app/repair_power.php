<?php
// repair_power.php - Robust Database Normalization
require_once 'config/db.php';
header('Content-Type: text/plain');

echo "Starting Power Repair...\n";

try {
    // 1. Clean Subjects
    $pdo->exec("UPDATE subjects SET slug = LOWER(TRIM(REPLACE(slug, ' ', '-'))) WHERE slug IS NOT NULL");
    echo "Subjects cleaned.\n";

    // 2. Clean Topics
    $pdo->exec("UPDATE topics SET slug = LOWER(TRIM(REPLACE(slug, ' ', '-'))) WHERE slug IS NOT NULL");
    // Handle the '&' issue specifically
    $pdo->exec("UPDATE topics SET slug = REPLACE(slug, '&', 'and') WHERE slug LIKE '%&%'");
    echo "Topics cleaned.\n";

    // 3. Clean Modules
    $pdo->exec("UPDATE modules SET slug = LOWER(TRIM(REPLACE(slug, ' ', '-'))) WHERE slug IS NOT NULL");
    echo "Modules cleaned.\n";

    // 4. Clean Progress (Crucial)
    // We only clean the slugs to be lowercase and no spaces, making them match the fuzzy matcher's expectations
    $pdo->exec("UPDATE progress SET 
        subject_slug = LOWER(TRIM(REPLACE(subject_slug, ' ', '-'))),
        topic_slug = LOWER(TRIM(REPLACE(topic_slug, ' ', '-'))),
        module_slug = LOWER(TRIM(REPLACE(module_slug, ' ', '-')))
    ");
    echo "Progress table cleaned.\n";

    echo "\nREPAIR COMPLETE. Please refresh your dashboard.";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
