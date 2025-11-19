<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Jo i autorizuar']);
    exit;
}

if (!isset($_GET['channel_id'])) {
    echo json_encode(['success' => false, 'message' => 'Channel ID nuk është specifikuar']);
    exit;
}

$channelId = $_GET['channel_id'];
$userMac = getUserMac(); // Merr MAC nga session ose database

try {
    // Gjenero token (në varësi të implementimit të serverit)
    $token = generatePlayToken($userMac, $channelId);
    
    // Ndërto URL-në e stream-it
    $streamUrl = API_BASE_URL . "/player/live.php?" . http_build_query([
        'mac' => $userMac,
        'stream' => $channelId,
        'extension' => 'ts',
        'play_token' => $token,
        'type' => 'm3u8' // Ose ts direkt
    ]);
    
    // Për MPEG-TS direkt, përdor HLS wrapper
    $hlsStreamUrl = "api/hls_proxy.php?" . http_build_query([
        'stream_url' => base64_encode($streamUrl)
    ]);
    
    echo json_encode([
        'success' => true,
        'stream_url' => $hlsStreamUrl,
        'channel_id' => $channelId
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gabim në gjenerimin e stream: ' . $e->getMessage()
    ]);
}
?>