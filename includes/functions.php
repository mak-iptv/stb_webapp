<?php
function getChannelsFromProvider() {
    $cache_file = CACHE_DIR . '/channels_cache.json';
    $cache_time = 300; // 5 minuta
    
    // Kontrollo cache fillimisht
    if (CACHE_ENABLED && file_exists($cache_file) && 
        (time() - filemtime($cache_file)) < $cache_time) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    // Provo të marrësh kanale nga provideri
    $channels = getChannelsFromAPI();
    
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

function getChannelsFromAPI() {
    try {
        // Metoda 1: Xtreme Codes API
        $channels = getChannelsFromXtremeCodes();
        if (!empty($channels)) return $channels;
        
        // Metoda 2: Stalker Middleware API
        $channels = getChannelsFromStalker();
        if (!empty($channels)) return $channels;
        
        // Metoda 3: M3U Playlist
        $channels = getChannelsFromM3U();
        if (!empty($channels)) return $channels;
        
    } catch (Exception $e) {
        error_log("Provider API Error: " . $e->getMessage());
    }
    
    return [];
}

// Metoda 1: Xtreme Codes API
function getChannelsFromXtremeCodes() {
    $api_url = PROVIDER_API_CHANNELS . '?action=get_live_streams';
    
    $context = stream_context_create([
        'http' => [
            'header' => "Authorization: Basic " . base64_encode(IPTV_USERNAME . ":" . IPTV_PASSWORD)
        ]
    ]);
    
    $response = @file_get_contents($api_url, false, $context);
    
    if ($response === FALSE) return [];
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        return [];
    }
    
    $channels = [];
    foreach ($data as $stream) {
        $channels[] = [
            'id' => $stream['stream_id'] ?? $stream['num'] ?? count($channels) + 1,
            'stream_id' => $stream['stream_id'] ?? $stream['num'] ?? null,
            'name' => $stream['name'] ?? 'Unknown Channel',
            'category' => $stream['category_name'] ?? $stream['category_id'] ?? 'General',
            'logo' => $stream['stream_icon'] ?? '',
            'stream_url' => $stream['stream_url'] ?? '',
            'epg_channel_id' => $stream['epg_channel_id'] ?? null
        ];
    }
    
    return $channels;
}

// Metoda 2: Stalker Middleware
function getChannelsFromStalker() {
    $api_url = IPTV_PROVIDER_URL . '/server/load.php';
    
    $post_data = [
        'type' => 'stb',
        'action' => 'get_all_channels',
        'mac' => IPTV_MAC_ADDRESS,
        'auth' => md5(IPTV_USERNAME . IPTV_PASSWORD)
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post_data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || empty($response)) {
        return [];
    }
    
    $data = json_decode($response, true);
    
    if (empty($data['js']) || !is_array($data['js'])) {
        return [];
    }
    
    $channels = [];
    foreach ($data['js'] as $channel) {
        $channels[] = [
            'id' => $channel['id'] ?? count($channels) + 1,
            'stream_id' => $channel['id'] ?? null,
            'name' => $channel['name'] ?? 'Unknown Channel',
            'category' => $channel['category'] ?? $channel['cat_name'] ?? 'General',
            'logo' => $channel['logo'] ?? $channel['logo_small'] ?? '',
            'stream_url' => $channel['cmd'] ?? '',
            'number' => $channel['number'] ?? null
        ];
    }
    
    return $channels;
}

// Metoda 3: M3U Playlist
function getChannelsFromM3U() {
    $m3u_url = IPTV_PROVIDER_URL . '/get.php?username=' . IPTV_USERNAME . 
               '&password=' . IPTV_PASSWORD . '&type=m3u_plus&output=ts';
    
    $response = @file_get_contents($m3u_url);
    
    if ($response === FALSE) return [];
    
    return parseM3UContent($response);
}

function parseM3UContent($m3u_content) {
    $channels = [];
    $lines = explode("\n", $m3u_content);
    
    $current_channel = null;
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        if (strpos($line, '#EXTINF:') === 0) {
            // Extract channel info from EXTINF line
            preg_match('/#EXTINF:.*?,(.*)/', $line, $name_matches);
            preg_match('/tvg-id="(.*?)"/', $line, $id_matches);
            preg_match('/tvg-name="(.*?)"/', $line, $tvg_name_matches);
            preg_match('/tvg-logo="(.*?)"/', $line, $logo_matches);
            preg_match('/group-title="(.*?)"/', $line, $group_matches);
            
            $current_channel = [
                'id' => $id_matches[1] ?? count($channels) + 1,
                'stream_id' => $id_matches[1] ?? null,
                'name' => $tvg_name_matches[1] ?? $name_matches[1] ?? 'Unknown Channel',
                'category' => $group_matches[1] ?? 'General',
                'logo' => $logo_matches[1] ?? '',
                'stream_url' => ''
            ];
            
        } elseif (!empty($line) && strpos($line, '#') !== 0 && $current_channel) {
            // This is the stream URL line
            $current_channel['stream_url'] = $line;
            $channels[] = $current_channel;
            $current_channel = null;
        }
    }
    
    return $channels;
}

// Gjenero stream URL për provider të ndryshëm
function getStreamUrl($channel_data) {
    $stream_id = $channel_data['stream_id'] ?? $channel_data['id'];
    
    // Format të ndryshëm për provider të ndryshëm
    if (strpos(IPTV_PROVIDER_URL, 'xtream') !== false) {
        // Xtreme Codes format
        return IPTV_PROVIDER_URL . '/live/' . IPTV_USERNAME . '/' . IPTV_PASSWORD . '/' . $stream_id . '.ts';
        
    } elseif (strpos(IPTV_PROVIDER_URL, 'stalker') !== false) {
        // Stalker Middleware format
        return IPTV_PROVIDER_URL . '/live/' . IPTV_USERNAME . '/' . IPTV_PASSWORD . '/' . $stream_id;
        
    } elseif (!empty($channel_data['stream_url'])) {
        // Përdor stream_url direkt nga M3U
        return $channel_data['stream_url'];
        
    } else {
        // Format default
        return IPTV_PROVIDER_URL . '/live/' . IPTV_USERNAME . '/' . IPTV_PASSWORD . '/' . $stream_id;
    }
}

function getDemoChannels() {
    return [
        ['id' => 1, 'stream_id' => 'rtsh1', 'name' => 'RTSH 1', 'category' => 'Lajme', 'logo' => '', 'stream_url' => ''],
        ['id' => 2, 'stream_id' => 'rtsh2', 'name' => 'RTSH 2', 'category' => 'Argëtim', 'logo' => '', 'stream_url' => ''],
        ['id' => 3, 'stream_id' => 'topchannel', 'name' => 'Top Channel', 'category' => 'General', 'logo' => '', 'stream_url' => ''],
        ['id' => 4, 'stream_id' => 'klan', 'name' => 'Klan TV', 'category' => 'General', 'logo' => '', 'stream_url' => ''],
    ];
}

function verifyUserCredentials($username, $password) {
    $valid_users = [
        'demo' => 'demo',
        IPTV_USERNAME => IPTV_PASSWORD // Kredenciale reale nga provideri
    ];
    
    return isset($valid_users[$username]) && $valid_users[$username] === $password;
}
?>
