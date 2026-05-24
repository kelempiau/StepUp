<?php
// debug_challenges.php
require_once 'config/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM challenges");
    $challenges = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Database Challenges</h1>";
    if (empty($challenges)) {
        echo "<p>No challenges found in the database.</p>";
    } else {
        echo "<pre>";
        print_r($challenges);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>