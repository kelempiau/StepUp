<?php
require 'config/db.php';
$stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute(['admin']);
print_r($stmt->fetch());
?>