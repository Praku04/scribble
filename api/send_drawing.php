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

if (!$data || !isset($data['stroke_data'])) {
    echo json_encode(['error' => 'Drawing data required']);
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

// Store drawing data
$db = Database::getInstance();
$result = $db->execute(
    "INSERT INTO drawing_data (room_id, round_number, stroke_data) VALUES (?, ?, ?)",
    [$roomInfo['id'], $roomInfo['current_round'], json_encode($data['stroke_data'])]
);

// Add state update for other players
$game->addStateUpdate($roomInfo['id'], 'draw', $data['stroke_data']);

echo json_encode(['success' => true]);
