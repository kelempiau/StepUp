<?php
require_once 'config/db.php';
header('Content-Type: text/plain');

$sub = $pdo->query("SELECT * FROM subjects LIMIT 1")->fetch(PDO::FETCH_ASSOC);
echo "SUBJECT: {$sub['name']} (ID: {$sub['id']})\n";

$topics = $pdo->prepare("SELECT * FROM topics WHERE subject_id = ?");
$topics->execute([$sub['id']]);
$all_topics = $topics->fetchAll(PDO::FETCH_ASSOC);
echo "TOPICS COUNT: " . count($all_topics) . "\n";
foreach($all_topics as $t) {
    echo "- Topic: {$t['name']} (ID: {$t['id']})\n";
    $modules = $pdo->prepare("SELECT * FROM modules WHERE topic_id = ?");
    $modules->execute([$t['id']]);
    echo "  - MODULES COUNT: " . count($modules->fetchAll()) . "\n";
}
?>
