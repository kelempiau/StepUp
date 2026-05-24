<?php
require_once 'config/db.php';

echo "Checking users table...\n";
$users = $pdo->query("SELECT role, COUNT(*) as cnt FROM users GROUP BY role")->fetchAll(PDO::FETCH_ASSOC);
print_r($users);

echo "\nChecking friends table...\n";
try {
    $friends = $pdo->query("SELECT COUNT(*) FROM friends")->fetchColumn();
    echo "Total friends records: $friends\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nChecking current user (if any)...\n";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "\n";
} else {
    echo "No session user_id\n";
}
?>
