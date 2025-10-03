<?php
// Scribble Game - Game Logic Class

class GameLogic {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Generate unique room code
    public function generateRoomCode() {
        do {
            $code = strtoupper(substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 6));
            $existing = $this->db->fetchOne("SELECT id FROM game_rooms WHERE room_code = ?", [$code]);
        } while ($existing);
        
        return $code;
    }
    
    // Create a new room
    public function createRoom($hostId, $rounds, $timer, $customWords = []) {
        $roomCode = $this->generateRoomCode();
        
        $sql = "INSERT INTO game_rooms (room_code, host_id, num_rounds, timer_duration, status) 
                VALUES (?, ?, ?, ?, 'waiting')";
        
        if ($this->db->execute($sql, [$roomCode, $hostId, $rounds, $timer])) {
            $roomId = $this->db->lastInsertId();
            
            // Add custom words
            if (!empty($customWords)) {
                foreach ($customWords as $word) {
                    $this->db->execute(
                        "INSERT INTO custom_word_lists (room_id, word) VALUES (?, ?)",
                        [$roomId, strtolower(trim($word))]
                    );
                }
            }
            
            // Add host to room
            $this->addPlayerToRoom($roomId, $hostId, 0);
            
            return ['success' => true, 'room_code' => $roomCode, 'room_id' => $roomId];
        }
        
        return ['error' => 'Failed to create room'];
    }
    
    // Join a room
    public function joinRoom($playerId, $roomCode) {
        $room = $this->db->fetchOne(
            "SELECT id, status FROM game_rooms WHERE room_code = ?",
            [$roomCode]
        );
        
        if (!$room) {
            return ['error' => 'Room not found'];
        }
        
        if ($room['status'] !== 'waiting') {
            return ['error' => 'Game already started'];
        }
        
        // Check if room is full
        $playerCount = $this->db->fetchOne(
            "SELECT COUNT(*) as count FROM room_players WHERE room_id = ? AND is_connected = 1",
            [$room['id']]
        );
        
        if ($playerCount['count'] >= MAX_PLAYERS_PER_ROOM) {
            return ['error' => 'Room is full'];
        }
        
        // Check if player already in room
        $existing = $this->db->fetchOne(
            "SELECT id FROM room_players WHERE room_id = ? AND player_id = ?",
            [$room['id'], $playerId]
        );
        
        if ($existing) {
            // Rejoin - update connection status
            $this->db->execute(
                "UPDATE room_players SET is_connected = 1, last_activity = NOW() WHERE room_id = ? AND player_id = ?",
                [$room['id'], $playerId]
            );
        } else {
            // New join
            $joinOrder = $this->db->fetchOne(
                "SELECT COALESCE(MAX(join_order), -1) + 1 as next_order FROM room_players WHERE room_id = ?",
                [$room['id']]
            );
            
            $this->addPlayerToRoom($room['id'], $playerId, $joinOrder['next_order']);
        }
        
        // Add state update
        $this->addStateUpdate($room['id'], 'player_joined', ['player_id' => $playerId]);
        
        return ['success' => true, 'room_id' => $room['id']];
    }
    
    // Add player to room
    private function addPlayerToRoom($roomId, $playerId, $joinOrder) {
        return $this->db->execute(
            "INSERT INTO room_players (room_id, player_id, join_order) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE is_connected = 1, last_activity = NOW()",
            [$roomId, $playerId, $joinOrder]
        );
    }
    
    // Start game
    public function startGame($roomId, $hostId) {
        // Verify host
        $room = $this->db->fetchOne(
            "SELECT host_id, status FROM game_rooms WHERE id = ?",
            [$roomId]
        );
        
        if (!$room || $room['host_id'] != $hostId) {
            return ['error' => 'Only host can start the game'];
        }
        
        if ($room['status'] !== 'waiting') {
            return ['error' => 'Game already started'];
        }
        
        // Update room status
        $this->db->execute(
            "UPDATE game_rooms SET status = 'playing', current_round = 1 WHERE id = ?",
            [$roomId]
        );
        
        // Create game history entry
        $this->db->execute(
            "INSERT INTO game_history (room_id, total_rounds) 
             SELECT id, num_rounds FROM game_rooms WHERE id = ?",
            [$roomId]
        );
        
        // Add state update
        $this->addStateUpdate($roomId, 'game_started', []);
        
        // Start first round
        return $this->startRound($roomId);
    }
    
    // Start a new round
    public function startRound($roomId) {
        $room = $this->db->fetchOne(
            "SELECT id, current_round, num_rounds FROM game_rooms WHERE id = ?",
            [$roomId]
        );
        
        if (!$room) {
            return ['error' => 'Room not found'];
        }
        
        // Get next drawer
        $players = $this->db->fetchAll(
            "SELECT rp.player_id, p.username 
             FROM room_players rp 
             JOIN players p ON rp.player_id = p.id 
             WHERE rp.room_id = ? AND rp.is_connected = 1 
             ORDER BY rp.join_order",
            [$roomId]
        );
        
        if (empty($players)) {
            return ['error' => 'No players in room'];
        }
        
        // Get drawer index based on round
        $drawerIndex = ($room['current_round'] - 1) % count($players);
        $drawer = $players[$drawerIndex];
        
        // Reset all players' guessed status
        $this->db->execute(
            "UPDATE room_players SET guessed = 0 WHERE room_id = ?",
            [$roomId]
        );
        
        // Update room with drawer and round start time
        $this->db->execute(
            "UPDATE game_rooms SET current_drawer_id = ?, round_start_time = ? WHERE id = ?",
            [$drawer['player_id'], time(), $roomId]
        );
        
        // Add state update
        $this->addStateUpdate($roomId, 'round_starting', [
            'drawer' => $drawer['username'],
            'drawer_id' => $drawer['player_id'],
            'round' => $room['current_round']
        ]);
        
        return ['success' => true, 'drawer_id' => $drawer['player_id']];
    }
    
    // Get random words for selection
    public function getRandomWords($roomId, $count = 3) {
        // Get custom words for room
        $customWords = $this->db->fetchAll(
            "SELECT word FROM custom_word_lists WHERE room_id = ?",
            [$roomId]
        );
        
        // Get default words
        $defaultWords = $this->db->fetchAll(
            "SELECT word FROM words ORDER BY RAND() LIMIT ?",
            [$count * 2]
        );
        
        // Merge words
        $allWords = array_merge(
            array_column($customWords, 'word'),
            array_column($defaultWords, 'word')
        );
        
        // Get fallback words if needed
        if (empty($allWords)) {
            $allWords = json_decode(DEFAULT_WORDS, true);
        }
        
        // Shuffle and return random selection
        shuffle($allWords);
        return array_slice($allWords, 0, $count);
    }
    
    // Set the chosen word for the round
    public function setRoundWord($roomId, $word) {
        $room = $this->db->fetchOne(
            "SELECT timer_duration FROM game_rooms WHERE id = ?",
            [$roomId]
        );
        
        if (!$room) {
            return ['error' => 'Room not found'];
        }
        
        // Update room with word and start time
        $this->db->execute(
            "UPDATE game_rooms SET current_word = ?, round_start_time = ? WHERE id = ?",
            [$word, time(), $roomId]
        );
        
        // Create hint
        $hint = $this->createHint($word, 0);
        
        // Add state update
        $this->addStateUpdate($roomId, 'round_started', [
            'hint' => $hint,
            'timer' => $room['timer_duration']
        ]);
        
        return ['success' => true];
    }
    
    // Create hint from word based on progress
    public function createHint($word, $progress) {
        $hint = [];
        $wordLength = strlen($word);
        
        for ($i = 0; $i < $wordLength; $i++) {
            if ($word[$i] === ' ') {
                $hint[] = ' ';
            } else {
                $hint[] = '_';
            }
        }
        
        // Reveal letters based on progress
        if ($progress > 0.3) {
            $revealed = max(1, (int)(strlen(str_replace(' ', '', $word)) * 0.3));
            $indices = [];
            for ($i = 0; $i < $wordLength; $i++) {
                if ($word[$i] !== ' ' && $hint[$i] === '_') {
                    $indices[] = $i;
                }
            }
            shuffle($indices);
            for ($j = 0; $j < min($revealed, count($indices)); $j++) {
                $hint[$indices[$j]] = $word[$indices[$j]];
            }
        }
        
        if ($progress > 0.6) {
            $revealed = max(2, (int)(strlen(str_replace(' ', '', $word)) * 0.5));
            $indices = [];
            for ($i = 0; $i < $wordLength; $i++) {
                if ($word[$i] !== ' ' && $hint[$i] === '_') {
                    $indices[] = $i;
                }
            }
            shuffle($indices);
            for ($j = 0; $j < min($revealed, count($indices)); $j++) {
                $hint[$indices[$j]] = $word[$indices[$j]];
            }
        }
        
        return implode(' ', $hint);
    }
    
    // Calculate points based on guess time
    public function calculatePoints($guessTime, $totalTime) {
        $timeRatio = ($totalTime - $guessTime) / $totalTime;
        return (int)(POINTS_MIN + (POINTS_MAX - POINTS_MIN) * $timeRatio);
    }
    
    // Check if guess is correct
    public function checkGuess($roomId, $playerId, $guess) {
        $room = $this->db->fetchOne(
            "SELECT current_word, current_drawer_id, round_start_time, timer_duration 
             FROM game_rooms WHERE id = ?",
            [$roomId]
        );
        
        if (!$room || !$room['current_word']) {
            return ['error' => 'No active round'];
        }
        
        // Drawer can't guess
        if ($playerId == $room['current_drawer_id']) {
            return ['type' => 'drawer'];
        }
        
        // Check if already guessed
        $player = $this->db->fetchOne(
            "SELECT guessed FROM room_players WHERE room_id = ? AND player_id = ?",
            [$roomId, $playerId]
        );
        
        if ($player && $player['guessed']) {
            return ['type' => 'already_guessed'];
        }
        
        // Check if correct
        $guess = strtolower(trim($guess));
        $word = strtolower(trim($room['current_word']));
        
        if ($guess === $word) {
            // Calculate points
            $elapsedTime = time() - $room['round_start_time'];
            $points = $this->calculatePoints($elapsedTime, $room['timer_duration']);
            
            // Update player score and guessed status
            $this->db->execute(
                "UPDATE room_players SET score = score + ?, guessed = 1 WHERE room_id = ? AND player_id = ?",
                [$points, $roomId, $playerId]
            );
            
            // Update drawer bonus if all guessed
            $this->checkAllGuessed($roomId);
            
            return ['type' => 'correct', 'points' => $points];
        }
        
        // Check if close (for fun)
        $similarity = 0;
        similar_text($guess, $word, $similarity);
        if ($similarity > 70) {
            return ['type' => 'close'];
        }
        
        return ['type' => 'normal'];
    }
    
    // Check if all players have guessed
    private function checkAllGuessed($roomId) {
        $counts = $this->db->fetchOne(
            "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN guessed = 1 THEN 1 ELSE 0 END) as guessed,
                MAX(CASE WHEN player_id = (SELECT current_drawer_id FROM game_rooms WHERE id = ?) THEN 1 ELSE 0 END) as has_drawer
             FROM room_players 
             WHERE room_id = ? AND is_connected = 1",
            [$roomId, $roomId]
        );
        
        // If all non-drawer players have guessed, give drawer bonus
        if ($counts['total'] > 1 && $counts['guessed'] >= ($counts['total'] - $counts['has_drawer'])) {
            $room = $this->db->fetchOne(
                "SELECT current_drawer_id FROM game_rooms WHERE id = ?",
                [$roomId]
            );
            
            if ($room) {
                $this->db->execute(
                    "UPDATE room_players SET score = score + ? WHERE room_id = ? AND player_id = ?",
                    [DRAWER_BONUS, $roomId, $room['current_drawer_id']]
                );
            }
            
            // End round early
            $this->endRound($roomId);
        }
    }
    
    // End current round
    public function endRound($roomId) {
        $room = $this->db->fetchOne(
            "SELECT current_round, num_rounds, current_word FROM game_rooms WHERE id = ?",
            [$roomId]
        );
        
        if (!$room) {
            return ['error' => 'Room not found'];
        }
        
        // Add state update with word reveal
        $this->addStateUpdate($roomId, 'round_ended', ['word' => $room['current_word']]);
        
        // Check if game should end
        if ($room['current_round'] >= $room['num_rounds']) {
            return $this->endGame($roomId);
        }
        
        // Move to next round
        $this->db->execute(
            "UPDATE game_rooms SET current_round = current_round + 1, current_word = NULL WHERE id = ?",
            [$roomId]
        );
        
        // Start next round
        return $this->startRound($roomId);
    }
    
    // End game
    public function endGame($roomId) {
        // Get final scores
        $scores = $this->db->fetchAll(
            "SELECT p.username, rp.score 
             FROM room_players rp 
             JOIN players p ON rp.player_id = p.id 
             WHERE rp.room_id = ? 
             ORDER BY rp.score DESC",
            [$roomId]
        );
        
        // Update game status
        $this->db->execute(
            "UPDATE game_rooms SET status = 'finished' WHERE id = ?",
            [$roomId]
        );
        
        // Update game history
        if (!empty($scores)) {
            $winner = $this->db->fetchOne(
                "SELECT player_id FROM room_players WHERE room_id = ? ORDER BY score DESC LIMIT 1",
                [$roomId]
            );
            
            $this->db->execute(
                "UPDATE game_history SET ended_at = NOW(), winner_id = ? WHERE room_id = ? AND ended_at IS NULL",
                [$winner['player_id'], $roomId]
            );
            
            // Update player statistics
            $this->updatePlayerStats($roomId);
        }
        
        // Add state update
        $this->addStateUpdate($roomId, 'game_ended', ['final_scores' => $scores]);
        
        return ['success' => true, 'scores' => $scores];
    }
    
    // Update player statistics after game
    private function updatePlayerStats($roomId) {
        $players = $this->db->fetchAll(
            "SELECT player_id, score, 
                    SUM(CASE WHEN guessed = 1 THEN 1 ELSE 0 END) as correct_guesses
             FROM room_players 
             WHERE room_id = ? 
             GROUP BY player_id, score",
            [$roomId]
        );
        
        // Get winner
        $winner = $this->db->fetchOne(
            "SELECT player_id FROM room_players WHERE room_id = ? ORDER BY score DESC LIMIT 1",
            [$roomId]
        );
        
        foreach ($players as $player) {
            $isWinner = ($player['player_id'] == $winner['player_id']) ? 1 : 0;
            
            $this->db->execute(
                "UPDATE players 
                 SET total_games = total_games + 1,
                     total_wins = total_wins + ?,
                     total_points = total_points + ?,
                     total_correct_guesses = total_correct_guesses + ?
                 WHERE id = ?",
                [$isWinner, $player['score'], $player['correct_guesses'], $player['player_id']]
            );
        }
    }
    
    // Add game state update
    public function addStateUpdate($roomId, $type, $data = []) {
        return $this->db->execute(
            "INSERT INTO game_state_updates (room_id, update_type, update_data) VALUES (?, ?, ?)",
            [$roomId, $type, json_encode($data)]
        );
    }
    
    // Get room info
    public function getRoomInfo($roomCode) {
        return $this->db->fetchOne(
            "SELECT * FROM game_rooms WHERE room_code = ?",
            [$roomCode]
        );
    }
    
    // Get room players
    public function getRoomPlayers($roomId) {
        return $this->db->fetchAll(
            "SELECT p.id, p.username, rp.score, rp.guessed, rp.is_connected,
                    (rp.player_id = g.current_drawer_id) as is_drawer
             FROM room_players rp
             JOIN players p ON rp.player_id = p.id
             JOIN game_rooms g ON rp.room_id = g.id
             WHERE rp.room_id = ? AND rp.is_connected = 1
             ORDER BY rp.score DESC",
            [$roomId]
        );
    }
}
