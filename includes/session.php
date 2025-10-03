<?php
// Scribble Game - Session Management

class SessionManager {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->startSession();
    }
    
    private function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_lifetime', SESSION_LIFETIME);
            ini_set('session.cookie_httponly', SESSION_COOKIE_HTTPONLY);
            ini_set('session.cookie_secure', SESSION_COOKIE_SECURE);
            ini_set('session.cookie_samesite', SESSION_COOKIE_SAMESITE);
            session_start();
        }
    }
    
    public function getPlayerId() {
        return $_SESSION['player_id'] ?? null;
    }
    
    public function getUsername() {
        return $_SESSION['username'] ?? null;
    }
    
    public function getRoomCode() {
        return $_SESSION['room_code'] ?? null;
    }
    
    public function setPlayer($playerId, $username) {
        $_SESSION['player_id'] = $playerId;
        $_SESSION['username'] = $username;
        $_SESSION['session_id'] = session_id();
        
        // Update player's session and activity time
        $this->updatePlayerActivity($playerId);
    }
    
    public function setRoom($roomCode) {
        $_SESSION['room_code'] = $roomCode;
    }
    
    public function clearRoom() {
        unset($_SESSION['room_code']);
    }
    
    public function logout() {
        $playerId = $this->getPlayerId();
        if ($playerId) {
            // Clear session ID in database
            $this->db->execute(
                "UPDATE players SET session_id = NULL WHERE id = ?",
                [$playerId]
            );
        }
        
        session_destroy();
    }
    
    public function createPlayer($username) {
        // Check if username already exists
        $existing = $this->db->fetchOne(
            "SELECT id FROM players WHERE username = ?",
            [$username]
        );
        
        if ($existing) {
            return ['error' => 'Username already taken'];
        }
        
        // Create new player
        $sessionId = session_id();
        $sql = "INSERT INTO players (username, session_id, last_activity) VALUES (?, ?, NOW())";
        
        if ($this->db->execute($sql, [$username, $sessionId])) {
            $playerId = $this->db->lastInsertId();
            $this->setPlayer($playerId, $username);
            return ['success' => true, 'player_id' => $playerId];
        }
        
        return ['error' => 'Failed to create player'];
    }
    
    public function getOrCreatePlayer($username) {
        // Try to find existing player with this session
        $sessionId = session_id();
        $player = $this->db->fetchOne(
            "SELECT id, username FROM players WHERE session_id = ?",
            [$sessionId]
        );
        
        if ($player) {
            $this->setPlayer($player['id'], $player['username']);
            $this->updatePlayerActivity($player['id']);
            return ['success' => true, 'player_id' => $player['id']];
        }
        
        // Create new player
        return $this->createPlayer($username);
    }
    
    public function updatePlayerActivity($playerId) {
        $sessionId = session_id();
        return $this->db->execute(
            "UPDATE players SET last_activity = NOW(), session_id = ? WHERE id = ?",
            [$sessionId, $playerId]
        );
    }
    
    public function isPlayerActive($playerId) {
        $timeout = time() - INACTIVE_TIMEOUT;
        $player = $this->db->fetchOne(
            "SELECT id FROM players WHERE id = ? AND UNIX_TIMESTAMP(last_activity) > ?",
            [$playerId, $timeout]
        );
        
        return $player !== null;
    }
    
    public function validateCSRFToken($token) {
        if (!ENABLE_CSRF_PROTECTION) {
            return true;
        }
        
        if (!isset($_SESSION['csrf_token']) || !$token) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public function validateRequestCSRF() {
        if (!ENABLE_CSRF_PROTECTION) {
            return true;
        }
        
        // Check POST data for CSRF token
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['_csrf_token'] ?? '';
        
        return $this->validateCSRFToken($token);
    }
    
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}
