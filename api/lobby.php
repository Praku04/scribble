<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

$db = Database::getInstance();

// Get public rooms that are in waiting status
$rooms = $db->fetchAll(
    "SELECT gr.room_code, gr.num_rounds, gr.timer_duration,
            p.username as host,
            (SELECT COUNT(*) FROM room_players WHERE room_id = gr.id AND is_connected = 1) as players
     FROM game_rooms gr
     LEFT JOIN players p ON gr.host_id = p.id
     WHERE gr.status = 'waiting' AND gr.is_public = 1
     ORDER BY gr.created_at DESC
     LIMIT 20"
);

echo json_encode($rooms);
