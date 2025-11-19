<?php
function getChannelsFromProvider() {
    $cache_file = CACHE_DIR . '/channels_cache.json';
    $cache_time = 300; // 5 minutes
    
    // Check cache first
    if (CACHE_ENABLED && file_exists($cache_file) && 
        (time() - filemtime($cache_file)) < $cache_time) {
        return json_decode(file_get_contents($cache_file), true);
    }
    
    try {
        // API call to your IPTV provider
        $api_url = IPTV_PROVIDER_URL . '/player/api/channels.php';
        
        $post_data = [
            'username' => IPTV_USERNAME,
            'password' => IPTV_PASSWORD,
            'mac' => IPTV_MAC_ADDRESS,
            'type' => 'm3u_plus'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post_data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200 && !empty($response)) {
            $channels = parseM3UResponse($response);
            
            // Cache the result
            if (CACHE_ENABLED) {
                file_put_contents($cache_file, json_encode($channels));
            }
            
            return $channels;
        }
        
    } catch (Exception $e) {
        error_log("Provider API Error: " . $e->getMessage());
    }
    
    // Return demo channels if provider fails
    return getDemoChannels();
}

function parseM3UResponse($m3u_content) {
    $channels = [];
    $lines = explode("\n", $m3u_content);
    
    for ($i = 0; $i < count($lines); $i++) {
        if (strpos($lines[$i], '#EXTINF:') === 0) {
            $extinf = $lines[$i];
            $stream_url = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : '';
            
            // Extract channel name
            preg_match('/#EXTINF:.*?,(.*)/', $extinf, $matches);
            $channel_name = $matches[1] ?? 'Unknown Channel';
            
            // Extract logo if available
            preg_match('/tvg-logo="(.*?)"/', $extinf, $logo_matches);
            $logo = $logo_matches[1] ?? '';
            
            // Extract group
            preg_match('/group-title="(.*?)"/', $extinf, $group_matches);
            $category = $group_matches[1] ?? 'General';
            
            if (!empty($stream_url) && !empty($channel_name)) {
                $channels[] = [
                    'id' => count($channels) + 1,
                    'name' => htmlspecialchars($channel_name),
                    'category' => htmlspecialchars($category),
                    'logo' => $logo,
                    'stream_url' => $stream_url
                ];
            }
        }
    }
    
    return $channels;
}

function getStreamUrl($channel_id, $channel_data) {
    $token = generatePlayToken($channel_id);
    
    return IPTV_PROVIDER_URL . '/live/' . IPTV_USERNAME . '/' . IPTV_PASSWORD . '/' . $channel_id . '.ts?token=' . $token;
    
    // Ose nëse provideri përdor format tjetër:
    // return IPTV_PROVIDER_URL . '/player/live.php?stream=' . $channel_id . '&extension=ts&play_token=' . $token;
}

function generatePlayToken($channel_id) {
    return md5(IPTV_USERNAME . IPTV_PASSWORD . $channel_id . date('YmdH'));
}

function getDemoChannels() {
    return [
        ['id' => 1, 'name' => 'RTSH 1', 'category' => 'Lajme', 'logo' => '', 'stream_url' => ''],
        ['id' => 2, 'name' => 'RTSH 2', 'category' => 'Argëtim', 'logo' => '', 'stream_url' => ''],
        ['id' => 3, 'name' => 'Top Channel', 'category' => 'General', 'logo' => '', 'stream_url' => ''],
        ['id' => 4, 'name' => 'Klan TV', 'category' => 'General', 'logo' => '', 'stream_url' => ''],
        ['id' => 5, 'name' => 'Vizion Plus', 'category' => 'General', 'logo' => '', 'stream_url' => ''],
    ];
}

function verifyUserCredentials($username, $password) {
    // Këtu vendos kredencialet e vërteta të përdoruesit
    $valid_users = [
        'demo' => 'demo', // Për testim
        'username_your' => 'password_your' // Për providerin real
    ];
    
    return isset($valid_users[$username]) && $valid_users[$username] === $password;
}
?>
