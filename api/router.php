<?php
require_once '../config.php';

$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$parts = explode('/', $path);

// Simple routing
if (count($parts) >= 3 && $parts[1] == 'api') {
    $endpoint = $parts[2];
    
    switch ($endpoint) {
        case 'channels':
            require 'channels.php';
            break;
        case 'stream':
            require 'stream.php';
            break;
        case 'auth':
            require 'auth.php';
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'API endpoint not found']);
            break;
    }
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
}
?>
