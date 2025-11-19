<?php
function getChannelsFromProvider() {
    $cache_file = CACHE_DIR . '/channels_cache.json';
    $cache_time = 300; // 5 minuta
    
    // Kontrollo cache fillimisht
    if (CACHE_ENABLED && file_exists($cache_file) && 
        (time() - filemtime($cache_file)) < $cache_time) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    // Merr kanale nga Stalker Middleware
    $channels = getChannelsFromStalker();
    
    if (!empty($channels)) {
        // Ruaj në cache
        if (CACHE_ENABLED) {
            file_put_contents($cache_file, json_encode($channels));
        }
        return $channels;
    }
    
    // Nëse dështon, kthe kanale demo
    return getDemoChannels();
}

function getChannelsFromStalker() {
    try {
        $api_url = STALKER_PORTAL_URL . '/server/load.php';
        
        $post_data = [
            'type' => 'stb',
            'action' => 'get_all_channels',
            'mac' => STALKER_MAC_ADDRESS,
            'JsHttpRequest' => '1-xml'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: ' . STALKER_PORTAL_URL . '/c/'
            ]
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code !== 200 || empty($response)) {
            error_log("Stalker API Error: HTTP $http_code - $curl_error");
            return [];
        }
        
        // Parse JSON response
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("Stalker JSON Error: " . json_last_error_msg());
            return [];
        }
        
        // Kontrollo strukturën e përgjigjes
        if (empty($data['js']['data'])) {
            error_log("Stalker API: No channels data found");
            return [];
        }
        
        $channels = [];
        foreach ($data['js']['data'] as $channel) {
            if (!empty($channel['id']) && !empty($channel['name'])) {
                $channels[] = [
                    'id' => $channel['id'],
                    'stream_id' => $channel['id'],
                    'name' => $channel['name'],
                    'number' => $channel['number'] ?? 0,
                    'category' => $channel['cat_name'] ?? $channel['category'] ?? 'General',
                    'logo' => getStalkerLogoUrl($channel['logo'] ?? $channel['tv_icon_url'] ?? ''),
                    'cmd' => $channel['cmd'] ?? '',
                    'is_radio' => $channel['is_radio'] ?? 0
                ];
            }
        }
        
        // Sort by channel number
        usort($channels, function($a, $b) {
            return ($a['number'] ?? 9999) - ($b['number'] ?? 9999);
        });
        
        return $channels;
        
    } catch (Exception $e) {
        error_log("Stalker API Exception: " . $e->getMessage());
        return [];
    }
}

function getStalkerLogoUrl($logo_path) {
    if (empty($logo_path)) return '';
    
    // Nëse logo ka URL të plotë
    if (strpos($logo_path, 'http') === 0) {
        return $logo_path;
    }
    
    // Nëse logo ka path relative
    if (strpos($logo_path, '/') === 0) {
        return STALKER_PORTAL_URL . $logo_path;
    }
    
    // Default logo path
    return STALKER_PORTAL_URL . '/misc/logos/320/' . $logo_path;
}

function getStreamUrl($channel_data) {
    $stream_id = $channel_data['stream_id'];
    
    // Format Stalker i saktë sipas shembullit tënd
    $stream_url = STALKER_PORTAL_URL . '/play/live.php?' . http_build_query([
        'mac' => STALKER_MAC_ADDRESS,
        'stream' => $stream_id,
        'extension' => 'ts',
        'play_token' => generateStalkerToken($stream_id),
        'type' => 'm3u8'
    ]);
    
    return $stream_url;
}

function generateStalkerToken($stream_id) {
    // Gjenero token sipas algoritmit të Stalker
    $timestamp = time();
    $token_data = STALKER_USERNAME . STALKER_PASSWORD . $stream_id . $timestamp;
    return md5($token_data);
}

// Funksion për të testuar lidhjen me Stalker
function testStalkerConnection() {
    try {
        $channels = getChannelsFromStalker();
        return [
            'success' => !empty($channels),
            'channels_count' => count($channels),
            'channels_sample' => array_slice($channels, 0, 5),
            'api_url' => STALKER_PORTAL_URL . '/server/load.php'
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

function getDemoChannels() {
    return [
        [
            'id' => 9356, 
            'stream_id' => 9356, 
            'name' => 'RTSH 1', 
            'number' => 1,
            'category' => 'Shqipëri', 
            'logo' => '',
            'cmd' => '1'
        ],
        [
            'id' => 9357, 
            'stream_id' => 9357, 
            'name' => 'RTSH 2', 
            'number' => 2,
            'category' => 'Shqipëri', 
            'logo' => '',
            'cmd' => '2'
        ],
        [
            'id' => 1234, 
            'stream_id' => 1234, 
            'name' => 'Top Channel', 
            'number' => 3,
            'category' => 'Shqipëri', 
            'logo' => '',
            'cmd' => '3'
        ],
    ];
}

function verifyUserCredentials($username, $password) {
    $valid_users = [
        'demo' => 'demo',
        STALKER_USERNAME => STALKER_PASSWORD
    ];
    
    return isset($valid_users[$username]) && $valid_users[$username] === $password;
}
?>
