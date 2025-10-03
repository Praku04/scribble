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

if (!$data || !isset($data['word'])) {
    echo json_encode(['error' => 'Word required']);
    exit;
}

$playerId = $session->getPlayerId();
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

// Check if this player is the current drawer
if ($roomInfo['current_drawer_id'] != $playerId) {
    echo json_encode(['error' => 'Not your turn to draw']);
    exit;
}

// Set word
$result = $game->setRoundWord($roomInfo['id'], $data['word']);

echo json_encode($result);
