<?php
/**
 * Merr konfigurimin nga session
 */
function getStalkerConfig() {
    if (!isset($_SESSION['portal_url']) || !isset($_SESSION['mac_address']) || 
        !isset($_SESSION['username']) || !isset($_SESSION['password'])) {
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
    if (!$config) return null;
    
    $url = $config['portal_url'];
    $port = $config['portal_port'];
    
    // Shto port nëse është specifikuar dhe nuk është 80
    if (!empty($port) && $port !== '80') {
        // Kontrollo nëse URL ka tashmë port
        if (parse_url($url, PHP_URL_PORT) === null) {
            $url .= ':' . $port;
        }
    }
    
    return $url;
}

/**
 * Merr kanalet nga Stalker provideri
 */
function getChannelsFromProvider($force_refresh = false) {
    // Kontrollo nëse kemi konfigurim
    $config = getStalkerConfig();
    if (!$config) {
        return [];
    }
    
    // Memory cache për 5 minuta
    static $channels_cache = null;
    static $cache_time = 0;
    
    if (!$force_refresh && $channels_cache !== null && (time() - $cache_time) < 300) {
        return $channels_cache;
    }
    
    // Merr kanale nga Stalker API
    $channels = getChannelsFromStalkerAPI();
    
    if (!empty($channels)) {
        $channels_cache = $channels;
        $cache_time = time();
        return $channels;
    }
    
    return [];
}

/**
 * Merr kanalet nga Stalker Middleware API
 */
function getChannelsFromStalkerAPI() {
    $config = getStalkerConfig();
    if (!$config) return [];
    
    $portal_url = getPortalUrl();
    if (!$portal_url) return [];
    
    try {
        $api_url = $portal_url . '/server/load.php';
        
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
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || empty($response)) {
            error_log("API Error: HTTP $http_code");
            return [];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['js']['data'])) {
            return [];
        }
        
        return processStalkerChannels($data['js']['data']);
        
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

/**
 * Gjenero stream URL për Stalker format
 */
function getStreamUrl($channel_data) {
    $config = getStalkerConfig();
    $portal_url = getPortalUrl();
    
    if (!$config || !$portal_url) {
        return '';
    }
    
    $stream_id = $channel_data['stream_id'];
    
    $stream_url = $portal_url . '/play/live.php?' . http_build_query([
        'mac' => $config['mac_address'],
        'stream' => $stream_id,
        'extension' => 'm3u8',
        'play_token' => generateStalkerToken($stream_id, $config),
        'sn2' => generateSerialNumber($config['mac_address']),
        'type' => 'm3u8'
    ]);
    
    return $stream_url;
}

/**
 * Gjenero token për Stalker
 */
function generateStalkerToken($stream_id, $config) {
    $timestamp = time();
    $token_data = $config['username'] . $config['password'] . $stream_id . $timestamp;
    return substr(md5($token_data), 0, 10);
}

/**
 * Gjenero serial number për Stalker
 */
function generateSerialNumber($mac_address) {
    return substr(md5($mac_address . time()), 0, 12);
}
?>
