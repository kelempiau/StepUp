<?php
/**
 * Migration: Add new community columns
 * 
 * Adds new columns to `communities` and `community_members` tables
 * for the community settings overhaul.
 * 
 * Run: /Applications/XAMPP/xamppfiles/bin/php migrate_community.php
 */

require_once __DIR__ . '/config/db.php';

echo "=== Community Migration Script ===\n\n";

$alterations = [
    // --- communities table ---
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS chat_disabled TINYINT(1) DEFAULT 0",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS slowmode_seconds INT DEFAULT 0",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS video_disabled TINYINT(1) DEFAULT 0",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS video_max_participants INT DEFAULT 0",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS video_admin_only TINYINT(1) DEFAULT 0",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS invite_code VARCHAR(20) DEFAULT NULL",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS vision TEXT DEFAULT NULL",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS mission TEXT DEFAULT NULL",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS avatar_color VARCHAR(20) DEFAULT 'blue'",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS welcome_message TEXT DEFAULT NULL",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS rules TEXT DEFAULT NULL",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS max_members INT DEFAULT 0",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS auto_approve TINYINT(1) DEFAULT 1",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS privacy VARCHAR(10) DEFAULT 'public'",
    "ALTER TABLE communities ADD COLUMN IF NOT EXISTS image VARCHAR(255) DEFAULT NULL",

    // --- community_members table ---
    "ALTER TABLE community_members ADD COLUMN IF NOT EXISTS last_video_pulse TIMESTAMP NULL DEFAULT NULL",
    "ALTER TABLE community_members ADD COLUMN IF NOT EXISTS is_banned TINYINT(1) DEFAULT 0",
    "ALTER TABLE community_members ADD COLUMN IF NOT EXISTS notify_messages TINYINT(1) DEFAULT 1",
    "ALTER TABLE community_members ADD COLUMN IF NOT EXISTS notify_members TINYINT(1) DEFAULT 1",
    "ALTER TABLE community_members ADD COLUMN IF NOT EXISTS muted_until TIMESTAMP NULL DEFAULT NULL",
];

$success = 0;
$skipped = 0;
$failed  = 0;

foreach ($alterations as $sql) {
    try {
        $pdo->exec($sql);
        echo "[OK]   $sql\n";
        $success++;
    } catch (PDOException $e) {
        // Error 1060 = Duplicate column name (column already exists)
        if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "[SKIP] $sql  (already exists)\n";
            $skipped++;
        } else {
            echo "[FAIL] $sql\n       Error: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
}

echo "\n=== Migration Complete ===\n";
echo "Success: $success | Skipped: $skipped | Failed: $failed\n";

if ($failed > 0) {
    echo "\n⚠️  Some alterations failed. Check the errors above.\n";
    exit(1);
} else {
    echo "\n✅ All columns are present.\n";
    exit(0);
}
