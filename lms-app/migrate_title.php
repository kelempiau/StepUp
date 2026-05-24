<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN title VARCHAR(100) DEFAULT NULL");
    echo "Column 'title' added successfully.";
} catch (Exception $e) {
    echo "Notice: " . $e->getMessage();
}
?>
