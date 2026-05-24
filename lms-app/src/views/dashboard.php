<?php
// src/views/dashboard.php — StepUp v4
error_reporting(0);
ini_set('display_errors', 0);
session_start();
if (!isset($_SESSION['user_id'])) { header("Location: ../../index.php"); exit; }
require_once '../../config/db.php';

// Auto-create tables if they don't exist (safe on any host)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS user_preferences (
        id INT AUTO_INCREMENT PRIMARY KEY, 
        user_id INT NOT NULL UNIQUE, 
        bg_type VARCHAR(20) DEFAULT 'batik', 
        bg_value TEXT, 
        glass_opacity INT DEFAULT 50,
        theme VARCHAR(10) DEFAULT 'light', 
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=MyISAM");
    $pdo->exec("CREATE TABLE IF NOT EXISTS todos (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, task VARCHAR(255) NOT NULL, is_completed TINYINT(1) DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=MyISAM");
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_log (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, activity_date DATE NOT NULL, UNIQUE KEY (user_id, activity_date)) ENGINE=MyISAM");
    $pdo->exec("CREATE TABLE IF NOT EXISTS calendar_events (id INT AUTO_INCREMENT PRIMARY KEY, user_id INT NOT NULL, event_date DATE NOT NULL, title VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=MyISAM");
    
    // Silent Column Migration
    try { $pdo->exec("ALTER TABLE user_preferences ADD COLUMN glass_opacity INT DEFAULT 50 AFTER bg_value"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE users ADD COLUMN title VARCHAR(100) DEFAULT NULL"); } catch(Exception $e) {}
    try { $pdo->exec("ALTER TABLE subjects ADD COLUMN batik_bg VARCHAR(255) DEFAULT NULL AFTER icon"); } catch(Exception $e) {}
} catch(Exception $e) {}

// Log today's activity
try {
    $stmt = $pdo->prepare("INSERT IGNORE INTO activity_log (user_id, activity_date) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], date('Y-m-d')]);
} catch(Exception $e) {}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
if (!$user) { session_destroy(); header("Location: ../../index.php"); exit; }

// Preferences — Default = batik megamendung (mega.jpg)
$_BATIK_REL = 'assets/img/mega.jpg';
$_BATIK_URL = '../../' . $_BATIK_REL;

// Initial Default
$pref = ['bg_type'=>'batik','bg_value'=>$_BATIK_REL, 'glass_opacity' => 50];

try {
    $p = $pdo->prepare("SELECT * FROM user_preferences WHERE user_id=?");
    $p->execute([$_SESSION['user_id']]);
    $pr = $p->fetch();
    if ($pr) {
        if(!empty($pr['bg_value'])) {
            $pref['bg_type'] = $pr['bg_type'];
            $pref['bg_value'] = $pr['bg_value'];
        }
        if(isset($pr['glass_opacity'])) {
            $pref['glass_opacity'] = intval($pr['glass_opacity']);
        }
    }
} catch(Exception $e){}

// Random quote
$quote = "Belajar hari ini adalah investasi terbaik untuk masa depanmu.";
try { $q = $pdo->query("SELECT text FROM motivational_quotes ORDER BY RAND() LIMIT 1")->fetch(); if($q) $quote=$q['text']; }catch(Exception $e){}

// Enhanced Subjects with Completion data
$subjects = [];
$all_progress = [];
try {
    $stAllP = $pdo->prepare("SELECT subject_slug, topic_slug, module_slug, MAX(is_completed) as is_completed FROM progress WHERE user_id = ? GROUP BY subject_slug, topic_slug, module_slug");
    $stAllP->execute([$_SESSION['user_id']]);
    foreach($stAllP->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $p_s = preg_replace('/[^a-z0-9]/', '', strtolower($row['subject_slug']));
        $p_t = preg_replace('/[^a-z0-9]/', '', strtolower($row['topic_slug']));
        $p_m = preg_replace('/[^a-z0-9]/', '', strtolower($row['module_slug']));
        if(!isset($all_progress[$p_s])) $all_progress[$p_s] = [];
        if(!isset($all_progress[$p_s][$p_t])) $all_progress[$p_s][$p_t] = [];
        if(!isset($all_progress[$p_s][$p_t][$p_m]) || $row['is_completed'] == 1) {
            $all_progress[$p_s][$p_t][$p_m] = $row['is_completed'];
        }
    }
} catch(Exception $e){}

try {
    $qS = $pdo->query("SELECT * FROM subjects ORDER BY id");
    while($sub = $qS->fetch(PDO::FETCH_ASSOC)) {
        // Normalisasi slug yang SANGAT ketat: trim, lowercase, spasi jadi minus
        $raw_slug = str_replace(' ', '-', trim($sub['slug']));
        $sl = strtolower($raw_slug); 
        $nm = trim($sub['name'] ?? ($sub['title'] ?? 'Mapel'));
        $ic=trim($sub['icon']??'');
        if (preg_match('/fa-[a-z0-9-]+/', $ic, $m)) {
            $ic = $m[0];
        } else if(empty($ic)){
            if(stripos($nm,'Matematik')!==false)$ic='fa-calculator';
            elseif(stripos($nm,'PKN')!==false)$ic='fa-landmark';
            elseif(stripos($nm,'Seni')!==false)$ic='fa-palette';
            else$ic='fa-book-open';
        }
        if(!isset($subjects[$sl])) {
            $subjects[$sl]=[
                'id'=>$sub['id'],
                'slug'=>$sl,
                'title'=>$nm,
                'icon'=>$ic, // default icon
                'color'=>($sub['color']??'#2563eb'),
                'desc'=>($sub['description']??'Pelajari '.$nm.' di StepUp.'),
                'topics'=>[], 
                'completed_mods'=>0, 
                'total_mods'=>0,
                'batik_bg'=>($sub['batik_bg']??null)
            ];
        }
        $sub_ref = &$subjects[$sl];
        
        $stT=$pdo->prepare("SELECT * FROM topics WHERE subject_id=? ORDER BY id");
        $stT->execute([$sub['id']]);
        foreach($stT->fetchAll(PDO::FETCH_ASSOC) as $top){
            $ts = trim($top['slug'], " \t\n\r\0\x0B-");
            if (!isset($sub_ref['topics'][$ts])) {
                $sub_ref['topics'][$ts]=['id'=>$top['id'],'slug'=>$ts,'title'=>trim($top['name']??($top['title']??'Topik')),'modules'=>[], 'is_topic_completed'=>true];
            }
            $top_ref = &$sub_ref['topics'][$ts];
            $stM=$pdo->prepare("SELECT * FROM modules WHERE topic_id=? ORDER BY id");
            $stM->execute([$top['id']]);
            $mods = $stM->fetchAll(PDO::FETCH_ASSOC);
            if(empty($mods)) $top_ref['is_topic_completed'] = false;
            
            foreach($mods as $mod){
                $ms = trim($mod['slug'], " \t\n\r\0\x0B-"); // Trim whitespace and trailing hyphens
                
                // In-depth Universal Slug Matcher to prevent progress stalling
                // Power Clean Matcher: removes all non-alphanumeric
                $c_s = preg_replace('/[^a-z0-9]/', '', strtolower($sl));
                $c_t = preg_replace('/[^a-z0-9]/', '', strtolower($ts));
                $c_m = preg_replace('/[^a-z0-9]/', '', strtolower($ms));
                
                $is_done = false;
                if(isset($all_progress[$c_s][$c_t][$c_m]) && $all_progress[$c_s][$c_t][$c_m] == 1) {
                    $is_done = true;
                }
                
                $top_ref['modules'][$ms]=['id'=>$mod['id'],'slug'=>$ms,'title'=>trim($mod['name']??($mod['title']??'Modul')), 'is_completed'=>$is_done];
                
                $sub_ref['total_mods']++;
                if($is_done) $sub_ref['completed_mods']++;
                else $top_ref['is_topic_completed'] = false;
            }
        }
        $subjects[$sl]['progress'] = ($subjects[$sl]['total_mods'] > 0) ? round(($subjects[$sl]['completed_mods'] / $subjects[$sl]['total_mods']) * 100) : 0;
    }
} catch(Exception $e){}

// Global progress calculation
$g_total_mods = 0; $g_done_mods = 0;
foreach($subjects as $s){ $g_total_mods += $s['total_mods']; $g_done_mods += $s['completed_mods']; }
// Calculate Real Streak
$streak_days = [];
$current_streak = 0;
try {
    $qS = $pdo->prepare("SELECT activity_date FROM activity_log WHERE user_id = ? ORDER BY activity_date DESC");
    $qS->execute([$_SESSION['user_id']]);
    $streak_days = $qS->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($streak_days)) {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if ($streak_days[0] === $today || $streak_days[0] === $yesterday) {
            $current_streak = 0;
            $check_date = $streak_days[0];
            foreach($streak_days as $date) {
                if ($date === $check_date) {
                    $current_streak++;
                    $check_date = date('Y-m-d', strtotime($check_date . ' -1 day'));
                } else {
                    break;
                }
            }
        }
    }
} catch(Exception $e){}

$total_progress = ($g_total_mods > 0) ? round(($g_done_mods / $g_total_mods) * 100) : 0;

$ini = strtoupper(substr($user['full_name'],0,1));
$fn  = explode(' ',$user['full_name'])[0];

// Build bg style
$bgStyle = '';
$isBatikBg = false;
// Overlay putih 60% membuat batik terlihat soft & tidak menyilaukan
$batikOverlay = "linear-gradient(rgba(255,255,255,0.58),rgba(255,255,255,0.58))";
if ($pref['bg_type']==='batik') {
    // Gunakan nilai dari DB (bisa berisi spasi), encode untuk CSS
    $batikVal = $pref['bg_value'] ?: $_BATIK_REL;
    // Encode hanya bagian nama file (bukan seluruh path)
    $batikParts = explode('/', $batikVal);
    $batikEncoded = implode('/', array_map('rawurlencode', $batikParts));
    $bgStyle = "background-image: {$batikOverlay}, url('../../{$batikEncoded}'); background-position: center; background-size: cover; background-attachment: fixed;";
    $isBatikBg = true;
} elseif ($pref['bg_type']==='image') {
    $imgParts = explode('/', $pref['bg_value']);
    $imgEncoded = implode('/', array_map('rawurlencode', $imgParts));
    $bgStyle = "background-image: {$batikOverlay}, url('../../{$imgEncoded}'); background-position: center; background-size: cover; background-attachment: fixed;";
    $isBatikBg = true;
} elseif ($pref['bg_type']==='gradient') {
    $bgStyle = "background:".$pref['bg_value'].";";
} else {
    $bgStyle = "background:".$pref['bg_value'].";";
}

// Fetch User Activities (Top 5 for dashboard, more for popup)
$user_activities = [];
try {
    $stmt = $pdo->prepare("
        (SELECT 'Menyelesaikan Kuis' as action, CAST(q.score AS CHAR) as details, q.created_at as time_ref, 'quiz' as type, m.title as mod_name
        FROM quiz_scores q
        JOIN modules m ON REPLACE(REPLACE(q.module_slug, '-', ''), ' ', '') = REPLACE(REPLACE(m.slug, '-', ''), ' ', '')
        WHERE q.user_id = ?)
        UNION ALL
        (SELECT 'Membaca Modul' as action, m.title as details, COALESCE(p.created_at, NOW()) as time_ref, 'progress' as type, m.title as mod_name
        FROM progress p
        JOIN modules m ON REPLACE(REPLACE(p.module_slug, '-', ''), ' ', '') = REPLACE(REPLACE(m.slug, '-', ''), ' ', '')
        WHERE p.user_id = ?)
        ORDER BY time_ref DESC LIMIT 50
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $user_activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Log error or handle silently
}
?>
<!DOCTYPE html>
<html lang="id" class="">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<style>
  html { scroll-behavior: smooth; }
  html.dark { background-color: #060b1d; }
  .zoom-120 { transform: scale(1); transform-origin: top left; } 
</style>
<title>Dashboard – StepUp</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={darkMode:'class'};</script>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,400;0,600;0,700;0,800;1,600&display=swap" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../assets/js/theme.js"></script>
<style>
:root {
  --glass-opacity: <?php echo ($pref['glass_opacity'] ?? 50) / 100; ?>;
}
*{font-family:'Plus Jakarta Sans',sans-serif;}
body{overflow-x:hidden;}
::-webkit-scrollbar{width:4px}::-webkit-scrollbar-thumb{background:#cbd5e1;border-radius:8px}
.dark ::-webkit-scrollbar-thumb{background:#334155}

/* Nav */
.navi{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:14px;font-weight:700;font-size:.875rem;color:#64748b;cursor:pointer;transition:all .2s;width:100%;text-align:left;}
.navi:hover{background:#f1f5f9;color:#1e293b;transform:translateX(3px);}
.navi.active{background:#2563eb;color:#fff;box-shadow:0 8px 20px -5px rgba(37,99,235,.4);}
.dark .navi{color:#94a3b8}.dark .navi:hover{background:#1e293b;color:#f1f5f9}.dark .navi.active{background:#2563eb;color:#fff}

/* Tabs */
.tp{display:none}.tp.on{display:block;animation:fu .3s ease}
@keyframes fu{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

/* Calendar */
.cd{aspect-ratio:1;display:flex;align-items:center;justify-content:center;border-radius:12px;font-weight:700;font-size:.85rem;color:#64748b;transition:all .15s;}
.cd:hover:not(.emp){background:rgba(37,99,235,.08);color:#2563eb;transform:scale(1.1)}
.cd.tod{background:#2563eb;color:#fff;box-shadow:0 8px 16px -4px rgba(37,99,235,.4);transform:scale(1.15)}
.dark .cd{color:#94a3b8}.dark .cd:hover:not(.emp){background:#1e293b;color:#60a5fa}

/* Motif / dot pattern */
.motif{position:relative;}
.motif::before{content:'';position:absolute;inset:0;background-image:radial-gradient(circle,rgba(255,255,255,.25) 1.5px,transparent 1.5px);background-size:14px 14px;pointer-events:none;border-radius:inherit;}
.motif2{background-image:radial-gradient(rgba(255,255,255,.18) 1.5px,transparent 1.5px);background-size:12px 12px;}

/* Accordion */
.acb{display:none}.acb.on{display:block}
.aci{transition:transform .25s}.aci.on{transform:rotate(180deg)}

/* Plain (Polos) Banners */
.batik-kawung, .batik-megamendung, .batik-parang, .batik-grid, .batik-dots { 
    background-image: none !important; 
}
.motif::before, .motif2 { display: none !important; }

/* Mobile sidebar */
#sidebar{transition:transform .35s cubic-bezier(.77,0,.175,1)}
@media(max-width:1023px){#sidebar{position:fixed;top:0;bottom:0;left:0;z-index:200;transform:translateX(-100%)}#sidebar.on{transform:translateX(0)}}
#sbk{display:none;position:fixed;inset:0;background:rgba(2,6,23,.5);backdrop-filter:blur(4px);z-index:199}
#sbk.on{display:block}

/* INP */
.inp{width:100%;padding:.8rem 1rem;border-radius:.875rem;border:2px solid #e2e8f0;background:#f8fafc;color:#0f172a;font-weight:600;font-size:.875rem;outline:none;transition:all .2s}
.inp:focus{border-color:#2563eb;background:#fff;box-shadow:0 0 0 4px rgba(37,99,235,.1)}
.dark .inp{background:#1e293b;border-color:#334155;color:#f1f5f9}
.dark .inp:focus{border-color:#3b82f6;background:#1e293b}

/* Modal */
.moverlay{position:fixed;inset:0;background:rgba(2,6,23,.55);backdrop-filter:blur(6px);z-index:400;display:none;align-items:center;justify-content:center;padding:1rem}
.moverlay.on{display:flex}

/* Todo */
.todo-item{display:flex;align-items:center;gap:10px;padding:10px 12px;border-radius:12px;background:#f8fafc;border:1px solid #f1f5f9;transition:all .2s}
.dark .todo-item{background:#1e293b;border-color:#334155}
.todo-item.done span{text-decoration:line-through;color:#94a3b8}

/* Pill */
.pill{display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:9999px;font-size:.65rem;font-weight:800;text-transform:uppercase;letter-spacing:.07em}

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

/* Batik background mode adjustments */
.batik-mode .frost {
    background: rgba(255, 255, 255, var(--glass-opacity)) !important;
    border-color: rgba(255, 255, 255, 0.5);
}
.dark.batik-mode .frost {
    background: rgba(10, 18, 35, var(--glass-opacity)) !important;
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
.glass-btn:hover { transform: translateY(-3px); background: #f1f5f9; }
.glass-btn.active {
    background: #2563eb;
    color: #fff;
    box-shadow: 0 8px 20px -5px rgba(37, 99, 235, 0.4);
}
.dark .glass-btn { background: rgba(30, 41, 59, 0.6); color: #94a3b8; }
.dark .glass-btn:hover { background: #334155; color: #f1f5f9; }
.dark .glass-btn.active { background: #3b82f6; color: #fff; }

/* Settings tabs */
.stab{padding:8px 16px;border-radius:10px;font-size:.8rem;font-weight:700;cursor:pointer;transition:all .2s;color:#64748b}
.stab.act{background:#2563eb;color:#fff}
.dark .stab{color:#94a3b8}.dark .stab.act{background:#2563eb;color:#fff}

/* BG swatches */
.swatch-sm{width:42px;height:42px;border-radius:14px;cursor:pointer;border:3px solid transparent;transition:all .3s ease;display:flex;align-items:center;justify-content:center;background-clip:padding-box;position:relative;box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);}
.swatch-sm:hover{transform:translateY(-2px);box-shadow:0 10px 15px -3px rgba(37,99,235,0.2);}
.swatch-sm.sel{border-color:#2563eb;transform:scale(1.1);border-width:4px;box-shadow: 0 0 0 4px rgba(37,99,235,0.15);}
.swatch-sm.color-white{border-color:#f1f5f9;}.dark .swatch-sm.color-white{border-color:#334155;}

/* Dark Mode Professional Polish */
.dark body { background-color: #060b1d !important; color: #f1f5f9; }
.dark .frost { background: rgba(15, 23, 42, 0.8) !important; border-color: rgba(255,255,255,0.08) !important; box-shadow: 0 10px 40px -10px rgba(0,0,0,0.6) !important; }
.dark .bg-white/80, .dark .bg-white/60 { background-color: rgba(30, 41, 59, 0.7) !important; }
.dark .text-slate-900 { color: #f8fafc !important; }
.dark .text-slate-600, .dark .text-slate-500 { color: #94a3b8 !important; }
.dark .border-slate-200, .dark .border-white/50 { border-color: rgba(255,255,255,0.05) !important; }
.dark .bg-slate-50 { background-color: #0f172a !important; }
.dark .cd:not(.tod):not(.has-event) { color: #475569; }
.dark .cd:hover:not(.tod) { background: rgba(59, 130, 246, 0.1) !important; color: #60a5fa; }
.dark .inp { background: #1e293b !important; border-color: #334155 !important; color: #f1f5f9 !important; }

.custom-scrollbar::-webkit-scrollbar { width: 5px; }
.custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
.dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #334155; }
.cd.has-event { position: relative; color: #b45309; font-weight: 800; z-index: 1; }
.swatch-batik-card { position: relative; width: 100%; height: 60px; border-radius: 18px; overflow: hidden; cursor: pointer; border: 3px solid transparent; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
.swatch-batik-card:hover { transform: translateY(-2px); border-color: rgba(37,99,235,0.3); box-shadow: 0 10px 15px -3px rgba(37,99,235,0.2); }
.swatch-batik-card.active { border-color: #2563eb; transform: scale(1.02); box-shadow: 0 12px 20px -8px rgba(37,99,235,0.4); }
.swatch-batik-card i { font-size: 14px; margin-left: 4px; }
.cd.has-event::before {
    content: '';
    position: absolute;
    inset: 4px;
    border: 2px solid #eab308;
    border-radius: 50%;
    background: rgba(234, 179, 8, 0.05);
    box-shadow: 0 0 10px rgba(234, 179, 8, 0.2);
    z-index: -1;
}
.dark .cd.has-event { color: #facc15; }
.dark .cd.has-event::before { border-color: #facc15; background: rgba(250, 204, 21, 0.1); }
</style>
</head>
<body class="flex bg-slate-50 dark:bg-[#0b1121] h-screen <?php echo $isBatikBg ? 'batik-mode' : ''; ?>" id="appBody" style="<?php echo $bgStyle ?>">

<div id="sbk" onclick="closeSB()"></div>

<!-- ═══ SIDEBAR ═══ -->
<aside id="sidebar" class="w-[260px] frost dark:bg-[#0d1526]/90 dark:border-slate-800 border-r border-white/60 flex flex-col h-screen lg:relative shrink-0">
    <div class="p-6 flex items-center justify-between shrink-0">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-500/25"><i class="fas fa-bolt text-lg"></i></div>
            <span class="font-black text-xl text-slate-900 dark:text-white tracking-tight">StepUp</span>
        </div>
        <button onclick="closeSB()" class="lg:hidden text-slate-400 hover:text-red-500 p-1"><i class="fas fa-times"></i></button>
    </div>
    <nav class="flex-1 overflow-y-auto px-3 pb-3 space-y-1">
        <p class="text-[9px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-widest px-3 mb-2">Utama</p>
        <button onclick="sw('home')" id="nav-home" class="navi active"><i class="fas fa-th-large w-5 tc"></i><span>Dashboard</span></button>
        <p class="text-[9px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-widest px-3 mb-2 mt-5">Materi</p>
        <?php foreach($subjects as $sl=>$s):?>
        <button onclick="sw('<?php echo $sl?>')" id="nav-<?php echo $sl?>" class="navi">
            <i class="fas <?php echo htmlspecialchars($s['icon'])?> w-5 tc"></i>
            <span><?php echo htmlspecialchars($s['title'])?></span>
        </button>
        <?php endforeach; ?>
    </nav>
    <div class="shrink-0 p-3 border-t border-slate-100 dark:border-slate-800/80">
        <div class="flex items-center gap-3 p-3 rounded-2xl bg-white/60 dark:bg-slate-800/50 border border-white/50 dark:border-slate-700/50 mb-2 overflow-hidden">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black shrink-0 shadow-lg shadow-blue-500/20 overflow-hidden relative" id="sidebarPPBox">
                <?php if (!empty($user['profile_pic']) && file_exists('../../' . $user['profile_pic'])): ?>
                    <img src="../../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="w-full h-full object-cover" id="sidebarPP">
                <?php else: ?>
                    <span id="sidebarThumb"><?php echo $ini ?></span>
                <?php endif; ?>
            </div>
            <div class="flex-1 min-w-0">
                <p class="font-black text-sm text-slate-900 dark:text-white truncate"><?php echo htmlspecialchars($user['full_name'])?></p>
                <p class="text-[9px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest"><?php echo htmlspecialchars(!empty($user['title']) ? $user['title'] : 'Siswa') ?></p>
            </div>
            <button onclick="toggleDark()" class="w-8 h-8 rounded-lg text-slate-400 hover:text-blue-500 dark:hover:text-yellow-400 transition-colors flex items-center justify-center shrink-0"><i class="fas fa-moon" id="mIcon"></i></button>
        </div>
        <button onclick="openSettings()" class="navi w-full mb-1"><i class="fas fa-cog w-5 tc"></i><span>Pengaturan</span></button>
        <button onclick="doLogout()" class="navi w-full text-red-500 hover:!bg-red-50 dark:hover:!bg-red-900/20"><i class="fas fa-sign-out-alt w-5 tc"></i><span>Sign Out</span></button>
    </div>
</aside>

<!-- ═══ MAIN ═══ -->
<main class="flex-1 flex flex-col overflow-hidden">
    <header class="lg:hidden shrink-0 flex items-center justify-between px-5 py-4 frost dark:bg-[#0d1526]/90 border-b border-white/50 dark:border-slate-800">
        <button onclick="openSB()" class="w-10 h-10 rounded-xl bg-white/60 dark:bg-slate-800 flex items-center justify-center text-slate-500"><i class="fas fa-bars"></i></button>
        <span class="font-black text-slate-900 dark:text-white">StepUp</span>
        <div class="flex items-center gap-2">
            <button onclick="openSettings()" class="w-10 h-10 rounded-xl bg-white/60 dark:bg-slate-800 flex items-center justify-center overflow-hidden border border-white/40 dark:border-slate-700 shadow-sm" id="mobilePPBox">
                <?php if (!empty($user['profile_pic']) && file_exists('../../' . $user['profile_pic'])): ?>
                    <img src="../../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="w-full h-full object-cover" id="mobilePP">
                <?php else: ?>
                    <span class="text-[10px] font-black text-blue-600"><?php echo $ini ?></span>
                <?php endif; ?>
            </button>
            <button onclick="openSettings()" class="w-10 h-10 rounded-xl bg-white/60 dark:bg-slate-800 flex items-center justify-center text-slate-500"><i class="fas fa-cog"></i></button>
        </div>
    </header>
    <div class="flex-1 overflow-y-auto p-6 md:p-12">

        <!-- HOME TAB -->
        <div id="tab-home" class="tp on">
            <div class="max-w-7xl mx-auto">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-2xl md:text-3xl font-black text-slate-900 dark:text-white tracking-tight">
                        Halo, <span class="text-blue-600"><?php echo htmlspecialchars($fn)?>!</span>
                    </h1>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-[15px] text-blue-500 font-bold" id="liveClock">00:00:00</p>
                        <span class="text-slate-300 dark:text-slate-700">•</span>
                        <p class="text-xs text-slate-400 font-semibold"><?php echo date('l, d F Y')?></p>
                    </div>
                </div>
                <div class="hidden lg:flex items-center gap-3">
                    <button onclick="toggleDark()" class="w-11 h-11 rounded-2xl bg-white/80 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-500 hover:text-blue-500 dark:text-slate-400 dark:hover:text-yellow-400 shadow-sm transition-all"><i class="fas fa-moon" id="mIcon2"></i></button>
                    
                    <button onclick="openSettings()" class="flex items-center gap-3 p-1.5 pr-4 rounded-2xl bg-white/80 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:border-blue-500 transition-all shadow-sm group">
                        <div class="w-8 h-8 rounded-xl bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black shrink-0 shadow-md overflow-hidden relative" id="navbarPPBox">
                            <?php if (!empty($user['profile_pic']) && file_exists('../../' . $user['profile_pic'])): ?>
                                <img src="../../<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile" class="w-full h-full object-cover" id="navbarPP">
                            <?php else: ?>
                                <span class="text-[10px]" id="navbarThumb"><?php echo $ini ?></span>
                            <?php endif; ?>
                        </div>
                        <span class="text-xs font-black text-slate-600 dark:text-slate-300 uppercase tracking-widest group-hover:text-blue-500"><?php echo explode(' ', $user['full_name'])[0] ?></span>
                    </button>
                    
                    <button onclick="openSettings()" class="w-11 h-11 rounded-2xl bg-white/80 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 flex items-center justify-center text-slate-500 hover:text-blue-500 shadow-sm transition-all"><i class="fas fa-cog"></i></button>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-12 gap-5">
                <!-- Calendar -->
                <div class="xl:col-span-7 frost cal-card rounded-3xl overflow-hidden border border-slate-100 dark:border-slate-800 shadow-sm flex flex-col">
                    <div class="p-6 border-b border-slate-50 dark:border-slate-800/50">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="font-black text-slate-900 dark:text-white text-lg tracking-tight">Kalender Akademik</h2>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">Jadwal & Agenda Pribadi</p>
                            </div>
                            <div class="flex items-center gap-2 bg-slate-50 dark:bg-slate-900/50 p-1.5 rounded-2xl border border-slate-100 dark:border-slate-800">
                                <button onclick="chM(-1)" class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 tc flex items-center justify-center text-slate-400 hover:text-blue-600 shadow-sm transition-all"><i class="fas fa-chevron-left"></i></button>
                                <span id="calLbl" class="px-2 font-black text-[10px] uppercase tracking-widest text-slate-700 dark:text-slate-200 min-w-[120px] text-center"></span>
                                <button onclick="chM(1)" class="w-8 h-8 rounded-xl bg-white dark:bg-slate-800 tc flex items-center justify-center text-slate-400 hover:text-blue-600 shadow-sm transition-all"><i class="fas fa-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 pb-2">
                        <div class="grid grid-cols-7 mb-4 text-center">
                            <?php foreach(['MG','SN','SL','RB','KM','JM','SB'] as $d):?>
                                <div class="text-[10px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest"><?php echo $d?></div>
                            <?php endforeach;?>
                        </div>
                        <div id="calG" class="grid grid-cols-7 gap-1.5"></div>
                    </div>

                    <div class="mt-auto p-5 bg-slate-50/50 dark:bg-slate-900/30 border-t border-slate-100 dark:border-slate-800/50">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-[10px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">Agenda Bulan Ini</h3>
                            <button onclick="addAgenda()" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-[9px] font-black uppercase tracking-widest transition-all shadow-lg shadow-blue-500/20 flex items-center gap-2">
                                <i class="fas fa-plus"></i> Tambah Acara
                            </button>
                        </div>
                        <div id="agendaList" class="space-y-2 max-h-[150px] overflow-y-auto custom-scrollbar pr-2">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>

                <!-- Right col -->
                <div class="xl:col-span-5 flex flex-col gap-4">
                    <div class="relative overflow-hidden rounded-[3rem] p-12 text-white shadow-2xl flex items-center min-h-[220px] group/quote" style="background:linear-gradient(135deg,#6366f1,#3b82f6)">
                        <div class="absolute top-0 right-0 w-80 h-80 bg-white/10 rounded-full -mr-40 -mt-40 blur-[100px] transition-transform duration-1000 group-hover/quote:scale-125"></div>
                        <div class="absolute bottom-0 left-0 w-60 h-60 bg-blue-400/10 rounded-full -ml-30 -mb-30 blur-[80px]"></div>
                        
                        <div class="relative z-10 w-full">
                            <i class="fas fa-quote-left text-4xl opacity-30 mb-6 block group-hover/quote:-translate-y-1 transition-transform"></i>
                            <p class="text-2xl md:text-3xl font-black italic mb-6 tracking-tight leading-[1.15] drop-shadow-xl text-white/95">"<?php echo htmlspecialchars($quote)?>"</p>
                            <div class="flex items-center gap-3">
                                <span class="w-12 h-[3px] bg-white/40 rounded-full"></span>
                                <p class="text-[11px] font-black uppercase tracking-[0.4em] opacity-80">Boost Energi Hari Ini</p>
                            </div>
                        </div>
                        
                        <!-- Premium Toga Icon Decoration -->
                        <div class="absolute bottom-[-20px] right-[-20px] lg:right-4 lg:bottom-4 group-hover/quote:rotate-[25deg] transition-all duration-700 pointer-events-none">
                            <i class="fas fa-graduation-cap text-[180px] lg:text-[220px] text-white/10 rotate-[15deg]"></i>
                        </div>
                    </div>

                    <!-- Stats Row -->
                    <div class="grid grid-cols-2 gap-4">
                        <!-- Streak Card -->
                        <div onclick="showStreakDetails()" class="frost dark:bg-[#0d1526]/80 rounded-[2.5rem] p-6 border border-white/50 dark:border-slate-800 shadow-sm transition-all hover:shadow-xl hover:-translate-y-1 active:scale-95 cursor-pointer group/streak relative overflow-hidden">
                            <div class="absolute -right-4 -top-4 w-24 h-24 bg-orange-500/5 rounded-full blur-2xl group-hover/streak:bg-orange-500/10 transition-colors"></div>
                            <div class="w-12 h-12 rounded-2xl bg-orange-100 dark:bg-orange-900/30 flex items-center justify-center mb-4 text-orange-500 text-xl group-hover/streak:scale-110 transition shadow-inner">
                                <i class="fas fa-fire"></i>
                            </div>
                            <p class="text-4xl font-black text-slate-900 dark:text-white mb-1"><?php echo $current_streak ?></p>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">HARI STREAK</p>
                        </div>
                        
                        <!-- Progress Card -->
                        <div onclick="showProgressDetails()" class="frost dark:bg-[#0d1526]/80 rounded-[2.5rem] p-6 border border-white/50 dark:border-slate-800 shadow-sm transition-all hover:shadow-xl hover:-translate-y-1 active:scale-95 cursor-pointer group/prog relative overflow-hidden">
                            <div class="absolute -right-4 -top-4 w-24 h-24 bg-blue-500/5 rounded-full blur-2xl group-hover/prog:bg-blue-500/10 transition-colors"></div>
                            <div class="w-12 h-12 rounded-2xl bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center mb-4 text-blue-600 text-xl group-hover/prog:scale-110 transition shadow-inner">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <p class="text-4xl font-black text-slate-900 dark:text-white mb-1"><?php echo $total_progress ?>%</p>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">TOTAL PENGUASAAN</p>
                        </div>
                    </div>

                    <!-- TODO -->
                    <div class="flex-1 frost dark:bg-[#0d1526]/80 rounded-2xl p-5 border border-white/50 dark:border-slate-800 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="font-black text-slate-900 dark:text-white">Tugas Hari Ini</h3>
                            <span class="pill bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300" id="todoCnt">0 tersisa</span>
                        </div>
                        <!-- Add form -->
                        <form onsubmit="addTodo(event)" class="flex gap-2 mb-3">
                            <input id="todoInput" type="text" placeholder="Tambah tugas baru..." class="inp flex-1 !py-2.5 text-sm">
                            <button type="submit" class="w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-xl flex items-center justify-center shadow-md shadow-blue-500/20 shrink-0 transition"><i class="fas fa-plus text-xs"></i></button>
                        </form>
                        <div id="todoList" class="space-y-2 max-h-52 overflow-y-auto"></div>
                    </div>
                </div> <!-- End col-5 (331) -->
            </div> <!-- End Grid (313) -->

            <!-- Global Activities Section - dibungkus card putih agar tidak samar -->
            <div class="mt-10 mb-10 w-full">
                <div class="frost rounded-[3rem] border border-white/70 dark:border-slate-800 shadow-md p-8">
                    <div class="mb-6 flex flex-col md:flex-row md:items-end justify-between gap-4">
                        <div class="flex-1">
                            <h3 class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter leading-none mb-3">Aktivitas Terkini</h3>
                            <p class="text-sm font-semibold text-slate-400">Log belajar dan pencapaian terbarumu.</p>
                        </div>
                        <button onclick="openActivityHistory()" class="px-6 py-2.5 bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-2xl text-[10px] font-black uppercase tracking-widest border border-blue-100 dark:border-blue-900/10 hover:bg-blue-600 hover:text-white transition-all shadow-sm shrink-0">
                            Lihat Semua
                        </button>
                    </div>

                    <div class="w-full">
                        <?php 
                        $top_activities = array_slice($user_activities, 0, 5);
                        if (empty($top_activities)): ?>
                            <div class="w-full p-12 bg-white/60 dark:bg-slate-800/30 rounded-[3rem] border-2 border-dashed border-slate-200 dark:border-slate-800 flex flex-col md:flex-row items-center justify-center gap-8 text-center md:text-left transition-all hover:border-blue-300">
                                <div class="w-24 h-24 bg-white dark:bg-slate-800 rounded-[2rem] flex items-center justify-center shadow-xl text-slate-200 dark:text-slate-700">
                                    <i class="fas fa-history text-5xl"></i>
                                </div>
                                <div class="max-w-md">
                                    <h3 class="font-black text-slate-800 dark:text-white text-xl tracking-tight mb-2">Belum Ada Jejak Belajar</h3>
                                    <p class="text-[13px] font-bold text-slate-400 leading-relaxed uppercase tracking-wider">Materi sedang menunggu untuk dipelajari. Selesaikan kuis atau baca modul untuk melihat progresmu di sini!</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            <?php foreach ($top_activities as $act): 
                                $isQuiz = $act['type'] === 'quiz';
                                $icon = $isQuiz ? 'fa-certificate' : 'fa-book-reader';
                                $color = $isQuiz ? 'amber' : 'blue';
                            ?>
                            <div class="bg-white/90 dark:bg-slate-800/60 backdrop-blur-md p-6 rounded-[2.5rem] shadow-md border border-white/80 dark:border-slate-700 flex items-center justify-between group hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                                <div class="flex items-center space-x-6">
                                    <div class="w-14 h-14 rounded-2xl bg-<?php echo $color ?>-50 dark:bg-<?php echo $color ?>-900/20 text-<?php echo $color ?>-600 dark:text-<?php echo $color ?>-400 flex items-center justify-center text-xl shadow-inner group-hover:scale-110 duration-300 transition-transform">
                                        <i class="fas <?php echo $icon ?>"></i>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-[0.2em] mb-1"><?php echo $act['action'] ?></p>
                                        <h4 class="font-black text-slate-800 dark:text-white tracking-tight leading-none text-sm truncate max-w-[120px]"><?php echo htmlspecialchars($act['mod_name']) ?></h4>
                                        <?php if($isQuiz): ?>
                                            <span class="inline-block mt-2 px-2 py-0.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[9px] font-black rounded-lg border border-amber-100 dark:border-amber-800">SKOR: <?php echo $act['details'] ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="text-right">
                                     <p class="text-[9px] font-black text-slate-400 dark:text-slate-600 uppercase tracking-widest mb-1"><?php echo date('d M', strtotime($act['time_ref'])) ?></p>
                                     <p class="text-lg font-black text-slate-800 dark:text-white tabular-nums tracking-tighter"><?php echo date('H:i', strtotime($act['time_ref'])) ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div> <!-- End .max-w-7xl -->
        </div> <!-- End #tab-home -->

        <!-- SUBJECT TABS -->
        <?php foreach($subjects as $sl=>$s): ?>
        <div id="tab-<?php echo $sl ?>" class="tp pb-20">
            <div class="max-w-7xl mx-auto py-10">
            <!-- Banner with Polos vibrant style (Balanced Size) -->
            <div class="relative overflow-hidden rounded-[3rem] p-10 md:p-14 text-white mb-10 shadow-2xl" style="background: linear-gradient(135deg, <?php echo $s['color'] ?> 0%, <?php echo $s['color'] ?>cc 100%);">
                <div class="relative z-10 flex flex-col md:flex-row items-center md:justify-between gap-8">
                    <div class="text-center md:text-left">
                        <div class="pill bg-white/30 border border-white/40 text-white mb-4 backdrop-blur-md px-4 py-1 text-xs">Kurikulum 2026</div>
                        <h2 class="text-4xl md:text-6xl font-black tracking-tighter uppercase mb-4 leading-none drop-shadow-xl"><?php echo htmlspecialchars($s['title'])?></h2>
                        <p class="text-sm md:text-base font-bold opacity-90 tracking-wide italic max-w-xl leading-relaxed"><?php echo htmlspecialchars($s['desc'] ?: 'Pelajari '.$s['title'].' dengan materi eksklusif dan interaktif di platform StepUp Learning.')?></p>
                    </div>
                    <div class="bg-white/10 dark:bg-black/20 backdrop-blur-3xl text-white rounded-2xl p-8 text-center shadow-lg min-w-[180px] group/score relative border border-white/25 overflow-hidden transition-transform">
                        <div class="relative z-10 flex flex-col items-center">
                            <div class="flex items-end gap-1">
                                <span class="text-5xl font-black leading-none"><?php echo $s['progress'] ?></span>
                                <span class="text-xl font-black opacity-60 mb-1">%</span>
                            </div>
                            <div class="text-[10px] font-black uppercase tracking-[0.2em] opacity-80 mt-2">PENGUASAAN</div>
                        </div>
                    </div>
                </div>
                <!-- Toga Icon Decoration -->
                <i class="fas fa-graduation-cap absolute bottom-[-15px] right-12 text-[140px] opacity-10 rotate-[20deg] pointer-events-none"></i>
            </div>

            <!-- 2-column: topics left, mini panel right -->
            <div class="flex gap-5">
              <!-- Topics -->
              <div class="flex-1 min-w-0">
                <?php if(empty($s['topics'])):?>
                <div class="text-center p-20 frost rounded-[2.5rem] border-2 border-dashed border-slate-200/50"><p class="font-black text-slate-400 uppercase text-sm tracking-widest">Materi sedang diracik...</p></div>
                <?php else:?>
                <div class="space-y-4">
                    <?php foreach($s['topics'] as $ts=>$top):
                        $is_topic_done = $top['is_topic_completed'];
                    ?>
                    <div class="group">
                        <button onclick="tgAcc('<?php echo $sl.'-'.$ts?>')" class="w-full flex items-center justify-between p-7 frost rounded-[2.5rem] hover:bg-white transition-all shadow-sm group-hover:shadow-md border border-white/60 dark:border-slate-800">
                            <div class="flex items-center gap-6">
                                <div class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-900/40 text-blue-600 flex items-center justify-center text-xl shadow-inner group-hover:scale-110 transition shrink-0"><i class="fas fa-folder<?php echo $is_topic_done?'-open':''?>"></i></div>
                                <div class="text-left">
                                    <h4 class="font-black text-slate-900 dark:text-white text-xl tracking-tighter leading-none"><?php echo htmlspecialchars($top['title'])?></h4>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2"><?php echo count($top['modules'])?> MODUL PEMBELAJARAN</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <?php if($is_topic_done): ?>
                                    <a href="certificate.php?topic=<?php echo urlencode($ts)?>" class="pill bg-emerald-500 text-white border-none px-4 py-2 scale-110 hover:bg-emerald-600 transition shadow-lg shadow-emerald-500/30 flex items-center gap-2">
                                        <i class="fas fa-certificate"></i> CETAK SERTIFIKAT
                                    </a>
                                <?php endif; ?>
                                <i id="chv-<?php echo $sl.'-'.$ts?>" class="fas fa-chevron-down text-slate-300 aci transition-transform duration-300"></i>
                            </div>
                        </button>
                        <div id="acc-<?php echo $sl.'-'.$ts?>" class="acb mt-5 pl-4 border-l-4 border-blue-500/10 ml-7">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 py-2">
                                <?php foreach($top['modules'] as $ms=>$mod):
                                    $is_done = $mod['is_completed'];
                                ?>
                                <div class="p-8 rounded-[2.5rem] border-2 transition-all hover:shadow-2xl hover:-translate-y-1 relative group/mod flex flex-col h-full <?php echo $is_done ? 'bg-emerald-50/50 border-emerald-100 dark:bg-emerald-900/10 dark:border-emerald-900/30' : 'bg-white/60 dark:bg-slate-800/40 border-slate-100 dark:border-slate-800' ?>">
                                    <div class="pill <?php echo $is_done ? 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-600' : 'bg-blue-100 dark:bg-blue-900/40 text-blue-600' ?> mb-6 px-4 py-1.5 text-[10px] w-fit">MODUL</div>
                                    <h5 class="font-black text-xl text-slate-800 dark:text-white mb-10 leading-snug tracking-tighter"><?php echo htmlspecialchars($mod['title'])?></h5>
                                    
                                    <div class="mt-auto">
                                        <a href="module.php?subject=<?php echo urlencode($sl)?>&topic=<?php echo urlencode($ts)?>&module=<?php echo urlencode($ms)?>" class="w-full py-5 <?php echo $is_done ? 'bg-emerald-500 hover:bg-emerald-600 shadow-emerald-500/20' : 'bg-blue-600 hover:bg-blue-700 shadow-blue-500/20' ?> text-white rounded-2xl font-black text-center text-[11px] uppercase tracking-[.15em] transition flex items-center justify-center gap-3 shadow-lg active:scale-95">
                                            <?php echo $is_done ? '<i class="fas fa-check-circle text-lg"></i> SUDAH DIKERJAKAN' : 'MULAI BELAJAR <i class="fas fa-arrow-right"></i>' ?>
                                        </a>
                                    </div>

                                    <?php if($is_done): ?>
                                    <div class="absolute -top-3 -right-3 w-12 h-12 bg-emerald-500 text-white rounded-full flex items-center justify-center shadow-lg border-4 border-white dark:border-slate-900 animate-bounce"><i class="fas fa-check"></i></div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach;?>
                            </div>
                            
                            <?php if($is_topic_done): ?>
                            <div class="mt-8 p-8 rounded-[2.5rem] bg-gradient-to-r from-yellow-400 to-amber-500 text-white flex flex-col sm:flex-row items-center justify-between gap-6 shadow-2xl shadow-yellow-500/30 border-2 border-white/20">
                                <div class="flex items-center gap-6">
                                    <div class="w-20 h-20 bg-white/20 rounded-[1.5rem] flex items-center justify-center text-4xl backdrop-blur-md shadow-inner"><i class="fas fa-trophy"></i></div>
                                    <div class="text-center sm:text-left">
                                        <p class="font-black text-2xl uppercase tracking-tighter">Topik Dikuasai!</p>
                                        <p class="text-xs font-bold opacity-90 uppercase tracking-widest mt-1">Sertifikat digital topik <?php echo $top['title'] ?> telah terbit.</p>
                                    </div>
                                </div>
                                <a href="certificate.php?topic=<?php echo urlencode($ts) ?>&subject=<?php echo urlencode($sl) ?>&type=topic" class="w-full sm:w-auto px-10 py-4 bg-white text-amber-600 rounded-2xl font-black text-xs uppercase tracking-widest hover:scale-110 transition shadow-xl active:scale-95 shrink-0 flex items-center justify-center gap-3">
                                    <i class="fas fa-medal"></i> CEK SERTIFIKAT
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach;?>
                </div>
                <?php endif;?>
              </div>

              <!-- Right mini panel (Enlarged) -->
              <div class="w-80 shrink-0 hidden xl:flex flex-col gap-4">
                <!-- Mini Calendar — sinkron dengan data agenda utama -->
                <div class="frost rounded-3xl p-6 border border-white/60 dark:border-slate-700 shadow-sm transition-all hover:shadow-xl hover:shadow-blue-500/5">
                    <div class="flex items-center justify-between mb-4">
                        <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Kalender</span>
                        <span class="text-xs font-extrabold text-blue-600" id="miniCalLbl-<?php echo $sl?>"></span>
                    </div>
                    <div class="grid grid-cols-7 text-center mb-2"><?php foreach(['M','S','S','R','K','J','S'] as $d):?><div class="text-[10px] font-black text-slate-300 uppercase"><?php echo $d?></div><?php endforeach;?></div>
                    <div id="miniCalG-<?php echo $sl?>" class="grid grid-cols-7 gap-1"></div>
                    
                    <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800/50">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-[9px] font-black text-slate-500 dark:text-slate-400 uppercase tracking-[0.2em]">Agenda Bulan Ini</h3>
                            <button onclick="addAgenda()" class="px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-[8px] font-black uppercase tracking-widest transition-all shadow-md shadow-blue-500/20 flex items-center gap-1">
                                <i class="fas fa-plus"></i> Tambah
                            </button>
                        </div>
                        <div id="miniAgendaList-<?php echo $sl?>" class="space-y-2 max-h-[120px] overflow-y-auto custom-scrollbar pr-1">
                            <!-- Populated by JS -->
                        </div>
                    </div>
                </div>
                <!-- Mini Quote -->
                <div class="relative overflow-hidden rounded-[3rem] p-8 text-white shadow-2xl group/quote transition-all hover:scale-[1.02]" style="background:linear-gradient(135deg,<?php echo $s['color']?>,<?php echo $s['color']?>cc)">
                    <div class="relative z-10">
                        <i class="fas fa-quote-left mb-4 text-white/40 text-2xl block group-hover/quote:-translate-y-1 transition-transform"></i>
                        <p class="italic text-lg text-white leading-relaxed font-bold tracking-tight">&ldquo;<?php echo htmlspecialchars(substr($quote,0,120))?>&rdquo;</p>
                    </div>
                    <!-- Toga Icon Decoration -->
                    <i class="fas fa-graduation-cap absolute bottom-[-15px] right-2 text-[110px] opacity-10 rotate-[20deg] pointer-events-none"></i>
                </div>
                <!-- Mini Todo -->
                <div class="frost dark:bg-[#0d1526]/80 rounded-[3rem] p-8 border border-white/50 dark:border-slate-800 shadow-sm flex-1 transition-all hover:shadow-xl hover:shadow-blue-500/5">
                    <div class="flex items-center justify-between mb-6">
                        <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Tugas Berjalan</p>
                        <button onclick="addQuickTodo()" class="w-10 h-10 bg-blue-600 text-white rounded-2xl flex items-center justify-center text-xs shadow-xl shadow-blue-500/30 hover:scale-110 active:scale-90 transition"><i class="fas fa-plus"></i></button>
                    </div>
                    <div id="miniTodo-<?php echo $s['id'] ?>" class="mini-todo-list space-y-3.5 max-h-[400px] overflow-y-auto pr-1 custom-scrollbar"></div>
                </div>
            </div>
        </div> <!-- End .flex gap-5 -->
        </div> <!-- End .max-w-7xl -->
        </div> <!-- End tab-id -->
        <?php endforeach;?>
    </div> <!-- End single flex-1 overflow-y-auto -->
</main>


<!-- ═══ SETTINGS MODAL ═══ -->
<div id="sModal" class="moverlay" onclick="if(event.target===this)closeSettings()">
  <div class="relative w-full max-w-lg bg-white/80 dark:bg-[#0a1128]/90 rounded-[3.5rem] shadow-2xl overflow-hidden border border-white/20 dark:border-blue-900/20 backdrop-blur-2xl" style="animation:swal-modern-in .6s cubic-bezier(0.175, 0.885, 0.32, 1.275) both">
    <!-- Header -->
    <div class="p-10 pb-6">
      <button onclick="closeSettings()" class="absolute top-8 right-8 w-10 h-10 bg-slate-100 hover:bg-red-500 hover:text-white dark:bg-slate-800 dark:hover:bg-red-500 rounded-2xl flex items-center justify-center text-slate-400 transition-all shadow-sm"><i class="fas fa-times"></i></button>
      <div class="flex items-center gap-6 mb-8">
        <div class="w-20 h-20 rounded-[2rem] bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-black text-3xl shadow-xl shadow-blue-500/30"><?php echo $ini?></div>
        <div>
          <h3 class="text-2xl font-black text-slate-900 dark:text-white tracking-tight"><?php echo htmlspecialchars($user['full_name']) ?></h3>
          <p class="text-blue-500 text-[10px] font-black uppercase tracking-[0.2em]"><?php echo htmlspecialchars(!empty($user['title']) ? $user['title'] : 'Siswa') ?></p>
        </div>
      </div>
      <!-- Setting Tabs -->
      <div class="flex gap-2 bg-slate-100/50 dark:bg-slate-900/50 p-1.5 rounded-[2rem] border border-slate-100 dark:border-slate-800/50">
        <button onclick="stab('profile')" id="stab-profile" class="stab act flex-1 py-3.5 rounded-[1.5rem]">Profil</button>
        <button onclick="stab('security')" id="stab-security" class="stab flex-1 py-3.5 rounded-[1.5rem]">Keamanan</button>
        <button onclick="stab('appearance')" id="stab-appearance" class="stab flex-1 py-3.5 rounded-[1.5rem]">Tampilan</button>
      </div>
    </div>

    <!-- Body -->
    <div class="p-10 pt-4 overflow-y-auto max-h-[60vh] custom-scrollbar">
      <!-- Profile Tab -->
      <div id="s-profile" class="stpane">
        <div class="space-y-6">
          <!-- Profile Pic Upload -->
          <div class="flex flex-col items-center py-4 bg-slate-50 dark:bg-slate-800/40 rounded-[2rem] border border-slate-100 dark:border-slate-800">
            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white text-3xl font-black shadow-xl mb-4 relative group overflow-hidden" id="ppPreviewBox">
                <?php if (!empty($user['profile_pic']) && file_exists('../../' . $user['profile_pic'])): ?>
                    <img src="../../<?php echo htmlspecialchars($user['profile_pic']); ?>" id="ppPreview" alt="Profile" class="w-full h-full object-cover">
                <?php else: ?>
                    <span id="ppThumb"><?php echo $ini ?></span>
                    <img id="ppPreview" src="" class="w-full h-full object-cover hidden">
                <?php endif; ?>
                <label class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer">
                    <i class="fas fa-camera text-white"></i>
                    <input type="file" id="ppInp" accept="image/*" class="hidden" onchange="previewPP(this)">
                </label>
            </div>
            <button onclick="document.getElementById('ppInp').click()" class="text-[10px] font-black text-blue-600 dark:text-blue-400 uppercase tracking-[0.2em] hover:text-blue-700 transition">Pilih Foto Profil</button>
          </div>

          <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-2">Nama Lengkap</label>
            <input id="s_name" type="text" class="inp" value="<?php echo htmlspecialchars($user['full_name'])?>"></div>
          <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-2">Gelar / Julukan (Opsional)</label>
            <input id="s_title" type="text" class="inp" placeholder="Contoh: Sang Juara, Master Matematika..." value="<?php echo htmlspecialchars($user['title'] ?? '') ?>"></div>
          <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5 pl-2">Email</label>
            <input type="text" class="inp bg-slate-50 dark:bg-slate-800/60 opacity-60" value="<?php echo htmlspecialchars($user['email']??'') ?>" disabled></div>
          
          <button onclick="saveProfileWithPic()" id="saveProfileBtn" class="w-full py-4 bg-blue-600 hover:bg-blue-700 text-white rounded-2xl font-black uppercase tracking-widest text-xs shadow-xl shadow-blue-500/20 tc hover:scale-[1.01] active:scale-95 transition-all">Simpan Perubahan</button>
        </div>
      </div>

      <!-- Security Tab -->
      <div id="s-security" class="stpane hidden">
        <div class="space-y-4">
          <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Password Baru</label>
            <div class="relative"><input id="s_pw" type="password" class="inp pr-12" placeholder="Minimal 6 karakter...">
              <button type="button" onclick="tgPw('s_pw','sEye')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-500 tc"><i class="fas fa-eye" id="sEye"></i></button>
            </div>
          </div>
          <div><label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Konfirmasi Password</label>
            <div class="relative"><input id="s_pw2" type="password" class="inp pr-12" placeholder="Ulangi password...">
              <button type="button" onclick="tgPw('s_pw2','sEye2')" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-blue-500 tc"><i class="fas fa-eye" id="sEye2"></i></button>
            </div>
          </div>
          <div class="p-4 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800/50 text-xs text-amber-700 dark:text-amber-400 font-semibold"><i class="fas fa-shield-alt mr-2"></i>Pastikan password kamu kuat dan unik!</div>
          <button onclick="savePassword()" class="w-full py-3.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black uppercase tracking-widest text-sm shadow-xl shadow-blue-500/20 tc hover:scale-[1.01] active:scale-95">Update Password</button>
        </div>
      </div>

      <!-- Appearance Tab -->
      <div id="s-appearance" class="stpane hidden">
        <div class="space-y-6">
          <!-- Compact Dark Mode Toggle -->
          <div class="flex items-center justify-between p-5 bg-slate-50 dark:bg-slate-800/40 rounded-3xl border border-slate-100 dark:border-slate-800/50">
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 bg-white dark:bg-slate-700 rounded-2xl flex items-center justify-center shadow-sm text-slate-500 dark:text-yellow-400">
                <i class="fas fa-moon"></i>
              </div>
              <div>
                <p class="font-black text-slate-900 dark:text-white text-sm">Mode Gelap</p>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Sesuaikan kenyamanan mata</p>
              </div>
            </div>
            <button onclick="toggleDark()" class="relative w-11 h-6 rounded-full bg-slate-200 dark:bg-blue-600 transition-colors">
              <div class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full shadow-sm transition-transform duration-300 dark:translate-x-5"></div>
            </button>
          </div>

          <!-- Color Presets -->
          <div>
            <div class="flex items-center justify-between mb-4 px-2">
              <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Warna Suasana</label>
              <button onclick="resetBg()" class="text-[10px] font-black text-blue-500 hover:text-blue-600 uppercase tracking-widest">Reset Default</button>
            </div>
            
            <!-- Batik Options -->
            <div class="space-y-3 pt-1 mb-4">
              <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Motif Batik Pilihan</p>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <!-- Megamendung -->
                  <button onclick="setBg('batik','assets/img/mega.jpg')" 
                          class="swatch-batik-card <?php echo $pref['bg_value']==='assets/img/mega.jpg'?'active':'' ?>"
                          style="background-image: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('../../assets/img/mega.jpg'); background-size: cover; background-position: center;">
                      <span class="text-white text-[10px] font-black uppercase tracking-widest">Megamendung</span>
                      <?php if($pref['bg_value']==='assets/img/mega.jpg'): ?><i class="fas fa-check-circle text-white"></i><?php endif; ?>
                  </button>
              </div>
            </div>

          <!-- Glass Transparency Setting -->
          <div class="mt-6 mb-6">
              <div class="flex items-center justify-between mb-4 px-2">
                  <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Transparansi Kaca</label>
                  <span class="text-[10px] font-bold text-blue-500" id="opacityValLabel"><?php echo $pref['glass_opacity'] ?>%</span>
              </div>
              <div class="bg-slate-50 dark:bg-slate-800/50 p-4 rounded-3xl border border-slate-100 dark:border-slate-800">
                  <div class="grid grid-cols-5 gap-2" id="opacityControls">
                      <?php foreach([5, 25, 50, 75, 100] as $ov): ?>
                          <button onclick="setGlassOpacity(<?php echo $ov ?>)" 
                                  id="gop-<?php echo $ov ?>"
                                  class="py-3 rounded-2xl text-[10px] font-black transition-all flex items-center justify-center border-2 <?php echo $pref['glass_opacity'] == $ov ? 'bg-blue-600 text-white border-blue-600 shadow-lg shadow-blue-500/20' : 'bg-white dark:bg-slate-800 text-slate-400 border-transparent hover:border-slate-200 dark:hover:border-slate-700' ?>">
                              <?php echo $ov ?>%
                          </button>
                      <?php endforeach; ?>
                  </div>
              </div>
          </div>

          <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-2 px-1">Warna Polos</p>
          <div class="grid grid-cols-6 gap-3 pt-1 mb-6" id="swatches">
            <?php
            $bgs = [
              ['#f2f5ff','Soft Blue'], ['#ffffff','Pure White'], ['#eef6f2','Soft Green'],
              ['#fffdf5','Cream'], ['#fdf7f9','Soft Pink'], ['#f2f9ff','Baby Blue'],
            ];
            foreach($bgs as $bg):
              $bgVal = $bg[0]; $bgTitle = $bg[1];
              $isSel = ($pref['bg_type'] === 'color' && $pref['bg_value'] === $bgVal);
            ?>
              <button class="w-10 h-10 rounded-xl border-4 transition-all <?php echo $isSel?'border-blue-500 scale-110 shadow-lg shadow-blue-500/20':'border-transparent' ?>"
                      style="background:<?php echo $bgVal; ?>"
                      onclick="setBg('color','<?php echo $bgVal; ?>')"
                      title="<?php echo $bgTitle; ?>">
              </button>
            <?php endforeach; ?>
          </div>

          <!-- Custom Image Upload -->
          <div class="pt-2">
            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 px-1">Gambar Background</label>
            <label class="group block relative p-6 bg-slate-50 dark:bg-slate-800/40 rounded-2xl border-2 border-dashed border-slate-100 dark:border-slate-800 hover:border-blue-400 dark:hover:border-blue-500 transition-all cursor-pointer overflow-hidden">
                <div class="flex items-center gap-4 relative z-10">
                    <div class="w-12 h-12 bg-white dark:bg-slate-700 rounded-xl flex items-center justify-center text-slate-400 group-hover:text-blue-500 transition-colors shadow-sm">
                        <i class="fas fa-cloud-upload-alt text-xl"></i>
                    </div>
                    <div>
                        <p class="font-black text-slate-800 dark:text-white text-sm">Upload Foto Kostum</p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">JPG, PNG, WEBP (Maks 5MB)</p>
                    </div>
                </div>
                <input type="file" id="bgFile" accept="image/*" class="hidden" onchange="uploadBg(this)">
            </label>
          </div>

          <div class="grid grid-cols-1 gap-3 mt-4">
              <button onclick="removeBg()" class="py-4 bg-slate-100 dark:bg-slate-800 text-slate-500 hover:text-red-500 rounded-3xl font-black uppercase tracking-widest text-[11px] transition-all flex items-center justify-center gap-2">
                  <i class="fas fa-trash-alt"></i> Kembalikan ke Default
              </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ═══ ACTIVITY HISTORY MODAL ═══ -->
<div id="activityModal" class="moverlay" onclick="if(event.target===this)closeActivityHistory()">
  <div class="relative w-full max-w-2xl bg-white dark:bg-[#0d1526] rounded-[3rem] shadow-2xl overflow-hidden border border-slate-100 dark:border-slate-800" style="animation:fu .3s ease both">
    <div class="p-8 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-800 dark:text-white tracking-tighter">Riwayat Aktivitas</h2>
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-1">Seluruh log belajar kamu</p>
        </div>
        <button onclick="closeActivityHistory()" class="w-10 h-10 bg-slate-100 dark:bg-slate-800 rounded-2xl flex items-center justify-center text-slate-500 hover:text-red-500 transition-colors"><i class="fas fa-times"></i></button>
    </div>
    <div class="p-8 max-h-[70vh] overflow-y-auto custom-scrollbar space-y-4 bg-slate-50/50 dark:bg-slate-900/50">
        <?php if(!empty($user_activities)): ?>
            <?php foreach ($user_activities as $act): 
                $isQuiz = $act['type'] === 'quiz';
                $icon = $isQuiz ? 'fa-certificate' : 'fa-book-reader';
                $color = $isQuiz ? 'amber' : 'blue';
            ?>
            <div class="flex items-center justify-between p-6 bg-white dark:bg-slate-800/40 rounded-3xl border border-slate-100 dark:border-slate-800 shadow-sm">
                <div class="flex items-center space-x-5">
                    <div class="w-12 h-12 rounded-xl bg-<?php echo $color ?>-100 dark:bg-<?php echo $color ?>-900/30 text-<?php echo $color ?>-600 dark:text-<?php echo $color ?>-400 flex items-center justify-center text-xl">
                        <i class="fas <?php echo $icon ?>"></i>
                    </div>
                    <div>
                        <h4 class="font-black text-slate-800 dark:text-white text-sm leading-none mb-1.5"><?php echo htmlspecialchars($act['mod_name']) ?></h4>
                        <div class="flex items-center gap-2">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest"><?php echo $act['action'] ?></p>
                            <?php if($isQuiz): ?>
                                <span class="px-1.5 py-0.5 bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 text-[8px] font-black rounded-md border border-amber-100 dark:border-amber-800"><?php echo $act['details'] ?>%</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-[9px] font-black text-slate-400 dark:text-slate-500 uppercase tracking-widest mb-1"><?php echo date('d M Y', strtotime($act['time_ref'])) ?></p>
                    <p class="text-lg font-black text-slate-800 dark:text-white tabular-nums tracking-tighter"><?php echo date('H:i', strtotime($act['time_ref'])) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center py-20 opacity-40">
                <i class="fas fa-history text-5xl mb-4 block"></i>
                <p class="font-black text-xs uppercase tracking-[0.2em]">Belum ada aktivitas tercatat.</p>
            </div>
        <?php endif; ?>
    </div>
  </div>
</div>

<script src="../../assets/js/theme.js"></script>
<script>
console.log('StepUp Dashboard: Script starting...');
const TODO_API = '../../src/api/todo.php';
const PREF_API = 'set_preference.php';

/* ── Fallback: fungsi dari theme.js (jika belum ter-upload ke server) ─── */
if (typeof parseMagicJSON === 'undefined') {
    window.parseMagicJSON = function(text) {
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
if (typeof showModernAlert === 'undefined') {
    window.showModernAlert = function(options = {}) {
        return Swal.fire({ icon: options.icon || 'success', title: options.title || 'Berhasil!', text: options.text || '', confirmButtonText: 'OKE', ...options });
    };
}
if (typeof confirmModernAlert === 'undefined') {
    window.confirmModernAlert = function(options = {}) {
        return Swal.fire({ icon: options.icon || 'warning', title: options.title || 'Anda Yakin?', text: options.text || '', showCancelButton: true, confirmButtonText: options.confirmButtonText || 'YA', cancelButtonText: 'BATAL', ...options });
    };
}

/* ── Modern Alerts (Already defined in theme.js) ─────── */
// Using definitions from assets/js/theme.js

/* ── Dark mode ─────────── */
function applyDark(on){
    document.documentElement.classList.toggle('dark',on);
    localStorage.setItem('dark',on?'1':'0');
    const ic=on?'fa-sun':'fa-moon';
    ['mIcon','mIcon2'].forEach(id=>{const e=document.getElementById(id);if(e)e.className='fas '+ic;});
}
function toggleDark(){applyDark(!document.documentElement.classList.contains('dark'));}
applyDark(localStorage.getItem('dark')==='1');

/* ── Sidebar ────────────── */
function openSB(){document.getElementById('sidebar').classList.add('on');document.getElementById('sbk').classList.add('on');}
function closeSB(){document.getElementById('sidebar').classList.remove('on');document.getElementById('sbk').classList.remove('on');}

const SUBJECTS_DATA = <?php 
    $s_by_id = [];
    foreach($subjects as $s) {
        $s_by_id[$s['id']] = $s;
    }
    echo json_encode($s_by_id); 
?>;
const USER_PREF_BG = <?php echo json_encode($pref); ?>;
const DEFAULT_BATIK = 'assets/img/mega.jpg';

/* ── Tab switch ─────────── */
function sw(id){
    const cleanId = String(id).toLowerCase().trim();
    if(window.innerWidth<1024)closeSB();
    
    document.querySelectorAll('.tp').forEach(p=>p.classList.remove('on'));
    document.querySelectorAll('.navi').forEach(b=>b.classList.remove('active'));
    
    const targetTab = document.getElementById('tab-'+cleanId);
    const targetNav = document.getElementById('nav-'+cleanId);
    
    if(targetTab) {
        targetTab.classList.add('on');
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    if(targetNav) targetNav.classList.add('active');

    const body = document.getElementById('appBody');
    const OVERLAY_SOFT = 'linear-gradient(rgba(255,255,255,0.58), rgba(255,255,255,0.58))';
    const OVERLAY_SUBJECT = 'linear-gradient(rgba(255,255,255,0.5), rgba(255,255,255,0.5))';

    body.style.transition = 'background 0.5s ease';

    // Helper: encode spasi di path untuk CSS url()
    function encodePath(p) { return p.split('/').map(s => encodeURIComponent(s)).join('/'); }

    if (cleanId === 'home') {
        if (USER_PREF_BG.bg_type === 'color') {
            body.style.background = USER_PREF_BG.bg_value;
            body.classList.remove('batik-mode');
        } else {
            body.style.background = 'none';
            body.style.backgroundImage = `${OVERLAY_SOFT}, url('../../${encodePath(USER_PREF_BG.bg_value)}')`;
            body.style.backgroundPosition = 'center';
            body.style.backgroundSize = 'cover';
            body.style.backgroundAttachment = 'fixed';
            body.classList.add('batik-mode');
        }
    } else {
        const subj = SUBJECTS_DATA[cleanId];
        // Priority: 1. Subject specific batik, 2. USER preferred batik (if chosen), 3. User preferred color, 4. Default Mega
        if (subj && subj.batik_bg) {
            body.style.background = 'none';
            body.style.backgroundImage = `${OVERLAY_SUBJECT}, url('../../${encodePath(subj.batik_bg)}')`;
            body.style.backgroundPosition = 'center';
            body.style.backgroundSize = 'cover';
            body.style.backgroundAttachment = 'fixed';
            body.classList.add('batik-mode');
        } else if (USER_PREF_BG.bg_type === 'batik') {
            body.style.background = 'none';
            body.style.backgroundImage = `${OVERLAY_SOFT}, url('../../${encodePath(USER_PREF_BG.bg_value)}')`;
            body.style.backgroundPosition = 'center';
            body.style.backgroundSize = 'cover';
            body.style.backgroundAttachment = 'fixed';
            body.classList.add('batik-mode');
        } else if (USER_PREF_BG.bg_type === 'color') {
            body.style.background = USER_PREF_BG.bg_value;
            body.classList.remove('batik-mode');
        } else {
            body.style.background = 'none';
            body.style.backgroundImage = `${OVERLAY_SUBJECT}, url('../../${DEFAULT_BATIK}')`;
            body.style.backgroundPosition = 'center';
            body.style.backgroundSize = 'cover';
            body.style.backgroundAttachment = 'fixed';
            body.classList.add('batik-mode');
        }
    }
}

/* ── Accordion ──────────── */
function tgAcc(id){
    document.getElementById('acc-'+id).classList.toggle('on');
    document.getElementById('chv-'+id).classList.toggle('on');
}

/* ── Settings ───────────── */
function openSettings(){
    document.getElementById('sModal').classList.add('on');
}
function closeSettings(){
    document.getElementById('sModal').classList.remove('on');
}

const stabEls={};
function stab(tab){
    ['profile','security','appearance'].forEach(t=>{
        document.getElementById('stab-'+t)?.classList.toggle('act',t===tab);
        document.getElementById('s-'+t)?.classList.toggle('hidden',t!==tab);
    });
}

function tgPw(inpId,eyeId){
    const i=document.getElementById(inpId),e=document.getElementById(eyeId);
    i.type=i.type==='password'?'text':'password';
    e.className=i.type==='text'?'fas fa-eye-slash':'fas fa-eye';
}

function previewPP(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('ppPreview');
            const sidebarPic = document.getElementById('sidebarPP');
            const navbarPic = document.getElementById('navbarPP');
            const mobilePic = document.getElementById('mobilePP');
            const thumb = document.getElementById('ppThumb');
            
            if(preview) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
            }
            if(sidebarPic) sidebarPic.src = e.target.result;
            else {
                const box = document.getElementById('sidebarPPBox');
                if (box) box.innerHTML = `<img src="${e.target.result}" id="sidebarPP" alt="Profile" class="w-full h-full object-cover">`;
            }

            if(navbarPic) navbarPic.src = e.target.result;
            else {
                const box = document.getElementById('navbarPPBox');
                if (box) box.innerHTML = `<img src="${e.target.result}" id="navbarPP" alt="Profile" class="w-full h-full object-cover">`;
            }

            if(mobilePic) mobilePic.src = e.target.result;
            else {
                const box = document.getElementById('mobilePPBox');
                if (box) box.innerHTML = `<img src="${e.target.result}" id="mobilePP" alt="Profile" class="w-full h-full object-cover">`;
            }

            if(thumb) thumb.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

async function saveProfileWithPic() {
    const btn = document.getElementById('saveProfileBtn');
    const name = document.getElementById('s_name').value.trim();
    const title = document.getElementById('s_title').value.trim();
    const ppFile = document.getElementById('ppInp').files[0];
    
    if(!name) { 
        Swal.fire({icon:'error', title:'Oops...', text:'Nama tidak boleh kosong!'});
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
        if(!data.success) throw new Error(data.error || 'Gagal simpan nama');

        if(ppFile) {
            const picData = new FormData();
            picData.append('action', 'upload_profile_pic');
            picData.append('pic', ppFile);
            
            let resP = await fetch(PREF_API, { method: 'POST', body: picData });
            let dataP = await resP.json();
            if(!dataP.success) throw new Error(dataP.error || 'Gagal upload foto');
        }

        showModernAlert({
            title: 'Profil Diperbarui!',
            text: 'Data profil kamu berhasil disimpan.'
        }).then(() => location.reload());

    } catch(err) {
        Swal.fire({icon:'error', title:'Gagal', text: err.message});
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Simpan Perubahan';
    }
}

function openActivityHistory(){ document.getElementById('activityModal').classList.add('on'); }
function closeActivityHistory(){ document.getElementById('activityModal').classList.remove('on'); }

async function savePassword(){
    const pw=document.getElementById('s_pw').value,pw2=document.getElementById('s_pw2').value;
    if(!pw){Swal.fire({icon:'warning',title:'Password kosong!'});return;}
    if(pw!==pw2){Swal.fire({icon:'error',title:'Password tidak cocok!'});return;}
    if(pw.length<6){Swal.fire({icon:'warning',title:'Min 6 karakter!'});return;}
    const fd=new FormData();fd.append('action','save_profile');fd.append('full_name',document.getElementById('s_name').value);fd.append('password',pw);
    const r=await fetch(PREF_API,{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){
        showModernAlert({title:'Password diperbarui!'});
        document.getElementById('s_pw').value='';
        document.getElementById('s_pw2').value='';
    }
    else showModernAlert({icon:'error',title:'Gagal',text:d.error});
}


function setGlassOpacity(val) {
    USER_PREF_BG.glass_opacity = val;
    document.documentElement.style.setProperty('--glass-opacity', val/100);
    
    // Update label UI if exists
    const label = document.getElementById('opacityValLabel');
    if(label) label.textContent = val + '%';
    
    // Update button states
    document.querySelectorAll('[id^="gop-"]').forEach(btn => {
        btn.className = btn.id === 'gop-'+val ? 
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
    function enc(p) { return p.split('/').map(s => encodeURIComponent(s)).join('/'); }

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
    USER_PREF_BG.bg_type = type;
    USER_PREF_BG.bg_value = val;
    applyBg(type, val);
    
    // Update UI active states for batik cards
    document.querySelectorAll('.swatch-batik-card').forEach(btn => {
        const isMatch = btn.getAttribute('onclick')?.includes(`'${val}'`);
        btn.classList.toggle('active', isMatch);
        // Clear check circles
        const check = btn.querySelector('.fa-check-circle');
        if(check) check.style.display = isMatch ? 'block' : 'none';
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
        if(!res.success) console.warn('Save failed:', res.error);
    } catch(err) {
        console.error('Network error saving preferences:', err);
    }
}

async function uploadBg(inp){
    if(!inp.files[0])return;
    const fd=new FormData();fd.append('action','upload_bg');fd.append('bg_image',inp.files[0]);
    const r=await fetch(PREF_API,{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){
        document.getElementById('appBody').style.background = 'none';
        document.getElementById('appBody').style.backgroundImage = `url('../../${d.url}')`;
        document.getElementById('appBody').style.backgroundPosition = 'center';
        document.getElementById('appBody').style.backgroundSize = 'cover';
        document.getElementById('appBody').style.backgroundAttachment = 'fixed';
        showModernAlert({title:'Background diperbarui!'});
    }
    else showModernAlert({icon:'error',title:'Upload gagal',text:d.error});
}

async function resetBg(){
    // Reset ke default batik megamendung
    await setBg('batik', 'assets/img/mega.jpg');
    location.reload();
}

async function removeBg(){
    const res = await confirmModernAlert({ 
        title: 'Hapus Background?', 
        text: 'Gambar atau warna background akan direset ke default StepUp.',
        icon: 'warning'
    });
    if(res.isConfirmed) {
        await resetBg();
    }
}

/* ── Logout ─────────────── */
function doLogout(){
    confirmModernAlert({
        title: 'Keluar Sesi?',
        text: 'Kamu akan keluar dari StepUp Learning.',
        icon: 'question',
        confirmButtonText: 'YA, LOGOUT',
        confirmButtonColor: '#ef4444'
    }).then(r => {
        if(r.isConfirmed) window.location.href='../auth/logout.php';
    });
}

/* ── Calendar ────────────── */
let cY=new Date().getFullYear(),cM=new Date().getMonth();
let userAgendas = [];
const MN=["Januari","Februari","Maret","April","Mei","Juni","Juli","Agustus","September","Oktober","November","Desember"];

async function loadAgendas() {
    try {
        const r = await fetch(PREF_API + '?action=get_agendas');
        const text = await r.text();
        const d = parseMagicJSON(text);
        if(d.success) {
            userAgendas = d.agendas || [];
            renderCal();
        }
    } catch(e) {
        console.error("Load Agendas failed:", e);
    }
}

function renderCal(){
    const g=document.getElementById('calG');if(!g)return;
    document.getElementById('calLbl').textContent=MN[cM]+' '+cY;
    const now=new Date(), fd=new Date(cY,cM,1).getDay(), td=new Date(cY,cM+1,0).getDate();
    let h='';
    
    // Group agendas by date for easier lookup (Fix: Safe Local Parsing)
    const agendaMap = {};
    userAgendas.forEach(a => {
        const p = a.event_date.split('-');
        const y = parseInt(p[0]), m = parseInt(p[1]) - 1, d = parseInt(p[2]);
        if(y === cY && m === cM) {
            if(!agendaMap[d]) agendaMap[d] = [];
            agendaMap[d].push(a);
        }
    });

    for(let i=0;i<fd;i++)h+='<div class="cd emp"></div>';
    for(let d=1;d<=td;d++){
        const isToday = d===now.getDate()&&cM===now.getMonth()&&cY===now.getFullYear();
        const hasAgenda = agendaMap[d] !== undefined;
        h+=`<button onclick="showDayDetail(${d})" 
            class="cd ${isToday?'tod':''} ${hasAgenda?'has-event':''} hover:bg-blue-50 dark:hover:bg-blue-900/40 transition-all">${d}</button>`;
    }
    g.innerHTML=h;

    // Populate Agenda List Footer
    const listE = document.getElementById('agendaList');
    const monthAgendas = userAgendas.filter(a => {
        const p = a.event_date.split('-');
        const y = parseInt(p[0]), m = parseInt(p[1]) - 1;
        return y === cY && m === cM;
    }).sort((a,b) => {
        const pa = a.event_date.split('-'), pb = b.event_date.split('-');
        return new Date(pa[0], pa[1]-1, pa[2]) - new Date(pb[0], pb[1]-1, pb[2]);
    });

    if(listE) {
        if(monthAgendas.length > 0) {
            listE.innerHTML = monthAgendas.map(a => {
                const p = a.event_date.split('-');
                const localDate = new Date(p[0], p[1]-1, p[2]);
                return `
                <div class="flex items-center gap-4 p-4 bg-white/80 dark:bg-slate-800/50 rounded-2xl border border-slate-100 dark:border-slate-700/50 shadow-sm group hover:border-blue-500/30 transition-all">
                    <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-500/10 flex flex-col items-center justify-center shrink-0 border border-blue-100/50 dark:border-blue-500/20">
                        <span class="text-sm font-black text-blue-600 dark:text-blue-400 leading-none">${localDate.getDate()}</span>
                        <span class="text-[9px] font-black text-blue-400/80 uppercase tracking-tighter">${MN[cM].substring(0,3)}</span>
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
        if(monthAgendas.length > 0) {
            miniList.innerHTML = monthAgendas.map(a => {
                const p = a.event_date.split('-');
                const localDate = new Date(p[0], p[1]-1, p[2]);
                return `
                <div class="flex items-center gap-2 p-2 bg-slate-50/50 dark:bg-slate-800/40 rounded-xl border border-slate-100 dark:border-slate-700 shadow-sm group">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex flex-col items-center justify-center shrink-0 border border-blue-100/50 dark:border-blue-800">
                        <span class="text-xs font-black text-blue-600 dark:text-blue-400 leading-none">${localDate.getDate()}</span>
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
    const res = await confirmModernAlert({ title: 'Hapus Agenda?', text: 'Agenda ini akan dihapus permanen.' });
    if(!res.isConfirmed) return;

    const fd = new FormData();
    fd.append('action', 'delete_agenda');
    fd.append('id', id);
    const r = await fetch(PREF_API, { method: 'POST', body: fd });
    const d = await r.json();
    if(d.success) {
        showModernAlert({ title: 'Terhapus!', icon: 'success' });
        loadAgendas();
    }
}

function showDayDetail(d) {
    const agendaMap = {};
    userAgendas.forEach(a => {
        const p = a.event_date.split('-');
        const y = parseInt(p[0]), m = parseInt(p[1]) - 1, day = parseInt(p[2]);
        if(y === cY && m === cM) {
            if(!agendaMap[day]) agendaMap[day] = [];
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
function chM(d){cM+=d;if(cM<0){cM=11;cY--;}if(cM>11){cM=0;cY++;}renderCal();}

async function addAgenda() {
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
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'SIMPAN AGENDA',
        cancelButtonText: 'BATAL',
        preConfirm: () => {
            const d = document.getElementById('swal-input-date').value;
            const t = document.getElementById('swal-input-title').value;
            if(!d || !t) { Swal.showValidationMessage('Semua kolom wajib diisi!'); return false; }
            if(d < todayStr) { Swal.showValidationMessage('Tidak bisa memilih tanggal yang sudah lewat!'); return false; }
            return { date: d, title: t }
        }
    });

    if (formValues && formValues.title) {
        const fd = new FormData();
        fd.append('action', 'add_agenda');
        fd.append('date', formValues.date);
        fd.append('title', formValues.title);
        
        const r = await fetch(PREF_API, { method: 'POST', body: fd });
        const d = await r.json();
        if(d.success) {
            showModernAlert({ title: 'Agenda Disimpan!', text: 'Acara kamu sudah tercatat di kalender.' });
            loadAgendas();
        } else {
            showModernAlert({ icon: 'error', title: 'Gagal', text: d.error || 'Terjadi kesalahan saat menyimpan.' });
        }
    }
}

/* ── Mini Calendars (subject tabs) ─ */
function renderMiniCal(slug){
    const g=document.getElementById('miniCalG-'+slug);
    const l=document.getElementById('miniCalLbl-'+slug);
    if(!g||!l)return;
    const now=new Date();
    // Gunakan cY dan cM dari main calendar agar tersinkronisasi
    l.textContent=MN[cM].substring(0,3)+' '+cY;
    const fd=new Date(cY,cM,1).getDay(),td=new Date(cY,cM+1,0).getDate();
    let h='';
    
    // Group agendas for the mini calendar
    const agendaMap = {};
    userAgendas.forEach(a => {
        const p = a.event_date.split('-');
        const y = parseInt(p[0]), m = parseInt(p[1]) - 1, d = parseInt(p[2]);
        if(y === cY && m === cM) {
            if(!agendaMap[d]) agendaMap[d] = [];
            agendaMap[d].push(a);
        }
    });

    for(let i=0;i<Math.max(0, fd);i++) h+='<div class="py-1"></div>';
    
    for(let d=1;d<=td;d++){
        const t = d===now.getDate() && cM===now.getMonth() && cY===now.getFullYear();
        const hasAgenda = agendaMap[d] !== undefined;
        let classes = 'text-center text-[11px] font-bold rounded-lg py-1 transition relative ';
        if (t) classes += 'bg-blue-600 text-white shadow-lg shadow-blue-500/30 ';
        else classes += 'text-slate-400 hover:bg-blue-100 dark:hover:bg-blue-900/40 ';
        
        let marker = hasAgenda && !t ? `<span class="absolute bottom-0.5 left-1/2 -translate-x-1/2 w-1 h-1 bg-amber-500 rounded-full"></span>` : '';
        if (hasAgenda && t) marker = `<span class="absolute top-0 right-0 w-1.5 h-1.5 bg-amber-300 rounded-full border border-blue-600"></span>`;
        if (hasAgenda && !t) classes += 'text-amber-600 dark:text-amber-500 font-extrabold ';
        
        h+=`<button onclick="showDayDetail(${d})" class="${classes}">${d}${marker}</button>`;
    }
    g.innerHTML=h;
}

/* ── Mini Todo (mirror in subject tabs) ─ */
function updateMiniTodo(todos){
    document.querySelectorAll('.mini-todo-list').forEach(el => {
        if(!todos || !todos.length){
            el.innerHTML = '<p class="text-xs text-slate-300 text-center py-4 italic font-medium">Bebas tugas! 🎉</p>';
            return;
        }
        el.innerHTML = todos.slice(0, 5).map(t => `
            <div class="flex items-center justify-between group/mini py-3 border-b border-slate-50 dark:border-slate-700/50 last:border-0 transition-colors">
                <div onclick="toggleTodo(${t.id})" class="flex items-center gap-3.5 cursor-pointer flex-1 min-w-0">
                    <div class="w-2.5 h-2.5 rounded-full ${t.is_completed ? 'bg-emerald-500' : 'bg-blue-500 animate-pulse'} shrink-0 group-hover/mini:scale-125 transition"></div>
                    <span class="text-[13px] text-slate-700 dark:text-slate-300 font-bold truncate ${t.is_completed ? 'line-through opacity-40' : 'group-hover/mini:text-blue-500'}">${t.task}</span>
                </div>
                <button onclick="delQuickTodo(${t.id})" class="opacity-0 group-hover/mini:opacity-100 text-xs text-red-400 hover:text-red-500 p-1.5 transition"><i class="fas fa-trash-alt"></i></button>
            </div>
        `).join('');
    });
}

/* ── Quick Todo Actions ── */
async function addQuickTodo(){
    const { value: task } = await promptModernAlert({
        title: 'TUGAS CEPAT',
        inputPlaceholder: 'Apa yang harus dikerjakan?',
        iconHtml: `<div class="text-blue-500 scale-110"><i class="fas fa-tasks"></i></div>`
    });
    if(task){
        const fd=new FormData();fd.append('action','add');fd.append('task',task);
        const res=await fetch(TODO_API,{method:'POST',body:fd});
        const data=await res.json();
        if(data.success) loadTodos();
    }
}
async function delQuickTodo(id){
    const fd=new FormData();fd.append('action','delete');fd.append('id',id);
    await fetch(TODO_API,{method:'POST',body:fd});
    loadTodos();
}

/* ── Real-time Clock ─────── */
function tick(){
    const e = document.getElementById('liveClock');
    if(!e) return;
    const n = new Date();
    e.textContent = n.toLocaleTimeString('id-ID',{hour:'2-digit',minute:'2-digit',second:'2-digit'}).replace(/\./g,':');
}

/* ── TODO ─────────────────── */
async function loadTodos(){
    try{
        const r=await fetch(TODO_API);const d=await r.json();
        if(d.success){ renderTodos(d.todos); updateMiniTodo(d.todos); }
    }catch(e){}
}

function renderTodos(todos){
    const list=document.getElementById('todoList');
    const rem=todos.filter(t=>!t.is_completed).length;
    document.getElementById('todoCnt').textContent=rem+' tersisa';
    list.innerHTML=todos.map(t=>`
        <div class="todo-item ${t.is_completed?'done':''}" id="ti-${t.id}">
            <button onclick="toggleTodo(${t.id})" class="w-6 h-6 rounded-lg border-2 ${t.is_completed?'bg-blue-600 border-blue-600':'border-slate-300 dark:border-slate-600'} flex items-center justify-center shrink-0 transition tc">
                ${t.is_completed?'<i class="fas fa-check text-white text-[10px]"></i>':''}
            </button>
            <span class="flex-1 text-base font-bold text-slate-700 dark:text-slate-300">${t.task}</span>
            <button onclick="delTodo(${t.id})" class="w-8 h-8 rounded-xl bg-red-50 dark:bg-red-900/20 text-red-400 hover:bg-red-500 hover:text-white flex items-center justify-center tc shrink-0"><i class="fas fa-trash-alt text-[11px]"></i></button>
        </div>
    `).join('');
}

async function addTodo(e){
    e.preventDefault();
    const inp=document.getElementById('todoInput');
    const task=inp.value.trim();if(!task)return;
    const fd=new FormData();fd.append('action','add');fd.append('task',task);
    const r=await fetch(TODO_API,{method:'POST',body:fd});
    const d=await r.json();
    if(d.success){inp.value='';loadTodos();}
}

async function toggleTodo(id){
    const fd=new FormData();fd.append('action','toggle');fd.append('id',id);
    await fetch(TODO_API,{method:'POST',body:fd});
    loadTodos();
}

async function delTodo(id){
    const res = await confirmModernAlert({
        title: 'Hapus Tugas?',
        text: 'Tugas ini akan dihapus secara permanen.',
        icon: 'warning',
        confirmButtonColor: '#ef4444',
        confirmButtonText: 'YA, HAPUS'
    });
    if(!res.isConfirmed) return;
    const fd=new FormData();fd.append('action','delete');fd.append('id',id);
    await fetch(TODO_API,{method:'POST',body:fd});
    loadTodos();
}

function showStreakDetails() {
    const dates = <?php echo json_encode($streak_days ?? []) ?>;
    const current = <?php echo $current_streak ?? 0 ?>;
    if (!dates || !Array.isArray(dates)) { console.error('Streak dates missing'); return; }
    
    let html = `
        <div class="text-left p-2">
            <div class="flex items-center justify-between mb-8 bg-gradient-to-br from-orange-50 to-orange-100/50 dark:from-orange-950/20 dark:to-orange-900/10 p-8 rounded-[2.5rem] border border-orange-200/50 dark:border-orange-800/30">
                <div>
                    <p class="text-[10px] font-black text-orange-500 uppercase tracking-[0.2em] mb-2">PENCAPAIAN SAAT INI</p>
                    <p class="text-5xl font-black text-slate-900 dark:text-white">${current} <span class="text-xl opacity-50">HARI</span></p>
                </div>
                <div class="w-20 h-20 bg-orange-500 text-white rounded-3xl flex items-center justify-center text-4xl shadow-2xl shadow-orange-500/40 animate-pulse">
                    <i class="fas fa-fire"></i>
                </div>
            </div>
            
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-6 flex items-center gap-2">
                <i class="fas fa-history"></i> RIWAYAT AKTIVITAS
            </p>
            
            <div class="space-y-3 max-h-[350px] overflow-y-auto pr-3 custom-scrollbar">
    `;
    
    if (dates.length === 0) {
        html += `
            <div class="text-center py-12 bg-slate-50 dark:bg-slate-900/50 rounded-[2rem] border-2 border-dashed border-slate-200 dark:border-slate-800">
                <i class="fas fa-calendar-alt text-4xl text-slate-200 dark:text-slate-700 mb-4 block"></i>
                <p class="text-slate-400 font-bold italic">Belum ada catatan aktivitas.</p>
            </div>
        `;
    } else {
        dates.forEach(d => {
            const dateObj = new Date(d);
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            const formatted = dateObj.toLocaleDateString('id-ID', options);
            html += `
                <div class="flex items-center gap-4 p-5 bg-white dark:bg-slate-800/40 rounded-[1.8rem] border border-slate-100 dark:border-slate-800 hover:border-orange-200 dark:hover:border-orange-900/50 transition-all hover:shadow-lg group">
                    <div class="w-12 h-12 rounded-[1.2rem] bg-slate-50 dark:bg-slate-700 flex items-center justify-center text-orange-500 shadow-inner group-hover:bg-orange-50 dark:group-hover:bg-orange-900/30 transition-colors">
                        <i class="fas fa-calendar-check text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-black text-slate-800 dark:text-slate-200 text-base">${formatted}</p>
                        <p class="text-[10px] font-black text-emerald-500 uppercase tracking-widest mt-1 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                            SESI AKTIF
                        </p>
                    </div>
                </div>
            `;
        });
    }
    
    html += `</div></div>`;

    Swal.fire({
        ...getModernConfig(),
        title: 'Konsistensi Belajar',
        iconHtml: '<div class="text-orange-500 transform scale-125"><i class="fas fa-fire"></i></div>',
        html: html,
        confirmButtonText: 'MANTAP, TERUSKAN!',
        customClass: {
            htmlContainer: 'text-left',
            icon: 'border-none bg-orange-50 dark:bg-orange-950/30'
        }
    });
}

function showProgressDetails() {
    const subjects = <?php echo json_encode($subjects ?? []) ?>;
    if (!subjects || typeof subjects !== 'object') { console.error('Subjects data missing'); return; }
    const totalProgress = Object.values(subjects).length > 0 ? Math.round(Object.values(subjects).reduce((a, b) => a + b.progress, 0) / Object.values(subjects).length) : 0;
    
    let htmlStart = `
        <div class="text-left p-2">
            <!-- Mastery Overview Graphic -->
            <div class="mb-10 p-8 rounded-[3rem] bg-gradient-to-br from-blue-600 to-indigo-700 text-white flex items-center justify-between shadow-2xl shadow-blue-500/30">
                <div class="space-y-1">
                    <p class="text-[10px] font-black text-blue-100 uppercase tracking-[0.3em]">TOTAL PENGUASAAN</p>
                    <p class="text-5xl font-black">${totalProgress}%</p>
                    <p class="text-xs font-bold text-blue-200">Terus tingkatkan skor belajarmu!</p>
                </div>
                <!-- Mini Radial Chart SVG -->
                <div class="relative w-24 h-24">
                    <svg class="w-full h-full transform -rotate-90">
                        <circle cx="48" cy="48" r="40" stroke="rgba(255,255,255,0.1)" stroke-width="8" fill="transparent" />
                        <circle cx="48" cy="48" r="40" stroke="white" stroke-width="8" fill="transparent" 
                            stroke-dasharray="251.2" stroke-dashoffset="${251.2 - (251.2 * totalProgress / 100)}" 
                            stroke-linecap="round" class="transition-all duration-1000" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <i class="fas fa-graduation-cap text-2xl text-white/50"></i>
                    </div>
                </div>
            </div>

            <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.3em] mb-6 flex items-center gap-2">
                <i class="fas fa-layer-group"></i> RINCIAN PER MATA PELAJARAN
            </p>
            <div class="space-y-4 max-h-[400px] overflow-y-auto pr-3 custom-scrollbar">
    `;

    Object.values(subjects).forEach(s => {
        htmlStart += `
            <div class="p-6 bg-white dark:bg-slate-800/40 rounded-[2rem] border border-slate-100 dark:border-slate-800 hover:border-blue-200 dark:hover:border-blue-900/50 transition-all group">
                <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-[1.2rem] flex items-center justify-center text-white shadow-lg" style="background:${s.color}">
                            <i class="fas ${s.icon}"></i>
                        </div>
                        <div>
                            <p class="font-black text-slate-900 dark:text-white text-base tracking-tight leading-none">${s.title}</p>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mt-2">${s.completed_mods}/${s.total_mods} MODUL</p>
                        </div>
                    </div>
                    <p class="text-2xl font-black text-slate-900 dark:text-white tracking-tighter">${s.progress}%</p>
                </div>
                <div class="h-3 bg-slate-100 dark:bg-slate-700 rounded-full overflow-hidden flex">
                    <div class="h-full rounded-full bg-gradient-to-r transition-all duration-1000" 
                         style="width:${s.progress}%; background-color:${s.color}; filter: brightness(1.1); box-shadow: 0 0 10px ${s.color}66;"></div>
                </div>
            </div>
        `;
    });

    htmlStart += `</div></div>`;

    Swal.fire({
        ...getModernConfig(),
        title: 'Analisa Penguasaan',
        iconHtml: '<div class="text-blue-500 transform scale-110"><i class="fas fa-chart-line"></i></div>',
        html: htmlStart,
        confirmButtonText: 'MANTAP, LANJUTKAN!',
        customClass: {
            htmlContainer: 'text-left',
            icon: 'border-none bg-blue-50 dark:bg-blue-950/30'
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    console.log('StepUp Dashboard: DOM Content Loaded');
    try {
        // 1. Initial State
        applyBg(USER_PREF_BG.bg_type, USER_PREF_BG.bg_value);
        if(USER_PREF_BG.glass_opacity) document.documentElement.style.setProperty('--glass-opacity', USER_PREF_BG.glass_opacity / 100);
        
        loadTodos();
        loadAgendas();
        renderCal();
        tick();
        
        // 2. Start intervals
        setInterval(tick, 1000);
        
        // 3. Mini Cals (only if element exists)
        <?php foreach($subjects as $sl=>$s): ?>
        if(document.getElementById('miniCalG-<?php echo $sl?>')) renderMiniCal('<?php echo $sl?>');
        <?php endforeach; ?>
        
        // 4. Auto-Switch to Subject Tab if requested in URL
        const urlParams = new URLSearchParams(window.location.search);
        const subTab = urlParams.get('tab');
        if(subTab) sw(subTab);
        
        console.log('StepUp Dashboard: Initialization complete');
    } catch (err) {
        console.error('StepUp Dashboard: Critical initialization error:', err);
    }
});
</script>
</body>
</html>
