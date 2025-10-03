<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

$db = Database::getInstance();

$achievements = $db->fetchAll(
    "SELECT id, name, description, icon, requirement_type, requirement_value, points_reward 
     FROM achievements 
     ORDER BY requirement_value ASC"
);

echo json_encode($achievements);
