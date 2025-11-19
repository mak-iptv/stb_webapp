<?php
// Dërgo headers PARA çdo output!
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
header('Access-Control-Allow-Methods: GET, POST');

// Tani include config dhe functions
require_once '../config.php';
require_once '../includes/functions.php';

// Kontrollo authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Jo i autorizuar'
    ]);
    exit;
}

// Merr parametrat e kërkesës
$force_refresh = isset($_GET['force_refresh']) && $_GET['force_refresh'] === 'true';
$category_filter = $_GET['category'] ?? null;

try {
    // Merr kanalet nga provideri
    $channels = getChannelsFromProvider($force_refresh);
    
    // Apliko filtrin e kategorisë nëse është specifikuar
    if ($category_filter && $category_filter !== 'all') {
        $channels = array_filter($channels, function($channel) use ($category_filter) {
            return $channel['category'] === $category_filter;
        });
        $channels = array_values($channels);
    }
    
    // Përgatit përgjigjen
    $response = [
        'success' => true,
        'data' => [
            'channels' => $channels,
            'total_count' => count($channels),
            'categories' => array_values(array_unique(array_column($channels, 'category')))
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    
    echo json_encode([
        'success' => false,
        'message' => 'Gabim në marrjen e kanaleve',
        'fallback_data' => [
            'channels' => getDemoChannels(),
            'total_count' => count(getDemoChannels()),
            'is_fallback' => true
        ]
    ]);
}
?>
