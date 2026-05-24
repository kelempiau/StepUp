<?php
require_once 'config/db.php';
header('Content-Type: text/plain');
try {
    echo "SUBJECTS:\n";
    $q = $pdo->query("SELECT id, name, slug FROM subjects");
    while($r = $q->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$r['id']} | Name: {$r['name']} | Slug: {$r['slug']}\n";
    }

    echo "\nTOPICS (first 5):\n";
    $q = $pdo->query("SELECT id, subject_id, name, slug FROM topics LIMIT 5");
    while($r = $q->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$r['id']} | SubID: {$r['subject_id']} | Name: {$r['name']}\n";
    }

    echo "\nMODULES (first 5):\n";
    $q = $pdo->query("SELECT id, topic_id, name, slug FROM modules LIMIT 5");
    while($r = $q->fetch(PDO::FETCH_ASSOC)) {
        echo "ID: {$r['id']} | TopID: {$r['topic_id']} | Name: {$r['name']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
?>
