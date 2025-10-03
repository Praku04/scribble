<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

$session = new SessionManager();
$db = Database::getInstance();

$playerId = $session->getPlayerId();

if (!$playerId) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$stats = $db->fetchOne(
    "SELECT total_games, total_wins, total_points, total_correct_guesses, total_drawings 
     FROM players 
     WHERE id = ?",
    [$playerId]
);

echo json_encode($stats ? $stats : []);
