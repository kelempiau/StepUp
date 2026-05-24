<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
$_SESSION['user_id'] = 4; // user ID of "Kent" or another user
$_SESSION['role'] = 'student';
chdir(__DIR__ . '/src/views');
ob_start();
require 'dashboard.php';
$html = ob_get_clean();

$pos1 = strpos($html, 'id="tab-2"');
if ($pos1 !== false) {
    echo "tab-2 FOUND!\n";
    echo "Content snippet:\n" . substr($html, $pos1, 400) . "\n-----------\n";
} else {
    echo "tab-2 NOT FOUND!\n";
}

echo "Total length: " . strlen($html) . "\n";
$err = error_get_last();
if ($err) {
    echo "Error: " . print_r($err, true) . "\n";
}
?>
