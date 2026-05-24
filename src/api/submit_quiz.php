<?php
ob_start(); // Trap everything including potential BOM or injected scripts
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

// 1. Auth Guard
if (!isset($_SESSION['user_id'])) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// 2. Data Parsing
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);

$user_id = (int)$_SESSION['user_id'];
$subject = isset($input['subject']) ? trim($input['subject']) : '';
$topic   = isset($input['topic'])   ? trim($input['topic'])   : ''; 
$module  = isset($input['module'])  ? trim($input['module'])  : '';
$score   = isset($input['score'])   ? (int)$input['score']    : 0;

try {
    // 3. Database Self-Healing (Consistently MyISAM for InfinityFree)
    $pdo->exec("CREATE TABLE IF NOT EXISTS quiz_scores (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, subject_slug VARCHAR(255), topic_slug VARCHAR(255), module_slug VARCHAR(255), score INT, created_at DATETIME) ENGINE=MyISAM");
    $pdo->exec("CREATE TABLE IF NOT EXISTS progress (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT, subject_slug VARCHAR(255), topic_slug VARCHAR(255), module_slug VARCHAR(255), is_completed TINYINT(1) DEFAULT 0, completed_step INT DEFAULT 1, created_at DATETIME) ENGINE=MyISAM");
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_log (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, activity_date DATE, action TEXT, created_at DATETIME, UNIQUE KEY user_date (user_id, activity_date)) ENGINE=MyISAM");

    $now = date('Y-m-d H:i:s');

    // 4. Critical: Save Score
    $st1 = $pdo->prepare("INSERT INTO quiz_scores (user_id, subject_slug, topic_slug, module_slug, score, created_at) VALUES (?, ?, ?, ?, ?, ?)");
    $st1->execute([$user_id, $subject, $topic, $module, $score, $now]);

    // 5. Critical: Update Progress (Using Strong Fuzzy Match for robustness)
    // Fetch all user progress and find match in PHP since MySQL REPLACE fails on brackets
    $c_s = preg_replace('/[^a-z0-9]/', '', strtolower($subject));
    $c_t = preg_replace('/[^a-z0-9]/', '', strtolower($topic));
    $c_m = preg_replace('/[^a-z0-9]/', '', strtolower($module));

    $stAllP = $pdo->prepare("SELECT id, subject_slug, topic_slug, module_slug, is_completed FROM progress WHERE user_id = ?");
    $stAllP->execute([$user_id]);
    $ex = null;
    foreach($stAllP->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $r_s = preg_replace('/[^a-z0-9]/', '', strtolower($row['subject_slug']));
        $r_t = preg_replace('/[^a-z0-9]/', '', strtolower($row['topic_slug']));
        $r_m = preg_replace('/[^a-z0-9]/', '', strtolower($row['module_slug']));
        
        if ($r_s === $c_s && $r_t === $c_t && $r_m === $c_m) {
            $ex = $row;
            break;
        }
    }

    $is_comp = ($score >= 70) ? 1 : ($ex ? (int)$ex['is_completed'] : 0);
    $newly_completed = ($is_comp == 1 && (!$ex || (int)$ex['is_completed'] == 0));

    if ($ex) {
        $st3 = $pdo->prepare("UPDATE progress SET is_completed = ?, completed_step = 1, created_at = ? WHERE id = ?");
        $st3->execute([$is_comp, $now, (int)$ex['id']]);
    } else {
        $st4 = $pdo->prepare("INSERT INTO progress (user_id, subject_slug, topic_slug, module_slug, is_completed, completed_step, created_at) VALUES (?, ?, ?, ?, ?, 1, ?)");
        $st4->execute([$user_id, $subject, $topic, $module, $is_comp, $now]);
    }

    // 6. Point Awarding & Leveling
    if ($newly_completed) {
        $pdo->prepare("UPDATE users SET total_points = COALESCE(total_points,0) + 5 WHERE id = ?")->execute([$user_id]);
        
        // Recalculate level: level 1 = 0-49 pts, level 2 = 50-99 pts, etc.
        $stL = $pdo->prepare("SELECT total_points FROM users WHERE id = ?");
        $stL->execute([$user_id]);
        $uData = $stL->fetch();
        $newPoints = (int)$uData['total_points'];
        $newLevel = floor($newPoints / 50) + 1;
        
        $pdo->prepare("UPDATE users SET current_level = ? WHERE id = ?")->execute([$newLevel, $user_id]);
    }

    // 7. Non-Critical: Activity Log (Streaks)
    try {
        $st5 = $pdo->prepare("INSERT IGNORE INTO activity_log (user_id, activity_date) VALUES (?, DATE(?))");
        @$st5->execute([$user_id, $now]);
    } catch(Exception $e) {}

    // 8. Log Activity for History Popup
    try {
        $actType = ($score >= 70) ? 'quiz_completed' : 'quiz_attempted';
        $actDesc = "Mengerjakan kuis modul " . $module . " dengan skor " . $score . "%";
        $pdo->prepare("INSERT INTO user_activities (user_id, activity_type, module_id, description) VALUES (?, ?, ?, ?)")
            ->execute([$user_id, $actType, $module, $actDesc]);
    } catch(Exception $e) {}

    // 7. Clean Output
    while (ob_get_level()) ob_end_clean();
    echo "<!--JSON_START-->" . json_encode(['success' => true]) . "<!--JSON_END-->";
    exit;

} catch (Exception $e) {
    while (ob_get_level()) ob_end_clean();
    echo "<!--JSON_START-->" . json_encode(['success' => false, 'error' => $e->getMessage()]) . "<!--JSON_END-->";
    exit;
}
