<?php
// seed_challenges_now.php
require_once 'config/db.php';

try {
    // 1. Ensure table exists with correct structure
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

    // 2. Ensure user_challenges table exists for tracking
    $pdo->exec("CREATE TABLE IF NOT EXISTS `user_challenges` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `challenge_id` int(11) NOT NULL,
      `is_completed` tinyint(1) DEFAULT '0',
      `is_claimed` tinyint(1) DEFAULT '0',
      `completed_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`challenge_id`) REFERENCES challenges(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. Clear existing challenges to avoid duplicates during testing
    $pdo->exec("DELETE FROM `user_challenges` ");
    $pdo->exec("DELETE FROM `challenges` ");
    $pdo->exec("ALTER TABLE `challenges` AUTO_INCREMENT = 1");

    // 4. Seed with diverse test data
    $challenges = [
        [
            'title' => '🚀 Pelari Cepat Modul', 
            'desc' => 'Selesaikan 3 modul dalam satu hari untuk membuktikan kecepatan belajarmu!', 
            'points' => 100, 
            'diff' => 'medium', 
            'week' => 'current'
        ],
        [
            'title' => '🔥 Konsistensi Tinggi', 
            'desc' => 'Login dan baca materi selama 3 hari berturut-turut.', 
            'points' => 50, 
            'diff' => 'easy', 
            'week' => 'current'
        ],
        [
            'title' => '🏆 Master Kuis', 
            'desc' => 'Dapatkan skor sempurna (100%) pada 2 kuis berbeda.', 
            'points' => 200, 
            'diff' => 'hard', 
            'week' => 'current'
        ],
        [
            'title' => '📅 Persiapan Minggu Depan', 
            'desc' => 'Selesaikan semua materi prasyarat sebelum minggu depan dimulai.', 
            'points' => 150, 
            'diff' => 'medium', 
            'week' => 'next'
        ],
    ];

    $stmt = $pdo->prepare("INSERT INTO challenges (title, description, points, difficulty, week_type, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    
    foreach ($challenges as $c) {
        $stmt->execute([$c['title'], $c['desc'], $c['points'], $c['diff'], $c['week']]);
    }
    
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h1 style='color:green;'>✅ Success!</h1>";
    echo "<p>Database has been seeded with 4 test challenges.</p>";
    echo "<p>Please check your <b>User Dashboard &rarr; Tantangan</b> tab now.</p>";
    echo "<a href='src/views/dashboard.php' style='display:inline-block; padding:10px 20px; background:blue; color:white; text-decoration:none; border-radius:10px; font-weight:bold;'>Go to Dashboard</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='font-family:sans-serif; text-align:center; margin-top:50px;'>";
    echo "<h1 style='color:red;'>❌ Error!</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>