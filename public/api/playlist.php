<?php
// playlist.php - generate simple M3U playlist (requires token)
require_once __DIR__ . '/auth_middleware.php';

$channels = [
    ['id'=>1, 'name'=>'Demo Channel (HLS)', 'stream_url'=>'https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8', 'logo'=>'/assets/logo.png', 'category'=>'Demo'],
];

header('Content-Type: audio/x-mpegurl; charset=utf-8');
echo "#EXTM3U\n";
foreach ($channels as $ch) {
    $url = $ch['stream_url'] . (strpos($ch['stream_url'], '?') ? '&' : '?') . 'token=' . urlencode(get_auth_token());
    echo "#EXTINF:-1 tvg-id="{$ch['id']}" tvg-logo="{$ch['logo']}",{$ch['name']}\n";
    echo $url . "\n";
}

function get_auth_token() {
    // auth middleware ensures token exists; prefer header or query
    if (!empty($_SERVER['HTTP_X_AUTH_TOKEN'])) return $_SERVER['HTTP_X_AUTH_TOKEN'];
    if (!empty($_GET['token'])) return $_GET['token'];
    return '';
}
