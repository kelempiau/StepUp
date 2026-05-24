<?php
// src/views/settings.php
session_start();
require_once '../../config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// 1. Fetch Current User Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic Validation
    if (empty($full_name) || empty($email)) {
        $_SESSION['settings_error'] = "Nama dan Email wajib diisi.";
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $_SESSION['settings_error'] = "Password baru minimal 6 karakter.";
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $_SESSION['settings_error'] = "Konfirmasi password tidak cocok. Pastikan kedua kolom password sama.";
    } else {
        try {
            // Profile Picture Upload
            $profile_pic = $user['profile_pic'];
            $delete_pic = isset($_POST['delete_profile_pic']) && $_POST['delete_profile_pic'] === '1';

            if ($delete_pic) {
                if($user['profile_pic'] && file_exists('../../uploads/profile_pics/' . $user['profile_pic'])) {
                    unlink('../../uploads/profile_pics/' . $user['profile_pic']);
                }
                $profile_pic = null;
            } elseif (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['profile_pic']['tmp_name'];
                $fileName = $_FILES['profile_pic']['name'];
                $fileNameCmps = explode(".", $fileName);
                $fileExtension = strtolower(end($fileNameCmps));
                
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
                if (in_array($fileExtension, $allowedExtensions)) {
                    $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
                    $uploadFileDir = '../../uploads/profile_pics/';
                    
                    if (!is_dir($uploadFileDir)) {
                        mkdir($uploadFileDir, 0777, true);
                    }
                    
                    $dest_path = $uploadFileDir . $newFileName;
                    if(move_uploaded_file($fileTmpPath, $dest_path)) {
                        // Delete old pic if exists
                        if($user['profile_pic'] && file_exists($uploadFileDir . $user['profile_pic'])) {
                            unlink($uploadFileDir . $user['profile_pic']);
                        }
                        $profile_pic = $newFileName;
                    }
                }
            }

            if (!empty($new_password)) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, password = ?, profile_pic = ? WHERE id = ?");
                $update->execute([$full_name, $email, $hashed, $profile_pic, $user_id]);
            } else {
                $update = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_pic = ? WHERE id = ?");
                $update->execute([$full_name, $email, $profile_pic, $user_id]);
            }
            
            $_SESSION['full_name'] = $full_name;
            $_SESSION['settings_success'] = "Profil berhasil diperbarui!";
            
            header("Location: dashboard.php");
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['settings_error'] = "Gagal memperbarui: " . $e->getMessage();
        }
    }
    header("Location: dashboard.php");
    exit;
} else {
    // Redirect GET requests back to dashboard
    header("Location: dashboard.php");
    exit;
}
?>
