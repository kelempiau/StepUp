<?php
require_once 'config/db.php';
header('Content-Type: text/plain');

$q = $pdo->query("SELECT * FROM subjects LIMIT 3");
echo "SUBJECTS:\n";
while($r = $q->fetch(PDO::FETCH_ASSOC)) print_r($r);

$q = $pdo->query("SELECT * FROM topics LIMIT 3");
echo "\nTOPICS:\n";
while($r = $q->fetch(PDO::FETCH_ASSOC)) print_r($r);

$q = $pdo->query("SELECT * FROM modules LIMIT 3");
echo "\nMODULES:\n";
while($r = $q->fetch(PDO::FETCH_ASSOC)) print_r($r);
?>
