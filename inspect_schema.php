<?php
require_once 'config/db.php';
header('Content-Type: text/plain');

function checkTable($pdo, $tableName) {
    echo "--- TABLE: $tableName ---\n";
    try {
        $q = $pdo->query("DESCRIBE $tableName");
        while($r = $q->fetch(PDO::FETCH_ASSOC)) {
            echo "Field: {$r['Field']} | Type: {$r['Type']}\n";
        }
        $count = $pdo->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
        echo "Total Records: $count\n\n";
    } catch (Exception $e) {
        echo "ERROR on $tableName: " . $e->getMessage() . "\n\n";
    }
}

checkTable($pdo, 'subjects');
checkTable($pdo, 'topics');
checkTable($pdo, 'modules');
checkTable($pdo, 'users');
?>
