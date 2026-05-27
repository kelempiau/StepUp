<?php
// chat_save.php - Save chat history to database
require_once '../../config/db.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false]);
    exit;
}

$userMsg = $_POST['user_message'] ?? '';
$aiMsg = $_POST['ai_response'] ?? '';

if (empty($userMsg) || empty($aiMsg)) {
    echo json_encode(['success' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO chat_logs (user_id, message, response) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $userMsg, $aiMsg]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
