<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/session.php';

header('Content-Type: application/json');

$session = new SessionManager();
$token = $session->generateCSRFToken();

echo json_encode(['token' => $token]);
