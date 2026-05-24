<?php
require "config/db.php";
echo "PROGRESS:\n";
foreach($pdo->query("SELECT * FROM progress") as $r) {
    echo json_encode($r) . "\n";
}
echo "SUBJECTS:\n";
foreach($pdo->query("SELECT * FROM subjects") as $r) {
    echo json_encode($r) . "\n";
}
echo "TOPICS:\n";
foreach($pdo->query("SELECT id, subject_id, slug, name, title FROM topics") as $r) {
    echo json_encode($r) . "\n";
}
echo "MODULES:\n";
foreach($pdo->query("SELECT id, topic_id, slug, name, title FROM modules") as $r) {
    echo json_encode($r) . "\n";
}
