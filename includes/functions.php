<?php
/**
 * Merr konfigurimin nga session
 */
function getStalkerConfig() {
    if (!isset($_SESSION['portal_url']) || !isset($_SESSION['mac_address']) || 
        !isset($_SESSION['username']) || !isset($_SESSION['password'])) {
        error_log("Missing session configuration");
        return null;
    }
    
    return [
        'portal_url' => $_SESSION['portal_url'],
        'portal_port' => $_SESSION['portal_port'] ?? '80',
        'mac_address' => $_SESSION['mac_address'],
        'username' => $_SESSION['username'],
        'password' => $_SESSION['password']
    ];
}

/**
 * Merr URL të plotë të portalit
 */
function getPortalUrl() {
    $config = getStalkerConfig();
    if (!$config) {
        error_log("No configuration available");
        return null;
    }
    
    $url = $config['portal_url'];
    $port = $config['portal_port'];
    
    // Shto http:// nëse nuk ka
    if (!preg_match('/^https?:\/\//', $url)) {
        $url = 'http://' . $url;
    }
    
    // Shto port nëse është specifikuar dhe nuk është 80
    if (!empty($port) && $port !== '80') {
        // Kontrollo nëse URL ka tashmë port
        $parsed = parse_url($url);
        if (!isset($parsed['port'])) {
            $url .= ':' . $port;
        }
    }
    
    error_log("Generated portal URL: " . $url);
    return $url;
}

/**
 * Merr kanalet nga Stalker provideri
 */
function getChannelsFromProvider($force_refresh = false) {
    error_log("Getting channels from provider");
    
    // Kontrollo nëse kemi konfigurim
    $config = getStalkerConfig();
    if (!$config) {
        error_log("No configuration available for provider");
        return [];
    }
    
    // Memory cache për 2 minuta
    static $channels_cache = null;
    static $cache_time = 0;
    
    if (!$force_refresh && $channels_cache !== null && (time() - $cache_time) < 120) {
        error_log("Returning cached channels");
        return $channels_cache;
    }
    
    // Merr kanale nga Stalker API
    $channels = getChannelsFromStalkerAPI();
    
    if (!empty($channels)) {
        error_log("Successfully got " . count($channels) . " channels from API");
        $channels_cache = $channels;
        $cache_time = time();
        return $channels;
    }
    
    error_log("No channels received from API");
    return [];
}

/**
 * Merr kanalet nga Stalker Middleware API
 */
function getChannelsFromStalkerAPI() {
    $config = getStalkerConfig();
    if (!$config) {
        error_log("No config for API call");
        return [];
    }
    
    $portal_url = getPortalUrl();
    if (!$portal_url) {
        error_log("No portal URL for API call");
        return [];
    }
    
    try {
        $api_url = $portal_url . '/server/load.php';
        error_log("Calling Stalker API: " . $api_url);
        
        $post_data = [
            'type' => 'stb',
            'action' => 'get_all_channels',
            'mac' => $config['mac_address'],
            'JsHttpRequest' => '1-xml'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        error_log("API Response - HTTP Code: $http_code, Error: $error");
        
        if ($http_code !== 200 || empty($response)) {
            error_log("API call failed - HTTP $http_code: $error");
            return [];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log("JSON decode error: " . json_last_error_msg());
            return [];
        }
        
        if (!isset($data['js']['data'])) {
            error_log("Invalid API response structure");
            return [];
        }
        
        $channels = processStalkerChannels($data['js']['data']);
        error_log("Processed " . count($channels) . " channels from API");
        return $channels;
        
    } catch (Exception $e) {
        error_log("Stalker API Exception: " . $e->getMessage());
        return [];
    }
}

/**
 * Process kanalet nga Stalker API
 */
function processStalkerChannels($raw_channels) {
    $portal_url = getPortalUrl();
    $channels = [];
    
    foreach ($raw_channels as $channel) {
        if (empty($channel['id']) || empty($channel['name'])) {
            continue;
        }
        
        $channels[] = [
            'id' => (int)$channel['id'],
            'stream_id' => (int)$channel['id'],
            'name' => trim($channel['name']),
            'number' => isset($channel['number']) ? (int)$channel['number'] : 0,
            'category' => $channel['cat_name'] ?? $channel['category'] ?? 'General',
            'logo' => getStalkerLogoUrl($channel['logo'] ?? $channel['tv_icon_url'] ?? ''),
            'cmd' => $channel['cmd'] ?? ''
        ];
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
    if (empty($logo_path)) return '';
    
    $portal_url = getPortalUrl();
    if (!$portal_url) return '';
    
    if (strpos($logo_path, 'http') === 0) {
        return $logo_path;
    }
    
    if (strpos($logo_path, '/') === 0) {
        return $portal_url . $logo_path;
    }
    
    return $portal_url . '/misc/logos/320/' . $logo_path;
}
?>
