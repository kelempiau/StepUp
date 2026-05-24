<?php
require "config/db.php";
$st = $pdo->query("SELECT * FROM progress");
$res = $st->fetchAll(PDO::FETCH_ASSOC);
foreach($res as $r) {
    if(stripos($r['module_slug'], 'pancasila') !== false) {
        print_r($r);
    }
}
echo "TOPICS:\n";
foreach($pdo->query("SELECT id, title, slug FROM topics") as $r) {
    if(stripos($r['title'], 'pancasila') !== false) { print_r($r); }
}
echo "MODS:\n";
foreach($pdo->query("SELECT id, title, slug FROM modules") as $r) {
    if(stripos($r['title'], 'pancasila') !== false) { print_r($r); }
}
