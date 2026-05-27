<?php
// Load DB session handler first (before session_start)
require_once '../../config/db.php';
session_start();

// 1. Get current session ID before destroying
$session_id = session_id();

// 2. Clear all session variables
$_SESSION = array();

// 3. Delete the session cookie from browser
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Destroy session (triggers handler's destroy method)
session_destroy();

// 5. Force-delete from database as failsafe
try {
    if (isset($pdo) && $session_id) {
        $stmt = $pdo->prepare("DELETE FROM php_sessions WHERE id = ?");
        $stmt->execute([$session_id]);
    }
} catch (Exception $e) {
    // Ignore errors, we're logging out anyway
}

// 6. Redirect to login page
header("Location: ../../login.php");
exit;
