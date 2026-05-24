<?php
// src/views/set_preference.php — Save user preferences
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../../config/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) { 
    http_response_code(401); 
    echo json_encode(['success'=>false, 'error'=>'Session expired']); 
    exit; 
}

$uid    = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

// Auto-create table
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_preferences (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        user_id    INT NOT NULL,
        bg_type    VARCHAR(20) DEFAULT 'color',
        bg_value   TEXT,
        glass_opacity INT DEFAULT 50,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user (user_id)
    ) ENGINE=MyISAM");
} catch(Exception $e) {}

try {
    // ── Save Profile (Name, Title & Password) ─────────
    if ($action === 'save_profile') {
        $name     = trim($_POST['full_name'] ?? '');
        $title    = trim($_POST['title'] ?? '');
        $password = trim($_POST['password']  ?? '');
        
        if (empty($name)) { echo json_encode(['success'=>false,'error'=>'Nama tidak boleh kosong']); exit; }
        $title = !empty($title) ? htmlspecialchars($title) : null;

        if (!empty($password)) {
            if (strlen($password) < 6) { echo json_encode(['success'=>false,'error'=>'Password minimal 6 karakter']); exit; }
            $pdo->prepare("UPDATE users SET full_name=?, title=?, password=? WHERE id=?")
                ->execute([$name, $title, password_hash($password, PASSWORD_DEFAULT), $uid]);
        } else {
            $pdo->prepare("UPDATE users SET full_name=?, title=? WHERE id=?")
                ->execute([$name, $title, $uid]);
        }
        $_SESSION['full_name'] = $name;
        echo json_encode(['success'=>true]);
        exit;
    }

    // ── Upload Profile Picture ────────────────────────
    if ($action === 'upload_profile_pic') {
        if (!isset($_FILES['pic']) || $_FILES['pic']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success'=>false,'error'=>'Gagal menerima file']); exit;
        }
        $ext = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            echo json_encode(['success'=>false,'error'=>'Format file harus JPG/PNG/WebP']); exit;
        }
        $dir = '../../uploads/profile_pics/';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $filename = 'pp_' . $uid . '_' . substr(md5(time()),0,6) . '.' . $ext;
        if (!move_uploaded_file($_FILES['pic']['tmp_name'], $dir . $filename)) {
            echo json_encode(['success'=>false,'error'=>'Gagal menyimpan foto']); exit;
        }
        $url = 'uploads/profile_pics/' . $filename;
        $pdo->prepare("UPDATE users SET profile_pic=? WHERE id=?")->execute([$url, $uid]);
        $_SESSION['profile_pic'] = $url;
        echo json_encode(['success'=>true, 'url'=>$url]);
        exit;
    }

    // ── Save Background (color or batik) ────────────
    if ($action === 'save_bg') {
        $type  = $_POST['bg_type']  ?? 'color';
        $value = $_POST['bg_value'] ?? '#f0f4ff';
        $opacity = intval($_POST['glass_opacity'] ?? 50);

        $chk = $pdo->prepare("SELECT id FROM user_preferences WHERE user_id=?");
        $chk->execute([$uid]);
        if ($chk->fetch()) {
            $pdo->prepare("UPDATE user_preferences SET bg_type=?, bg_value=?, glass_opacity=? WHERE user_id=?")
                ->execute([$type, $value, $opacity, $uid]);
        } else {
            $pdo->prepare("INSERT INTO user_preferences (user_id, bg_type, bg_value, glass_opacity) VALUES (?,?,?,?)")
                ->execute([$uid, $type, $value, $opacity]);
        }
        echo json_encode(['success'=>true]);
        exit;
    }

    // ── Upload Background Image ─────────────────────────
    if ($action === 'upload_bg') {
        if (!isset($_FILES['bg_image']) || $_FILES['bg_image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success'=>false,'error'=>'Upload gagal']); exit;
        }
        $ext = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            echo json_encode(['success'=>false,'error'=>'Format tidak didukung']); exit;
        }
        $dir = '../../uploads/backgrounds/';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $filename = 'bg_' . $uid . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($_FILES['bg_image']['tmp_name'], $dir . $filename)) {
            echo json_encode(['success'=>false,'error'=>'Gagal menyimpan file']); exit;
        }
        $url = 'uploads/backgrounds/' . $filename;

        $chk = $pdo->prepare("SELECT id FROM user_preferences WHERE user_id=?");
        $chk->execute([$uid]);
        if ($chk->fetch()) {
            $pdo->prepare("UPDATE user_preferences SET bg_type='image', bg_value=? WHERE user_id=?")->execute([$url, $uid]);
        } else {
            $pdo->prepare("INSERT INTO user_preferences (user_id, bg_type, bg_value) VALUES (?,'image',?)")->execute([$uid, $url]);
        }
        echo json_encode(['success'=>true, 'url'=>$url]);
        exit;
    }

    // ── Agenda Actions ──────────────────────────
    if ($action === 'add_agenda') {
        $date  = $_POST['date'] ?? '';
        $title = trim($_POST['title'] ?? '');
        if (empty($date) || empty($title)) { echo json_encode(['success'=>false, 'error'=>'Data tidak lengkap']); exit; }
        $pdo->prepare("INSERT INTO calendar_events (user_id, event_date, title) VALUES (?, ?, ?)")
            ->execute([$uid, $date, $title]);
        echo json_encode(['success'=>true]);
        exit;
    }

    if ($action === 'get_agendas') {
        $stmt = $pdo->prepare("SELECT id, event_date, title FROM calendar_events WHERE user_id = ? ORDER BY event_date ASC");
        $stmt->execute([$uid]);
        echo json_encode(['success'=>true, 'agendas'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($action === 'delete_agenda') {
        $id = $_POST['id'] ?? 0;
        $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
        echo json_encode(['success'=>true]);
        exit;
    }

} catch(Exception $e) {
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
exit;
