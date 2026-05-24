<?php
// fix_db.php - Database Setup & Migration
require_once 'config/db.php';
echo "<pre style='font-family:monospace; padding:20px; background:#0f172a; color:#94a3b8; font-size:14px;'>";
echo "🚀 Memulai Perbaikan Database StepUp...\n\n";

try {
    // 1. Users: profile_pic
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_pic'");
    if (!$stmt->fetch()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL");
        echo "✅ Kolom profile_pic ditambahkan ke users.\n";
    } else { echo "☑️  Kolom profile_pic sudah ada.\n"; }

    // 2. Tabel user_preferences (background + settings)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_preferences (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            user_id     INT NOT NULL UNIQUE,
            bg_type     ENUM('color','gradient','image') DEFAULT 'color',
            bg_value    TEXT DEFAULT NULL,
            theme       ENUM('light','dark') DEFAULT 'light',
            updated_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=MyISAM
    ");
    echo "✅ Tabel user_preferences siap.\n";

    // 3. Tabel todos (user-defined tasks)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS todos (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            user_id      INT NOT NULL,
            task         VARCHAR(255) NOT NULL,
            is_completed TINYINT(1) DEFAULT 0,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=MyISAM
    ");
    echo "✅ Tabel todos siap.\n";

    // 4. chat_logs
    $pdo->exec("DROP TABLE IF EXISTS chat_logs");
    $pdo->exec("
        CREATE TABLE chat_logs (
            id         INT AUTO_INCREMENT PRIMARY KEY,
            user_id    INT NOT NULL,
            message    TEXT NOT NULL,
            response   TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=MyISAM
    ");
    echo "✅ Tabel chat_logs dibuat ulang.\n";

    // 5. final_exam_scores
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS final_exam_scores (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            user_id      INT NOT NULL,
            subject_slug VARCHAR(50) NOT NULL,
            score        INT NOT NULL,
            passed       BOOLEAN DEFAULT FALSE,
            created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=MyISAM
    ");
    echo "✅ Tabel final_exam_scores siap.\n";

    echo "\n<span style='color:#34d399'>🎉 SEMUA TABEL SIAP! Database StepUp sudah lengkap.</span>";
    echo "\n\n<a href='src/views/dashboard.php' style='color:#60a5fa'>→ Kembali ke Dashboard</a>";

} catch (Exception $e) {
    echo "<span style='color:#f87171'>❌ ERROR: " . $e->getMessage() . "</span>";
}
echo "</pre>";
