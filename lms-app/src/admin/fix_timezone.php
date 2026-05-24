<?php
// src/admin/fix_timezone.php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die("Access Denied");
}

echo "<h2>Database & Timezone Fix Tool</h2>";

try {
    // 1. Fix progress table
    $pdo->exec("ALTER TABLE progress ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");
    echo "<p style='color:green'>+ COLUMN created_at added to 'progress' table.</p>";
} catch (Exception $e) {
    echo "<p style='color:orange'>~ 'progress' table already has created_at or cannot be altered.</p>";
}

try {
    // 2. Set timezone session (Test)
    $pdo->exec("SET time_zone = '+07:00'");
    $res = $pdo->query("SELECT NOW() as sekarang")->fetch();
    echo "<p style='color:green'>+ Timezone test: " . $res['sekarang'] . " (WIB)</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>- Failed to set MySQL timezone: " . $e->getMessage() . "</p>";
}

echo "<hr><p>Done. You can close this page.</p>";
?>
