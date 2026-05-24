<?php
require_once 'lms-app/config/db.php';
try {
    $pdo->exec("ALTER TABLE subjects ADD COLUMN batik_bg VARCHAR(255) DEFAULT NULL AFTER icon");
    echo "Sukses: Kolom batik_bg berhasil ditambahkan.";
} catch (Exception $e) {
    echo "Info: Kolom mungkin sudah ada atau error: " . $e->getMessage();
}
