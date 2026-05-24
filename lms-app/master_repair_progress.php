<?php
// master_repair_progress.php
require_once 'config/db.php';

echo "<pre>";
echo "🚀 [MASTER REPAIR] Starting database consistency check...\n";

function cleanSlug($slug) {
    if (!$slug) return '';
    $new = trim($slug);
    // Remove trailing hyphens
    $new = rtrim($new, '-');
    // Remove any special chars that might have slipped in
    $new = trim($new);
    return $new;
}

$tables_to_clean = [
    'subjects' => ['slug'],
    'topics' => ['slug'],
    'modules' => ['slug', 'topic_id'], // but mainly slug
    'progress' => ['subject_slug', 'topic_slug', 'module_slug'],
    'quiz_scores' => ['subject_slug', 'topic_slug', 'module_slug'],
    'activity_log' => ['action'] // check for slugs in strings? maybe not necessary for logic
];

foreach ($tables_to_clean as $table => $cols) {
    echo "\n📂 Processing: $table\n";
    $stmt = $pdo->query("SELECT * FROM $table");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($items as $item) {
        $updates = [];
        $params = [];
        
        foreach ($cols as $col) {
            $old = $item[$col];
            if ($col === 'action') continue; // specialized logic
            
            $new = cleanSlug($old);
            if ($old !== $new) {
                $updates[] = "$col = ?";
                $params[] = $new;
                echo "   ✅ $table (ID {$item['id']}) $col: ['$old'] -> ['$new']\n";
            }
        }
        
        if (!empty($updates)) {
            $params[] = $item['id'];
            $sql = "UPDATE $table SET " . implode(', ', $updates) . " WHERE id = ?";
            $upd = $pdo->prepare($sql);
            $upd->execute($params);
        }
    }
}

// Additional logic: Handle the Geometri-&-Trigonometri case specifically
echo "\n🔍 Checking for encoded '&' characters...\n";
$stmt = $pdo->prepare("UPDATE topics SET slug = REPLACE(slug, ' & ', '-') WHERE slug LIKE '% & %'");
$stmt->execute();

$stmt = $pdo->prepare("UPDATE progress SET topic_slug = REPLACE(topic_slug, ' & ', '-') WHERE topic_slug LIKE '% & %'");
$stmt->execute();

echo "\n✨ [MASTER REPAIR] Completed successfully!";
echo "\n💡 Please Refresh your Dashboard to see the changes.";
echo "</pre>";
?>
