<?php
require_once '../../config/db.php';
echo "SUBJECTS:\n";
$q = $pdo->query("SELECT id, name, slug FROM subjects");
while($r = $q->fetch(PDO::FETCH_ASSOC)) print_r($r);

echo "\nTOPICS:\n";
$q = $pdo->query("SELECT id, subject_id, name, slug FROM topics");
while($r = $q->fetch(PDO::FETCH_ASSOC)) print_r($r);

echo "\nMODULES:\n";
$q = $pdo->query("SELECT id, topic_id, name, slug FROM modules");
while($r = $q->fetch(PDO::FETCH_ASSOC)) print_r($r);
?>
