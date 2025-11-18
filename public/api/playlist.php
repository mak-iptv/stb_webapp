<?php
require_once __DIR__ . '/middleware_jwt.php';
require_once __DIR__ . '/../../vendor/autoload.php';
use App\DB;

$pdo = DB::get();
$stmt = $pdo->query('SELECT id, name, stream_url, logo FROM channels WHERE is_active=1');
$channels = $stmt->fetchAll();

header('Content-Type: audio/x-mpegurl; charset=utf-8');
echo "#EXTM3U\n";
foreach ($channels as $ch) {
    $token = '';
    if (!empty($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) $token = $m[1];
    }
    $url = $ch['stream_url'] . (strpos($ch['stream_url'], '?') ? '&' : '?') . 'token=' . urlencode($token);
    echo "#EXTINF:-1 tvg-id="{$ch['id']}" tvg-logo="{$ch['logo']}",{$ch['name']}\n";
    echo $url . "\n";
}
