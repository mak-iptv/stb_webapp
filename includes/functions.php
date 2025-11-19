<?php
/**
 * Merr kanalet nga Stalker provideri
 */
function getChannelsFromProvider($force_refresh = false) {
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
    
    // Nëse API dështon, kthe array bosh
    return [];
}

/**
 * Merr kanalet nga Stalker Middleware API
 */
function getChannelsFromStalkerAPI() {
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
            CURLOPT_TIMEOUT => 15,
            CURLOPT_USERAGENT => 'Mozilla/5.0 (QtEmbedded; U; Linux; C) AppleWebKit/533.3 (KHTML, like Gecko) MAG200 stbapp ver: 2 rev: 250 Safari/533.3'
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code !== 200 || empty($response)) {
            return [];
        }
        
        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['js']['data'])) {
            return [];
        }
        
        return processStalkerChannels($data['js']['data']);
        
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Process kanalet nga Stalker API
 */
function processStalkerChannels($raw_channels) {
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
    
    if (strpos($logo_path, 'http') === 0) {
        return $logo_path;
    }
    
    if (strpos($logo_path, '/') === 0) {
        return STALKER_PORTAL_URL . $logo_path;
    }
    
    return STALKER_PORTAL_URL . '/misc/logos/320/' . $logo_path;
}

/**
 * Gjenero stream URL për Stalker format
 */
function getStreamUrl($channel_data) {
    $stream_id = $channel_data['stream_id'];
    
    $stream_url = STALKER_PORTAL_URL . '/play/live.php?' . http_build_query([
        'mac' => STALKER_MAC_ADDRESS,
        'stream' => $stream_id,
        'extension' => 'm3u8',
        'play_token' => generateStalkerToken($stream_id),
        'sn2' => generateSerialNumber(),
        'type' => 'm3u8'
    ]);
    
    return $stream_url;
}

/**
 * Gjenero token për Stalker
 */
function generateStalkerToken($stream_id) {
    $timestamp = time();
    $token_data = STALKER_USERNAME . STALKER_PASSWORD . $stream_id . $timestamp;
    return substr(md5($token_data), 0, 10);
}

/**
 * Gjenero serial number për Stalker
 */
function generateSerialNumber() {
    return substr(md5(STALKER_MAC_ADDRESS . time()), 0, 12);
}

/**
 * Verifikimi i kredencialeve të përdoruesit - VETËM PROVIDERI
 */
function verifyUserCredentials($username, $password) {
    // KRAHASO DIRECT ME KREDENCIALET E PROVIDERIT
    return $username === STALKER_USERNAME && $password === STALKER_PASSWORD;
}
?>
