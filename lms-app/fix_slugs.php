<?php
// fix_slugs.php
require_once 'config/db.php';

echo "<pre>";
echo "Starting slug cleanup...\n";

$tables = ['subjects', 'topics', 'modules'];

foreach ($tables as $table) {
    echo "Processing table: $table...\n";
    $stmt = $pdo->query("SELECT id, slug FROM $table");
    $items = $stmt->fetchAll();
    
    foreach ($items as $item) {
        $old_slug = $item['slug'];
        // Trim spaces AND trailing hyphens
        $new_slug = trim($old_slug);
        $new_slug = rtrim($new_slug, '-');
        $new_slug = trim($new_slug); // One more trim just in case

        if ($old_slug !== $new_slug) {
            echo "Updating $table ID {$item['id']}: '$old_slug' -> '$new_slug'\n";
            $update = $pdo->prepare("UPDATE $table SET slug = ? WHERE id = ?");
            $update->execute([$new_slug, $item['id']]);
        }
    }
}

echo "\nCleanup finished!";
echo "</pre>";
?>
