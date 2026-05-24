<?php
require_once 'config/db.php';
session_start();
if(!isset($_SESSION['user_id'])) die("Not logged in");
$uid = $_SESSION['user_id'];
echo "<pre>";
echo "User ID: $uid\n";
echo "Progress recorded:\n";
$st = $pdo->prepare("SELECT * FROM progress WHERE user_id = ?");
$st->execute([$uid]);
print_r($st->fetchAll(PDO::FETCH_ASSOC));

echo "\nModules in DB:\n";
$st = $pdo->query("SELECT id, slug, title FROM modules");
print_r($st->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
