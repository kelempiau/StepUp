<?php
require_once 'config/db.php';

try {
    // 1. Remove duplicates, keeping the one with the highest ID
    $pdo->exec("DELETE t1 FROM user_challenges t1
                INNER JOIN user_challenges t2 
                WHERE t1.id > t2.id 
                AND t1.user_id = t2.user_id 
                AND t1.challenge_id = t2.challenge_id");

    // 2. Add unique constraint
    $pdo->exec("ALTER TABLE user_challenges ADD UNIQUE KEY unique_user_challenge (user_id, challenge_id)");
    
    echo "Database fixed successfully: Duplicates removed and unique constraint added.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
