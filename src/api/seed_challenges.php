<?php
require_once '../../config/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `challenges` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `description` text NOT NULL,
      `points` int(11) NOT NULL DEFAULT '0',
      `difficulty` enum('easy','medium','hard') DEFAULT 'easy',
      `week_type` enum('current','next') DEFAULT 'current',
      `is_active` tinyint(1) DEFAULT '1',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $pdo->exec("
    ALTER TABLE `challenges` ADD COLUMN IF NOT EXISTS `difficulty` enum('easy','medium','hard') DEFAULT 'easy';
    ALTER TABLE `challenges` ADD COLUMN IF NOT EXISTS `week_type` enum('current','next') DEFAULT 'current';
    ALTER TABLE `challenges` ADD COLUMN IF NOT EXISTS `is_active` tinyint(1) DEFAULT '1';
    DELETE FROM `user_challenges`; DELETE FROM `challenges`; ALTER TABLE `challenges` AUTO_INCREMENT = 1;");

    $challenges = [
        ['title' => 'Beruntun Belajar', 'desc' => 'Login dan belajar 3 hari berturut-turut.', 'points' => 50, 'diff' => 'easy', 'week' => 'current'],
        ['title' => 'Master Modul', 'desc' => 'Selesaikan 2 modul dari mata pelajaran apapun.', 'points' => 100, 'diff' => 'medium', 'week' => 'current'],
        ['title' => 'Aktif Bersosialisasi', 'desc' => 'Kirim 5 pesan di grup komunitas.', 'points' => 75, 'diff' => 'easy', 'week' => 'current'],
        ['title' => 'Pencari Nilai Sempurna', 'desc' => 'Dapatkan skor 100% pada 2 kuis.', 'points' => 200, 'diff' => 'hard', 'week' => 'next'],
    ];

    $stmt = $pdo->prepare("INSERT INTO challenges (title, description, points, difficulty, week_type, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    foreach ($challenges as $c) {
        $stmt->execute([$c['title'], $c['desc'], $c['points'], $c['diff'], $c['week']]);
    }
    
    echo "Success seeding challenges!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
