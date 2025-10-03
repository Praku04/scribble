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

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['username']) || !isset($data['room_code'])) {
    echo json_encode(['error' => 'Username and room code required']);
    exit;
}

// Create or get player
$result = $session->getOrCreatePlayer($data['username']);
if (isset($result['error'])) {
    echo json_encode($result);
    exit;
}

$playerId = $result['player_id'];
$roomCode = strtoupper(trim($data['room_code']));

// Join room
$result = $game->joinRoom($playerId, $roomCode);

if (isset($result['error'])) {
    echo json_encode($result);
    exit;
}

// Set room in session
$session->setRoom($roomCode);

// Get room info
$roomInfo = $game->getRoomInfo($roomCode);
$players = $game->getRoomPlayers($result['room_id']);

// Check if host
$isHost = ($roomInfo['host_id'] == $playerId);

echo json_encode([
    'success' => true,
    'room_code' => $roomCode,
    'room_id' => $result['room_id'],
    'settings' => [
        'rounds' => $roomInfo['num_rounds'],
        'timer' => $roomInfo['timer_duration']
    ],
    'players' => $players,
    'is_host' => $isHost
]);
