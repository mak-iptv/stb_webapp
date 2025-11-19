<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Jo i autorizuar']);
    exit;
}

try {
    $channels = getChannelsFromProvider();
    
    echo json_encode([
        'success' => true,
        'channels' => $channels,
        'total' => count($channels),
        'provider' => IPTV_PROVIDER_URL
    ]);
    
} catch (Exception $e) {
    error_log("Channels API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Gabim në marrjen e kanaleve',
        'channels' => getDemoChannels()
    ]);
}
?>
