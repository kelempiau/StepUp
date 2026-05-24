<?php
// src/api/chat.php — Unified Chat API (save + load) with auto-create table
error_reporting(0);
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

require_once '../../config/db.php';
$uid = $_SESSION['user_id'];

// Auto-create table (safe on any host)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS chat_logs (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        message    TEXT NOT NULL,
        response   TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=MyISAM");
} catch (Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // POST: save a chat exchange
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    // Fallback to $_POST if JSON not sent
    $userMsg = $data['message'] ?? ($_POST['message'] ?? '');
    $aiMsg   = $data['response'] ?? ($_POST['response'] ?? '');

    if (empty($userMsg) || empty($aiMsg)) {
        echo json_encode(['success' => false, 'error' => 'Pesan kosong']);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO chat_logs (user_id, message, response) VALUES (?, ?, ?)");
        $stmt->execute([$uid, $userMsg, $aiMsg]);
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// GET: load last 50 messages for this user
try {
    $stmt = $pdo->prepare("SELECT message, response, created_at FROM chat_logs WHERE user_id = ? ORDER BY created_at ASC LIMIT 50");
    $stmt->execute([$uid]);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'history' => $history]);
} catch (Exception $e) {
    echo json_encode(['success' => true, 'history' => []]);
}
