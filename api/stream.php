<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

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
        throw new Exception('Kanali nuk u gjet');
    }
    
    // Gjenero stream URL në format Stalker
    $stream_url = getStreamUrl($channel_data);
    
    echo json_encode([
        'success' => true,
        'stream_url' => $stream_url,
        'channel_id' => $channel_id,
        'stream_id' => $channel_data['stream_id'],
        'channel_name' => $channel_data['name'],
        'format' => 'mpegts',
        'player_url' => $stream_url // Për debug
    ]);
    
} catch (Exception $e) {
    error_log("Stream API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Gabim në marrjen e stream-it: ' . $e->getMessage()
    ]);
}
?>
