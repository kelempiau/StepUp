<?php
require_once 'config/db.php';
header('Content-Type: text/plain');

echo "--- CHECKING SUBJECTS ---\n";
$st = $pdo->query("SELECT id, title, slug FROM subjects");
while($r = $st->fetch()) echo "ID: {$r['id']} | Slug: [{$r['slug']}] | Title: {$r['title']}\n";

echo "\n--- CHECKING TOPICS ---\n";
$st = $pdo->query("SELECT id, subject_id, title, slug FROM topics");
while($r = $st->fetch()) echo "ID: {$r['id']} | SubID: {$r['subject_id']} | Slug: [{$r['slug']}] | Title: {$r['title']}\n";

echo "\n--- CHECKING MODULES (Sample) ---\n";
$st = $pdo->query("SELECT id, topic_id, title, slug FROM modules LIMIT 20");
while($r = $st->fetch()) echo "ID: {$r['id']} | TopID: {$r['topic_id']} | Slug: [{$r['slug']}] | Title: {$r['title']}\n";
?>
