<?php
// src/api/progress.php
session_start();
require_once '../../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $subject = $_GET['subject'];
    $topic = $_GET['topic'];
    $module = $_GET['module'];

    // Get progress - Aggregated to avoid "stuck" bugs from duplicate entries
    $stmt = $pdo->prepare("SELECT MAX(completed_step) as completed_step, MAX(is_completed) as is_completed FROM progress WHERE user_id = ? AND subject_slug = ? AND topic_slug = ? AND module_slug = ?");
    $stmt->execute([$user_id, $subject, $topic, $module]);
    $progress = $stmt->fetch();

    echo json_encode([
        'current_step' => $progress ? $progress['completed_step'] : 0,
        'completed' => $progress ? (bool)$progress['is_completed'] : false
    ]);
} 
elseif ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $subject = $data['subject'];
    $topic = $data['topic'];
    $module = $data['module'];
    $step_completed = $data['step']; 

    // Check existing
    $stmt = $pdo->prepare("SELECT completed_step FROM progress WHERE user_id = ? AND subject_slug = ? AND topic_slug = ? AND module_slug = ?");
    $stmt->execute([$user_id, $subject, $topic, $module]);
    $existing = $stmt->fetch();

    if (!$existing) {
        $insert = $pdo->prepare("INSERT INTO progress (user_id, subject_slug, topic_slug, module_slug, completed_step) VALUES (?, ?, ?, ?, ?)");
        $insert->execute([$user_id, $subject, $topic, $module, $step_completed]);
    } else {
        if ($step_completed > $existing['completed_step']) {
            $update = $pdo->prepare("UPDATE progress SET completed_step = ? WHERE user_id = ? AND subject_slug = ? AND topic_slug = ? AND module_slug = ?");
            $update->execute([$step_completed, $user_id, $subject, $topic, $module]);
        }
    }

    echo json_encode(['success' => true]);
}
?>
