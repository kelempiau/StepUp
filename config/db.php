<?php
date_default_timezone_set('Asia/Jakarta');
$host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'stepup';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
$db_socket = getenv('DB_SOCKET');

try {
    if ($db_socket) {
        $dsn = "mysql:unix_socket=$db_socket;dbname=$db_name";
    } else {
        $dsn = "mysql:host=$host;dbname=$db_name";
    }
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET time_zone = '+07:00'");
    
    // Load Database session handler globally
    if (file_exists(__DIR__ . '/session.php')) {
        require_once __DIR__ . '/session.php';
    }
} catch(PDOException $e) {
    // If we are in an API request (checking header or constant), throwing is better
    // But for backward compatibility with existing non-API pages, we might need to be careful.
    // Ideally: throw new Exception("DB Connection failed: " . $e->getMessage());
    // For now, let's just error_log and NOT die with text if we can avoid it.
    error_log("Database Error: " . $e->getMessage());
    if (defined('API_REQUEST')) {
        throw new Exception("Connection failed");
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

