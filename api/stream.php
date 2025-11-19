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
    // Merr të gjitha kanalet për të gjetur stream URL
    $channels = getChannelsFromProvider();
    $channel_data = null;
    
    foreach ($channels as $channel) {
        if ($channel['id'] == $channel_id) {
            $channel_data = $channel;
            break;
        }
    }
    
    if ($channel_data && !empty($channel_data['stream_url'])) {
        $stream_url = $channel_data['stream_url'];
        
        // Nëse provideri kërkon token
        if (strpos($stream_url, 'token') === false) {
            $stream_url .= '?token=' . generatePlayToken($channel_id);
        }
        
        echo json_encode([
            'success' => true,
            'stream_url' => $stream_url,
            'channel_id' => $channel_id,
            'channel_name' => $channel_data['name']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Stream nuk u gjet për këtë kanal'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Stream API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Gabim në marrjen e stream-it'
    ]);
}
?>
