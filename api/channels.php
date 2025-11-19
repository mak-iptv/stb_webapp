<?php
require_once '../config.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET, POST');

// Kontrollo authentication
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false, 
        'message' => 'Jo i autorizuar. Ju lutem hyni përsëri.',
        'error_code' => 'UNAUTHORIZED'
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
        $channels = array_values($channels); // Reset keys
    }
    
    // Përgatit përgjigjen
    $response = [
        'success' => true,
        'data' => [
            'channels' => $channels,
            'total_count' => count($channels),
            'categories' => array_values(array_unique(array_column($channels, 'category'))),
            'cache_info' => [
                'cached' => !$force_refresh && isCached('channels'),
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ],
        'provider_info' => [
            'name' => 'Stalker Middleware',
            'url' => STALKER_PORTAL_URL,
            'total_channels' => count($channels)
        ]
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    
    error_log("Channels API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Gabim në marrjen e kanaleve nga provideri',
        'error' => $e->getMessage(),
        'error_code' => 'PROVIDER_ERROR',
        'fallback_data' => [
            'channels' => getDemoChannels(),
            'total_count' => count(getDemoChannels()),
            'is_fallback' => true
        ]
    ]);
}

/**
 * Kontrollo nëse të dhënat janë të cache-ruara
 */
function isCached($cache_key) {
    $cache_file = CACHE_DIR . '/' . $cache_key . '_cache.json';
    return file_exists($cache_file) && (time() - filemtime($cache_file)) < 300; // 5 minuta
}

/**
 * Merr informacion rreth cache
 */
function getCacheInfo($cache_key) {
    $cache_file = CACHE_DIR . '/' . $cache_key . '_cache.json';
    
    if (!file_exists($cache_file)) {
        return ['exists' => false];
    }
    
    return [
        'exists' => true,
        'created' => date('Y-m-d H:i:s', filemtime($cache_file)),
        'age_seconds' => time() - filemtime($cache_file),
        'size_bytes' => filesize($cache_file)
    ];
}
?>
