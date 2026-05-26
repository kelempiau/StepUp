<?php
// src/views/set_preference.php — Save user preferences
error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
session_start();
require_once '../../config/db.php';
header('Content-Type: application/json');

// Helper to output JSON and exit cleanly
function sendResponse($data) {
    ob_clean();
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['user_id'])) { 
    http_response_code(401); 
    sendResponse(['success'=>false, 'error'=>'Session expired']); 
}

$uid    = $_SESSION['user_id'];
$action = $_REQUEST['action'] ?? '';

// Auto-create table removed

try {
    // ── Save Profile (Name, Title & Password) ─────────
    if ($action === 'save_profile') {
        $name     = trim($_POST['full_name'] ?? '');
        $title    = trim($_POST['title'] ?? '');
        $password = trim($_POST['password']  ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $address  = trim($_POST['address'] ?? '');
        
        if (empty($name)) sendResponse(['success'=>false,'error'=>'Nama tidak boleh kosong']);
        $title = !empty($title) ? htmlspecialchars($title) : null;

        if (!empty($password)) {
            if (strlen($password) < 6) sendResponse(['success'=>false,'error'=>'Password minimal 6 karakter']);
            $pdo->prepare("UPDATE users SET full_name=?, title=?, password=?, phone=?, address=? WHERE id=?")
                ->execute([$name, $title, password_hash($password, PASSWORD_DEFAULT), $phone, $address, $uid]);
        } else {
            $pdo->prepare("UPDATE users SET full_name=?, title=?, phone=?, address=? WHERE id=?")
                ->execute([$name, $title, $phone, $address, $uid]);
        }
        $_SESSION['full_name'] = $name;
        sendResponse(['success'=>true]);
    }

    // ── Upload Profile Picture ────────────────────────
    if ($action === 'upload_profile_pic') {
        if (!isset($_FILES['pic']) || $_FILES['pic']['error'] !== UPLOAD_ERR_OK) {
            sendResponse(['success'=>false,'error'=>'Gagal menerima file']);
        }
        $ext = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) {
            sendResponse(['success'=>false,'error'=>'Format file harus JPG/PNG/WebP']);
        }
        $dir = '../../uploads/profile_pics/';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $filename = 'pp_' . $uid . '_' . substr(md5(time()),0,6) . '.' . $ext;
        if (!move_uploaded_file($_FILES['pic']['tmp_name'], $dir . $filename)) {
            sendResponse(['success'=>false,'error'=>'Gagal menyimpan foto']);
        }
        $url = 'uploads/profile_pics/' . $filename;
        $pdo->prepare("UPDATE users SET profile_pic=? WHERE id=?")->execute([$url, $uid]);
        $_SESSION['profile_pic'] = $url;
        sendResponse(['success'=>true, 'url'=>$url]);
    }

    // ── Save Background (color or image) ────────────
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
        sendResponse(['success'=>true]);
    }

    // ── Upload Background Image ─────────────────────────
    if ($action === 'upload_bg') {
        if (!isset($_FILES['bg_image']) || $_FILES['bg_image']['error'] !== UPLOAD_ERR_OK) {
            sendResponse(['success'=>false,'error'=>'Upload gagal']);
        }
        $ext = strtolower(pathinfo($_FILES['bg_image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            sendResponse(['success'=>false,'error'=>'Format tidak didukung']);
        }
        $dir = '../../uploads/backgrounds/';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $filename = 'bg_' . $uid . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($_FILES['bg_image']['tmp_name'], $dir . $filename)) {
            sendResponse(['success'=>false,'error'=>'Gagal menyimpan file']);
        }
        $url = 'uploads/backgrounds/' . $filename;

        $chk = $pdo->prepare("SELECT id FROM user_preferences WHERE user_id=?");
        $chk->execute([$uid]);
        if ($chk->fetch()) {
            $pdo->prepare("UPDATE user_preferences SET bg_type='image', bg_value=? WHERE user_id=?")->execute([$url, $uid]);
        } else {
            $pdo->prepare("INSERT INTO user_preferences (user_id, bg_type, bg_value) VALUES (?,'image',?)")->execute([$uid, $url]);
        }
        sendResponse(['success'=>true, 'url'=>$url]);
    }

    // ── Agenda Actions ──────────────────────────
    if ($action === 'add_agenda') {
        $date  = $_POST['date'] ?? date('Y-m-d');
        $title = trim($_POST['title'] ?? '');
        $cat   = trim($_POST['category'] ?? 'TUGAS');
        if (empty($title)) sendResponse(['success'=>false, 'error'=>'Judul wajib diisi']);
        $pdo->prepare("INSERT INTO calendar_events (user_id, event_date, title, category) VALUES (?, ?, ?, ?)")
            ->execute([$uid, $date, $title, $cat]);
        sendResponse(['success'=>true]);
    }

    if ($action === 'get_agendas') {
        $stmt = $pdo->prepare("SELECT id, event_date, title FROM calendar_events WHERE user_id = ? ORDER BY event_date ASC");
        $stmt->execute([$uid]);
        sendResponse(['success'=>true, 'agendas'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'delete_agenda') {
        $id = $_POST['id'] ?? 0;
        $pdo->prepare("DELETE FROM calendar_events WHERE id = ? AND user_id = ?")->execute([$id, $uid]);
        sendResponse(['success'=>true]);
    }

    // ── Community Actions ──────────────────────────
    if ($action === 'create_community') {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if (empty($name)) sendResponse(['success'=>false, 'error'=>'Nama komunitas wajib diisi']);
        
        $pdo->prepare("INSERT INTO communities (name, description, owner_id) VALUES (?, ?, ?)")
            ->execute([$name, $desc, $uid]);
        $commId = $pdo->lastInsertId();
        
        // Auto-join creator as owner
        $pdo->prepare("INSERT INTO community_members (community_id, user_id, role) VALUES (?, ?, 'owner')")
            ->execute([$commId, $uid]);
        
        sendResponse(['success'=>true, 'id'=>$commId]);
    }

    if ($action === 'delete_community') {
        $commId = intval($_POST['community_id'] ?? 0);
        $chk = $pdo->prepare("SELECT owner_id FROM communities WHERE id = ?");
        $chk->execute([$commId]);
        $comm = $chk->fetch();
        if (!$comm || $comm['owner_id'] != $uid) sendResponse(['success'=>false, 'error'=>'Hanya pemilik yang bisa menghapus']);
        
        $pdo->prepare("DELETE FROM community_messages WHERE community_id = ?")->execute([$commId]);
        $pdo->prepare("DELETE FROM community_members WHERE community_id = ?")->execute([$commId]);
        $pdo->prepare("DELETE FROM communities WHERE id = ?")->execute([$commId]);
        sendResponse(['success'=>true]);
    }

    if ($action === 'leave_community') {
        $commId = intval($_POST['community_id'] ?? 0);
        $pdo->prepare("DELETE FROM community_members WHERE community_id = ? AND user_id = ?")->execute([$commId, $uid]);
        sendResponse(['success'=>true]);
    }

    if ($action === 'get_community_messages') {
        $commId = intval($_GET['community_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT cm.*, u.full_name, u.profile_pic 
                               FROM community_messages cm 
                               JOIN users u ON cm.user_id = u.id 
                               WHERE cm.community_id = ? 
                               ORDER BY cm.created_at ASC LIMIT 100");
        $stmt->execute([$commId]);
        sendResponse(['success'=>true, 'messages'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'send_community_message') {
        $commId = intval($_POST['community_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');
        if (empty($message)) sendResponse(['success'=>false, 'error'=>'Pesan kosong']);
        
        // Check if chat is restricted
        $stmt = $pdo->prepare("SELECT chat_disabled FROM communities WHERE id = ?");
        $stmt->execute([$commId]);
        $comm = $stmt->fetch();
        
        if ($comm && $comm['chat_disabled']) {
            $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
            $chk->execute([$commId, $uid]);
            $mem = $chk->fetch();
            if (!$mem || !in_array($mem['role'], ['owner', 'admin'])) {
                sendResponse(['success'=>false, 'error'=>'Hanya Admin dan Pemilik yang bisa mengirim pesan di grup ini']);
            }
        }

        $pdo->prepare("INSERT INTO community_messages (community_id, user_id, message) VALUES (?, ?, ?)")
            ->execute([$commId, $uid, $message]);
        
        // Log activity
        $stmt = $pdo->prepare("SELECT name FROM communities WHERE id = ?");
        $stmt->execute([$commId]);
        $c = $stmt->fetch();
        $cname = $c ? $c['name'] : 'Komunitas';
        
        $pdo->prepare("INSERT INTO user_activities (user_id, activity_type, description) VALUES (?, 'chat_message', ?)")
            ->execute([$uid, "Mengirim pesan di komunitas $cname"]);
            
        sendResponse(['success'=>true]);
    }

    if ($action === 'toggle_community_chat') {
        $commId = intval($_POST['community_id'] ?? 0);
        $disabled = intval($_POST['disabled'] ?? 0);
        $chk = $pdo->prepare("SELECT owner_id FROM communities WHERE id = ?");
        $chk->execute([$commId]);
        $comm = $chk->fetch();
        if (!$comm || $comm['owner_id'] != $uid) sendResponse(['success'=>false, 'error'=>'Akses ditolak']);
        $pdo->prepare("UPDATE communities SET chat_disabled = ? WHERE id = ?")->execute([$disabled, $commId]);
        sendResponse(['success'=>true]);
    }

    if ($action === 'get_community_members') {
        $commId = intval($_GET['community_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT cm.*, u.full_name, u.profile_pic, u.username
                               FROM community_members cm
                               JOIN users u ON cm.user_id = u.id
                               WHERE cm.community_id = ? AND (cm.is_banned = 0 OR cm.is_banned IS NULL)
                               ORDER BY CASE cm.role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END");
        $stmt->execute([$commId]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Also fetch community details (name, description, vision, mission)
        $commStmt = $pdo->prepare("SELECT name, description, vision, mission FROM communities WHERE id = ?");
        $commStmt->execute([$commId]);
        $community = $commStmt->fetch(PDO::FETCH_ASSOC);

        sendResponse(['success'=>true, 'members'=>$members, 'community'=>$community ?: null]);
    }

    if ($action === 'kick_community_member') {
        $commId = intval($_POST['community_id'] ?? 0);
        $targetId = intval($_POST['user_id'] ?? 0);
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $req = $chk->fetch();
        if (!$req || !in_array($req['role'], ['owner', 'admin'])) sendResponse(['success'=>false, 'error'=>'Akses ditolak']);
        
        $chk2 = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk2->execute([$commId, $targetId]);
        $target = $chk2->fetch();
        
        if (!$target) sendResponse(['success'=>false, 'error'=>'Anggota tidak ditemukan']);
        if ($target['role'] === 'owner') sendResponse(['success'=>false, 'error'=>'Tidak bisa mengeluarkan pemilik']);
        if ($req['role'] === 'admin' && $target['role'] === 'admin') sendResponse(['success'=>false, 'error'=>'Admin tidak bisa mengeluarkan admin lain']);
        
        $pdo->prepare("DELETE FROM community_members WHERE community_id = ? AND user_id = ?")->execute([$commId, $targetId]);
        sendResponse(['success'=>true]);
    }

    if ($action === 'update_member_role') {
        $commId = intval($_POST['community_id'] ?? 0);
        $targetId = intval($_POST['user_id'] ?? 0);
        $newRole = $_POST['role'] ?? 'member';
        
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $req = $chk->fetch();
        if (!$req || $req['role'] !== 'owner') sendResponse(['success'=>false, 'error'=>'Hanya pemilik yang bisa merubah role']);
        
        if ($targetId == $uid) sendResponse(['success'=>false, 'error'=>'Tidak bisa merubah role sendiri']);
        
        $pdo->prepare("UPDATE community_members SET role = ? WHERE community_id = ? AND user_id = ?")
            ->execute([$newRole, $commId, $targetId]);
        sendResponse(['success'=>true]);
    }

    if ($action === 'search_communities') {
        $q = '%' . ($_GET['q'] ?? '') . '%';
        $stmt = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM community_members WHERE community_id = c.id AND (is_banned = 0 OR is_banned IS NULL)) as member_count
                               FROM communities c
                               WHERE c.name LIKE ? OR c.description LIKE ?
                               ORDER BY c.created_at DESC");
        $stmt->execute([$q, $q]);
        sendResponse(['success'=>true, 'communities'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'join_community') {
        $commId = intval($_POST['community_id'] ?? 0);
        $pdo->prepare("INSERT IGNORE INTO community_members (community_id, user_id, role) VALUES (?, ?, 'member')")
            ->execute([$commId, $uid]);
        sendResponse(['success'=>true]);
    }

    if ($action === 'video_pulse') {
        $commId = intval($_POST['community_id'] ?? 0);
        $pdo->prepare("UPDATE community_members SET last_video_pulse = CURRENT_TIMESTAMP WHERE community_id = ? AND user_id = ?")
            ->execute([$commId, $uid]);
        sendResponse(['success'=>true]);
    }

    if ($action === 'get_active_video_users') {
        $commId = intval($_GET['community_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.profile_pic 
                               FROM community_members cm 
                               JOIN users u ON cm.user_id = u.id 
                               WHERE cm.community_id = ? AND cm.last_video_pulse > (CURRENT_TIMESTAMP - INTERVAL 15 SECOND)");
        $stmt->execute([$commId]);
        sendResponse(['success'=>true, 'users'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'claim_challenge') {
        $chId = intval($_POST['challenge_id'] ?? 0);
        $chk = $pdo->prepare("SELECT id FROM user_challenges WHERE user_id = ? AND challenge_id = ? AND is_claimed = 1");
        $chk->execute([$uid, $chId]);
        if ($chk->fetch()) sendResponse(['success'=>false, 'error'=>'Poin sudah diklaim']);
        
        $stmt = $pdo->prepare("SELECT points FROM challenges WHERE id = ?");
        $stmt->execute([$chId]);
        $ch = $stmt->fetch();
        if (!$ch) sendResponse(['success'=>false, 'error'=>'Tantangan tidak ditemukan']);
        
        $pdo->prepare("INSERT INTO user_challenges (user_id, challenge_id, is_claimed) VALUES (?, ?, 1) 
                       ON DUPLICATE KEY UPDATE is_claimed = 1")->execute([$uid, $chId]);
        
        $pdo->prepare("UPDATE users SET total_points = total_points + ? WHERE id = ?")->execute([$ch['points'], $uid]);
        
        $stmt = $pdo->prepare("SELECT total_points FROM users WHERE id = ?");
        $stmt->execute([$uid]);
        $userData = $stmt->fetch();
        $newLevel = floor($userData['total_points'] / 5) + 1;
        $pdo->prepare("UPDATE users SET current_level = ? WHERE id = ?")->execute([$newLevel, $uid]);
        
        sendResponse(['success'=>true, 'points'=>$ch['points'], 'new_level'=>$newLevel]);
    }

    if ($action === 'log_activity') {
        $type = $_POST['type'] ?? 'info';
        $desc = $_POST['description'] ?? '';
        $modId = !empty($_POST['module_id']) ? intval($_POST['module_id']) : null;
        $pdo->prepare("INSERT INTO user_activities (user_id, activity_type, module_id, description) VALUES (?, ?, ?, ?)")
            ->execute([$uid, $type, $modId, $desc]);
        sendResponse(['success'=>true]);
    }

    if ($action === 'get_activity_history') {
        $stmt = $pdo->prepare("SELECT * FROM user_activities WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$uid]);
        sendResponse(['success'=>true, 'history'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'get_login_history') {
        $stmt = $pdo->prepare("SELECT DATE(created_at) as date, TIME(created_at) as time FROM activity_log WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
        $stmt->execute([$uid]);
        sendResponse(['success'=>true, 'history'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'add_friend') {
        $friendId = intval($_POST['friend_id'] ?? 0);
        if ($friendId == $uid) sendResponse(['success' => false, 'error' => 'Tidak bisa menambah diri sendiri']);

        // Check if already friends
        $checkFriends = $pdo->prepare("SELECT id FROM friends WHERE (user_id = ? AND friend_id = ?) OR (user_id = ? AND friend_id = ?)");
        $checkFriends->execute([$uid, $friendId, $friendId, $uid]);
        if ($checkFriends->fetch()) sendResponse(['success' => false, 'error' => 'Sudah berteman']);

        // Check if request already pending
        $checkPending = $pdo->prepare("SELECT id FROM friends_requests WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)");
        $checkPending->execute([$uid, $friendId, $friendId, $uid]);
        if ($checkPending->fetch()) sendResponse(['success' => false, 'error' => 'Permintaan pertemanan sudah dikirim atau diterima']);

        // Insert into friends_requests table
        $pdo->prepare("INSERT INTO friends_requests (sender_id, receiver_id, status) VALUES (?, ?, 'pending')")
            ->execute([$uid, $friendId]);
        $requestId = $pdo->lastInsertId();

        $sName = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
        $sName->execute([$uid]);
        $sender = $sName->fetch();

        $pdo->prepare("INSERT INTO inbox (user_id, type, request_id, title, content) VALUES (?, 'friend_request', ?, 'Permintaan Pertemanan', ?)")
            ->execute([$friendId, $requestId, $sender['full_name'] . " ingin menjadi teman belajarmu!"]);

        sendResponse(['success' => true]);
    }

    if ($action === 'handle_friend_request') {
        $requestId = intval($_POST['request_id'] ?? 0);
        $status = $_POST['status'] ?? ''; // 'accept' or 'reject'

        $chk = $pdo->prepare("SELECT sender_id, receiver_id FROM friends_requests WHERE id = ? AND receiver_id = ? AND status = 'pending'");
        $chk->execute([$requestId, $uid]);
        $request = $chk->fetch();

        if (!$request) sendResponse(['success' => false, 'error' => 'Permintaan tidak ditemukan atau sudah diproses']);

        $senderId = $request['sender_id'];

        if ($status === 'accept') {
            $pdo->beginTransaction();
            // Add to friends table (both directions)
            $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted')")->execute([$senderId, $uid]);
            $pdo->prepare("INSERT INTO friends (user_id, friend_id, status) VALUES (?, ?, 'accepted')")->execute([$uid, $senderId]);
            // Update request status
            $pdo->prepare("UPDATE friends_requests SET status = 'accepted' WHERE id = ?")->execute([$requestId]);
            $pdo->commit();

            $sName = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $sName->execute([$uid]);
            $me = $sName->fetch();

            // Update inbox notification for the sender
            $pdo->prepare("UPDATE inbox SET type = ?, title = ?, content = ?, is_read = 0 WHERE user_id = ? AND type = 'friend_request' AND content LIKE ?")
                ->execute([
                    'system',
                    'Pertemanan Diterima',
                    $me['full_name'] . " telah menerima permintaan pertemananmu!",
                    $senderId,
                    '%'.$request['sender_id'].'%' // Match based on sender's ID in content
                ]);

            sendResponse(['success' => true]);

        } elseif ($status === 'reject') {
            $pdo->prepare("UPDATE friends_requests SET status = 'rejected' WHERE id = ?")->execute([$requestId]);
            
            // Update inbox notification for the sender
            $sName = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $sName->execute([$uid]);
            $me = $sName->fetch();

            $pdo->prepare("UPDATE inbox SET type = ?, title = ?, content = ?, is_read = 0 WHERE user_id = ? AND type = 'friend_request' AND content LIKE ?")
                ->execute([
                    'system',
                    'Permintaan Ditolak',
                    $me['full_name'] . " telah menolak permintaan pertemananmu.",
                    $senderId,
                    '%'.$request['sender_id'].'%' // Match based on sender's ID in content
                ]);

            sendResponse(['success' => true]);
        } else {
            sendResponse(['success' => false, 'error' => 'Status tidak valid']);
        }
    }

    if ($action === 'get_friend_list') {
        $stmt = $pdo->prepare("SELECT u.id, u.full_name, u.profile_pic, u.current_level as lvl, u.total_points as pts, f.status
                                FROM friends f
                                JOIN users u ON (f.friend_id = u.id AND f.user_id = ?) OR (f.user_id = u.id AND f.friend_id = ?)
                                WHERE f.status = 'accepted' AND u.id != ?");
        $stmt->execute([$uid, $uid, $uid]);
        sendResponse(['success' => true, 'friends' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'search_users') {
        $q = '%' . ($_GET['q'] ?? '') . '%';
        $stmt = $pdo->prepare("SELECT id, full_name, username, profile_pic, current_level as lvl, total_points as pts
                                FROM users
                                WHERE (full_name LIKE ? OR username LIKE ?) AND id != ? AND role = 'student'
                                LIMIT 15");
        $stmt->execute([$q, $q, $uid]);
        sendResponse(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'get_inbox') {
        $stmt = $pdo->prepare("SELECT * FROM inbox WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
        $stmt->execute([$uid]);
        sendResponse(['success'=>true, 'messages'=>$stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'mark_inbox_read') {
        $pdo->prepare("UPDATE inbox SET is_read = 1 WHERE user_id = ?")->execute([$uid]);
        sendResponse(['success'=>true]);
    }

    // ==================== Community Settings API Endpoints ====================

    // 1. update_community_info — Edit name, description, vision, mission (owner/admin)
    if ($action === 'update_community_info') {
        $commId = intval($_POST['community_id'] ?? 0);
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $mem = $chk->fetch();
        if (!$mem || !in_array($mem['role'], ['owner', 'admin'])) sendResponse(['success'=>false, 'error'=>'Akses ditolak']);
        
        $fields = [];
        $params = [];
        foreach (['name', 'description', 'vision', 'mission'] as $f) {
            if (isset($_POST[$f])) {
                $fields[] = "$f = ?";
                $params[] = trim($_POST[$f]);
            }
        }
        if (empty($fields)) sendResponse(['success'=>false, 'error'=>'Tidak ada perubahan']);
        $params[] = $commId;
        $pdo->prepare("UPDATE communities SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        sendResponse(['success'=>true]);
    }

    // 2. update_community_privacy — Toggle public/private (owner only)
    if ($action === 'update_community_privacy') {
        $commId = intval($_POST['community_id'] ?? 0);
        $privacy = in_array($_POST['privacy'] ?? '', ['public', 'private']) ? $_POST['privacy'] : 'public';
        $chk = $pdo->prepare("SELECT owner_id FROM communities WHERE id = ?");
        $chk->execute([$commId]);
        $comm = $chk->fetch();
        if (!$comm || $comm['owner_id'] != $uid) sendResponse(['success'=>false, 'error'=>'Hanya pemilik']);
        $pdo->prepare("UPDATE communities SET privacy = ? WHERE id = ?")->execute([$privacy, $commId]);
        sendResponse(['success'=>true]);
    }

    // 3. generate_invite_code — Generate/reset invite code (owner/admin)
    if ($action === 'generate_invite_code') {
        $commId = intval($_POST['community_id'] ?? 0);
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $mem = $chk->fetch();
        if (!$mem || !in_array($mem['role'], ['owner', 'admin'])) sendResponse(['success'=>false, 'error'=>'Akses ditolak']);
        $code = strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
        $pdo->prepare("UPDATE communities SET invite_code = ? WHERE id = ?")->execute([$code, $commId]);
        sendResponse(['success'=>true, 'invite_code'=>$code]);
    }

    // 4. join_by_invite — Join community using invite code (any user)
    if ($action === 'join_by_invite') {
        $code = trim($_POST['invite_code'] ?? '');
        if (empty($code)) sendResponse(['success'=>false, 'error'=>'Kode undangan kosong']);
        $stmt = $pdo->prepare("SELECT id, max_members FROM communities WHERE invite_code = ?");
        $stmt->execute([$code]);
        $comm = $stmt->fetch();
        if (!$comm) sendResponse(['success'=>false, 'error'=>'Kode undangan tidak valid']);
        
        // Check max members
        if ($comm['max_members'] > 0) {
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM community_members WHERE community_id = ? AND (is_banned = 0 OR is_banned IS NULL)");
            $cnt->execute([$comm['id']]);
            if ($cnt->fetchColumn() >= $comm['max_members']) sendResponse(['success'=>false, 'error'=>'Komunitas sudah penuh']);
        }
        
        // Check if banned
        $ban = $pdo->prepare("SELECT is_banned FROM community_members WHERE community_id = ? AND user_id = ?");
        $ban->execute([$comm['id'], $uid]);
        $existing = $ban->fetch();
        if ($existing && $existing['is_banned']) sendResponse(['success'=>false, 'error'=>'Kamu telah dibanned dari komunitas ini']);
        
        $pdo->prepare("INSERT IGNORE INTO community_members (community_id, user_id, role) VALUES (?, ?, 'member')")->execute([$comm['id'], $uid]);
        sendResponse(['success'=>true, 'community_id'=>$comm['id']]);
    }

    // 5. ban_community_member — Ban a user (owner/admin)
    if ($action === 'ban_community_member') {
        $commId = intval($_POST['community_id'] ?? 0);
        $targetId = intval($_POST['user_id'] ?? 0);
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $req = $chk->fetch();
        if (!$req || !in_array($req['role'], ['owner', 'admin'])) sendResponse(['success'=>false, 'error'=>'Akses ditolak']);
        
        // Can't ban owner
        $chk2 = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk2->execute([$commId, $targetId]);
        $target = $chk2->fetch();
        if ($target && $target['role'] === 'owner') sendResponse(['success'=>false, 'error'=>'Tidak bisa ban pemilik']);
        // Admin can't ban admin
        if ($req['role'] === 'admin' && $target && $target['role'] === 'admin') sendResponse(['success'=>false, 'error'=>'Admin tidak bisa ban admin lain']);
        
        $pdo->prepare("UPDATE community_members SET is_banned = 1 WHERE community_id = ? AND user_id = ?")->execute([$commId, $targetId]);
        sendResponse(['success'=>true]);
    }

    // 6. unban_community_member — Unban a user (owner/admin)
    if ($action === 'unban_community_member') {
        $commId = intval($_POST['community_id'] ?? 0);
        $targetId = intval($_POST['user_id'] ?? 0);
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $req = $chk->fetch();
        if (!$req || !in_array($req['role'], ['owner', 'admin'])) sendResponse(['success'=>false, 'error'=>'Akses ditolak']);
        $pdo->prepare("UPDATE community_members SET is_banned = 0 WHERE community_id = ? AND user_id = ?")->execute([$commId, $targetId]);
        sendResponse(['success'=>true]);
    }

    // 7. invite_member_by_username — Invite by username search (owner/admin)
    if ($action === 'invite_member_by_username') {
        $commId = intval($_POST['community_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $req = $chk->fetch();
        if (!$req || !in_array($req['role'], ['owner', 'admin'])) sendResponse(['success'=>false, 'error'=>'Akses ditolak']);
        
        $user = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $user->execute([$username]);
        $u = $user->fetch();
        if (!$u) sendResponse(['success'=>false, 'error'=>'User tidak ditemukan']);
        
        // Check if already member
        $exist = $pdo->prepare("SELECT id, is_banned FROM community_members WHERE community_id = ? AND user_id = ?");
        $exist->execute([$commId, $u['id']]);
        $ex = $exist->fetch();
        if ($ex && !$ex['is_banned']) sendResponse(['success'=>false, 'error'=>'User sudah menjadi anggota']);
        if ($ex && $ex['is_banned']) sendResponse(['success'=>false, 'error'=>'User telah dibanned, unban dulu']);
        
        $pdo->prepare("INSERT INTO community_members (community_id, user_id, role) VALUES (?, ?, 'member')")->execute([$commId, $u['id']]);
        sendResponse(['success'=>true]);
    }

    // 8. set_community_slowmode — Set slowmode seconds (owner/admin)
    if ($action === 'set_community_slowmode') {
        $commId = intval($_POST['community_id'] ?? 0);
        $seconds = intval($_POST['seconds'] ?? 0);
        if (!in_array($seconds, [0, 10, 30, 60, 300])) $seconds = 0;
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $req = $chk->fetch();
        if (!$req || !in_array($req['role'], ['owner', 'admin'])) sendResponse(['success'=>false, 'error'=>'Akses ditolak']);
        $pdo->prepare("UPDATE communities SET slowmode_seconds = ? WHERE id = ?")->execute([$seconds, $commId]);
        sendResponse(['success'=>true]);
    }

    // 9. clear_community_messages — Delete all messages (owner only)
    if ($action === 'clear_community_messages') {
        $commId = intval($_POST['community_id'] ?? 0);
        $chk = $pdo->prepare("SELECT owner_id FROM communities WHERE id = ?");
        $chk->execute([$commId]);
        $comm = $chk->fetch();
        if (!$comm || $comm['owner_id'] != $uid) sendResponse(['success'=>false, 'error'=>'Hanya pemilik']);
        $pdo->prepare("DELETE FROM community_messages WHERE community_id = ?")->execute([$commId]);
        sendResponse(['success'=>true]);
    }

    // 10. update_video_settings — Video room settings (owner only)
    if ($action === 'update_video_settings') {
        $commId = intval($_POST['community_id'] ?? 0);
        $chk = $pdo->prepare("SELECT owner_id FROM communities WHERE id = ?");
        $chk->execute([$commId]);
        $comm = $chk->fetch();
        if (!$comm || $comm['owner_id'] != $uid) sendResponse(['success'=>false, 'error'=>'Hanya pemilik']);
        
        $fields = [];
        $params = [];
        if (isset($_POST['video_disabled'])) { $fields[] = "video_disabled = ?"; $params[] = intval($_POST['video_disabled']); }
        if (isset($_POST['video_max_participants'])) { $fields[] = "video_max_participants = ?"; $params[] = intval($_POST['video_max_participants']); }
        if (isset($_POST['video_admin_only'])) { $fields[] = "video_admin_only = ?"; $params[] = intval($_POST['video_admin_only']); }
        if (empty($fields)) sendResponse(['success'=>false, 'error'=>'Tidak ada perubahan']);
        $params[] = $commId;
        $pdo->prepare("UPDATE communities SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);
        sendResponse(['success'=>true]);
    }

    // 11. update_notification_prefs — Per-user notification preferences
    if ($action === 'update_notification_prefs') {
        $commId = intval($_POST['community_id'] ?? 0);
        $fields = [];
        $params = [];
        if (isset($_POST['notify_messages'])) { $fields[] = "notify_messages = ?"; $params[] = intval($_POST['notify_messages']); }
        if (isset($_POST['notify_members'])) { $fields[] = "notify_members = ?"; $params[] = intval($_POST['notify_members']); }
        if (empty($fields)) sendResponse(['success'=>false, 'error'=>'Tidak ada perubahan']);
        $params[] = $commId;
        $params[] = $uid;
        $pdo->prepare("UPDATE community_members SET " . implode(', ', $fields) . " WHERE community_id = ? AND user_id = ?")->execute($params);
        sendResponse(['success'=>true]);
    }

    // 12. transfer_ownership — Transfer owner to another member (owner only)
    if ($action === 'transfer_ownership') {
        $commId = intval($_POST['community_id'] ?? 0);
        $newOwnerId = intval($_POST['new_owner_id'] ?? 0);
        $chk = $pdo->prepare("SELECT owner_id FROM communities WHERE id = ?");
        $chk->execute([$commId]);
        $comm = $chk->fetch();
        if (!$comm || $comm['owner_id'] != $uid) sendResponse(['success'=>false, 'error'=>'Hanya pemilik']);
        
        // Verify new owner is a member
        $mem = $pdo->prepare("SELECT id FROM community_members WHERE community_id = ? AND user_id = ? AND (is_banned = 0 OR is_banned IS NULL)");
        $mem->execute([$commId, $newOwnerId]);
        if (!$mem->fetch()) sendResponse(['success'=>false, 'error'=>'User bukan anggota aktif']);
        
        $pdo->beginTransaction();
        $pdo->prepare("UPDATE communities SET owner_id = ? WHERE id = ?")->execute([$newOwnerId, $commId]);
        $pdo->prepare("UPDATE community_members SET role = 'admin' WHERE community_id = ? AND user_id = ?")->execute([$commId, $uid]);
        $pdo->prepare("UPDATE community_members SET role = 'owner' WHERE community_id = ? AND user_id = ?")->execute([$commId, $newOwnerId]);
        $pdo->commit();
        sendResponse(['success'=>true]);
    }

    // 13. update_community_rules — Set community rules (owner/admin)
    if ($action === 'update_community_rules') {
        $commId = intval($_POST['community_id'] ?? 0);
        $rules = trim($_POST['rules'] ?? '');
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $mem = $chk->fetch();
        if (!$mem || !in_array($mem['role'], ['owner', 'admin'])) sendResponse(['success'=>false, 'error'=>'Akses ditolak']);
        $pdo->prepare("UPDATE communities SET rules = ? WHERE id = ?")->execute([$rules, $commId]);
        sendResponse(['success'=>true]);
    }

    // 14. update_welcome_message — Set welcome message for new members (owner/admin)
    if ($action === 'update_welcome_message') {
        $commId = intval($_POST['community_id'] ?? 0);
        $msg = trim($_POST['welcome_message'] ?? '');
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $mem = $chk->fetch();
        if (!$mem || !in_array($mem['role'], ['owner', 'admin'])) sendResponse(['success'=>false, 'error'=>'Akses ditolak']);
        $pdo->prepare("UPDATE communities SET welcome_message = ? WHERE id = ?")->execute([$msg, $commId]);
        sendResponse(['success'=>true]);
    }

    // 15. update_max_members — Set max member limit (owner only)
    if ($action === 'update_max_members') {
        $commId = intval($_POST['community_id'] ?? 0);
        $max = intval($_POST['max_members'] ?? 0);
        $chk = $pdo->prepare("SELECT owner_id FROM communities WHERE id = ?");
        $chk->execute([$commId]);
        $comm = $chk->fetch();
        if (!$comm || $comm['owner_id'] != $uid) sendResponse(['success'=>false, 'error'=>'Hanya pemilik']);
        $pdo->prepare("UPDATE communities SET max_members = ? WHERE id = ?")->execute([$max, $commId]);
        sendResponse(['success'=>true]);
    }

    // 16. get_community_settings — Get all settings for a community (any member)
    if ($action === 'get_community_settings') {
        $commId = intval($_GET['community_id'] ?? 0);
        
        // Check membership
        $chk = $pdo->prepare("SELECT role, notify_messages, notify_members FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $mem = $chk->fetch();
        if (!$mem) sendResponse(['success'=>false, 'error'=>'Bukan anggota']);
        
        // Get community details
        $stmt = $pdo->prepare("SELECT c.*, (SELECT COUNT(*) FROM community_members WHERE community_id = c.id AND (is_banned = 0 OR is_banned IS NULL)) as member_count FROM communities c WHERE c.id = ?");
        $stmt->execute([$commId]);
        $comm = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$comm) sendResponse(['success'=>false, 'error'=>'Komunitas tidak ditemukan']);
        
        // Get members
        $mstmt = $pdo->prepare("SELECT cm.user_id, cm.role, cm.is_banned, cm.created_at AS joined_at, u.full_name, u.profile_pic, u.username
                                FROM community_members cm
                                JOIN users u ON cm.user_id = u.id
                                WHERE cm.community_id = ?
                                ORDER BY CASE cm.role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END, cm.created_at ASC");
        $mstmt->execute([$commId]);
        $members = $mstmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get banned members
        $bstmt = $pdo->prepare("SELECT cm.user_id, u.full_name, u.username
                                FROM community_members cm
                                JOIN users u ON cm.user_id = u.id
                                WHERE cm.community_id = ? AND cm.is_banned = 1");
        $bstmt->execute([$commId]);
        $banned = $bstmt->fetchAll(PDO::FETCH_ASSOC);
        
        sendResponse([
            'success' => true,
            'community' => $comm,
            'members' => $members,
            'banned' => $banned,
            'my_role' => $mem['role'],
            'my_prefs' => [
                'notify_messages' => (int)$mem['notify_messages'],
                'notify_members' => (int)$mem['notify_members']
            ]
        ]);
    }

    // 17. upload_community_image — Upload community avatar/image (owner/admin)
    if ($action === 'upload_community_image') {
        $commId = intval($_POST['community_id'] ?? 0);
        if (!$commId) sendResponse(['success'=>false, 'error'=>'ID komunitas tidak valid']);

        // Check membership & role
        $chk = $pdo->prepare("SELECT role FROM community_members WHERE community_id = ? AND user_id = ?");
        $chk->execute([$commId, $uid]);
        $mem = $chk->fetch();
        if (!$mem || !in_array($mem['role'], ['owner', 'admin'])) {
            sendResponse(['success'=>false, 'error'=>'Hanya owner/admin yang bisa mengubah gambar']);
        }

        // Validate file
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            sendResponse(['success'=>false, 'error'=>'Gagal menerima file']);
        }
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            sendResponse(['success'=>false, 'error'=>'File terlalu besar (maks 2MB)']);
        }
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
            sendResponse(['success'=>false, 'error'=>'Format file harus JPG/PNG/WEBP/GIF']);
        }

        // Create directory
        $dir = '../../uploads/community_pics/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        // Save file
        $filename = 'comm_' . $commId . '_' . time() . '.' . $ext;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
            sendResponse(['success'=>false, 'error'=>'Gagal menyimpan file']);
        }
        $url = 'uploads/community_pics/' . $filename;

        // Update database
        $pdo->prepare("UPDATE communities SET image = ? WHERE id = ?")->execute([$url, $commId]);

        sendResponse(['success'=>true, 'image_url'=>$url]);
    }

} catch(Throwable $e) {
    sendResponse(['success'=>false, 'error'=>$e->getMessage()]);
}
exit;
