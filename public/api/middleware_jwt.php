<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use App\Jwt;

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = null;
if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
    $token = $matches[1];
} elseif (!empty($_GET['token'])) {
    $token = $_GET['token'];
}

$payload = null;
if ($token) {
    $payload = Jwt::validate($token);
}

if (!$payload) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error'=>'Unauthorized - invalid or missing token']);
    exit;
}

// expose $current_user
$current_user = $payload;
