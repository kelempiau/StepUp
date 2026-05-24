<?php
// src/views/update_profile.php — Save profile changes
ob_start();
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) { 
    ob_clean();
    http_response_code(401); 
    exit; 
}

header('Content-Type: application/json');

$name     = trim($_POST['full_name'] ?? '');
$password = trim($_POST['password'] ?? '');
$userId   = $_SESSION['user_id'];

try {
    if (!empty($password)) {
        if (strlen($password) < 6) {
            ob_clean();
            echo json_encode(['success' => false, 'error' => 'Password minimal 6 karakter.']);
            exit;
        }
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, password = ? WHERE id = ?");
        $stmt->execute([$name, $hashed, $userId]);
    } else {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ? WHERE id = ?");
        $stmt->execute([$name, $userId]);
    }

    // Update session
    $_SESSION['full_name'] = $name;

    ob_clean();
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error.']);
}
exit;
