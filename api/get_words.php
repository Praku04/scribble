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

// Get random words
$words = $game->getRandomWords($roomInfo['id'], 3);

echo json_encode(['success' => true, 'words' => $words]);
