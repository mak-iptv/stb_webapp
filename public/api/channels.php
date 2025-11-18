<?php
// channels.php - returns JSON list of channels
require_once __DIR__ . '/auth_middleware.php';

$channels = [
    ['id'=>1, 'name'=>'Demo Channel (HLS)', 'stream_url'=>'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8', 'logo'=>'/assets/logo.png', 'category'=>'Demo'],
    // Add your own channels here
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode(['channels'=>$channels], JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);
