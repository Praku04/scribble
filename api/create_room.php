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

if (!$data || !isset($data['username'])) {
    echo json_encode(['error' => 'Username required']);
    exit;
}

// Create or get player
$result = $session->getOrCreatePlayer($data['username']);
if (isset($result['error'])) {
    echo json_encode($result);
    exit;
}

$playerId = $result['player_id'];

// Get room settings
$rounds = isset($data['rounds']) ? (int)$data['rounds'] : DEFAULT_ROUNDS;
$timer = isset($data['timer']) ? (int)$data['timer'] : DEFAULT_TIMER;
$customWords = isset($data['custom_words']) ? $data['custom_words'] : [];

// Validate
$rounds = max(1, min($rounds, MAX_ROUNDS));
$timer = max(MIN_TIMER, min($timer, MAX_TIMER));

// Create room
$result = $game->createRoom($playerId, $rounds, $timer, $customWords);

if (isset($result['error'])) {
    echo json_encode($result);
    exit;
}

// Set room in session
$session->setRoom($result['room_code']);

// Get room info
$roomInfo = $game->getRoomInfo($result['room_code']);
$players = $game->getRoomPlayers($result['room_id']);

echo json_encode([
    'success' => true,
    'room_code' => $result['room_code'],
    'room_id' => $result['room_id'],
    'settings' => [
        'rounds' => $roomInfo['num_rounds'],
        'timer' => $roomInfo['timer_duration']
    ],
    'players' => $players,
    'is_host' => true
]);
