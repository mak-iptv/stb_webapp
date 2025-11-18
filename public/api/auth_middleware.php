<?php
// Very small middleware - DEMO ONLY
// In production, replace with proper JWT/session handling and DB checks.

$valid_demo_token = 'demo-token-123';

$provided = '';
// Header check
if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) {
    $provided = $_SERVER['HTTP_X_AUTH_TOKEN'];
} elseif (!empty($_GET['token'])) {
    $provided = $_GET['token'];
}

if ($provided !== $valid_demo_token) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>'Unauthorized - invalid token']);
    exit;
}
