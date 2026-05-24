<?php
// src/api/cron_reset_challenges.php
// This script should be run every Monday at 04:00 via Cron Job.
// Example Cron: 0 4 * * 1 php /path/to/src/api/cron_reset_challenges.php

require_once __DIR__ . '/../../config/db.php';

try {
    $pdo->beginTransaction();

    // 1. Mark current week challenges as inactive or delete them
    // Delete current week
    $pdo->exec("DELETE FROM challenges WHERE week_type = 'current'");

    // 2. Promote next week to current
    $pdo->exec("UPDATE challenges SET week_type = 'current' WHERE week_type = 'next'");

    // 3. Seed new 'next' week challenges (4 random challenges)
    $pool = [
        ['title' => 'Eksplorasi Ilmu', 'desc' => 'Selesaikan 3 modul materi hari ini.', 'points' => 150, 'diff' => 'medium'],
        ['title' => 'Si Paling Bertanya', 'desc' => 'Kirim 10 pesan diskusi di grup komunitas.', 'points' => 100, 'diff' => 'easy'],
        ['title' => 'Nilai Sempurna', 'desc' => 'Dapatkan skor 100 pada kuis mata pelajaran apapun.', 'points' => 200, 'diff' => 'hard'],
        ['title' => 'Rajin Belajar', 'desc' => 'Login 5 hari berturut-turut di minggu ini.', 'points' => 250, 'diff' => 'hard'],
        ['title' => 'Kolektor Sertifikat', 'desc' => 'Dapatkan 1 sertifikat kelulusan topik.', 'points' => 300, 'diff' => 'hard'],
        ['title' => 'Sapa Teman', 'desc' => 'Tambahkan 2 teman baru di minggu ini.', 'points' => 50, 'diff' => 'easy'],
        ['title' => 'Cepat Tanggap', 'desc' => 'Selesaikan kuis dalam waktu kurang dari 2 menit.', 'points' => 100, 'diff' => 'medium'],
        ['title' => 'Penjelajah Topik', 'desc' => 'Mulai belajar topik baru di luar mata pelajaran favoritmu.', 'points' => 80, 'diff' => 'easy'],
    ];

    shuffle($pool);
    $selected = array_slice($pool, 0, 4);

    $stmt = $pdo->prepare("INSERT INTO challenges (title, description, points, difficulty, week_type, is_active) VALUES (?, ?, ?, ?, 'next', 1)");
    foreach ($selected as $c) {
        $stmt->execute([$c['title'], $c['desc'], $c['points'], $c['diff']]);
    }

    $pdo->commit();
    echo "Challenges reset and seeded successfully at " . date('Y-m-d H:i:s');
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "Error resetting challenges: " . $e->getMessage();
}
