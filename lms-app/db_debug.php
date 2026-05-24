<?php
require_once 'config/db.php';
$st = $pdo->query("SELECT id, title, slug FROM topics WHERE title LIKE '%Geometri%'");
while($r = $st->fetch()) {
    echo "ID: " . $r['id'] . " | Title: [" . $r['title'] . "] | Slug: [" . $r['slug'] . "]\n";
}
$st = $pdo->query("SELECT id, title, slug FROM modules WHERE title LIKE '%Trigonometri%'");
while($r = $st->fetch()) {
    echo "ID: " . $r['id'] . " | Title: [" . $r['title'] . "] | Slug: [" . $r['slug'] . "]\n";
}
?>
