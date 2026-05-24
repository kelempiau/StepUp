<?php
// src/api/todo.php — Full CRUD + auto-create table
error_reporting(0);
session_start();
require_once '../../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { http_response_code(403); echo json_encode(['success'=>false]); exit; }
$uid = $_SESSION['user_id'];

// Auto-create table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS todos (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        user_id      INT NOT NULL,
        task         VARCHAR(255) NOT NULL,
        is_completed TINYINT(1) DEFAULT 0,
        created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=MyISAM");
} catch(Exception $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'add') {
            $task = trim($_POST['task'] ?? '');
            if (empty($task)) { echo json_encode(['success'=>false,'error'=>'Task kosong']); exit; }
            $stmt = $pdo->prepare("INSERT INTO todos (user_id, task) VALUES (?, ?)");
            $stmt->execute([$uid, $task]);
            echo json_encode(['success'=>true, 'id'=>$pdo->lastInsertId(), 'task'=>$task]);
            exit;
        }
        if ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("UPDATE todos SET is_completed = 1 - is_completed WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
            $row = $pdo->prepare("SELECT is_completed FROM todos WHERE id = ?");
            $row->execute([$id]);
            $r = $row->fetch();
            echo json_encode(['success'=>true, 'is_completed'=>(bool)($r['is_completed']??0)]);
            exit;
        }
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $pdo->prepare("DELETE FROM todos WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
            echo json_encode(['success'=>true]);
            exit;
        }
    } catch(Exception $e) {
        echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
        exit;
    }
}

// GET — fetch list
try {
    $stmt = $pdo->prepare("SELECT id, task, is_completed FROM todos WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$uid]);
    $todos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Cast is_completed to bool
    foreach ($todos as &$t) $t['is_completed'] = (bool)$t['is_completed'];
    echo json_encode(['success'=>true, 'todos'=>$todos]);
} catch(Exception $e) {
    echo json_encode(['success'=>true, 'todos'=>[]]);
}
