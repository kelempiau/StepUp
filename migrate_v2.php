<?php
require_once 'config/db.php';

try {
    // 1. Add columns to users if not exists
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS total_points INT DEFAULT 0");
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS current_level INT DEFAULT 1");

    // 2. Friends Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS friends (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        friend_id INT NOT NULL,
        status ENUM('pending', 'accepted') DEFAULT 'accepted',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY user_friend (user_id, friend_id)
    )");

    // 3. Messages Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        content TEXT,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 4. Communities Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS communities (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        vision TEXT,
        mission TEXT,
        profile_pic VARCHAR(255),
        owner_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 5. Community Members Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS community_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        community_id INT NOT NULL,
        user_id INT NOT NULL,
        role ENUM('owner', 'admin', 'member') DEFAULT 'member',
        status ENUM('pending', 'accepted') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY comm_user (community_id, user_id)
    )");

    // 6. Community Chats Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS community_chats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        community_id INT NOT NULL,
        user_id INT NOT NULL,
        content TEXT,
        image_path VARCHAR(255),
        type ENUM('text', 'image', 'system') DEFAULT 'text',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // 7. Challenges Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS challenges (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        points INT DEFAULT 50,
        difficulty ENUM('easy', 'medium', 'hard') DEFAULT 'medium',
        week_type ENUM('current', 'next') DEFAULT 'current',
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    echo "Database migration successful!\n";

    // Seed some initial challenges if empty
    $check = $pdo->query("SELECT COUNT(*) FROM challenges")->fetchColumn();
    if ($check == 0) {
        $pdo->exec("INSERT INTO challenges (title, description, points, difficulty, week_type) VALUES 
            ('Ekspresi Debat', 'Tonton materi Teks Debat dan kerjakan kuisnya hari ini!', 150, 'medium', 'current'),
            ('Maraton Sains', 'Selesaikan 3 modul Biologi dalam 24 jam.', 300, 'hard', 'current'),
            ('Halo Komunitas', 'Posting pertama kali di komunitas diskusi.', 50, 'easy', 'current'),
            ('Master Matematika', 'Dapatkan nilai 100 di kuis Aljabar.', 200, 'hard', 'next')");
        echo "Challenges seeded!\n";
    }

} catch (Exception $e) {
    die("Migration failed: " . $e->getMessage());
}
