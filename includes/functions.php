<?php
/**
 * Merr kanalet nga Stalker provideri
 */
function getChannelsFromProvider($force_refresh = false) {
    $cache_file = CACHE_DIR . '/channels_cache.json';
    $cache_time = 300; // 5 minuta cache
    
    // Kontrollo cache nëse nuk kërkohet refresh
    if (!$force_refresh && CACHE_ENABLED && file_exists($cache_file) && 
        (time() - filemtime($cache_file)) < $cache_time) {
        
        $cached_data = json_decode(file_get_contents($cache_file), true);
        if ($cached_data && !empty($cached_data)) {
            error_log("📦 Using cached channels data");
            return $cached_data;
        }
    }
    
    // Provo të marrësh kanale nga Stalker API
    $channels = getChannelsFromStalkerAPI();
    
    if (!empty($channels)) {
        // Ruaj në cache
        if (CACHE_ENABLED) {
            file_put_contents($cache_file, json_encode($channels, JSON_UNESCAPED_UNICODE));
            error_log("💾 Channels cached successfully: " . count($channels) . " channels");
        }
        return $channels;
    }
    
    // Fallback në kanale demo nëse API dështon
    error_log("❌ Provider API failed, using demo channels");
    return getDemoChannels();
}

/**
 * Merr kanalet nga Stalker Middleware API
 */
function getChannelsFromStalkerAPI() {
    $start_time = microtime(true);
    
    try {
        // URL e API-s së Stalker
        $api_url = STALKER_PORTAL_URL . '/server/load.php';
        
        // Të dhënat e kërkesës për Stalker
        $post_data = [
            'type' => 'stb',
            'action' => 'get_all_channels',
            'mac' => STALKER_MAC_ADDRESS,
            'JsHttpRequest' => '1-xml'
        ];
        
        // Konfigurimi i cURL
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Referer: ' . STALKER_PORTAL_URL . '/c/',
                'X-User-Agent: stalker-portal-client'
            ],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3
        ]);
        
        // Ekzekuto kërkesën
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $total_time = curl_getinfo($ch, CURLINFO_TOTAL_TIME);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Log kohën e ekzekutimit
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        error_log("🌐 Stalker API Call: {$http_code} - {$execution_time}ms");
        
        // Kontrollo përgjigjen HTTP
        if ($http_code !== 200) {
            error_log("❌ Stalker API HTTP Error: {$http_code} - {$curl_error}");
            return [];
        }
        
        if (empty($response)) {
            error_log("❌ Stalker API returned empty response");
            return [];
        }
        
        // Provo të parse JSON response
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("❌ JSON Parse Error: " . json_last_error_msg());
            error_log("📄 Raw response: " . substr($response, 0, 500));
            return [];
        }
        
        // Kontrollo strukturën e përgjigjes
        if (!isset($data['js']['data']) || !is_array($data['js']['data'])) {
            error_log("❌ Invalid API response structure");
            error_log("📊 Response keys: " . implode(', ', array_keys($data)));
            return [];
        }
        
        // Process kanalet
        $channels = processStalkerChannels($data['js']['data']);
        
        error_log("✅ Successfully fetched " . count($channels) . " channels from Stalker");
        return $channels;
        
    } catch (Exception $e) {
        error_log("❌ Stalker API Exception: " . $e->getMessage());
        return [];
    }
}

/**
 * Process kanalet nga Stalker API
 */
function processStalkerChannels($raw_channels) {
    $channels = [];
    
    foreach ($raw_channels as $channel) {
        // Kontrollo nëse kanali ka të dhëna minimale
        if (empty($channel['id']) || empty($channel['name'])) {
            continue;
        }
        
        // Përgatit të dhënat e kanalit
        $processed_channel = [
            'id' => (int)$channel['id'],
            'stream_id' => (int)$channel['id'],
            'name' => trim($channel['name']),
            'number' => isset($channel['number']) ? (int)$channel['number'] : 0,
            'category' => $channel['cat_name'] ?? $channel['category'] ?? 'General',
            'logo' => getStalkerLogoUrl($channel['logo'] ?? $channel['tv_icon_url'] ?? ''),
            'cmd' => $channel['cmd'] ?? '',
            'is_radio' => isset($channel['is_radio']) ? (bool)$channel['is_radio'] : false,
            'is_hd' => isset($channel['hd']) ? (bool)$channel['hd'] : false,
            'languages' => $channel['languages'] ?? [],
            'countries' => $channel['countries'] ?? [],
            'added' => $channel['added'] ?? null,
            'edited' => $channel['edited'] ?? null
        ];
        
        // Rregullo emrin e kategorisë nëse është bosh
        if (empty($processed_channel['category'])) {
            $processed_channel['category'] = 'General';
        }
        
        $channels[] = $processed_channel;
    }
    
    // Sort kanalet sipas numrit
    usort($channels, function($a, $b) {
        return ($a['number'] ?? 9999) - ($b['number'] ?? 9999);
    });
    
    return $channels;
}

/**
 * Gjenero URL për logo nga Stalker
 */
function getStalkerLogoUrl($logo_path) {
    if (empty($logo_path)) {
        return '';
    }
    
    // Nëse logo ka URL të plotë
    if (strpos($logo_path, 'http') === 0) {
        return $logo_path;
    }
    
    // Nëse logo ka path relative që fillon me /
    if (strpos($logo_path, '/') === 0) {
        return STALKER_PORTAL_URL . $logo_path;
    }
    
    // Formatet e zakonshme të logo-ve në Stalker
    $logo_formats = [
        STALKER_PORTAL_URL . '/misc/logos/320/' . $logo_path,
        STALKER_PORTAL_URL . '/images/channels/' . $logo_path,
        STALKER_PORTAL_URL . '/logos/' . $logo_path,
        STALKER_PORTAL_URL . '/tv/' . $logo_path
    ];
    
    // Provo secilin format derisa të gjejë një që ekziston
    foreach ($logo_formats as $logo_url) {
        if (checkRemoteFile($logo_url)) {
            return $logo_url;
        }
    }
    
    // Kthe path-in origjinal në asnjë nuk punon
    return STALKER_PORTAL_URL . '/misc/logos/320/' . $logo_path;
}

/**
 * Kontrollo nëse një file ekziston në distancë
 */
function checkRemoteFile($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 200;
}

/**
 * Gjenero stream URL për Stalker format
 */
function getStreamUrl($channel_data) {
    $stream_id = $channel_data['stream_id'];
    
    // Format Stalker i saktë sipas shembullit të dhënë
    $stream_url = STALKER_PORTAL_URL . '/play/live.php?' . http_build_query([
        'mac' => STALKER_MAC_ADDRESS,
        'stream' => $stream_id,
        'extension' => 'm3u8',
        'play_token' => generateStalkerToken($stream_id),
        'sn2' => generateSerialNumber(),
        'type' => 'm3u8',
        'client_type' => 'html5',
        'player_type' => 'hls'
    ]);
    
    return $stream_url;
}

/**
 * Gjenero token për Stalker
 */
function generateStalkerToken($stream_id) {
    $timestamp = time();
    $token_data = STALKER_USERNAME . STALKER_PASSWORD . $stream_id . $timestamp;
    return substr(md5($token_data), 0, 10); // Stalker token zakonisht është 10 karaktere
}

/**
 * Gjenero serial number për Stalker
 */
function generateSerialNumber() {
    return substr(md5(STALKER_MAC_ADDRESS . time()), 0, 12);
}

/**
 * Testo lidhjen me Stalker provider
 */
function testStalkerConnection() {
    $start_time = microtime(true);
    
    try {
        $channels = getChannelsFromStalkerAPI();
        $execution_time = round((microtime(true) - $start_time) * 1000, 2);
        
        return [
            'success' => !empty($channels),
            'channels_count' => count($channels),
            'execution_time_ms' => $execution_time,
            'channels_sample' => array_slice($channels, 0, 5),
            'api_url' => STALKER_PORTAL_URL . '/server/load.php',
            'cache_info' => getCacheInfo('channels')
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage(),
            'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2)
        ];
    }
}

/**
 * Kanale demo për fallback
 */
function getDemoChannels() {
    return [
        [
            'id' => 9356,
            'stream_id' => 9356,
            'name' => 'RTSH 1',
            'number' => 1,
            'category' => 'Shqipëri',
            'logo' => '',
            'cmd' => '1',
            'is_radio' => false,
            'is_hd' => false
        ],
        [
            'id' => 9357,
            'stream_id' => 9357,
            'name' => 'RTSH 2',
            'number' => 2,
            'category' => 'Shqipëri',
            'logo' => '',
            'cmd' => '2',
            'is_radio' => false,
            'is_hd' => false
        ],
        [
            'id' => 262022,
            'stream_id' => 262022,
            'name' => 'Top Channel',
            'number' => 3,
            'category' => 'Shqipëri',
            'logo' => '',
            'cmd' => '3',
            'is_radio' => false,
            'is_hd' => true
        ],
        [
            'id' => 262023,
            'stream_id' => 262023,
            'name' => 'Klan TV',
            'number' => 4,
            'category' => 'Shqipëri',
            'logo' => '',
            'cmd' => '4',
            'is_radio' => false,
            'is_hd' => true
        ],
        [
            'id' => 262024,
            'stream_id' => 262024,
            'name' => 'Vizion Plus',
            'number' => 5,
            'category' => 'Shqipëri',
            'logo' => '',
            'cmd' => '5',
            'is_radio' => false,
            'is_hd' => true
        ]
    ];
}

/**
 * Verifikimi i kredencialeve të përdoruesit
 */
function verifyUserCredentials($username, $password) {
    $valid_users = [
        'demo' => 'demo',
        STALKER_USERNAME => STALKER_PASSWORD
    ];
    
    return isset($valid_users[$username]) && $valid_users[$username] === $password;
}
?>
