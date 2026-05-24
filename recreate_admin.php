<?php
// recreate_admin.php
// Script untuk BUAT ULANG admin user jika hilang atau RESET password
require_once 'config/db.php';

try {
    $username = 'admin';
    $password = 'admin123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $email = 'admin@lms.com';
    $role = 'admin';
    $full_name = 'Administrator';

    // Cek apakah admin sudah ada
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Update password yang ada
        $stmt = $pdo->prepare("UPDATE users SET password = ?, role = 'admin' WHERE id = ?");
        $stmt->execute([$hashed_password, $user['id']]);
        echo "✅ Password admin berhasil di-reset menjadi: <b>admin123</b><br>";
    } else {
        // Insert user baru
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, full_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $hashed_password, $email, $role, $full_name]);
        echo "✅ User admin baru berhasil dibuat dengan password: <b>admin123</b><br>";
    }
    
    echo "Silakan login di halaman utama menggunakan:<br>";
    echo "Username: <b>admin</b><br>";
    echo "Password: <b>admin123</b><br><br>";
    echo "<a href='index.php'>Ke Halaman Login</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
