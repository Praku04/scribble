<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/session.php';
require_once '../includes/game.php';

header('Content-Type: application/json');

$session = new SessionManager();
$game = new GameLogic();

$playerId = $session->getPlayerId();
$roomCode = $session->getRoomCode();

if (!$playerId || !$roomCode) {
    echo json_encode(['error' => 'Not in a room']);
    exit;
}

// Update activity
$session->updatePlayerActivity($playerId);

// Get room info
$roomInfo = $game->getRoomInfo($roomCode);

if (!$roomInfo) {
    echo json_encode(['error' => 'Room not found']);
    exit;
}

// Get last update ID from request
$lastUpdateId = isset($_GET['last_id']) ? (int)$_GET['last_id'] : 0;

// Get new updates
$db = Database::getInstance();
$updates = $db->fetchAll(
    "SELECT id, update_type, update_data, UNIX_TIMESTAMP(created_at) as timestamp 
     FROM game_state_updates 
     WHERE room_id = ? AND id > ? 
     ORDER BY id ASC 
     LIMIT " . MAX_POLL_MESSAGES,
    [$roomInfo['id'], $lastUpdateId]
);

// Get current game state
$players = $game->getRoomPlayers($roomInfo['id']);

// Calculate timer
$timeRemaining = 0;
$progress = 0;
if ($roomInfo['round_start_time'] && $roomInfo['status'] === 'playing') {
    $elapsed = time() - $roomInfo['round_start_time'];
    $timeRemaining = max(0, $roomInfo['timer_duration'] - $elapsed);
    $progress = $elapsed / $roomInfo['timer_duration'];
    
    // Check if round should end
    if ($timeRemaining <= 0) {
        $game->endRound($roomInfo['id']);
        $roomInfo = $game->getRoomInfo($roomCode);
    }
}

// Get hint if not drawer
$hint = '';
if ($roomInfo['current_word'] && $playerId != $roomInfo['current_drawer_id']) {
    $hint = $game->createHint($roomInfo['current_word'], $progress);
} elseif ($roomInfo['current_word'] && $playerId == $roomInfo['current_drawer_id']) {
    $hint = 'Your word: ' . $roomInfo['current_word'];
}

// Get recent chat messages
$lastChatId = isset($_GET['last_chat_id']) ? (int)$_GET['last_chat_id'] : 0;
$chatMessages = $db->fetchAll(
    "SELECT id, username, message, message_type, UNIX_TIMESTAMP(created_at) as timestamp 
     FROM chat_messages 
     WHERE room_id = ? AND id > ? 
     ORDER BY id ASC 
     LIMIT " . MAX_POLL_MESSAGES,
    [$roomInfo['id'], $lastChatId]
);

// Get drawer username
$drawerUsername = '';
if ($roomInfo['current_drawer_id']) {
    $drawer = $db->fetchOne(
        "SELECT username FROM players WHERE id = ?",
        [$roomInfo['current_drawer_id']]
    );
    if ($drawer) {
        $drawerUsername = $drawer['username'];
    }
}

echo json_encode([
    'success' => true,
    'updates' => $updates,
    'chat_messages' => $chatMessages,
    'game_state' => [
        'status' => $roomInfo['status'],
        'current_round' => $roomInfo['current_round'],
        'total_rounds' => $roomInfo['num_rounds'],
        'time_remaining' => $timeRemaining,
        'hint' => $hint,
        'drawer' => $drawerUsername,
        'drawer_id' => $roomInfo['current_drawer_id'],
        'is_drawer' => ($playerId == $roomInfo['current_drawer_id']),
        'players' => $players
    ]
]);
