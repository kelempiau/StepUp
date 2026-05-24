<?php
// src/auth/google_login.php
session_start();
require_once '../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['credential'])) {
    $jwt = $_POST['credential'];
    
    // Verify token with Google's tokeninfo endpoint
    $url = "https://oauth2.googleapis.com/tokeninfo?id_token=" . $jwt;
    
    // Use cURL for better compatibility instead of file_get_contents
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if (!$response) {
        $_SESSION['login_error'] = "Gagal menghubungi server Google.";
        header("Location: ../../login.php");
        exit;
    }
    
    $payload = json_decode($response, true);
    
    // Check if token is valid and email is present
    if (isset($payload['email'])) {
        $email = $payload['email'];
        $name = $payload['name'];
        // Optional: save avatar
        $picture = isset($payload['picture']) ? $payload['picture'] : null;
        
        // Find user by email
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Login existing user
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Perbarui avatar jika ada
            if ($picture && empty($user['profile_pic'])) {
                // Untuk sementara simpan URL di kolom avatar_url atau biarkan jika profile_pic adalah nama file lokal
                $stmt = $pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
                $stmt->execute([$picture, $user['id']]);
            }
            
            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../views/dashboard.php");
            }
            exit;
        } else {
            // Register new user
            // Generate unique username from email prefix
            $base_username = explode('@', $email)[0];
            $username = $base_username;
            $counter = 1;
            while(true) {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                $stmt->execute([$username]);
                if (!$stmt->fetch()) break;
                $username = $base_username . $counter;
                $counter++;
            }
            
            // Random secure password for OAuth users (cannot login with normal password unless reset)
            $random_password = password_hash(bin2hex(random_bytes(10)), PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role, avatar_url, is_verified) VALUES (?, ?, ?, ?, 'student', ?, 1)");
            $stmt->execute([$username, $email, $random_password, $name, $picture]);
            
            $new_user_id = $pdo->lastInsertId();
            $_SESSION['user_id'] = $new_user_id;
            $_SESSION['role'] = 'student';
            $_SESSION['full_name'] = $name;
            
            header("Location: ../views/dashboard.php");
            exit;
        }
    } else {
        $_SESSION['login_error'] = "Token Google tidak valid atau kedaluwarsa.";
        header("Location: ../../login.php");
        exit;
    }
}
header("Location: ../../login.php");
exit;
