<?php
// src/views/dashboard.php — StepUp v4
error_reporting(E_ALL);
ini_set('display_errors', 0);
session_start();

// Guest mode: allow access without login, but restrict features
$isGuest = !isset($_SESSION['user_id']);
$userId  = $isGuest ? null : $_SESSION['user_id'];

require_once '../../config/db.php';

// Auto-create tables if they don't exist (safe on any host)
try {
    try { $pdo->exec("ALTER TABLE inbox CHANGE message content TEXT"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE inbox ADD COLUMN request_id INT DEFAULT NULL AFTER type"); } catch(Exception $e) {}

    // Silent Column Migration
    try {
        $pdo->exec("ALTER TABLE user_preferences ADD COLUMN glass_opacity INT DEFAULT 50 AFTER bg_value");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN title VARCHAR(100) DEFAULT NULL");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN profile_pic VARCHAR(255) DEFAULT NULL");
    } catch (Exception $e) {
    }
    // Challenge questions table logic removed
} catch (Exception $e) {
}
// Log today's activity (only for logged-in users)
if (!$isGuest) {
    try {
        $stmt = $pdo->prepare("INSERT IGNORE INTO activity_log (user_id, activity_date) VALUES (?, ?)");
        $stmt->execute([$userId, date('Y-m-d')]);
    } catch (Exception $e) {}
}

if ($isGuest) {
    // Guest user — pakai data placeholder
    $user = [
        'id' => null, 'username' => 'tamu', 'full_name' => 'Tamu',
        'email' => '', 'profile_pic' => null, 'role' => 'student',
        'current_level' => 1, 'total_points' => 0
    ];
} else {
    $stmt = $pdo->prepare("SELECT *, COALESCE(total_points, 0) as total_points, COALESCE(current_level, 1) as current_level FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    // Recalculate Level Logic: Level 1 (0-4 pts), Level 2 (5-14 pts), Level 3 (15-29 pts)...
    // Requirement: "kenaikan level itu butuh poin kelipatan 5"
    // Lvl 1: 0 (total needed for lvl 2: 5)
    // Lvl 2: 5 (total needed for lvl 3: 5 + 10 = 15)
    // Lvl 3: 15 (total needed for lvl 4: 15 + 15 = 30)
    // Lvl n: sum_{i=1}^{n-1} (5*i) = 5 * (n-1)*n / 2
    
    $pts = (int)$user['total_points'];
    $lvl = 1;
    while ($pts >= (5 * $lvl * ($lvl + 1) / 2)) {
        $lvl++;
    }
    
    if ($lvl != $user['current_level']) {
        $pdo->prepare("UPDATE users SET current_level = ? WHERE id = ?")->execute([$lvl, $userId]);
        $user['current_level'] = $lvl;
    }
}
if (!$user) {
    session_destroy();
    header("Location: ../../index.php");
    exit;
}

// Preferences — Default = light color
$pref = ['bg_type' => 'color', 'bg_value' => '#f8fafc', 'glass_opacity' => 50];

try {
    $p = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id=?");
    $p->execute([$userId]);
    $pr = $p->fetch();
    if ($pr) {
        if (!empty($pr['bg_value'])) {
            $pref['bg_type'] = $pr['bg_type'];
            $pref['bg_value'] = $pr['bg_value'];
        }
        if (isset($pr['glass_opacity'])) {
            $pref['glass_opacity'] = intval($pr['glass_opacity']);
        }
    }
} catch (Exception $e) {}

// Random quote
$quote = "Belajar hari ini adalah investasi terbaik untuk masa depanmu.";
try {
    $q = $pdo->query("SELECT text FROM motivational_quotes ORDER BY RAND() LIMIT 1")->fetch();
    if ($q)
        $quote = $q['text'];
} catch (Exception $e) {
}

// Fetch Inbox for current user
$inbox_count = 0;
if (!$isGuest) {
    try {
        $stIn = $pdo->prepare("SELECT COUNT(*) FROM inbox WHERE user_id = ? AND is_read = 0");
        $stIn->execute([$userId]);
        $inbox_count = $stIn->fetchColumn();
    } catch (Exception $e) {}
}

// Enhanced Subjects with Completion data
$subjects = [];
$all_progress = [];
try {
    $stAllP = $pdo->prepare("SELECT subject_slug, topic_slug, module_slug, MAX(is_completed) as is_completed FROM progress WHERE user_id = ? GROUP BY subject_slug, topic_slug, module_slug");
    $stAllP->execute([$userId]);
    foreach ($stAllP->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $p_s = preg_replace('/[^a-z0-9]/', '', strtolower($row['subject_slug']));
        $p_t = preg_replace('/[^a-z0-9]/', '', strtolower($row['topic_slug']));
        $p_m = preg_replace('/[^a-z0-9]/', '', strtolower($row['module_slug']));
        if (!isset($all_progress[$p_s]))
            $all_progress[$p_s] = [];
        if (!isset($all_progress[$p_s][$p_t]))
            $all_progress[$p_s][$p_t] = [];
        if (!isset($all_progress[$p_s][$p_t][$p_m]) || $row['is_completed'] == 1) {
            $all_progress[$p_s][$p_t][$p_m] = $row['is_completed'];
        }
    }
} catch (Exception $e) {}

// Fetch Real Challenges
$current_challenges = [];
$next_challenges = [];
try {
    $stmtCh = $pdo->prepare("SELECT * FROM challenges WHERE is_active = 1 ORDER BY week_type DESC, created_at DESC");
    $stmtCh->execute();
    while($row = $stmtCh->fetch()) {
        if($row['week_type'] === 'current') $current_challenges[] = $row;
        else $next_challenges[] = $row;
    }
} catch (Exception $e) {}

// Fetch Real Friends
$friends = [];
try {
    $stmtFr = $pdo->prepare("SELECT u.id, u.full_name, u.profile_pic, COALESCE(u.total_points,0) as pts, COALESCE(u.current_level,1) as lvl 
                             FROM friends f JOIN users u ON f.friend_id = u.id 
                             WHERE f.user_id = ? AND f.status = 'accepted' LIMIT 10");
    $stmtFr->execute([$userId]);
    $friends = $stmtFr->fetchAll();
} catch (Exception $e) {}

// Fetch Real Communities
$communities = [];
try {
    $stmtC = $pdo->prepare("SELECT c.*,
                            (SELECT COUNT(*) FROM community_members WHERE community_id = c.id AND (is_banned = 0 OR is_banned IS NULL)) as member_count
                            FROM communities c
                            JOIN community_members cm ON c.id = cm.community_id
                            WHERE cm.user_id = ? AND (cm.is_banned = 0 OR cm.is_banned IS NULL)
                            ORDER BY c.created_at DESC");
    $stmtC->execute([$userId]);
    $communities = $stmtC->fetchAll();
} catch (Exception $e) {}

// Fetch Real Challenges with Claim Status
$db_challenges = [];
try {
    $stmtCh = $pdo->prepare("SELECT c.*, uc.is_completed, uc.is_claimed
                             FROM challenges c
                             LEFT JOIN user_challenges uc ON c.id = uc.challenge_id AND uc.user_id = ?
                             WHERE c.is_active = 1 ORDER BY c.week_type DESC, c.created_at DESC");
    $stmtCh->execute([$userId]);
    $db_challenges = $stmtCh->fetchAll();
} catch (Exception $e) {
    error_log("Error fetching challenges: " . $e->getMessage());
}

try {
   $qS = $pdo->query("SELECT * FROM subjects ORDER BY id");
    while ($sub = $qS->fetch(PDO::FETCH_ASSOC)) {
        // Normalisasi slug yang SANGAT ketat: trim, lowercase, spasi jadi minus
        $raw_slug = str_replace(' ', '-', trim($sub['slug']));
        $sl = strtolower($raw_slug);
        $nm = trim($sub['name'] ?? ($sub['title'] ?? 'Mapel'));
        $ic = trim($sub['icon'] ?? '');
        if (preg_match('/fa-[a-z0-9-]+/', $ic, $m)) {
            $ic = $m[0];
        } else if (empty($ic)) {
            if (stripos($nm, 'Matematik') !== false)
                $ic = 'fa-calculator';
            elseif (stripos($nm, 'PKN') !== false)
                $ic = 'fa-landmark';
            elseif (stripos($nm, 'Seni') !== false)
                $ic = 'fa-palette';
            else
                $ic = 'fa-book-open';
        }
        if (!isset($subjects[$sl])) {
            $subjects[$sl] = [
                'id' => $sub['id'],
                'slug' => $sl,
                'title' => $nm,
                'icon' => $ic, // default icon
                'color' => ($sub['color'] ?? '#2563eb'),
                'desc' => ($sub['description'] ?? 'Pelajari ' . $nm . ' di StepUp.'),
                'topics' => [],
                'completed_mods' => 0,
                'total_mods' => 0,
                'batik_bg' => ($sub['batik_bg'] ?? null)
            ];
        }
        $sub_ref = &$subjects[$sl];

        $stT = $pdo->prepare("SELECT * FROM topics WHERE subject_id=? ORDER BY id");
        $stT->execute([$sub['id']]);
        foreach ($stT->fetchAll(PDO::FETCH_ASSOC) as $top) {
            $ts = trim($top['slug'], " \t\n\r\0\x0B-");
            if (!isset($sub_ref['topics'][$ts])) {
                $sub_ref['topics'][$ts] = ['id' => $top['id'], 'slug' => $ts, 'title' => trim($top['name'] ?? ($top['title'] ?? 'Topik')), 'modules' => [], 'is_topic_completed' => true];
            }
            $top_ref = &$sub_ref['topics'][$ts];
            $stM = $pdo->prepare("SELECT * FROM modules WHERE topic_id=? ORDER BY id");
            $stM->execute([$top['id']]);
            $mods = $stM->fetchAll(PDO::FETCH_ASSOC);
            if (empty($mods))
                $top_ref['is_topic_completed'] = false;

            foreach ($mods as $mod) {
                $ms = trim($mod['slug'], " \t\n\r\0\x0B-"); // Trim whitespace and trailing hyphens

                // In-depth Universal Slug Matcher to prevent progress stalling
                // Power Clean Matcher: removes all non-alphanumeric
                $c_s = preg_replace('/[^a-z0-9]/', '', strtolower($sl));
                $c_t = preg_replace('/[^a-z0-9]/', '', strtolower($ts));
                $c_m = preg_replace('/[^a-z0-9]/', '', strtolower($ms));

                $is_done = false;
                if (isset($all_progress[$c_s][$c_t][$c_m]) && $all_progress[$c_s][$c_t][$c_m] == 1) {
                    $is_done = true;
                }

                $top_ref['modules'][$ms] = ['id' => $mod['id'], 'slug' => $ms, 'title' => trim($mod['name'] ?? ($mod['title'] ?? 'Modul')), 'is_completed' => $is_done];

                $sub_ref['total_mods']++;
                if ($is_done)
                    $sub_ref['completed_mods']++;
                else
                    $top_ref['is_topic_completed'] = false;
            }
        }
        $subjects[$sl]['progress'] = ($subjects[$sl]['total_mods'] > 0) ? round(($subjects[$sl]['completed_mods'] / $subjects[$sl]['total_mods']) * 100) : 0;
    }
} catch (Exception $e) {
}

// Global progress calculation
$g_total_mods = 0;
$g_done_mods = 0;
foreach ($subjects as $s) {
    $g_total_mods += $s['total_mods'];
    $g_done_mods += $s['completed_mods'];
}
// Calculate Real Streak
$streak_days = [];
$current_streak = 0;
try {
    $qS = $pdo->prepare("SELECT activity_date FROM activity_log WHERE user_id = ? ORDER BY activity_date DESC");
    $qS->execute([$userId]);
    $streak_days = $qS->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($streak_days)) {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        if ($streak_days[0] === $today || $streak_days[0] === $yesterday) {
            $current_streak = 0;
            $check_date = $streak_days[0];
            foreach ($streak_days as $date) {
                if ($date === $check_date) {
                    $current_streak++;
                    $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
                } else {
                    break;
                }
            }
        }
    }
} catch (Exception $e) {
}

$total_progress = ($g_total_mods > 0) ? round(($g_done_mods / $g_total_mods) * 100) : 0;

$ini = strtoupper(substr($user['full_name'] ?? 'T', 0, 1));
$fn = explode(' ', $user['full_name'] ?? 'Tamu')[0];

if ($pref['bg_type'] === 'image') {
    $imgParts = explode('/', $pref['bg_value']);
    $imgEncoded = implode('/', array_map('rawurlencode', $imgParts));
    $bgStyle = "background-image: linear-gradient(rgba(255,255,255,0.5),rgba(255,255,255,0.5)), url('../../$imgEncoded'); background-size: cover; background-attachment: fixed;";
} else if ($pref['bg_type'] === 'color' || $pref['bg_type'] === 'gradient') {
    $bgStyle = "background:" . ($pref['bg_value'] ?? '#f8fafc') . ";";
} else {
    $bgStyle = "background-color: #f8fafc;";
}

// ─── NEW FEATURES DATA FETCHING ───

// 1. Community Posts
$community_posts = [];
try {
    $stPosts = $pdo->query("SELECT p.*, u.full_name, u.username, u.profile_pic FROM community_posts p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 20");
    $community_posts = $stPosts->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// 2. Leaderboard (Top 100) - Requirement: Top 100
$leaderboard = [];
try {
    $stLeader = $pdo->query("
        SELECT id, full_name, username, profile_pic, total_points as points, current_level as level
        FROM users
        WHERE role = 'student'
        ORDER BY total_points DESC, id ASC
        LIMIT 100
    ");
    $leaderboard = $stLeader->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// 3. Active Challenges
$challenges = [];
try {
    $stChal = $pdo->query("SELECT * FROM challenges WHERE is_active = 1 ORDER BY created_at DESC");
    $challenges = $stChal->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// 4. User Badges
$user_badges = [];
try {
    $stBadges = $pdo->prepare("SELECT b.* FROM badges b JOIN user_badges ub ON b.id = ub.badge_id WHERE ub.user_id = ?");
    $stBadges->execute([$userId]);
    $user_badges = $stBadges->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}


// Fetch User Activities (for dashboard preview and popup)
$user_activities = [];
try {
    $stmt = $pdo->prepare("
        (SELECT
            CONVERT('Menyelesaikan Kuis' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS action,
            CONVERT(CAST(q.score AS CHAR) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS details,
            q.created_at AS time_ref,
            CONVERT('quiz' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS type,
            CONVERT(m.title USING utf8mb4) COLLATE utf8mb4_unicode_ci AS mod_name
        FROM quiz_scores q
        JOIN modules m ON REPLACE(REPLACE(q.module_slug, '-', ''), ' ', '') = REPLACE(REPLACE(m.slug, '-', ''), ' ', '')
        WHERE q.user_id = ?)
        UNION ALL
        (SELECT
            CONVERT('Membaca Modul' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS action,
            CONVERT(m.title USING utf8mb4) COLLATE utf8mb4_unicode_ci AS details,
            COALESCE(p.created_at, NOW()) AS time_ref,
            CONVERT('progress' USING utf8mb4) COLLATE utf8mb4_unicode_ci AS type,
            CONVERT(m.title USING utf8mb4) COLLATE utf8mb4_unicode_ci AS mod_name
        FROM progress p
        JOIN modules m ON REPLACE(REPLACE(p.module_slug, '-', ''), ' ', '') = REPLACE(REPLACE(m.slug, '-', ''), ' ', '')
        WHERE p.user_id = ?)
        ORDER BY time_ref DESC LIMIT 50
    ");
    $stmt->execute([$userId, $userId]);
    $user_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { error_log('Activities query error: ' . $e->getMessage()); }

// Fetch Inbox Data
$unread_inbox_count = 0;
$inbox_messages = [];
if (!$isGuest) {
    try {
        $stInCount = $pdo->prepare("SELECT COUNT(*) FROM inbox WHERE user_id = ? AND is_read = 0");
        $stInCount->execute([$userId]);
        $unread_inbox_count = $stInCount->fetchColumn();

        $stIn = $pdo->prepare("SELECT * FROM inbox WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
        $stIn->execute([$userId]);
        $inbox_messages = $stIn->fetchAll(PDO::FETCH_ASSOC);

        // Filter friend request messages for home page display
        $friend_requests_inbox = array_filter($inbox_messages, function($msg) {
            return $msg['type'] === 'friend_request';
        });

    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="id" class="">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <style>
        html {
            scroll-behavior: smooth;
        }

        html.dark {
            background-color: #060b1d;
        }

        .zoom-120 {
            transform: scale(1);
            transform-origin: top left;
        }
    </style>
    <title>Dashboard – StepUp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' };</script>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,600;0,700;0,800;1,600&display=swap"
        rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/peerjs@1.5.2/dist/peerjs.min.js"></script>
    <style>
        :root {
            --glass-opacity:
                <?php echo ($pref['glass_opacity'] ?? 50) / 100; ?>
            ;
        }

        * {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        body {
            overflow-x: hidden;
        }

        ::-webkit-scrollbar {
            width: 4px
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 8px
        }

        .dark ::-webkit-scrollbar-thumb {
            background: #334155
        }

        /* Nav */
        .navi {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 14px;
            border-radius: 14px;
            font-weight: 700;
            font-size: .875rem;
            color: #64748b;
            cursor: pointer;
            transition: all .2s;
            width: 100%;
            text-align: left;
        }

        .navi:hover {
            background: #f1f5f9;
            color: #1e293b;
            transform: translateX(3px);
        }

        .navi.active {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 8px 20px -5px rgba(37, 99, 235, .4);
        }

        .dark .navi {
            color: #94a3b8
        }

        .dark .navi:hover {
            background: #1e293b;
            color: #f1f5f9
        }

        .dark .navi.active {
            background: #2563eb;
            color: #fff
        }

        /* Tabs */
        .tp { 
            display: none; 
            animation: fadeIn .4s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(12px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .tp.on { display: block }

        /* AI Pulse */
        #stepup-asst-trigger {
            animation: pulse-blue 3s infinite;
            background: #2563eb !important; /* Requirement: Blue assistant */
        }
        @keyframes pulse-blue {
            0% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0.4); }
            70% { box-shadow: 0 0 0 20px rgba(37, 99, 235, 0); }
            100% { box-shadow: 0 0 0 0 rgba(37, 99, 235, 0); }
        }

        /* Chatbot Assistant color tweak */
        #stepup-asst-bubble-btn {
            background: #2563eb !important;
        }

        /* Calendar */
        .cd {
            aspect-ratio: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: 700;
            font-size: .85rem;
            color: #334155;
            transition: all .15s;
            position: relative;
        }

        .cd:hover:not(.emp) {
            background: rgba(37, 99, 235, .08);
            color: #2563eb;
            transform: scale(1.1)
        }

        .cd.tod {
            position: relative;
            color: #1e40af !important;
            font-weight: 900;
            border-radius: 50%;
            z-index: 1;
            background: transparent;
        }
        .cd.tod::before {
            content: ''; position: absolute; inset: 3px;
            border: 2.5px solid #2563eb; border-radius: 50%;
            background: rgba(37, 99, 235, 0.10);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.25);
            z-index: -1;
        }
        .dark .cd.tod { color: #93c5fd !important; }
        .dark .cd.tod::before { border-color: #3b82f6; background: rgba(59, 130, 246, 0.08); }

        .dark .cd {
            color: #94a3b8
        }

        .dark .cd:hover:not(.emp) {
            background: #1e293b;
            color: #60a5fa
        }

        /* Accordion */
        .acb {
            display: none
        }

        .acb.on {
            display: block
        }

        .aci {
            transition: transform .25s
        }

        .aci.on {
            transform: rotate(180deg)
        }

        /* Mobile sidebar */
        #sidebar {
            transition: transform .35s cubic-bezier(.77, 0, .175, 1)
        }

        @media(max-width:1023px) {
            #sidebar {
                position: fixed;
                top: 0;
                bottom: 0;
                left: 0;
                z-index: 200;
                transform: translateX(-100%)
            }

            #sidebar.on {
                transform: translateX(0)
            }
        }

        #sbk {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, .5);
            backdrop-filter: blur(4px);
            z-index: 199
        }

        #sbk.on {
            display: block
        }

        /* ── Responsive Fixes ── */
        @media(max-width: 768px) {
            .quote-text { font-size: 1.25rem !important; }
            .topic-btn-title { font-size: 1rem !important; }
            .topic-btn-inner { padding: 1.25rem 1rem !important; }
            .module-card { padding: 1.25rem !important; border-radius: 1.5rem !important; }
            .subject-banner { padding: 2rem 1.5rem !important; border-radius: 2rem !important; }
            .stats-row { grid-template-columns: 1fr !important; }
            .module-grid { grid-template-columns: 1fr !important; }
        }
        @media(max-width: 480px) {
            .quote-text { font-size: 1.1rem !important; }
            .cert-banner { flex-direction: column !important; text-align: center !important; }
            .cert-btn { width: 100% !important; }
            #stepup-asst-window { width: calc(100vw - 20px) !important; right: 10px !important; bottom: 90px !important; }
            #stepup-asst-bubble-btn { bottom: 20px !important; right: 15px !important; width: 55px !important; height: 55px !important; font-size: 22px !important; }
        }

        /* INP */
        .inp {
            width: 100%;
            padding: .8rem 1rem;
            border-radius: .875rem;
            border: 2px solid #e2e8f0;
            background: #f8fafc;
            color: #0f172a;
            font-weight: 600;
            font-size: .875rem;
            outline: none;
            transition: all .2s
        }

        .inp:focus {
            border-color: #2563eb;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, .1)
        }

        .dark .inp {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9
        }

        .dark .inp:focus {
            border-color: #3b82f6;
            background: #1e293b
        }

        /* Modal */
        .moverlay {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, .55);
            backdrop-filter: blur(6px);
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            opacity: 0;
            pointer-events: none;
            transition: opacity .3s ease;
        }

        .moverlay.on {
            opacity: 1;
            pointer-events: auto;
        }

        /* Custom modal entrance animation (used by settings/activity/agenda overlays only) */
        @keyframes swal-modern-in {
            0% { opacity: 0; transform: scale(0.7) translateY(20px); }
            100% { opacity: 1; transform: scale(1) translateY(0); }
        }


        /* Todo */
        .todo-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            transition: all .2s
        }

        .dark .todo-item {
            background: #1e293b;
            border-color: #334155
        }

        .todo-item.done span {
            text-decoration: line-through;
            color: #94a3b8
        }

        /* Pill */
        .pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 3px 10px;
            border-radius: 9999px;
            font-size: .65rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .07em
        }

        /* Frosted card - iOS 26 Premium Glassmorphism */
        .frost {
            background: rgba(255, 255, 255, var(--glass-opacity)) !important;
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.4);
            box-shadow: 0 12px 40px -8px rgba(0, 0, 0, 0.08);
            transition: background 0.4s cubic-bezier(0.4, 0, 0.2, 1), transform 0.3s;
        }

        .dark .frost {
            background: rgba(13, 21, 38, var(--glass-opacity)) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 16px 48px -12px rgba(0, 0, 0, 0.6) !important;
        }

        /* Glass opacity button styles */
        .glass-btn {
            padding: 10px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 800;
            text-align: center;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: rgba(241, 245, 249, 0.5);
            color: #64748b;
            border: 1px solid transparent;
        }

        .glass-btn:hover {
            transform: translateY(-3px);
            background: #f1f5f9;
        }

        .glass-btn.active {
            background: #2563eb;
            color: #fff;
            box-shadow: 0 8px 20px -5px rgba(37, 99, 235, 0.4);
        }

        .dark .glass-btn {
            background: rgba(30, 41, 59, 0.6);
            color: #94a3b8;
        }

        .dark .glass-btn:hover {
            background: #334155;
            color: #f1f5f9;
        }

        .dark .glass-btn.active {
            background: #3b82f6;
            color: #fff;
        }

        /* Video Speaking Indicator & Mirroring */
        #localVideo { transform: scaleX(-1); }
        .speaking-ring { box-shadow: 0 0 0 6px #22c55e !important; transform: scale(1.02); transition: all 0.2s ease; }

        /* Settings tabs */
        .stab {
            padding: 8px 16px;
            border-radius: 10px;
            font-size: .8rem;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s;
            color: #64748b
        }

        .stab.act {
            background: #2563eb;
            color: #fff
        }

        .dark .stab {
            color: #94a3b8
        }

        .dark .stab.act {
            background: #2563eb;
            color: #fff
        }

        /* BG swatches */
        .swatch-sm {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            cursor: pointer;
            border: 3px solid transparent;
            transition: all .3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            background-clip: padding-box;
            position: relative;
            box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
        }

        .swatch-sm:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.2);
        }

        .swatch-sm.sel {
            border-color: #2563eb;
            transform: scale(1.1);
            border-width: 4px;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
        }

        .swatch-sm.color-white {
            border-color: #f1f5f9;
        }

        .dark .swatch-sm.color-white {
            border-color: #334155;
        }

        /* Dark Mode Professional Polish */
        .dark body {
            background-color: #060b1d !important;
            color: #f1f5f9;
        }

        .dark .frost {
            background: rgba(15, 23, 42, 0.8) !important;
            border-color: rgba(255, 255, 255, 0.08) !important;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.6) !important;
        }

        .dark .bg-white/80,
        .dark .bg-white/60 {
            background-color: rgba(30, 41, 59, 0.7) !important;
        }

        .dark .text-slate-900 {
            color: #f8fafc !important;
        }

        .dark .text-slate-600,
        .dark .text-slate-500 {
            color: #94a3b8 !important;
        }

        .dark .border-slate-200,
        .dark .border-white/50 {
            border-color: rgba(255, 255, 255, 0.05) !important;
        }

        .dark .bg-slate-50 {
            background-color: #0f172a !important;
        }

        .dark .cd:not(.tod):not(.has-event) {
            color: #475569;
        }

        .dark .cd:hover:not(.tod) {
            background: rgba(59, 130, 246, 0.1) !important;
            color: #60a5fa;
        }

        .dark .inp {
            background: #1e293b !important;
            border-color: #334155 !important;
            color: #f1f5f9 !important;
        }

        .custom-scrollbar::-webkit-scrollbar {
            width: 5px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #e2e8f0;
            border-radius: 10px;
        }

        .dark .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #334155;
        }

        /* Calendar has-event: amber ring, dark text readable */
        .cd.has-event { position: relative; color: #1e293b !important; font-weight: 900; z-index: 1; }
        .cd.has-event::before {
            content: ''; position: absolute; inset: 3px;
            border: 2.5px solid #eab308; border-radius: 50%;
            background: rgba(234, 179, 8, 0.12);
            box-shadow: 0 0 10px rgba(234, 179, 8, 0.25);
            z-index: -1;
        }
        .dark .cd.has-event { color: #fde68a !important; }
        .dark .cd.has-event::before { border-color: #facc15; background: rgba(250, 204, 21, 0.08); }
        
        /* No dot markers */
        .cd.has-event::after { display: none !important; }

        /* Community Minimize */
        #commMainWrapper.comm-minimized {
            height: auto !important;
            min-height: 0 !important;
            flex: 0 0 auto !important;
        }
        #commMainWrapper.comm-minimized > div:last-child {
            display: none !important;
        }
    </style>
</head>

<body class="flex flex-col bg-slate-50 dark:bg-[#0b1121] h-screen" id="appBody" style="<?php echo $bgStyle ?>">

    <div class="flex flex-1 overflow-hidden w-full relative">
        <?php 
        $btn = __DIR__ . '/../inc_chat_button.php'; 
        $win = __DIR__ . '/../inc_chat_window.php'; 
        if(file_exists($btn) && file_exists($win)) { 
            include_once $btn; 
            include_once $win; 
        } ?>

        <div id="sbk" onclick="closeSB()"></div>

        <!-- SIDEBAR -->
        <aside id="sidebar" class="w-[260px] frost dark:bg-[#0d1526]/90 dark:border-slate-800 border-r border-white/60 flex flex-col h-full lg:relative shrink-0">
        <div class="p-4 md:p-6 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-500/25"><i class="fas fa-bolt text-lg"></i></div>
                <span class="font-black text-xl text-slate-900 dark:text-white tracking-tight">StepUp</span>
            </div>
            <button onclick="closeSB()" class="lg:hidden text-slate-400 hover:text-red-500 p-1"><i class="fas fa-times"></i></button>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 pb-3 space-y-1">
            <p class="text-[9px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-widest px-3 mb-2">Utama</p>
            <button onclick="sw('home')" id="nav-home" class="navi active"><i class="fas fa-th-large w-5 tc"></i><span>Dashboard</span></button>
            <button onclick="sw('challenges')" id="nav-challenges" class="navi"><i class="fas fa-bullseye w-5 tc"></i><span>Tantangan</span></button>
            <p class="text-[9px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-widest px-3 mb-2 mt-5">Materi</p>
            <?php foreach ($subjects as $sl => $s): ?>
                <button onclick="sw('<?php echo $sl ?>')" id="nav-<?php echo $sl ?>" class="navi"><i class="fas <?php echo htmlspecialchars($s['icon']) ?> w-5 tc"></i><span><?php echo htmlspecialchars($s['title']) ?></span></button>
            <?php endforeach; ?>
        </nav>
        <div class="shrink-0 p-3 border-t border-slate-100 dark:border-slate-800/80">
            <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/60 dark:bg-slate-800/50 border border-white/50 dark:border-slate-700/50 mb-2 overflow-hidden">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-black shrink-0 shadow-lg overflow-hidden relative" id="sidebarPPBox">
                    <?php if (!empty($user['profile_pic']) && file_exists('../../' . $user['profile_pic'])): ?><img src="../../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="w-full h-full object-cover" id="sidebarPP"><?php else: ?><span id="sidebarThumb"><?php echo $ini ?></span><?php endif; ?>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-black text-sm text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($user['full_name']) ?></p>
                    <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest"><?php echo htmlspecialchars(!empty($user['title']) ? $user['title'] : 'Siswa') ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="toggleDark()"
                        class="w-10 h-10 rounded-xl bg-slate-50 dark:bg-slate-800 flex items-center justify-center text-slate-500 hover:text-blue-500 dark:hover:text-yellow-400 transition-all flex items-center justify-center shrink-0 border border-slate-100 dark:border-slate-700"><i class="fas fa-moon" id="mIcon"></i></button>
                </div>
            </div>
            <button onclick="openSettings()" class="navi w-full mb-1"><i class="fas fa-cog w-5 tc"></i><span>Pengaturan</span></button>
            <button onclick="<?php echo $isGuest ? 'window.location.href=\'../../login.php\'' : 'doLogout()' ?>" class="navi w-full <?php echo $isGuest ? 'text-blue-600 hover:!bg-blue-50 dark:hover:!bg-blue-900/20' : 'text-red-500 hover:!bg-red-50 dark:hover:!bg-red-900/20' ?>"><i class="fas <?php echo $isGuest ? 'fa-sign-in-alt' : 'fa-sign-out-alt' ?> w-5 tc"></i><span><?php echo $isGuest ? 'Login' : 'Sign Out' ?></span></button>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 flex flex-col overflow-hidden">
        <header class="lg:hidden shrink-0 flex items-center justify-between px-5 py-4 frost dark:bg-[#0d1526]/90 border-b border-white/50 dark:border-slate-800">
            <button onclick="openSB()" class="w-10 h-10 rounded-xl bg-white/60 dark:bg-slate-800 flex items-center justify-center text-slate-500"><i class="fas fa-bars"></i></button>
            <span class="font-black text-slate-900 dark:text-white">StepUp</span>
            <div class="flex items-center gap-2">
                <button onclick="openSettings()" class="w-10 h-10 rounded-xl bg-white/60 dark:bg-slate-800 flex items-center justify-center overflow-hidden border border-white/40 dark:border-slate-700 shadow-sm" id="mobilePPBox">
                    <?php if (!empty($user['profile_pic']) && file_exists('../../' . $user['profile_pic'])): ?><img src="../../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="w-full h-full object-cover" id="mobilePP"><?php else: ?><span class="text-[10px] font-black text-blue-600"><?php echo $ini ?></span><?php endif; ?>
                </button>
                <button onclick="openSettings()" class="w-10 h-10 rounded-xl bg-white/60 dark:bg-slate-800 flex items-center justify-center text-slate-500"><i class="fas fa-cog"></i></button>
            </div>
        </header>
        <div class="flex-1 overflow-y-auto p-4 sm:p-4 md:p-6 md:p-10" id="mainScrollArea">
            <div class="max-w-[1600px] mx-auto grid grid-cols-1 lg:grid-cols-[1fr_350px] gap-6 lg:gap-10 items-start" id="mainContainer">
                <div class="min-w-0" id="leftColumn">
                    
                    <!-- ═══ HOME TAB ═══ -->
                    <?php include 'components/inc_tab_home.php'; ?>






                    <!-- ═══ CHALLENGES ═══ -->
                    <?php include 'components/inc_tab_challenges.php'; ?>
                    <?php foreach ($subjects as $sl => $s): ?>
                        <div id="tab-<?php echo $sl ?>" class="tp pb-20">
                            <?php include 'components/inc_learning_path.php'; ?>
                        </div>
                    <?php endforeach; ?>
                </div> <!-- End leftColumn -->
                
                <!-- RIGHT SIDEBAR -->
                <?php
                include 'components/inc_dashboard_sidebar.php';
                ?>
            </div> <!-- End mainContainer -->
        </div>
    </main>
</div> <!-- End Wrapper -->

<!-- SETTINGS MODAL -->
<div id="sModal" class="moverlay" onclick="if(event.target===this)closeSettings()">
    <div class="relative w-full max-w-lg bg-white/80 dark:bg-[#0a1128]/90 rounded-[3.5rem] shadow-2xl overflow-hidden border border-white/20 dark:border-blue-900/20 backdrop-blur-2xl" style="animation:swal-modern-in .6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both">
        <!-- Header -->
        <div class="p-5 md:p-10 pb-6 relative">
            <button onclick="closeSettings()" class="absolute top-10 right-10 w-10 h-10 bg-slate-100 hover:bg-red-500 hover:text-white dark:bg-slate-800 dark:hover:bg-red-500 rounded-2xl flex items-center justify-center text-slate-400 transition-all shadow-sm z-[70]"><i class="fas fa-times"></i></button>
            <div class="flex items-center gap-6 mb-8">
                <div class="w-20 h-20 rounded-[2rem] bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-black text-3xl shadow-xl shadow-blue-500/30">
                    <?php if (!empty($user['profile_pic']) && file_exists('../../' . $user['profile_pic'])): ?><img src="../../<?php echo htmlspecialchars($user['profile_pic']); ?>" id="ppPreview" alt="Profile" class="w-full h-full object-cover"><?php else: ?><span id="ppThumb"><?php echo $ini ?></span><img id="ppPreview" src="" class="w-full h-full object-cover hidden"><?php endif; ?>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight"><?php echo htmlspecialchars($user['full_name']) ?></h3>
                    <p class="text-blue-500 text-[10px] font-black uppercase tracking-[0.2em]"><?php echo htmlspecialchars(!empty($user['title']) ? $user['title'] : 'Siswa') ?></p>
                </div>
            </div>
            <!-- Setting Tabs -->
            <div
                class="flex gap-2 bg-slate-100/50 dark:bg-slate-900/50 p-1.5 rounded-[2rem] border border-slate-100 dark:border-slate-800/50">
                <button onclick="stab('profile')" id="stab-profile"
                    class="stab act flex-1 py-3.5 rounded-[1.5rem]">Profil</button>
                <button onclick="stab('security')" id="stab-security"
                    class="stab flex-1 py-3.5 rounded-[1.5rem]">Keamanan</button>
                <button onclick="stab('appearance')" id="stab-appearance"
                    class="stab flex-1 py-3.5 rounded-[1.5rem]">Tampilan</button>
                <button onclick="stab('inbox')" id="stab-inbox"
                    class="stab flex-1 py-3.5 rounded-[1.5rem]">Inbox</button>
            </div>
        </div>

        <!-- Body -->
        <div class="p-5 md:p-10 pt-4 overflow-y-auto max-h-[60vh] custom-scrollbar">
            <!-- Profile Tab -->
            <div id="s-profile" class="stpane">
                <div class="space-y-6">
                    <!-- Profile Pic Upload -->
                    <div
                        class="flex flex-col items-center py-4 bg-slate-50 dark:bg-slate-800/40 rounded-[2rem] border border-slate-100 dark:border-slate-800">
                        <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-black text-3xl shadow-xl mb-4 relative group overflow-hidden"
                            id="ppPreviewBox">
                            <?php if (!empty($user['profile_pic']) && file_exists('../../' . $user['profile_pic'])): ?><img src="../../<?php echo htmlspecialchars($user['profile_pic']); ?>" id="ppPreview"
                                alt="Profile" class="w-full h-full object-cover"><?php else: ?><span id="ppThumb"><?php echo $ini ?></span><img id="ppPreview" src="" class="w-full h-full object-cover hidden"><?php endif; ?>
                                <label
                                    class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                                    <i class="fas fa-camera text-white"></i>
                                    <input type="file" id="ppInp" accept="image/*" class="hidden"
                                        onchange="previewPP(this)">
                                </label>
                            </div>
                            <button onclick="document.getElementById('ppInp').click()"
                                class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-[0.2em] hover:text-blue-700 transition">Pilih
                                Foto Profil</button>
                        </div>

                        <div><label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-2">Nama
                                Lengkap</label>
                            <input id="s_name" type="text" class="inp"
                                value="<?php echo htmlspecialchars($user['full_name']) ?>">
                        </div>
                        <div><label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-2">Gelar
                                / Julukan (Opsional)</label>
                            <input id="s_title" type="text" class="inp"
                                placeholder="Contoh: Sang Juara, Master Matematika..."
                                value="<?php echo htmlspecialchars($user['title'] ?? '') ?>">
                        </div>
                        <div><label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-2">Email</label>
                            <input type="text" class="inp bg-slate-50 dark:bg-slate-800/60 opacity-60"
                                value="<?php echo htmlspecialchars($user['email'] ?? '') ?>" disabled>
                        </div>

                        <button onclick="saveProfileWithPic()" id="saveProfileBtn"
                            class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-xl shadow-blue-500/20 tc hover:scale-[1.01] active:scale-95 transition-all">Simpan
                            Perubahan</button>
                    </div>
                </div>

                <!-- Inbox Tab (Hidden in Settings but accessible via button) -->
                <div id="s-inbox" class="stpane hidden">
                    <div class="flex items-center justify-between mb-6">
                        <h4 class="text-lg font-black text-slate-800 dark:text-white uppercase tracking-tight">Kotak Masuk</h4>
                        <button onclick="markAllAsRead()" class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest hover:underline">Tandai Semua Dibaca</button>
                    </div>
                    <div id="inboxList" class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                        <!-- Loaded via JS -->
                        <div class="text-center py-10 opacity-40">
                            <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                            <p class="text-[10px] font-black uppercase">Memuat pesan...</p>
                        </div>
                    </div>
                </div>

                <!-- Security Tab -->
                <div id="s-security" class="stpane hidden">
                    <div class="space-y-4">
                        <div><label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Password
                                Baru</label>
                            <div class="relative"><input id="s_pw" type="password" class="inp pr-12"
                                    placeholder="Minimal 6 karakter...">
                                <button type="button" onclick="tgPw('s_pw','sEye')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-500 tc"><i
                                        class="fas fa-eye" id="sEye"></i></button>
                            </div>
                        </div>
                        <div><label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Konfirmasi
                                Password</label>
                            <div class="relative"><input id="s_pw2" type="password" class="inp pr-12"
                                    placeholder="Ulangi password...">
                                <button type="button" onclick="tgPw('s_pw2','sEye2')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-500 tc"><i
                                        class="fas fa-eye" id="sEye2"></i></button>
                            </div>
                        </div>
                        <div
                            class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800/50 text-xs text-amber-700 dark:text-amber-400 font-semibold">
                            <i class="fas fa-shield-alt mr-2"></i>Pastikan password kamu kuat dan unik!</div>
                        <button onclick="savePassword()"
                            class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black uppercase tracking-widest text-sm shadow-xl shadow-blue-500/20 tc hover:scale-[1.01] active:scale-95">Update
                            Password</button>
                    </div>
                </div>

                <!-- Appearance Tab -->
                <div id="s-appearance" class="stpane hidden">
                    <div class="space-y-6">
                        <!-- Compact Dark Mode Toggle -->
                        <div
                            class="flex items-center justify-between p-5 bg-slate-50 dark:bg-slate-800/40 rounded-3xl border border-slate-100 dark:border-slate-800">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-10 h-10 bg-white dark:bg-slate-700 rounded-2xl flex items-center justify-center shadow-sm text-slate-500 dark:text-yellow-400">
                                    <i class="fas fa-moon"></i>
                                </div>
                                <div>
                                    <p class="font-black text-slate-900 dark:text-white text-sm">Mode Gelap</p>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Sesuaikan
                                        kenyamanan mata</p>
                                </div>
                            </div>
                            <button onclick="toggleDark()"
                                class="relative w-11 h-6 rounded-full bg-slate-200 dark:bg-blue-600 transition-colors">
                                <div
                                    class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow-sm transition-transform duration-300 dark:translate-x-5">
                                </div>
                            </button>
                        </div>

                        <!-- Glass Transparency Setting -->
                        <div class="mt-2 mb-6">
                            <div class="flex flex-wrap items-center justify-between gap-3 mb-4 px-2">
                                <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Transparansi Kaca</label>
                                <span class="text-[10px] font-bold text-blue-500" id="opacityValLabel"><?php echo $pref['glass_opacity'] ?>%</span>
                            </div>
                            <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-3xl border border-slate-100 dark:border-slate-800">
                                <div class="grid grid-cols-5 gap-2" id="opacityControls">
                                    <?php foreach ([5, 25, 50, 75, 100] as $ov):
                                        $isSel = ($pref['glass_opacity'] == $ov);
                                    ?>
                                        <button id="gop-<?php echo $ov ?>"
                                            class="py-3 rounded-2xl text-[10px] font-black transition-all flex items-center justify-center border-2 <?php echo $isSel ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-500/20' : 'bg-white dark:bg-slate-800 text-slate-400 border-transparent hover:border-slate-200 dark:hover:border-slate-700' ?>"
                                            onclick="setGlassOpacity(<?php echo $ov ?>)">
                                            <?php echo $ov ?>%
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Warna
                            Polos</p>
                        <div class="grid grid-cols-6 gap-3 pt-1 mb-6" id="swatches">
                            <?php
                            $bgs = [
                                ['#f2f5ff', 'Soft Blue'],
                                ['#ffffff', 'Pure White'],
                                ['#eef6f2', 'Soft Green'],
                                ['#fffdf5', 'Cream'],
                                ['#fdf7f9', 'Soft Pink'],
                                ['#f2f9ff', 'Baby Blue'],
                            ];
                            foreach ($bgs as $bg):
                                $bgVal = $bg[0];
                                $bgTitle = $bg[1];
                                $isSel = ($pref['bg_type'] === 'color' && $pref['bg_value'] === $bgVal);
                            ?>
                                <button
                                    class="w-10 h-10 rounded-xl border-4 transition-all <?php echo $isSel ? 'border-blue-500 scale-110 shadow-lg shadow-blue-500/20' : 'border-transparent' ?>"
                                    style="background:<?php echo $bgVal; ?>"
                                    onclick="setBg('color','<?php echo $bgVal; ?>')" title="<?php echo $bgTitle; ?>">
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Custom Image Upload -->
                        <div class="pt-2">
                            <label
                                class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 px-1">Gambar
                                Background</label>
                            <label
                                class="group block relative p-4 md:p-6 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border-2 border-dashed border-slate-100 dark:border-slate-800 hover:border-blue-400 dark:hover:border-blue-500 transition-all cursor-pointer overflow-hidden">
                                <div class="flex items-center gap-4 relative z-10">
                                    <div
                                        class="w-12 h-12 bg-white dark:bg-slate-700 rounded-2xl flex items-center justify-center text-slate-400 group-hover:text-blue-500 transition-colors shadow-sm">
                                        <i class="fas fa-cloud-upload-alt text-xl"></i>
                                    </div>
                                    <div>
                                        <p class="font-black text-slate-800 dark:text-white text-sm">Upload Foto
                                            Kostum</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">
                                            JPG, PNG, WEBP (Maks 5MB)</p>
                                    </div>
                                </div>
                                <input type="file" id="bgFile" accept="image/*" class="hidden"
                                    onchange="uploadBg(this)">
                            </label>
                        </div>

                        <div class="grid grid-cols-1 gap-3 mt-4">
                            <button onclick="removeBg()"
                                class="py-4 bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-red-500 rounded-3xl font-black uppercase tracking-widest text-[11px] transition-all flex items-center justify-center gap-2">
                                <i class="fas fa-trash-alt"></i> Kembalikan ke Default
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Activity History Modal removed — now uses Swal.fire via openActivityHistory() -->

        <script>
            console.log('StepUp Dashboard: Script starting...');
            const TODO_API = '../../src/api/todo.php';
            const PREF_API = 'set_preference.php';
            const USER_ID = <?php echo $userId !== null ? $userId : 'null' ?>;
            const GUEST_MODE = <?php echo $isGuest ? 'true' : 'false' ?>;

            if (typeof parseMagicJSON === 'undefined') {
                window.parseMagicJSON = function (text) {
                    try {
                        if (text.includes('<!--JSON_START-->')) {
                            return JSON.parse(text.split('<!--JSON_START-->')[1].split('<!--JSON_END-->')[0].trim());
                        }
                        const s = text.indexOf('{'), e = text.lastIndexOf('}');
                        if (s !== -1 && e !== -1) return JSON.parse(text.substring(s, e + 1).trim());
                        return JSON.parse(text.trim());
                    } catch (err) {
                        console.warn('JSON Parse Error. Full response:', text);
                        throw new Error('Respon server tidak valid');
                    }
                };
            }
            // ── SweetAlert2 back-button support (simple popstate only) ──
            // Close any visible SweetAlert2 popup when browser back is pressed
            window.addEventListener('popstate', function() {
                if (typeof Swal !== 'undefined' && Swal.isVisible()) {
                    Swal.close();
                }
            });

            // ── getModernConfig (inline definition, theme.js not loaded) ──
            const getModernConfig = () => {
                const isDark = document.documentElement.classList.contains('dark');
                return {
                    background: isDark ? 'linear-gradient(145deg, #0f172a, #1e293b)' : 'linear-gradient(145deg, #ffffff, #f8fafc)',
                    color: isDark ? '#f1f5f9' : '#0f172a',
                    backdrop: 'rgba(0,0,0,0.6)',
                    showCloseButton: true,
                    customClass: {
                        popup: 'rounded-[3rem] border border-white/20 dark:border-slate-800/50 shadow-[0_32px_64px_-16px_rgba(0,0,0,0.3)] p-10',
                        confirmButton: 'px-10 py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-black rounded-2xl shadow-xl shadow-blue-500/20 transition-all active:scale-95 text-xs tracking-widest',
                        cancelButton: 'px-8 py-4 bg-slate-100 dark:bg-slate-800 text-slate-500 font-black rounded-2xl hover:bg-slate-200 transition-all text-xs tracking-widest ml-3',
                        closeButton: 'top-6 right-6 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-red-500 hover:text-white transition-all z-[70]',
                        icon: 'border-0 bg-blue-50 dark:bg-blue-900/30 w-20 h-20 rounded-[1.5rem]',
                        title: 'text-2xl font-black tracking-tight',
                        htmlContainer: 'text-sm font-bold opacity-70 leading-relaxed'
                    }
                };
            };

            if (typeof showModernAlert === 'undefined') {
                window.showModernAlert = function (options = {}) {
                    const isDark = document.documentElement.classList.contains('dark');
                    const isError = options.icon === 'error';
                    const isSuccess = options.icon === 'success';
                    
                    return Swal.fire({
                        icon: options.icon || 'success',
                        title: options.title || (isError ? 'Oops...' : 'Berhasil!'),
                        text: options.text || '',
                        confirmButtonText: options.confirmButtonText || 'MENGERTI',
                        showCloseButton: true,
                        iconColor: isError ? '#ef4444' : (isSuccess ? '#3b82f6' : '#2563eb'),
                        background: isDark ? 'linear-gradient(145deg, #0f172a, #1e293b)' : 'linear-gradient(145deg, #ffffff, #f8fafc)',
                        color: isDark ? '#f1f5f9' : '#0f172a',
                        backdrop: 'rgba(0,0,0,0.6)',
                        customClass: {
                            popup: 'rounded-[3rem] border border-white/20 dark:border-slate-800/50 shadow-[0_32px_64px_-16px_rgba(0,0,0,0.3)] p-10',
                            confirmButton: 'px-10 py-4 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-black rounded-2xl shadow-xl shadow-blue-500/20 transition-all active:scale-95 text-xs tracking-widest',
                            closeButton: 'top-6 right-6 rounded-2xl bg-slate-100 dark:bg-slate-800 hover:bg-red-500 hover:text-white transition-all z-[70]',
                            title: 'text-2xl font-black tracking-tight',
                            htmlContainer: 'text-sm font-bold opacity-70 leading-relaxed'
                        },
                        ...options
                    });
                };
            }
            if (typeof confirmModernAlert === 'undefined') {
                window.confirmModernAlert = function (options = {}) {
                    const isDark = document.documentElement.classList.contains('dark');
                    return Swal.fire({
                        icon: options.icon || 'warning',
                        title: options.title || 'Anda Yakin?',
                        text: options.text || '',
                        showCancelButton: true,
                        confirmButtonText: options.confirmButtonText || 'YA, LANJUTKAN',
                        cancelButtonText: options.cancelButtonText || 'BATAL',
                        background: isDark ? 'linear-gradient(145deg, #0f172a, #1e293b)' : 'linear-gradient(145deg, #ffffff, #f8fafc)',
                        color: isDark ? '#f1f5f9' : '#0f172a',
                        backdrop: 'rgba(0,0,0,0.6)',
                        customClass: {
                            popup: 'rounded-[3rem] border border-white/20 dark:border-slate-800/50 shadow-2xl p-10',
                            confirmButton: 'px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white font-black rounded-2xl shadow-lg active:scale-95 transition-all text-xs tracking-widest',
                            cancelButton: 'px-8 py-4 bg-slate-100 dark:bg-slate-800 text-slate-500 font-black rounded-2xl hover:bg-slate-200 transition-all text-xs tracking-widest ml-3'
                        },
                        ...options
                    });
                };
            }

            function applyDark(on) {
                document.documentElement.classList.toggle('dark', on);
                localStorage.setItem('dark', on ? '1' : '0');
                const ic = on ? 'fa-sun' : 'fa-moon';
                ['mIcon', 'mIcon2'].forEach(id => { const e = document.getElementById(id); if (e) e.className = 'fas ' + ic; });
            }
            function toggleDark() { applyDark(!document.documentElement.classList.contains('dark')); }
            applyDark(localStorage.getItem('dark') === '1');

            function openSB() { document.getElementById('sidebar').classList.add('on'); document.getElementById('sbk').classList.add('on'); }
            function closeSB() { document.getElementById('sidebar').classList.remove('on'); document.getElementById('sbk').classList.remove('on'); }

            // Global showGuestModal — defined early so sw() and all inline handlers can use it
            window.showGuestModal = function(featureName) {
                const isDark = document.documentElement.classList.contains('dark');
                return Swal.fire({
                    iconHtml: '<i class="fas fa-lock" style="font-size:2rem;color:#3b82f6"></i>',
                    title: 'Perlu Login',
                    html: '<p style="line-height:1.8;font-weight:600">Kamu harus login untuk menggunakan <b style="color:#3b82f6">' + (featureName || 'fitur ini') + '</b> secara penuh.</p>',
                    confirmButtonText: 'LOGIN SEKARANG',
                    showCancelButton: true,
                    cancelButtonText: 'LANJUT TAMU',
                    showCloseButton: true,
                    background: isDark ? 'linear-gradient(145deg, #0f172a, #1e293b)' : 'linear-gradient(145deg, #ffffff, #f8fafc)',
                    color: isDark ? '#f1f5f9' : '#0f172a',
                    backdrop: 'rgba(0,0,0,0.6)',
                    customClass: {
                        popup: 'rounded-[3rem] border border-white/20 shadow-[0_32px_64px_-16px_rgba(0,0,0,0.3)] p-10',
                        confirmButton: 'px-10 py-4 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-black rounded-2xl shadow-xl text-xs tracking-widest',
                        cancelButton: 'px-8 py-4 bg-slate-100 text-slate-500 font-black rounded-2xl text-xs tracking-widest ml-3',
                        closeButton: 'top-6 right-6 rounded-2xl',
                        icon: 'border-0',
                        title: 'text-2xl font-black tracking-tight',
                        htmlContainer: 'text-sm opacity-70 leading-relaxed'
                    }
                }).then(r => {
                    if (r.isConfirmed) window.location.href = '../../login.php';
                });
            };

            function showGuestGuard() {
                return window.showGuestModal('Materi & Fitur Lengkap');
            }

            function navMod(link) {
                if (GUEST_MODE) {
                    showGuestGuard();
                } else {
                    window.location.href = link;
                }
            }

            const SUBJECTS_DATA = <?php
            $s_by_slug = [];
            foreach ($subjects as $slug => $s) {
                $s_by_slug[$slug] = $s;
            }
            $json_s = json_encode($s_by_slug, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            echo $json_s ? $json_s : '{}';
            ?>;
            const USER_PREF_BG = <?php 
                $json_p = json_encode($pref, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); 
                echo $json_p ? $json_p : '{"bg_type":"color","bg_value":"#f8fafc","glass_opacity":50}';
            ?>;
            const DEFAULT_BATIK = 'assets/img/mega.jpg';

            function sw(id) {
                const cleanId = String(id).replace('tab-', '').toLowerCase().trim();
                const guestRestricted = ['community', 'friends', 'followers', 'leaderboard'];
                
                if (GUEST_MODE && guestRestricted.includes(cleanId)) {
                    const tabNames = { community: 'Komunitas', friends: 'Teman', followers: 'Teman', leaderboard: 'Peringkat' };
                    showGuestModal(tabNames[cleanId] || 'fitur ini');
                    return;
                }

                // Persistence: save last tab
                localStorage.setItem('stepup_active_tab', cleanId);

                if (window.innerWidth < 1024) closeSB();
 
                document.querySelectorAll('.tp').forEach(p => p.classList.remove('on'));
                document.querySelectorAll('.navi').forEach(b => b.classList.remove('active'));

                if (cleanId === 'community') {
                    const firstComm = document.querySelector('.comm-btn');
                    if (firstComm) {
                        const id = firstComm.getAttribute('data-comm-id');
                        swComm(id, firstComm);
                    }
                }
 
                const targetTab = document.getElementById('tab-' + cleanId);
                const targetNav = document.getElementById('nav-' + cleanId);
 
                const rs = document.getElementById('rightSidebar');
                const lc = document.getElementById('leftColumn');
                const mc = document.getElementById('mainContainer');
                if (rs && lc && mc) {
                    const isSubject = !['home','community','friends','leaderboard','challenges'].includes(cleanId);
                    
                    if (cleanId === 'home') {
                        rs.style.display = 'none';
                        mc.className = "max-w-[1600px] mx-auto grid grid-cols-1 gap-6 lg:gap-10 items-start";
                        lc.className = "w-full max-w-7xl mx-auto pb-20";
                    } else if (isSubject) {
                        rs.style.display = 'none';
                        mc.className = "max-w-[1600px] mx-auto grid grid-cols-1 gap-6 lg:gap-10 items-start";
                        lc.className = "w-full max-w-5xl mx-auto pb-20";
                    } else {
                        rs.style.display = 'none';
                        mc.className = "max-w-[1600px] mx-auto grid grid-cols-1 gap-6 lg:gap-10 items-start";
                        lc.className = "w-full max-w-7xl mx-auto pb-20";
                    }
                }

                if (targetTab) {
                    targetTab.classList.add('on');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
                if (targetNav) targetNav.classList.add('active');

                const body = document.getElementById('appBody');
                body.style.transition = 'background 0.5s ease';

                function encodePath(p) { 
                    if (!p) return '';
                    return String(p).split('/').map(s => encodeURIComponent(s)).join('/'); 
                }

                if (cleanId === 'home') {
                    if (USER_PREF_BG && USER_PREF_BG.bg_type === 'color') {
                        body.style.background = USER_PREF_BG.bg_value || '#f8fafc';
                    } else if (USER_PREF_BG && USER_PREF_BG.bg_type === 'image') {
                        body.style.backgroundImage = `linear-gradient(rgba(255,255,255,0.5),rgba(255,255,255,0.5)), url('../../${encodePath(USER_PREF_BG.bg_value)}')`;
                        body.style.backgroundSize = 'cover';
                        body.style.backgroundAttachment = 'fixed';
                    } else {
                        body.style.background = '#f8fafc';
                    }
                } else {
                    const subj = SUBJECTS_DATA[cleanId];
                    if (subj && subj.batik_bg) {
                        body.style.backgroundImage = `linear-gradient(rgba(255,255,255,0.5),rgba(255,255,255,0.5)), url('../../${encodePath(subj.batik_bg)}')`;
                        body.style.backgroundSize = 'cover';
                        body.style.backgroundAttachment = 'fixed';
                    } else if (USER_PREF_BG && USER_PREF_BG.bg_type === 'color') {
                        body.style.background = USER_PREF_BG.bg_value;
                    } else {
                        body.style.background = '#f8fafc';
                    }
                }
            }

            /* ── Accordion ──────────── */
            function tgAcc(id) {
                document.getElementById('acc-' + id).classList.toggle('on');
                document.getElementById('chv-' + id).classList.toggle('on');
            }

            /* ── Settings ───────────── */
            function openSettings() {
                if (GUEST_MODE) {
                    showGuestModal('mengakses pengaturan');
                    return;
                }
                const modal = document.getElementById('sModal');
                modal.classList.add('on');
            }
            function closeSettings() {
                const modal = document.getElementById('sModal');
                modal.classList.remove('on');
            }

            const stabEls = {};
            function stab(tab) {
                ['profile', 'security', 'appearance', 'inbox'].forEach(t => {
                    document.getElementById('stab-' + t)?.classList.toggle('act', t === tab);
                    document.getElementById('s-' + t)?.classList.toggle('hidden', t !== tab);
                });
            }

            function tgPw(inpId, eyeId) {
                const i = document.getElementById(inpId), e = document.getElementById(eyeId);
                i.type = i.type === 'password' ? 'text' : 'password';
                e.className = i.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
            }

            function previewPP(input) {
                if (input.files && input.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const preview = document.getElementById('ppPreview');
                        const sidebarPic = document.getElementById('sidebarPP');
                        const navbarPic = document.getElementById('navbarPP');
                        const mobilePic = document.getElementById('mobilePP');
                        const thumb = document.getElementById('ppThumb');

                        if (preview) {
                            preview.src = e.target.result;
                            preview.classList.remove('hidden');
                        }
                        if (sidebarPic) sidebarPic.src = e.target.result;
                        else {
                            const box = document.getElementById('sidebarPPBox');
                            if (box) box.innerHTML = `<img src="${e.target.result}" id="sidebarPP" alt="Profile" class="w-full h-full object-cover">`;
                        }

                        if (navbarPic) navbarPic.src = e.target.result;
                        else {
                            const box = document.getElementById('navbarPPBox');
                            if (box) box.innerHTML = `<img src="${e.target.result}" id="navbarPP" alt="Profile" class="w-full h-full object-cover">`;
                        }

                        if (mobilePic) mobilePic.src = e.target.result;
                        else {
                            const box = document.getElementById('mobilePPBox');
                            if (box) box.innerHTML = `<img src="${e.target.result}" id="mobilePP" alt="Profile" class="w-full h-full object-cover">`;
                        }

                        if (thumb) thumb.classList.add('hidden');
                    }
                    reader.readAsDataURL(input.files[0]);
                }
            }

            async function saveProfileWithPic() {
                if (GUEST_MODE) {
                    showGuestModal('mengubah profil');
                    return;
                }
                const btn = document.getElementById('saveProfileBtn');
                const name = document.getElementById('s_name').value.trim();
                const title = document.getElementById('s_title').value.trim();
                const ppFile = document.getElementById('ppInp').files[0];

                if (!name) {
                    Swal.fire({ icon: 'error', title: 'Oops...', text: 'Nama tidak boleh kosong!' });
                    return;
                }

                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Menyimpan...';

                try {
                    const formData = new FormData();
                    formData.append('action', 'save_profile');
                    formData.append('full_name', name);
                    formData.append('title', title);

                    let res = await fetch(PREF_API, { method: 'POST', body: formData });
                    let data = await res.json();
                    if (!data.success) throw new Error(data.error || 'Gagal simpan nama');

                    if (ppFile) {
                        const picData = new FormData();
                        picData.append('action', 'upload_profile_pic');
                        picData.append('pic', ppFile);

                        let resP = await fetch(PREF_API, { method: 'POST', body: picData });
                        let dataP = await resP.json();
                        if (!dataP.success) throw new Error(dataP.error || 'Gagal upload foto');
                    }

                    showModernAlert({
                        title: 'Profil Diperbarui!',
                        text: 'Data profil kamu berhasil disimpan.'
                    }).then(() => location.reload());

                } catch (err) {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: err.message });
                } finally {
                    btn.disabled = false;
                    btn.innerHTML = 'Simpan Perubahan';
                }
            }


            async function savePassword() {
                if (GUEST_MODE) {
                    showGuestModal('mengubah password');
                    return;
                }
                const pw = document.getElementById('s_pw').value, pw2 = document.getElementById('s_pw2').value;
                if (!pw) { Swal.fire({ icon: 'warning', title: 'Password kosong!' }); return; }
                if (pw !== pw2) { Swal.fire({ icon: 'error', title: 'Password tidak cocok!' }); return; }
                if (pw.length < 6) { Swal.fire({ icon: 'warning', title: 'Min 6 karakter!' }); return; }
                const fd = new FormData(); fd.append('action', 'save_profile'); fd.append('full_name', document.getElementById('s_name').value); fd.append('password', pw);
                const r = await fetch(PREF_API, { method: 'POST', body: fd });
                const d = await r.json();
                if (d.success) {
                    showModernAlert({ title: 'Password diperbarui!' });
                    document.getElementById('s_pw').value = '';
                    document.getElementById('s_pw2').value = '';
                }
                else showModernAlert({ icon: 'error', title: 'Gagal', text: d.error });
            }


            function setGlassOpacity(val) {
                if (GUEST_MODE) {
                    showGuestModal('mengubah tampilan');
                    return;
                }
                USER_PREF_BG.glass_opacity = val;
                document.documentElement.style.setProperty('--glass-opacity', val / 100);

                // Update label UI if exists
                const label = document.getElementById('opacityValLabel');
                if (label) label.textContent = val + '%';

                // Update button states
                document.querySelectorAll('[id^="gop-"]').forEach(btn => {
                    btn.className = btn.id === 'gop-' + val ?
                        'py-3 rounded-2xl text-[10px] font-black transition-all flex items-center justify-center border-2 bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-500/20' :
                        'py-3 rounded-2xl text-[10px] font-black transition-all flex items-center justify-center border-2 bg-white dark:bg-slate-800 text-slate-400 border-transparent hover:border-slate-200 dark:hover:border-slate-700';
                });

                savePreferences();
            }

            /**
             * Global background applicator
             */
            function applyBg(type, val) {
                const b = document.body;
                const isDark = document.documentElement.classList.contains('dark');
                const overlay = isDark ? "linear-gradient(rgba(10,18,35,0.7),rgba(10,18,35,0.7))" : "linear-gradient(rgba(255,255,255,0.58),rgba(255,255,255,0.58))";

                // Helper: encode spasi di path untuk CSS url()
                function enc(p) { 
                    if (!p) return '';
                    return String(p).split('/').map(s => encodeURIComponent(s)).join('/'); 
                }

                if (type === 'batik' || type === 'image') {
                    const url = (val.startsWith('assets')) ? '../../' + val : val;
                    b.style.background = 'none';
                    b.style.backgroundImage = overlay + ", url('" + enc(url) + "')";
                    b.style.backgroundPosition = 'center';
                    b.style.backgroundSize = 'cover';
                    b.style.backgroundAttachment = 'fixed';
                    document.documentElement.classList.add('batik-mode');
                } else {
                    b.style.background = val;
                    document.documentElement.classList.remove('batik-mode');
                }
            }

            async function setBg(type, val) {
                if (GUEST_MODE) {
                    showGuestModal('mengubah background');
                    return;
                }
                USER_PREF_BG.bg_type = type;
                USER_PREF_BG.bg_value = val;
                applyBg(type, val);

                // Update UI active states for batik cards
                document.querySelectorAll('.swatch-batik-card').forEach(btn => {
                    const isMatch = btn.getAttribute('onclick')?.includes(`'${val}'`);
                    btn.classList.toggle('active', isMatch);
                    // Clear check circles
                    const check = btn.querySelector('.fa-check-circle');
                    if (check) check.style.display = isMatch ? 'block' : 'none';
                });

                savePreferences();
            }

            async function savePreferences() {
                try {
                    const fd = new FormData();
                    fd.append('action', 'save_bg');
                    fd.append('bg_type', USER_PREF_BG.bg_type);
                    fd.append('bg_value', USER_PREF_BG.bg_value);
                    fd.append('glass_opacity', USER_PREF_BG.glass_opacity || 50);

                    const r = await fetch(PREF_API, { method: 'POST', body: fd });
                    const res = await r.json();
                    if (!res.success) console.warn('Save failed:', res.error);
                } catch (err) {
                    console.error('Network error saving preferences:', err);
                }
            }

            async function uploadBg(inp) {
                if (GUEST_MODE) {
                    showGuestModal('mengupload background');
                    return;
                }
                if (!inp.files[0]) return;
                const fd = new FormData(); fd.append('action', 'upload_bg'); fd.append('bg_image', inp.files[0]);
                const r = await fetch(PREF_API, { method: 'POST', body: fd });
                const d = await r.json();
                if (d.success) {
                    document.getElementById('appBody').style.background = 'none';
                    document.getElementById('appBody').style.backgroundImage = `url('../../${d.url}')`;
                    document.getElementById('appBody').style.backgroundPosition = 'center';
                    document.getElementById('appBody').style.backgroundSize = 'cover';
                    document.getElementById('appBody').style.backgroundAttachment = 'fixed';
                    showModernAlert({ title: 'Background diperbarui!' });
                }
                else showModernAlert({ icon: 'error', title: 'Upload gagal', text: d.error });
            }

            async function resetBg() {
                // Reset ke default batik megamendung
                await setBg('batik', 'assets/img/mega.jpg');
                location.reload();
            }

            async function removeBg() {
                if (GUEST_MODE) {
                    showGuestModal('menghapus background');
                    return;
                }
                const res = await confirmModernAlert({
                    title: 'Hapus Background?',
                    text: 'Gambar atau warna background akan direset ke default StepUp.',
                    icon: 'warning'
                });
                if (res.isConfirmed) {
                    await resetBg();
                }
            }

            /* ── Logout ─────────────── */
            function doLogout() {
                confirmModernAlert({
                    title: 'Keluar Sesi?',
                    text: 'Kamu akan keluar dari StepUp Learning.',
                    icon: 'question',
                    confirmButtonText: 'YA, LOGOUT',
                    confirmButtonColor: '#ef4444'
                }).then(r => {
                    if (r.isConfirmed) window.location.href = '../auth/logout.php';
                });
            }

            /* ── Calendar ────────────── */
            let cY = new Date().getFullYear(), cM = new Date().getMonth();
            let userAgendas = [];
            const MN = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];

            async function loadAgendas() {
                if (GUEST_MODE) {
                    renderCal();
                    return;
                }
                try {
                    const r = await fetch(PREF_API + '?action=get_agendas');
                    const text = await r.text();
                    const d = parseMagicJSON(text);
                    if (d.success) {
                        userAgendas = d.agendas || [];
                        renderCal();
                    }
                } catch (e) {
                    console.error("Load Agendas failed:", e);
                }
            }

            function renderCal() {
                const g = document.getElementById('calG'); if (!g) return;
                document.getElementById('calLbl').textContent = MN[cM] + ' ' + cY;
                const now = new Date(), fd = new Date(cY, cM, 1).getDay(), td = new Date(cY, cM + 1, 0).getDate();
                let h = '';

                // Group agendas by date for easier lookup (Fix: Safe Local Parsing)
                const agendaMap = {};
                userAgendas.forEach(a => {
                    const p = a.event_date.split('-');
                    const y = parseInt(p[0]), m = parseInt(p[1]) - 1, d = parseInt(p[2]);
                    if (y === cY && m === cM) {
                        if (!agendaMap[d]) agendaMap[d] = [];
                        agendaMap[d].push(a);
                    }
                });

                for (let i = 0; i < fd; i++)h += '<div class="cd emp"></div>';
                for (let d = 1; d <= td; d++) {
                    const isToday = d === now.getDate() && cM === now.getMonth() && cY === now.getFullYear();
                    const hasAgenda = agendaMap[d] !== undefined;
                    h += `<button onclick="showDayDetail(${d})" 
            class="cd ${isToday ? 'tod' : ''} ${hasAgenda ? 'has-event' : ''} hover:bg-blue-50 dark:hover:bg-blue-900/40 transition-all">${d}</button>`;
                }
                g.innerHTML = h;

                // Populate Agenda List Footer
                const listE = document.getElementById('agendaList');
                const monthAgendas = userAgendas.filter(a => {
                    const p = a.event_date.split('-');
                    const y = parseInt(p[0]), m = parseInt(p[1]) - 1;
                    return y === cY && m === cM;
                }).sort((a, b) => {
                    const pa = a.event_date.split('-'), pb = b.event_date.split('-');
                    return new Date(pa[0], pa[1] - 1, pa[2]) - new Date(pb[0], pb[1] - 1, pb[2]);
                });

                if (listE) {
                    if (monthAgendas.length > 0) {
                        listE.innerHTML = monthAgendas.map(a => {
                            const p = a.event_date.split('-');
                            const localDate = new Date(p[0], p[1] - 1, p[2]);
                            return `
                <div class="flex items-center gap-4 p-4 bg-white/80 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm group hover:border-blue-500/30 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex flex-col items-center justify-center shrink-0 border border-blue-100/50 dark:border-blue-500/20">
                        <span class="text-sm font-black text-blue-600 dark:text-blue-400 leading-none">${localDate.getDate()}</span>
                        <span class="text-[9px] font-black text-blue-400/80 uppercase tracking-tighter">${MN[cM].substring(0, 3)}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-bold text-slate-700 dark:text-slate-100 truncate">${a.title}</p>
                        <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">${localDate.toLocaleDateString('id-ID', { weekday: 'long' })}</p>
                    </div>
                    <button onclick="deleteAgenda(${a.id})" class="w-9 h-9 rounded-xl text-slate-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-all opacity-0 group-hover:opacity-100 flex items-center justify-center"><i class="fas fa-trash-alt text-xs"></i></button>
                </div>`;
                        }).join('');
                    } else {
                        listE.innerHTML = `
                <div class="py-10 flex flex-col items-center justify-center text-center">
                    <div class="w-12 h-12 rounded-2xl bg-slate-50 dark:bg-slate-800/50 flex items-center justify-center text-slate-200 dark:text-slate-700 mb-3">
                        <i class="fas fa-calendar-alt text-xl"></i>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-widest">Belum ada agenda bulan ini</p>
                </div>
            `;
                    }
                }

                // Populate mini agendas for subjects
                document.querySelectorAll('[id^="miniAgendaList-"]').forEach(miniList => {
                    if (monthAgendas.length > 0) {
                        miniList.innerHTML = monthAgendas.map(a => {
                            const p = a.event_date.split('-');
                            const localDate = new Date(p[0], p[1] - 1, p[2]);
                            return `
                <div class="flex items-center gap-2 p-2 bg-slate-50/50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm group">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex flex-col items-center justify-center shrink-0 border border-blue-100/50 dark:border-blue-800">
                        <span class="text-xs font-black text-blue-600 dark:text-blue-400 leading-none">${localDate.getDate()}</span>
                        <span class="text-[9px] font-black text-blue-400/80 uppercase tracking-tighter">${MN[cM].substring(0, 3)}</span>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-[11px] font-bold text-slate-700 dark:text-slate-200 truncate">${a.title}</p>
                    </div>
                    <button onclick="deleteAgenda(${a.id})" class="w-6 h-6 rounded-lg text-slate-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 transition-all opacity-0 group-hover:opacity-100 flex items-center justify-center"><i class="fas fa-trash-alt text-[10px]"></i></button>
                </div>`;
                        }).join('');
                    } else {
                        miniList.innerHTML = `
                <div class="py-4 flex flex-col items-center justify-center text-center opacity-70">
                    <i class="fas fa-calendar-alt text-slate-300 dark:text-slate-600 text-lg mb-1"></i>
                    <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Kosong</p>
                </div>
            `;
                    }
                });

                // Render all mini calendars to keep them in sync
                document.querySelectorAll('[id^="miniCalG-"]').forEach(el => {
                    const slug = el.id.replace('miniCalG-', '');
                    renderMiniCal(slug);
                });
            }

            async function deleteAgenda(id) {
                if (GUEST_MODE) {
                    showGuestModal('menghapus agenda');
                    return;
                }
                const res = await confirmModernAlert({ title: 'Hapus Agenda?', text: 'Agenda ini akan dihapus permanen.' });
                if (!res.isConfirmed) return;

                const fd = new FormData();
                fd.append('action', 'delete_agenda');
                fd.append('id', id);
                const r = await fetch(PREF_API, { method: 'POST', body: fd });
                const d = await r.json();
                if (d.success) {
                    showModernAlert({ title: 'Terhapus!', icon: 'success' });
                    loadAgendas();
                }
            }

            function showDayDetail(d) {
                const agendaMap = {};
                userAgendas.forEach(a => {
                    const p = a.event_date.split('-');
                    const y = parseInt(p[0]), m = parseInt(p[1]) - 1, day = parseInt(p[2]);
                    if (y === cY && m === cM) {
                        if (!agendaMap[day]) agendaMap[day] = [];
                        agendaMap[day].push(a);
                    }
                });

                const hasAgenda = agendaMap[d] !== undefined;
                const agendaText = hasAgenda ? agendaMap[d].map(a => `
        <div class='p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl mb-3 text-left border border-amber-100 dark:border-amber-800/50'>
            <p class='text-[11px] font-black text-amber-600 uppercase mb-1.5'>AGENDA</p>
            <p class='text-base font-bold text-slate-700 dark:text-slate-200'>${a.title}</p>
        </div>
    `).join('') : '<p class="text-slate-400 py-6 text-sm italic">Tidak ada agenda khusus hari ini.</p>';

                showModernAlert({
                    title: `${d} ${MN[cM]} ${cY}`,
                    html: agendaText,
                    confirmButtonText: 'SIAP!',
                    icon: 'info',
                    iconHtml: '<div class="text-blue-500 scale-110"><i class="fas fa-calendar-day"></i></div>'
                });
            }
            function chM(d) { cM += d; if (cM < 0) { cM = 11; cY--; } if (cM > 11) { cM = 0; cY++; } renderCal(); }

            async function addAgenda() {
                if (GUEST_MODE) {
                    showGuestModal('menambah agenda');
                    return;
                }
                const todayStr = new Date().toISOString().split('T')[0];
                const { value: formValues } = await Swal.fire({
                    ...getModernConfig(),
                    title: 'BUAT AGENDA BARU',
                    icon: 'info',
                    iconHtml: '<div class="text-blue-500 scale-110"><i class="fas fa-calendar-plus"></i></div>',
                    html: `
            <div class="space-y-4 px-2 py-6">
                <div class="p-4 bg-blue-50/50 dark:bg-blue-950/30 rounded-2xl border border-blue-100/50 dark:border-blue-800/50 mb-5 text-left">
                    <div class="flex items-center gap-2 mb-1.5">
                        <i class="fas fa-info-circle text-blue-500 text-sm"></i>
                        <span class="text-[11px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-widest">Aturan Agenda</span>
                    </div>
                    <p class="text-[11px] font-bold text-slate-500 dark:text-slate-400 leading-relaxed">Jadwal hanya bisa dibuat untuk hari ini atau tanggal yang akan datang. Jadwal yang sudah lewat tidak dapat ditambah.</p>
                </div>
                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-2 text-left">Tanggal Acara</label>
                    <input id="swal-input-date" type="date" class="inp" value="${todayStr}" min="${todayStr}">
                </div>
                <div>
                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-2 text-left">Nama Agenda / Acara</label>
                    <input id="swal-input-title" type="text" class="inp" placeholder="Misal: Ujian Matematika, Kerja Kelompok...">
                </div>
            </div>
        `,
                    confirmButtonText: 'BUAT AGENDA',
                    showCancelButton: true,
                    cancelButtonText: 'BATAL',
                    preConfirm: () => {
                        const date = Swal.getPopup().querySelector('#swal-input-date').value;
                        const title = Swal.getPopup().querySelector('#swal-input-title').value.trim();
                        if (!title) {
                            Swal.showValidationMessage('Nama agenda tidak boleh kosong!');
                            return false;
                        }
                        if (!date) {
                            Swal.showValidationMessage('Tanggal acara harus dipilih!');
                            return false;
                        }
                        return { date: date, title: title };
                    }
                });

                if (formValues) {
                    try {
                        const fd = new FormData();
                        fd.append('action', 'add_agenda');
                        fd.append('event_date', formValues.date);
                        fd.append('title', formValues.title);
                        const r = await fetch(PREF_API, { method: 'POST', body: fd });
                        const d = await r.json();
                        if (d.success) {
                            showModernAlert({ title: 'Agenda Ditambahkan!', icon: 'success' });
                            loadAgendas();
                        } else {
                            showModernAlert({ title: 'Gagal', text: d.error, icon: 'error' });
                        }
                    } catch (e) {
                        showModernAlert({ title: 'Error', text: 'Terjadi kesalahan jaringan.', icon: 'error' });
                    }
                }
            }

            /* ── Stats Popups ─────────── */
            function showPointHistory() {
                const pts = <?php echo (int)($user['total_points'] ?? 0) ?>;
                const lvl = <?php echo (int)($user['current_level'] ?? 1) ?>;
                
                Swal.fire({
                    ...getModernConfig(),
                    title: 'RIWAYAT POIN',
                    icon: 'info',
                    iconHtml: '<div class="text-amber-500 scale-110"><i class="fas fa-star"></i></div>',
                    html: `
                        <div class="py-6">
                            <div class="text-center mb-8">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Poin</p>
                                <p class="text-5xl font-black text-slate-900 dark:text-white">${pts}</p>
                            </div>
                            <div class="p-6 bg-amber-50 dark:bg-amber-900/20 rounded-[2rem] border border-amber-100 dark:border-amber-800/50 text-left">
                                <p class="text-xs font-black text-amber-700 dark:text-amber-400 uppercase tracking-widest mb-4">Tips Mendapatkan Poin</p>
                                <ul class="space-y-3 text-xs font-bold text-slate-600 dark:text-slate-300">
                                    <li class="flex items-start gap-2"><i class="fas fa-check-circle text-amber-500 mt-0.5"></i> Selesaikan modul materi</li>
                                    <li class="flex items-start gap-2"><i class="fas fa-check-circle text-amber-500 mt-0.5"></i> Kerjakan kuis dengan benar</li>
                                    <li class="flex items-start gap-2"><i class="fas fa-check-circle text-amber-500 mt-0.5"></i> Selesaikan tantangan harian</li>
                                    <li class="flex items-start gap-2"><i class="fas fa-check-circle text-amber-500 mt-0.5"></i> Pertahankan streak belajar</li>
                                </ul>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'MENGERTI'
                });
            }
            function showLevelProgress() {
                const lvl = <?php echo (int)($user['current_level'] ?? 1) ?>;
                const pts = <?php echo (int)($user['total_points'] ?? 0) ?>;
                const nextLvlPts = (5 * lvl * (lvl + 1) / 2);
                const prevLvlPts = (5 * (lvl - 1) * lvl / 2);
                const progress = Math.min(100, Math.max(0, ((pts - prevLvlPts) / (nextLvlPts - prevLvlPts)) * 100));
                
                Swal.fire({
                    ...getModernConfig(),
                    title: 'PROGRES LEVEL',
                    icon: 'info',
                    iconHtml: '<div class="text-blue-600 scale-110"><i class="fas fa-medal"></i></div>',
                    html: `
                        <div class="py-6">
                            <div class="relative w-32 h-32 mx-auto mb-8">
                                <svg class="w-full h-full transform -rotate-90">
                                    <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="8" fill="transparent" class="text-slate-100 dark:text-slate-800" />
                                    <circle cx="64" cy="64" r="58" stroke="currentColor" stroke-width="8" fill="transparent" stroke-dasharray="364.4" stroke-dashoffset="${364.4 - (364.4 * progress / 100)}" class="text-blue-600 transition-all duration-1000" />
                                </svg>
                                <div class="absolute inset-0 flex flex-col items-center justify-center">
                                    <span class="text-4xl font-black text-slate-900 dark:text-white">${lvl}</span>
                                    <span class="text-[8px] font-black text-slate-400 uppercase tracking-widest">Level</span>
                                </div>
                            </div>
                            <p class="text-sm font-bold text-slate-600 dark:text-slate-300 mb-6">Butuh <span class="text-blue-600">${nextLvlPts - pts} poin</span> lagi untuk naik ke Level ${lvl + 1}</p>
                            <div class="grid grid-cols-2 gap-4">
                                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Poin Saat Ini</p>
                                    <p class="text-lg font-black text-slate-900 dark:text-white">${pts}</p>
                                </div>
                                <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">Target Berikutnya</p>
                                    <p class="text-lg font-black text-slate-900 dark:text-white">${nextLvlPts}</p>
                                </div>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'SEMANGAT!'
                });
            }
            function showStreakDetails() {
                const streak = <?php echo (int)$current_streak ?>;
                Swal.fire({
                    ...getModernConfig(),
                    title: 'STREAK BELAJAR',
                    icon: 'info',
                    iconHtml: '<div class="text-orange-500 scale-110"><i class="fas fa-fire"></i></div>',
                    html: `
                        <div class="py-6">
                            <div class="w-24 h-24 bg-orange-50 dark:bg-orange-900/20 rounded-[2rem] flex items-center justify-center text-orange-500 text-4xl shadow-inner mx-auto mb-6 animate__animated animate__pulse animate__infinite">
                                <i class="fas fa-fire"></i>
                            </div>
                            <p class="text-5xl font-black text-slate-900 dark:text-white mb-2">${streak}</p>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-8">Hari Berturut-turut</p>
                            <div class="p-6 bg-gradient-to-br from-orange-500 to-red-600 rounded-[2rem] text-white text-left shadow-xl shadow-orange-500/20">
                                <p class="text-sm font-black mb-2">🔥 Pertahankan Streak-mu!</p>
                                <p class="text-[11px] font-bold opacity-90 leading-relaxed">Belajar setiap hari untuk menjaga api semangatmu tetap menyala. Jangan biarkan streak-mu terputus!</p>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'SIAP, LANJUTKAN!'
                });
            }
            function showProgressDetails() {
                const subjectsData = <?php echo json_encode($subjects); ?>;
                const prog = <?php echo (int)$total_progress ?>;
                const done = <?php echo (int)$g_done_mods ?>;
                const total = <?php echo (int)$g_total_mods ?>;

                const subjectProgressHtml = Object.values(subjectsData).map(s => `
                    <div class="flex items-center justify-between p-3 bg-white dark:bg-slate-900/50 rounded-xl border border-slate-100 dark:border-slate-700/50 mb-2">
                        <div class="flex items-center gap-3">
                            <i class="fas ${s.icon} text-blue-500 text-xs"></i>
                            <span class="text-[11px] font-bold text-slate-700 dark:text-slate-200">${s.title}</span>
                        </div>
                        <span class="text-[11px] font-black text-blue-600">${s.progress}%</span>
                    </div>
                `).join('');

                Swal.fire({
                    ...getModernConfig(),
                    title: 'TOTAL PENGUASAAN',
                    icon: 'info',
                    iconHtml: '<div class="text-blue-500 scale-110"><i class="fas fa-chart-line"></i></div>',
                    html: `
                        <div class="py-6">
                            <div class="w-full h-4 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden mb-4 border border-slate-200 dark:border-slate-700">
                                <div class="h-full bg-gradient-to-r from-blue-500 to-indigo-600 transition-all duration-1000" style="width: ${prog}%"></div>
                            </div>
                            <div class="flex justify-between items-center mb-8">
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">${done} Modul Selesai</span>
                                <span class="text-2xl font-black text-blue-600">${prog}%</span>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Total ${total} Modul</span>
                            </div>
                            <div class="text-left mb-4">
                                <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Per Mata Pelajaran</p>
                                <div class="max-h-[200px] overflow-y-auto custom-scrollbar">${subjectProgressHtml}</div>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'GAS TERUS!'
                });
            }

            /* ── Inbox Handling ───────── */
            async function showInboxPopup() {
                if (GUEST_MODE) {
                    showGuestModal('melihat kotak masuk');
                    return;
                }

                Swal.fire({
                    ...getModernConfig(),
                    title: 'KOTAK MASUK',
                    icon: 'info',
                    iconHtml: '<div class="text-blue-600 scale-110"><i class="fas fa-envelope"></i></div>',
                    html: `
                        <div id="popupInboxList" class="py-4 space-y-3 max-h-[400px] overflow-y-auto custom-scrollbar pr-2">
                            <div class="text-center py-10 opacity-40">
                                <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                <p class="text-[10px] font-black uppercase">Memuat pesan...</p>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'TUTUP',
                    didOpen: async () => {
                        try {
                            const r = await fetch(PREF_API + '?action=get_inbox');
                            const text = await r.text();
                            const d = parseMagicJSON(text);
                            const list = document.getElementById('popupInboxList');
                            
                            if (d.success && d.messages) {
                                if (d.messages.length > 0) {
                                    list.innerHTML = d.messages.map(msg => {
                                        const isFriendRequest = msg.type === 'friend_request';
                                        const iconClass = isFriendRequest ? 'fa-user-plus' : (msg.type === 'system' ? 'fa-bell' : 'fa-envelope');
                                        const iconColor = isFriendRequest ? 'text-blue-500' : (msg.type === 'system' ? 'text-green-500' : 'text-slate-400');
                                        const msgClass = msg.is_read ? 'opacity-50' : 'font-bold';

                                        return `
                                            <div class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700 flex items-center gap-4 text-left transition-all ${msgClass}">
                                                <div class="w-10 h-10 rounded-xl bg-white dark:bg-slate-800 flex items-center justify-center text-lg shrink-0 ${iconColor} shadow-sm">
                                                    <i class="fas ${iconClass}"></i>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-xs font-black text-slate-800 dark:text-slate-900 truncate">${msg.title}</p>
                                                    <p class="text-[10px] text-slate-600 dark:text-slate-900 font-bold line-clamp-2 mt-0.5">${msg.content}</p>
                                                </div>
                                                ${isFriendRequest ? `
                                                <div class="flex gap-1 shrink-0">
                                                    <button onclick="handleFriendRequest(${msg.request_id}, 'accept', this); Swal.close();" class="w-8 h-8 rounded-lg bg-emerald-500 text-white flex items-center justify-center shadow-lg shadow-emerald-500/20 active:scale-90 transition-all">
                                                        <i class="fas fa-check text-[10px]"></i>
                                                    </button>
                                                    <button onclick="handleFriendRequest(${msg.request_id}, 'reject', this); Swal.close();" class="w-8 h-8 rounded-lg bg-red-500 text-white flex items-center justify-center shadow-lg shadow-red-500/20 active:scale-90 transition-all">
                                                        <i class="fas fa-times text-[10px]"></i>
                                                    </button>
                                                </div>` : ''}
                                            </div>
                                        `;
                                    }).join('');
                                } else {
                                    list.innerHTML = `
                                        <div class="py-10 text-center opacity-40">
                                            <i class="fas fa-envelope-open text-3xl mb-3"></i>
                                            <p class="text-[10px] font-black uppercase">Belum ada pesan</p>
                                        </div>
                                    `;
                                }
                            }
                        } catch (e) {
                            document.getElementById('popupInboxList').innerHTML = '<p class="text-red-500 text-xs font-bold py-10">Gagal memuat pesan.</p>';
                        }
                    }
                });
            }

            function toggleInbox() {
                showInboxPopup();
            }


            async function loadInbox() {
                if (GUEST_MODE) {
                    document.getElementById('inboxList').innerHTML = `
                <div class="p-8 text-center bg-slate-50 dark:bg-slate-900/30 rounded-2xl border-2 border-dashed border-slate-100 dark:border-slate-800 opacity-50">
                    <p class="text-[10px] font-black text-slate-400 uppercase">Silakan login untuk melihat kotak masuk.</p>
                </div>
            `;
                    return;
                }

                try {
                    const r = await fetch(PREF_API + '?action=get_inbox');
                    const text = await r.text();
                    const d = parseMagicJSON(text);
                    const inboxList = document.getElementById('inboxList');
                    if (d.success && d.messages) {
                        if (d.messages.length > 0) {
                            inboxList.innerHTML = d.messages.map(msg => {
                                const isFriendRequest = msg.type === 'friend_request';
                                const iconClass = isFriendRequest ? 'fa-user-plus' : (msg.type === 'system' ? 'fa-bell' : 'fa-envelope');
                                const iconColor = isFriendRequest ? 'text-blue-500' : (msg.type === 'system' ? 'text-green-500' : 'text-slate-400');
                                const msgClass = msg.is_read ? 'opacity-50' : 'font-bold';

                                return `
                    <div class="p-4 bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center gap-4 group hover:border-blue-200 dark:hover:border-blue-800 transition-all duration-300 ${msgClass}">
                        <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center text-xl shrink-0 ${iconColor}">
                            <i class="fas ${iconClass}"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-slate-700 dark:text-slate-100 truncate">${msg.title}</p>
                            <p class="text-[11px] text-slate-400 font-bold uppercase tracking-wider truncate">${msg.content}</p>
                            <p class="text-[9px] text-slate-400 font-bold mt-0.5">${msg.formatted_time}</p>
                        </div>
                        ${isFriendRequest ? `
                        <div class="flex gap-2 shrink-0">
                            <button onclick="handleFriendRequest(${msg.request_id}, 'accept', this)" class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all flex items-center justify-center shadow-sm hover:shadow-lg hover:scale-110 active:scale-95">
                                <i class="fas fa-check text-xs"></i>
                            </button>
                            <button onclick="handleFriendRequest(${msg.request_id}, 'reject', this)" class="w-10 h-10 rounded-xl bg-red-50 dark:bg-red-900/30 text-red-600 hover:bg-red-600 hover:text-white transition-all flex items-center justify-center shadow-sm hover:shadow-lg hover:scale-110 active:scale-95">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>` : ''}
                    </div>`;
                            }).join('');
                        } else {
                            inboxList.innerHTML = `
                <div class="p-8 text-center bg-slate-50 dark:bg-slate-900/30 rounded-2xl border-2 border-dashed border-slate-100 dark:border-slate-800 opacity-50">
                    <p class="text-[10px] font-black text-slate-400 uppercase">Kotak masuk kosong</p>
                </div>
            `;
                        }
                    } else {
                        throw new Error(d.error || 'Gagal memuat kotak masuk');
                    }
                } catch (e) {
                    console.error('Load Inbox failed:', e);
                    inboxList.innerHTML = `
                <div class="p-8 text-center bg-red-50 dark:bg-red-900/30 rounded-2xl border-2 border-dashed border-red-100 dark:border-red-800 opacity-70">
                    <p class="text-[10px] font-black text-red-600 uppercase">Error memuat kotak masuk</p>
                    <p class="text-[10px] text-red-400 mt-1">${e.message}</p>
                </div>
            `;
                }
            }

            async function markAllAsRead() {
                if (GUEST_MODE) return;
                try {
                    const r = await fetch(PREF_API + '?action=mark_inbox_read');
                    const d = await r.json();
                    if (d.success) {
                        const inboxList = document.getElementById('inboxList');
                        if (inboxList) {
                            inboxList.querySelectorAll('.font-bold').forEach(el => el.classList.remove('font-bold'));
                        }
                        document.querySelectorAll('.fa-envelope-open').forEach(icon => icon.className = 'fas fa-envelope');
                        const inboxCount = document.getElementById('inbox_count');
                        if (inboxCount) {
                            inboxCount.textContent = '0';
                            inboxCount.classList.add('hidden');
                        }
                        showModernAlert({ title: 'Semua pesan ditandai dibaca!' });
                    } else {
                        showModernAlert({ title: 'Gagal', text: d.error, icon: 'error' });
                    }
                } catch (e) {
                    showModernAlert({ title: 'Error', text: 'Terjadi kesalahan jaringan.', icon: 'error' });
                }
            }

            /* ── Friend Request Handling ── */
            async function handleFriendRequest(requestId, action, btn) {
                const isAccepted = action === 'accept';
                const fd = new FormData();
                fd.append('action', 'handle_friend_request');
                fd.append('request_id', requestId);
                fd.append('status', action);

                try {
                    const response = await fetch('../../src/views/set_preference.php', {
                        method: 'POST',
                        body: fd
                    });
                    const data = await response.json();

                    if (data.success) {
                        // Show success popup
                        showModernAlert({
                            title: isAccepted ? 'Permintaan Diterima!' : 'Permintaan Ditolak!',
                            text: data.message,
                            icon: isAccepted ? 'success' : 'info'
                        }).then(() => {
                            // Remove the request card from the UI
                            btn.closest('.p-4').remove();
                            // Refresh inbox count or list if needed, instead of full reload
                            if (typeof loadInbox === 'function') loadInbox();
                        });
                    } else {
                        // Show error popup
                        showModernAlert({ title: 'Gagal', text: data.error || 'Terjadi kesalahan.', icon: 'error' });
                    }
                } catch (error) {
                    console.error('Error handling friend request:', error);
                    showModernAlert({ title: 'Error', text: 'Koneksi bermasalah.', icon: 'error' });
                }
            }

            // ── Search Friends ───────── */
            let searchTimeout;
            async function searchGlobalUsers() {
                const input = document.getElementById('friendSearchInput');
                const query = input.value.trim();
                const resultsContainer = document.getElementById('friendSearchResults');
                const wrapper = document.getElementById('searchResultWrapper');

                if (!query) {
                    resultsContainer.innerHTML = '';
                    wrapper.classList.add('hidden');
                    return;
                }

                // Debounce search
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(async () => {
                    try {
                        const r = await fetch(`../../src/api/search.php?q=${encodeURIComponent(query)}`);
                        const d = await r.json();

                        if (d.success && d.users) {
                            resultsContainer.innerHTML = d.users.map(u => {
                                const isFriend = d.friends.includes(u.id);
                                const isPending = d.pending.includes(u.id);
                                const isSelf = u.id === USER_ID;

                                let buttonHtml = '';
                                if (isSelf) {
                                    buttonHtml = '<button class="w-10 h-10 rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-400 flex items-center justify-center"><i class="fas fa-user"></i></button>';
                                } else if (isFriend) {
                                    buttonHtml = '<button class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 hover:bg-blue-600 hover:text-white transition-all flex items-center justify-center shadow-sm"><i class="fas fa-user-check text-xs"></i></button>';
                                } else if (isPending) {
                                    buttonHtml = '<button class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 hover:bg-amber-600 hover:text-white transition-all flex items-center justify-center shadow-sm"><i class="fas fa-user-clock text-xs"></i></button>';
                                } else {
                                    buttonHtml = `<button onclick="addFriend(${u.id}, this)" class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 hover:bg-emerald-600 hover:text-white transition-all flex items-center justify-center shadow-sm hover:shadow-lg hover:scale-110 active:scale-95"><i class="fas fa-plus text-xs"></i></button>`;
                                }

                                return `
                                <div class="p-4 bg-white dark:bg-slate-900/80 rounded-2xl border border-slate-100 dark:border-slate-800 flex items-center gap-4 group hover:border-blue-200 dark:hover:border-blue-800 transition-all duration-300 hover:shadow-lg">
                                    <div class="w-12 h-12 rounded-xl bg-slate-50 dark:bg-slate-800 p-0.5 shrink-0 overflow-hidden">
                                        ${u.profile_pic ? `<img src="../../${u.profile_pic}" class="w-full h-full object-cover rounded-[10px]">` : `<div class="w-full h-full flex items-center justify-center font-black text-slate-400 text-lg uppercase rounded-[10px]">${u.full_name.charAt(0)}</div>`}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h5 class="text-sm font-black text-slate-800 dark:text-white truncate">${u.full_name}</h5>
                                        <p class="text-[10px] font-bold text-blue-500 uppercase tracking-wider mt-0.5">LVL ${u.current_level || 1} • ${u.total_points?.toLocaleString() || 0} PTS</p>
                                    </div>
                                    ${buttonHtml}
                                </div>`;
                            }).join('');
                            wrapper.classList.remove('hidden');
                        } else {
                            resultsContainer.innerHTML = '<p class="text-center text-slate-400 py-6">Tidak ditemukan hasil.</p>';
                            wrapper.classList.remove('hidden');
                        }
                    } catch (e) {
                        console.error('Search error:', e);
                        resultsContainer.innerHTML = '<p class="text-center text-red-400 py-6">Terjadi kesalahan saat mencari.</p>';
                        wrapper.classList.remove('hidden');
                    }
                }, 300);
            }

            function closeFriendSearch() {
                document.getElementById('friendSearchInput').value = '';
                document.getElementById('friendSearchResults').innerHTML = '';
                document.getElementById('searchResultWrapper').classList.add('hidden');
            }

            async function addFriend(userId, btn) {
                if (GUEST_MODE) {
                    showGuestModal('menambah teman');
                    return;
                }
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                btn.disabled = true;
                
                const fd = new FormData();
                fd.append('action', 'add_friend');
                fd.append('friend_id', userId);

                try {
                    const r = await fetch('../../src/views/set_preference.php', {
                        method: 'POST',
                        body: fd
                    });
                    const d = await r.json();
                    if (d.success) {
                        showModernAlert({ title: 'Permintaan Terkirim!', text: 'Permintaan pertemanan telah dikirim.' });
                        btn.innerHTML = '<i class="fas fa-user-clock"></i>';
                        btn.classList.remove('bg-emerald-50', 'text-emerald-600');
                        btn.classList.add('bg-amber-50', 'text-amber-600');
                    } else {
                        throw new Error(d.error || 'Gagal mengirim permintaan');
                    }
                } catch (e) {
                    showModernAlert({ title: 'Gagal', text: e.message, icon: 'error' });
                    btn.innerHTML = '<i class="fas fa-plus"></i>';
                    btn.disabled = false;
                }
            }

            function openFriendChat(userId) {
                if (GUEST_MODE) {
                    showGuestModal('mengobrol dengan teman');
                    return;
                }
                // Placeholder for opening chat window/modal
                showModernAlert({ title: 'Fitur Chat', text: 'Fungsionalitas chat akan segera hadir!', icon: 'info' });
            }

            function viewFriendProfile(userId) {
                // Placeholder for viewing friend profile
                showModernAlert({ title: 'Profil Teman', text: `Melihat profil teman dengan ID: ${userId}`, icon: 'info' });
            }

            /* ── To-Do List Handling ── */
            async function loadTodos() {
                if (GUEST_MODE) return;
                try {
                    const r = await fetch(TODO_API + `?user_id=${USER_ID}`);
                    const d = await r.json();
                    const list = document.getElementById('todoList');
                    const countEl = document.getElementById('todoCnt');
                    if (d.success && d.todos) {
                        list.innerHTML = d.todos.map(t => `
                    <div class="todo-item ${t.is_completed ? 'done' : ''}">
                        <input type="checkbox" class="w-4 h-4 accent-blue-500 rounded cursor-pointer" ${t.is_completed ? 'checked' : ''} onchange="toggleTodo(${t.id}, this)">
                        <span class="flex-1 text-sm font-bold ${t.is_completed ? 'text-slate-400' : 'text-slate-700 dark:text-slate-200'}">${t.task}</span>
                        <button onclick="deleteTodo(${t.id}, this)" class="text-slate-300 hover:text-red-500 transition-all opacity-0 group-hover:opacity-100"><i class="fas fa-times text-xs"></i></button>
                    </div>`).join('');
                        countEl.textContent = d.todos.filter(t => !t.is_completed).length;
                    } else {
                        list.innerHTML = '<p class="text-center text-slate-400 py-6">Belum ada tugas.</p>';
                        countEl.textContent = '0';
                    }
                } catch (e) {
                    console.error('Load Todos failed:', e);
                    list.innerHTML = '<p class="text-center text-red-400 py-6">Gagal memuat tugas.</p>';
                }
            }

            async function addTodo(event) {
                event.preventDefault();
                if (GUEST_MODE) {
                    showGuestModal('menambah tugas');
                    return;
                }
                const input = document.getElementById('todoInput');
                const task = input.value.trim();
                if (!task) return;

                try {
                    const fd = new FormData();
                    fd.append('action', 'add');
                    fd.append('task', task);
                    fd.append('user_id', USER_ID);
                    const r = await fetch(TODO_API, { method: 'POST', body: fd });
                    const d = await r.json();
                    if (d.success) {
                        input.value = '';
                        loadTodos();
                    } else {
                        throw new Error(d.error);
                    }
                } catch (e) {
                    showModernAlert({ title: 'Gagal', text: e.message, icon: 'error' });
                }
            }

            async function toggleTodo(id, checkbox) {
                if (GUEST_MODE) {
                    checkbox.checked = !checkbox.checked;
                    showGuestModal('menyelesaikan tugas');
                    return;
                }
                try {
                    const fd = new FormData();
                    fd.append('action', 'toggle');
                    fd.append('id', id);
                    const r = await fetch(TODO_API, { method: 'POST', body: fd });
                    const d = await r.json();
                    if (d.success) {
                        loadTodos(); // Re-load to update count and UI state
                    } else {
                        throw new Error(d.error || 'Gagal memperbarui tugas');
                    }
                } catch (e) {
                    checkbox.checked = !checkbox.checked; // Revert checkbox state
                    showModernAlert({ title: 'Gagal', text: e.message, icon: 'error' });
                }
            }

            async function deleteTodo(id, btn) {
                const item = btn.closest('.todo-item');
                const res = await confirmModernAlert({ title: 'Hapus Tugas?', text: 'Tugas ini akan dihapus permanen.', icon: 'warning' });
                if (!res.isConfirmed) return;

                try {
                    const fd = new FormData();
                    fd.append('action', 'delete');
                    fd.append('id', id);
                    const r = await fetch(TODO_API, { method: 'POST', body: fd });
                    const d = await r.json();
                    if (d.success) {
                        item.remove();
                        loadTodos(); // Re-load to update count
                    } else {
                        throw new Error(d.error);
                    }
                } catch (e) {
                    showModernAlert({ title: 'Gagal', text: e.message, icon: 'error' });
                }
            }

            /* ── Community Handling ── */
            let activeCommId = null;

            async function createComm() {
                if (GUEST_MODE) {
                    showGuestModal('membuat komunitas');
                    return;
                }
                const { value: formValues } = await Swal.fire({
                    ...getModernConfig(),
                    title: 'BUAT KOMUNITAS',
                    html: `
                        <div class="text-left space-y-4">
                            <div>
                                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-2 text-left">Nama Komunitas</label>
                                <input id="swal-comm-name" type="text" class="inp" placeholder="Misal: Pejuang PTN, Belajar Coding...">
                            </div>
                            <div>
                                <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-2 text-left">Deskripsi</label>
                                <textarea id="swal-comm-desc" class="inp h-24" placeholder="Apa tujuan komunitas ini?"></textarea>
                            </div>
                        </div>
                    `,
                    confirmButtonText: 'BUAT SEKARANG',
                    showCancelButton: true,
                    cancelButtonText: 'BATAL',
                    preConfirm: () => {
                        const name = Swal.getPopup().querySelector('#swal-comm-name').value.trim();
                        const desc = Swal.getPopup().querySelector('#swal-comm-desc').value.trim();
                        if (!name) {
                            Swal.showValidationMessage('Nama komunitas wajib diisi!');
                            return false;
                        }
                        return { name, desc };
                    }
                });

                if (formValues) {
                    try {
                        const fd = new FormData();
                        fd.append('action', 'create_community');
                        fd.append('name', formValues.name);
                        fd.append('description', formValues.desc);
                        const r = await fetch(PREF_API, { method: 'POST', body: fd });
                        const d = await r.json();
                        if (d.success) {
                            showModernAlert({ title: 'Komunitas Berhasil Dibuat!', icon: 'success' });
                            location.reload();
                        } else {
                            showModernAlert({ title: 'Gagal', text: d.error, icon: 'error' });
                        }
                    } catch (e) {
                        showModernAlert({ title: 'Error', text: 'Terjadi kesalahan jaringan.', icon: 'error' });
                    }
                }
            }

            async function loadCommInfo(id) {
                console.log('Loading Comm Info for ID:', id);
                if (!id) return;
                try {
                    const r = await fetch(`${PREF_API}?action=get_community_settings&community_id=${id}`);
                    const d = await r.json();
                    console.log('Comm Info Response:', d);
                    if (d.success) {
                        const c = d.community;
                        const members = d.members || [];
                        
                        // Header
                        document.getElementById('commHeaderName').textContent = c.name;
                        document.getElementById('commHeaderCount').textContent = `${c.member_count} Anggota`;
                        
                        // Info Panel
                        const avatar = document.getElementById('commInfoAvatar');
                        if (c.image) {
                            avatar.innerHTML = `<img src="../../${c.image}" class="w-full h-full object-cover">`;
                        } else {
                            avatar.textContent = c.name.substring(0, 2).toUpperCase();
                        }
                        
                        document.getElementById('commInfoName').textContent = c.name;
                        document.getElementById('commInfoDesc').textContent = c.description || 'Tidak ada deskripsi.';
                        document.getElementById('commInfoVision').textContent = c.vision || '-';
                        document.getElementById('commInfoMission').textContent = c.mission || '-';
                        document.getElementById('commInfoMemberCount').textContent = `${members.length} Member`;
                        
                        const memberList = document.getElementById('commMemberListInfo');
                        memberList.innerHTML = members.map(m => `
                            <div class="flex items-center justify-between p-3 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-slate-700 overflow-hidden">
                                        <img src="../../${m.profile_pic || 'assets/img/default-pp.png'}" class="w-full h-full object-cover">
                                    </div>
                                    <div class="flex flex-col">
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200">${m.full_name}</span>
                                        <span class="text-[8px] font-black uppercase text-blue-500">${m.role}</span>
                                    </div>
                                </div>
                                <div class="text-[8px] font-bold text-slate-400 uppercase">${m.joined_at ? m.joined_at.split(' ')[0] : ''}</div>
                            </div>
                        `).join('');
                    }
                } catch (e) {
                    console.error('Load Comm Info failed:', e);
                    showModernAlert({ icon: 'error', title: 'Error', text: 'Gagal memuat informasi komunitas.' });
                }
            }

            function swComm(id, btn) {
                console.log('Switching Community to ID:', id);
                activeCommId = id;
                document.querySelectorAll('.comm-btn').forEach(b => {
                    b.classList.remove('bg-blue-50', 'dark:bg-blue-900/20', 'shadow-sm', 'ring-1', 'ring-blue-500/20');
                    b.classList.add('hover:bg-slate-50', 'dark:hover:bg-slate-800/60');
                });
                btn.classList.add('bg-blue-50', 'dark:bg-blue-900/20', 'shadow-sm', 'ring-1', 'ring-blue-500/20');
                btn.classList.remove('hover:bg-slate-50', 'dark:hover:bg-slate-800/60');
        
                const name = btn.getAttribute('data-comm-name');
                document.getElementById('commHeaderName').textContent = name;
                
                swCommTab('chat');
                loadCommChat();
                loadCommInfo(id);
            }

            function filterCommList(val) {
                const query = val.toLowerCase();
                document.querySelectorAll('.comm-btn').forEach(btn => {
                    const name = btn.getAttribute('data-comm-name').toLowerCase();
                    btn.parentElement.parentElement.style.display = name.includes(query) ? 'block' : 'none';
                });
            }

            async function openExploreModal() {
                if (GUEST_MODE) {
                    showGuestModal('menjelajahi komunitas');
                    return;
                }

                const isDark = document.documentElement.classList.contains('dark');
                
                Swal.fire({
                    ...getModernConfig(),
                    title: 'JELAJAHI KOMUNITAS',
                    html: `
                        <div class="space-y-6 text-left">
                            <div class="relative">
                                <input type="text" id="exploreSearchInput" placeholder="Cari nama atau deskripsi komunitas..."
                                    class="w-full bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700 rounded-2xl px-6 py-4 text-sm font-bold outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 transition-all"
                                    onkeyup="searchCommunities(this.value)">
                                <i class="fas fa-search absolute right-6 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            </div>
                            <div id="exploreResults" class="space-y-3 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                                <div class="text-center py-10 opacity-40">
                                    <i class="fas fa-spinner fa-spin text-2xl mb-2"></i>
                                    <p class="text-[10px] font-black uppercase">Memuat komunitas...</p>
                                </div>
                            </div>
                        </div>
                    `,
                    showConfirmButton: false,
                    showCloseButton: true,
                    didOpen: () => {
                        searchCommunities('');
                    }
                });
            }

            async function searchCommunities(query) {
                const resultsContainer = document.getElementById('exploreResults');
                if (!resultsContainer) return;

                try {
                    const r = await fetch(`${PREF_API}?action=search_communities&q=${encodeURIComponent(query)}`);
                    const d = await r.json();
                    
                    if (d.success && d.communities) {
                        if (d.communities.length > 0) {
                            resultsContainer.innerHTML = d.communities.map(c => `
                                <div class="p-4 bg-white dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700 flex items-center justify-between group hover:border-blue-500/30 transition-all">
                                    <div class="flex items-center gap-4 min-w-0">
                                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-white font-black text-xs shrink-0 shadow-md">
                                            ${c.name.substring(0, 2).toUpperCase()}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-bold text-slate-900 dark:text-white text-sm truncate">${c.name}</p>
                                            <p class="text-[10px] text-slate-500 dark:text-slate-400 font-bold truncate">${c.description || 'Tidak ada deskripsi.'}</p>
                                            <p class="text-[9px] font-black text-blue-500 uppercase tracking-wider mt-1">${c.member_count} Anggota</p>
                                        </div>
                                    </div>
                                    <button onclick="joinCommunity(${c.id}, this)" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black text-[10px] uppercase tracking-widest transition-all active:scale-95 shadow-lg shadow-blue-500/20">Gabung</button>
                                </div>
                            `).join('');
                        } else {
                            resultsContainer.innerHTML = `
                                <div class="py-10 text-center opacity-40">
                                    <i class="fas fa-search text-3xl mb-3"></i>
                                    <p class="text-[10px] font-black uppercase">Komunitas tidak ditemukan</p>
                                </div>
                            `;
                        }
                    }
                } catch (e) {
                    resultsContainer.innerHTML = '<p class="text-center text-red-400 py-10 text-xs font-bold">Gagal memuat komunitas.</p>';
                }
            }

            async function joinCommunity(id, btn) {
                const originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                try {
                    const fd = new FormData();
                    fd.append('action', 'join_community');
                    fd.append('community_id', id);
                    const r = await fetch(PREF_API, { method: 'POST', body: fd });
                    const d = await r.json();
                    
                    if (d.success) {
                        showModernAlert({ title: 'Berhasil!', text: 'Kamu telah bergabung dengan komunitas ini.', icon: 'success' });
                        location.reload();
                    } else {
                        throw new Error(d.error || 'Gagal bergabung');
                    }
                } catch (e) {
                    showModernAlert({ title: 'Gagal', text: e.message, icon: 'error' });
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            }

            function swCommTab(tab) {
                document.querySelectorAll('.comm-panel').forEach(p => {
                    p.classList.add('hidden');
                    p.classList.remove('flex', 'opacity-100', 'translate-x-0');
                });
                document.querySelectorAll('.comm-tab-btn').forEach(b => {
                    b.classList.remove('bg-blue-600', 'text-white', 'shadow-lg', 'shadow-blue-500/20', 'scale-100');
                    b.classList.add('text-slate-400', 'hover:text-blue-500');
                });

                const panel = document.getElementById(`comm-panel-${tab}`);
                const btn = document.getElementById(`btn-comm-${tab}`);
                
                panel.classList.remove('hidden');
                panel.classList.add('flex');
                
                // Trigger transition
                setTimeout(() => {
                    panel.classList.add('opacity-100', 'scale-100');
                    panel.classList.remove('scale-95');
                    // Ensure translate-x-0 is applied to all panels except video,
                    // as video panel uses scale for its transition.
                    if (tab !== 'video') {
                        panel.classList.add('translate-x-0');
                    } else {
                        // For video tab, ensure it's visible and centered if needed
                        // The scale transition handles the entry animation.
                        // No translate-x needed here.
                    }
                }, 10);

                if (btn) {
                    btn.classList.add('bg-blue-600', 'text-white', 'shadow-lg', 'shadow-blue-500/20', 'scale-100');
                    btn.classList.remove('text-slate-400', 'hover:text-blue-500');
                }
            }

            async function loadCommChat() {
                console.log('Loading Comm Chat for ID:', activeCommId);
                if (!activeCommId) return;
                const chatArea = document.getElementById('commChatArea');
                chatArea.innerHTML = '<div class="flex items-center justify-center py-32 opacity-50"><i class="fas fa-spinner fa-spin text-2xl"></i></div>';
                
                try {
                    const r = await fetch(`${PREF_API}?action=get_community_messages&community_id=${activeCommId}`);
                    const d = await r.json();
                    console.log('Comm Chat Response:', d);
                    if (d.success && d.messages) {
                        chatArea.innerHTML = d.messages.map(m => `
                            <div class="flex ${m.user_id == USER_ID ? 'justify-end' : 'justify-start'} gap-3 group">
                                ${m.user_id != USER_ID ? `
                                    <div class="w-8 h-8 rounded-lg bg-slate-200 dark:bg-slate-700 overflow-hidden shrink-0">
                                        <img src="../../${m.profile_pic || 'assets/img/default-pp.png'}" class="w-full h-full object-cover">
                                    </div>
                                ` : ''}
                                <div class="max-w-[70%]">
                                    <div class="flex items-center gap-2 mb-1 ${m.user_id == USER_ID ? 'justify-end' : ''}">
                                        ${m.user_id != USER_ID ? `<span class="text-[10px] font-black text-slate-500 dark:text-slate-400">${m.full_name}</span>` : ''}
                                        <span class="text-[8px] font-bold text-slate-400 uppercase">${m.created_at.split(' ')[0]}</span>
                                    </div>
                                    <div class="p-3 rounded-2xl text-sm font-bold ${m.user_id == USER_ID ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 rounded-tl-none border border-slate-100 dark:border-slate-700'} shadow-sm">
                                        ${m.message}
                                    </div>
                                </div>
                            </div>
                        `).join('');
                        chatArea.scrollTop = chatArea.scrollHeight;
                    } else {
                        chatArea.innerHTML = '<div class="flex flex-col items-center justify-center py-32 opacity-20"><div class="w-24 h-24 rounded-[2.5rem] bg-slate-200 dark:bg-slate-800 flex items-center justify-center text-4xl mb-6 shadow-inner"><i class="fas fa-comments"></i></div><p class="font-black text-xs uppercase tracking-[0.3em]">Belum ada pesan</p></div>';
                    }
                } catch (e) {
                    console.error('Load Comm Chat failed:', e);
                    chatArea.innerHTML = '<p class="text-center text-red-400 py-32">Gagal memuat pesan.</p>';
                    showModernAlert({ icon: 'error', title: 'Error', text: 'Gagal memuat pesan diskusi.' });
                }
            }

            async function sendCommChat(e) {
                e.preventDefault();
                if (GUEST_MODE) {
                    showGuestModal('mengirim pesan komunitas');
                    return;
                }
                if (!activeCommId) return;
                const input = document.getElementById('commChatInput');
                const msg = input.value.trim();
                if (!msg) return;

                try {
                    const fd = new FormData();
                    fd.append('action', 'send_community_message');
                    fd.append('community_id', activeCommId);
                    fd.append('message', msg);
                    const r = await fetch(PREF_API, { method: 'POST', body: fd });
                    const d = await r.json();
                    if (d.success) {
                        input.value = '';
                        loadCommChat();
                    } else {
                        showModernAlert({ title: 'Gagal', text: d.error, icon: 'error' });
                    }
                } catch (e) {
                    showModernAlert({ title: 'Error', text: 'Koneksi bermasalah.', icon: 'error' });
                }
            }

            async function openCommSettings(id) {
                if (GUEST_MODE) {
                    showGuestModal('mengakses pengaturan komunitas');
                    return;
                }
                console.log('Opening Comm Settings for ID:', id);
                if (!id) {
                    showModernAlert({ icon: 'warning', title: 'Perhatian', text: 'Silakan pilih komunitas terlebih dahulu!' });
                    return;
                }
                try {
                    const r = await fetch(`${PREF_API}?action=get_community_settings&community_id=${id}`);
                    const d = await r.json();
                    if (d.success) {
                        const c = d.community;
                        const myRole = d.my_role;
                        
                        let settingsHtml = `
                            <div class="space-y-6 text-left">
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-2">Nama Komunitas</label>
                                    <input id="set-comm-name" type="text" class="inp" value="${c.name}">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-2">Deskripsi</label>
                                    <textarea id="set-comm-desc" class="inp h-24">${c.description}</textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-2">Visi</label>
                                        <input id="set-comm-vision" type="text" class="inp" value="${c.vision || ''}">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-2">Misi</label>
                                        <input id="set-comm-mission" type="text" class="inp" value="${c.mission || ''}">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-black text-slate-400 uppercase tracking-widest mb-2 pl-2">Privasi</label>
                                    <select id="set-comm-privacy" class="inp">
                                        <option value="public" ${c.privacy === 'public' ? 'selected' : ''}>Publik</option>
                                        <option value="private" ${c.privacy === 'private' ? 'selected' : ''}>Privat</option>
                                    </select>
                                </div>
                            </div>
                        `;

                        if (myRole === 'owner') {
                            settingsHtml += `
                                <div class="pt-6 border-t border-slate-100 dark:border-slate-800 mt-6">
                                    <button onclick="deleteCommunity(${id})" class="w-full py-3 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-red-600 hover:text-white transition-all">Hapus Komunitas</button>
                                </div>
                            `;
                        }

                        const result = await Swal.fire({
                            ...getModernConfig(),
                            title: 'PENGATURAN KOMUNITAS',
                            html: settingsHtml,
                            showCancelButton: true,
                            confirmButtonText: 'SIMPAN PERUBAHAN',
                            cancelButtonText: 'BATAL',
                            preConfirm: () => {
                                return {
                                    name: Swal.getPopup().querySelector('#set-comm-name').value,
                                    description: Swal.getPopup().querySelector('#set-comm-desc').value,
                                    vision: Swal.getPopup().querySelector('#set-comm-vision').value,
                                    mission: Swal.getPopup().querySelector('#set-comm-mission').value,
                                    privacy: Swal.getPopup().querySelector('#set-comm-privacy').value,
                                };
                            }
                        });

                        if (result.isConfirmed) {
                            try {
                                const fd = new FormData();
                                fd.append('action', 'update_community_info');
                                fd.append('community_id', id);
                                fd.append('name', result.value.name);
                                fd.append('description', result.value.description);
                                fd.append('vision', result.value.vision);
                                fd.append('mission', result.value.mission);
                                fd.append('privacy', result.value.privacy);
                                const r = await fetch(PREF_API, { method: 'POST', body: fd });
                                const d = await r.json();
                                if (d.success) {
                                    showModernAlert({ title: 'Berhasil!', text: 'Pengaturan komunitas diperbarui.', icon: 'success' });
                                    location.reload();
                                } else {
                                    showModernAlert({ title: 'Gagal', text: d.error, icon: 'error' });
                                }
                            } catch (e) {
                                showModernAlert({ title: 'Error', text: 'Terjadi kesalahan jaringan.', icon: 'error' });
                            }
                        }
                    }
                } catch (e) {
                    console.error(e);
                }
            }

            async function deleteCommunity(id) {
                if (GUEST_MODE) {
                    showGuestModal('menghapus komunitas');
                    return;
                }
                const res = await confirmModernAlert({ title: 'Hapus Komunitas?', text: 'Tindakan ini tidak bisa dibatalkan. Semua pesan akan hilang.', icon: 'warning' });
                if (!res.isConfirmed) return;
                try {
                    const fd = new FormData();
                    fd.append('action', 'delete_community');
                    fd.append('community_id', id);
                    const r = await fetch(PREF_API, { method: 'POST', body: fd });
                    const d = await r.json();
                    if (d.success) {
                        showModernAlert({ title: 'Terhapus!', text: 'Komunitas telah dihapus.', icon: 'success' });
                        location.reload();
                    } else {
                        showModernAlert({ title: 'Gagal', text: d.error, icon: 'error' });
                    }
                } catch (e) {
                    showModernAlert({ title: 'Error', text: 'Koneksi bermasalah.', icon: 'error' });
                }
            }

            /* ── Video Room Simulation ── */
            let videoPulseInterval = null;

            async function loadActiveVideoUsers() {
                if (!activeCommId) return;
                try {
                    const r = await fetch(`${PREF_API}?action=get_active_video_users&community_id=${activeCommId}`);
                    const d = await r.json();
                    if (d.success && d.users) {
                        const users = d.users;
                        
                        // Update Join Overlay active users
                        const activeUsersDiv = document.getElementById('videoActiveUsers');
                        if (activeUsersDiv) {
                            activeUsersDiv.innerHTML = users.map(u => `
                                <div class="w-8 h-8 rounded-full border-2 border-white dark:border-slate-900 overflow-hidden shadow-sm">
                                    <img src="../../${u.profile_pic || 'assets/img/default-pp.png'}" class="w-full h-full object-cover">
                                </div>
                            `).join('');
                        }
                        
                        // Update Video Grid remote videos
                        const remoteVideosDiv = document.getElementById('remoteVideos');
                        if (remoteVideosDiv) {
                            const remoteUsers = users.filter(u => u.id != USER_ID);
                            remoteVideosDiv.innerHTML = remoteUsers.map(u => `
                                <div class="relative group rounded-[3.5rem] overflow-hidden bg-slate-900 border-2 border-white/5 shadow-2xl transition-all duration-700 hover:border-blue-500/50">
                                    <div class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-slate-900 to-slate-800">
                                        <div class="w-36 h-36 rounded-full bg-gradient-to-br from-blue-600 to-blue-800 flex items-center justify-center text-white font-black text-3xl shadow-[0_0_50px_rgba(37,99,235,0.3)] mb-6 transition-all duration-700 group-hover:scale-110 overflow-hidden">
                                            ${u.profile_pic ? `<img src="../../${u.profile_pic}" class="w-full h-full object-cover">` : u.full_name.substring(0,2).toUpperCase()}
                                        </div>
                                        <p class="font-black text-slate-400 text-xs tracking-[0.4em] uppercase opacity-60">${u.full_name}</p>
                                    </div>
                                    <div class="absolute bottom-8 left-8 flex items-center gap-3 px-4 py-2 bg-black/60 backdrop-blur-xl rounded-2xl border border-white/10">
                                        <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse shadow-[0_0_10px_#3b82f6]"></div>
                                        <span class="text-[10px] font-black text-white uppercase tracking-widest">${u.full_name}</span>
                                    </div>
                                </div>
                            `).join('');
                        }
                    }
                } catch (e) {
                    console.error('Load Active Video Users failed:', e);
                }
            }

            async function joinVideoRoom() {
                if (GUEST_MODE) {
                    showGuestModal('masuk ke video room');
                    return;
                }
                document.getElementById('videoJoinOverlay').classList.add('opacity-0', 'pointer-events-none', 'scale-90');
                document.getElementById('localVideo').classList.remove('hidden');
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
                    document.getElementById('localVideo').srcObject = stream;
                    
                    if (videoPulseInterval) clearInterval(videoPulseInterval);
                    videoPulseInterval = setInterval(async () => {
                        const fd = new FormData();
                        fd.append('action', 'video_pulse');
                        fd.append('community_id', activeCommId);
                        await fetch(PREF_API, { method: 'POST', body: fd });
                        loadActiveVideoUsers();
                    }, 10000);
                    loadActiveVideoUsers();
                } catch (e) {
                    showModernAlert({ title: 'Kamera/Mic Gagal', text: 'Pastikan izin kamera dan mic diberikan.', icon: 'error' });
                    document.getElementById('localVideo').classList.add('hidden');
                    document.getElementById('localVideoPlaceholder').classList.remove('hidden');
                }
            }

            function toggleCam() {
                const video = document.getElementById('localVideo').srcObject;
                if (video && video.getVideoTracks().length > 0) {
                    const track = video.getVideoTracks()[0];
                    track.enabled = !track.enabled;
                    document.getElementById('camIcon').className = track.enabled ? 'fas fa-video' : 'fas fa-video-slash';
                }
            }

            function toggleMic() {
                const video = document.getElementById('localVideo').srcObject;
                if (video && video.getAudioTracks().length > 0) {
                    const track = video.getAudioTracks()[0];
                    track.enabled = !track.enabled;
                    document.getElementById('micIcon').className = track.enabled ? 'fas fa-microphone' : 'fas fa-microphone-slash';
                }
            }

            function leaveVideoRoom() {
                if (videoPulseInterval) {
                    clearInterval(videoPulseInterval);
                    videoPulseInterval = null;
                }
                const video = document.getElementById('localVideo').srcObject;
                if (video) {
                    video.getTracks().forEach(t => t.stop());
                }
                document.getElementById('localVideo').srcObject = null;
                document.getElementById('localVideo').classList.add('hidden');
                document.getElementById('localVideoPlaceholder').classList.remove('hidden');
                document.getElementById('videoJoinOverlay').classList.remove('opacity-0', 'pointer-events-none', 'scale-90');
                swCommTab('chat');
            }

            document.addEventListener('DOMContentLoaded', () => {
                // Load initial data
                loadAgendas();
                loadInbox();
                loadTodos();

                // Set initial tab based on localStorage
                const urlParams = new URLSearchParams(window.location.search);
                const lastTab = urlParams.get('login') ? 'home' : (localStorage.getItem('stepup_active_tab') || 'home');
                sw(lastTab);

                // Initialize clock
                function updateClock() {
                    const now = new Date();
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    document.getElementById('liveClock').textContent = `${hours}:${minutes}:${seconds}`;
                }
                setInterval(updateClock, 1000);
                updateClock(); // Initial call
            });

            // Initialize tooltips (if any) - not used currently
            // const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
            // const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))

        </script>
    </body>
</html>
