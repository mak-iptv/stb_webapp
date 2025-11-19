<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Jo i autorizuar']);
    exit;
}

if (!isset($_GET['channel_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Channel ID kërkohet']);
    exit;
}

$channel_id = intval($_GET['channel_id']);

try {
    // Merr të gjitha kanalet
    $channels = getChannelsFromProvider();
    $channel_data = null;
    
    // Gjej channel-in e zgjedhur
    foreach ($channels as $channel) {
        if ($channel['id'] == $channel_id) {
            $channel_data = $channel;
            break;
        }
    }
    
    if (!$channel_data) {
        throw new Exception('Kanali nuk u gjet në listë');
    }
    
    // Gjenero stream URL në format Stalker të saktë
    $stream_url = getStreamUrl($channel_data);
    
    // Përgatit përgjigjen
    $response = [
        'success' => true,
        'data' => [
            'stream_url' => $stream_url,
            'channel_id' => $channel_id,
            'stream_id' => $channel_data['stream_id'],
            'channel_name' => $channel_data['name'],
            'format' => 'hls',
            'player_type' => 'hls',
            'extension' => 'm3u8'
        ],
        'stream_info' => [
            'protocol' => 'hls',
            'requires_hls_js' => true,
            'is_live' => true,
            'url_format' => 'stalker_middleware'
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    
    error_log("Stream API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Gabim në gjenerimin e stream URL',
        'error' => $e->getMessage(),
        'error_code' => 'STREAM_GENERATION_ERROR'
    ]);
}
?>
