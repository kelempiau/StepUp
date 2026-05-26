<?php
// src/auth/google_callback.php
session_start();
require_once '../../config/db.php';

// Disable errors in output to avoid breaking JSON or redirects
error_reporting(0);

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Akses tidak valid.");
}

// Google sends the credential via POST
$credential = $_POST['credential'] ?? '';
if (empty($credential)) {
    die("Token Google tidak ditemukan.");
}

// Decode the JWT token (Google ID token)
// A JWT has 3 parts separated by dots. The payload is the 2nd part.
$jwt_parts = explode('.', $credential);
if (count($jwt_parts) !== 3) {
    die("Token tidak valid.");
}

$payload = base64_decode(strtr($jwt_parts[1], '-_', '+/'));
$user_data = json_decode($payload, true);

if (!$user_data || !isset($user_data['email'])) {
    die("Gagal mengekstrak data dari Google.");
}

$google_email = $user_data['email'];
$google_name = $user_data['name'] ?? 'User Google';
$google_picture = $user_data['picture'] ?? null;
// You can use 'sub' as a unique Google user ID if needed, but email is enough here

try {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$google_email]);
    $existing_user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_user) {
        // User exists: login directly
        
        // Auto-verify if they haven't verified but logged in via Google
        if (isset($existing_user['is_verified']) && $existing_user['is_verified'] == 0) {
            $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?")->execute([$existing_user['id']]);
        }

        // Update profile picture if empty
        if (empty($existing_user['profile_pic']) && $google_picture) {
            $pdo->prepare("UPDATE users SET profile_pic = ? WHERE id = ?")->execute([$google_picture, $existing_user['id']]);
        }

        $_SESSION['user_id'] = $existing_user['id'];
        $_SESSION['full_name'] = $existing_user['full_name'];
        $_SESSION['role'] = $existing_user['role'];
        
        // Redirect to dashboard
        header("Location: ../../" . ($existing_user['role'] === 'admin' ? "src/admin/dashboard.php" : "src/views/dashboard.php?login=1"));
        exit;
        
    } else {
        // User does NOT exist: register automatically
        
        // Generate a random username from email
        $base_username = strtolower(explode('@', $google_email)[0]);
        // Strip non-alphanumeric
        $base_username = preg_replace("/[^a-z0-9]/", "", $base_username);
        
        // Check uniqueness of username
        $username = $base_username;
        $counter = 1;
        while (true) {
            $chk = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $chk->execute([$username]);
            if (!$chk->fetch()) break; // Unique!
            $username = $base_username . $counter;
            $counter++;
        }
        
        // Generate random strong password (they login via Google anyway)
        $random_password = bin2hex(random_bytes(16));
        $hashed_password = password_hash($random_password, PASSWORD_DEFAULT);
        
        // Insert new user, automatically verified since it's from Google
        $stmt = $pdo->prepare("INSERT INTO users (full_name, username, email, password, role, is_verified, profile_pic) VALUES (?, ?, ?, ?, 'student', 1, ?)");
        $stmt->execute([$google_name, $username, $google_email, $hashed_password, $google_picture]);
        
        $new_user_id = $pdo->lastInsertId();
        
        // Login immediately
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['full_name'] = $google_name;
        $_SESSION['role'] = 'student';
        
        header("Location: ../../src/views/dashboard.php?login=1&new=1");
        exit;
    }

} catch (Exception $e) {
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}
