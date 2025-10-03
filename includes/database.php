<?php
// Scribble Game - Database Class

class Database {
    private $conn;
    private static $instance = null;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $e) {
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch(PDOException $e) {
            error_log("Database query error: " . $e->getMessage());
            return false;
        }
    }
    
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : null;
    }
    
    public function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt !== false;
    }
    
    public function lastInsertId() {
        return $this->conn->lastInsertId();
    }
    
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    public function commit() {
        return $this->conn->commit();
    }
    
    public function rollback() {
        return $this->conn->rollBack();
    }
    
    // Clean up old inactive rooms
    public function cleanupInactiveRooms() {
        $timeout = time() - ROOM_CLEANUP_TIME;
        $sql = "DELETE FROM game_rooms WHERE UNIX_TIMESTAMP(last_activity) < ?";
        return $this->execute($sql, [$timeout]);
    }
    
    // Clean up old inactive players
    public function cleanupInactivePlayers() {
        $timeout = time() - SESSION_LIFETIME;
        $sql = "UPDATE players SET session_id = NULL WHERE UNIX_TIMESTAMP(last_activity) < ?";
        return $this->execute($sql, [$timeout]);
    }
    
    // Clean up old game state updates
    public function cleanupOldUpdates() {
        $timeout = time() - 3600; // 1 hour
        $sql = "DELETE FROM game_state_updates WHERE UNIX_TIMESTAMP(created_at) < ?";
        return $this->execute($sql, [$timeout]);
    }
}
