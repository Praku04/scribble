<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/session.php';
require_once '../includes/game.php';

header('Content-Type: application/json');

$session = new SessionManager();
$game = new GameLogic();

// Validate CSRF token
if (!$session->validateRequestCSRF()) {
    echo json_encode(['error' => 'Invalid security token']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['message'])) {
    echo json_encode(['error' => 'Message required']);
    exit;
}

$playerId = $session->getPlayerId();
$username = $session->getUsername();
$roomCode = $session->getRoomCode();

if (!$playerId || !$roomCode) {
    echo json_encode(['error' => 'Not in a room']);
    exit;
}

// Get room info
$roomInfo = $game->getRoomInfo($roomCode);

if (!$roomInfo) {
    echo json_encode(['error' => 'Room not found']);
    exit;
}

$message = trim($data['message']);

// Check guess
$guessResult = $game->checkGuess($roomInfo['id'], $playerId, $message);

$messageType = 'normal';
if (isset($guessResult['type'])) {
    $messageType = $guessResult['type'];
}

// Store message
$db = Database::getInstance();
$db->execute(
    "INSERT INTO chat_messages (room_id, player_id, username, message, message_type) VALUES (?, ?, ?, ?, ?)",
    [$roomInfo['id'], $playerId, $username, $message, $messageType]
);

// Add state update
if ($messageType === 'correct') {
    $players = $game->getRoomPlayers($roomInfo['id']);
    $game->addStateUpdate($roomInfo['id'], 'correct_guess', [
        'username' => $username,
        'player_id' => $playerId,
        'points' => $guessResult['points'],
        'players' => $players
    ]);
} else {
    $game->addStateUpdate($roomInfo['id'], 'chat_message', [
        'username' => $username,
        'message' => $message,
        'type' => $messageType
    ]);
}

echo json_encode(['success' => true, 'type' => $messageType]);
