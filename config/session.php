<?php
// config/session.php
// Database-based Session Handler for Stateless Cloud Run

require_once __DIR__ . '/db.php';

class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function open($savePath, $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read($id): string {
        try {
            $stmt = $this->pdo->prepare("SELECT data FROM php_sessions WHERE id = ?");
            $stmt->execute([$id]);
            return (string)$stmt->fetchColumn();
        } catch (Exception $e) {
            return '';
        }
    }

    public function write($id, $data): bool {
        try {
            $stmt = $this->pdo->prepare("REPLACE INTO php_sessions (id, data, access) VALUES (?, ?, ?)");
            return $stmt->execute([$id, $data, time()]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function destroy($id): bool {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function gc($maxlifetime): int|false {
        try {
            $old = time() - $maxlifetime;
            $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE access < ?");
            $stmt->execute([$old]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}

// Create php_sessions table dynamically if not exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS php_sessions (
        id VARCHAR(128) NOT NULL PRIMARY KEY,
        data TEXT NOT NULL,
        access INT NOT NULL
    ) ENGINE=InnoDB;");

    // Register DB Session handler
    $handler = new DatabaseSessionHandler($pdo);
    
    // If session is already active, we need to migrate it to the DB handler
    $session_was_active = (session_status() === PHP_SESSION_ACTIVE);
    $saved_session_data = [];
    
    if ($session_was_active) {
        // Save current session data before closing
        $saved_session_data = $_SESSION ?? [];
        session_write_close();
    }
    
    session_set_save_handler($handler, true);
    
    // Restart session with the DB handler
    if ($session_was_active) {
        session_start();
        // Restore session data that was saved from the old handler
        if (!empty($saved_session_data) && empty($_SESSION)) {
            $_SESSION = $saved_session_data;
        }
    }
} catch (Exception $e) {
    error_log("DB Session Setup Failed: " . $e->getMessage());
}
