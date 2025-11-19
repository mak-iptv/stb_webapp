<?php
// includes/functions.php

/**
 * Funksione për Stalker Player
 */

/**
 * Merr listen e kanaleve nga provider-i
 */
function getChannelsFromProvider($portal_url, $mac_address = null) {
    // Nëse nuk ka MAC, gjenero një
    if (!$mac_address) {
        $mac_address = generateMacAddress();
    }
    
    // Provoni API të ndryshme endpoint-et e zakonshme të Stalker Portal
    $endpoints = [
        '/api/channels.php',
        '/api/get_channels.php', 
        '/api/live.php',
        '/portal.php?type=itv'
    ];
    
    foreach ($endpoints as $endpoint) {
        $api_url = rtrim($portal_url, '/') . $endpoint . '?mac=' . $mac_address;
        
        $channels = tryApiCall($api_url);
        if ($channels) {
            return $channels;
        }
    }
    
    // Nëse të gjitha API-t dështojnë, kthe kanale demo
    return getDemoChannels();
}

/**
 * Provon të bëjë thirrje API
 */
function tryApiCall($api_url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $api_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; StalkerClient)'
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && !empty($response)) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($data)) {
            return $data;
        }
        
        // Provo të parse-sh si XML nëse JSON dështon
        if (strpos($response, '<?xml') !== false) {
            return parseXmlChannels($response);
        }
    }
    
    return false;
}

/**
 * Parse XML response për kanale
 */
function parseXmlChannels($xml_string) {
    try {
        $xml = simplexml_load_string($xml_string);
        $channels = [];
        
        if ($xml && isset($xml->channel)) {
            foreach ($xml->channel as $channel) {
                $channels[] = [
                    'id' => (string)$channel->id,
                    'name' => (string)$channel->name,
                    'stream_id' => (string)$channel->stream_id,
                    'category' => (string)$channel->category,
                    'logo' => (string)$channel->logo
                ];
            }
        }
        
        return !empty($channels) ? $channels : false;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Gjeneron MAC address
 */
function generateMacAddress() {
    if (isset($_SESSION['user_mac'])) {
        return $_SESSION['user_mac'];
    }
    
    $mac = "00:1A:79:" . sprintf('%02X', mt_rand(0, 255)) . ':' . 
                          sprintf('%02X', mt_rand(0, 255)) . ':' . 
                          sprintf('%02X', mt_rand(0, 255));
    
    $_SESSION['user_mac'] = $mac;
    return $mac;
}

/**
 * Kthen kanale demo nëse provider-i nuk është i disponueshëm
 */
function getDemoChannels() {
    return [
        [
            'id' => 1,
            'name' => 'News TV',
            'stream_id' => '1001',
            'category' => 'News',
            'logo' => 'https://via.placeholder.com/100x50/007BFF/white?text=News+TV'
        ],
        [
            'id' => 2,
            'name' => 'Sports HD', 
            'stream_id' => '1002',
            'category' => 'Sports',
            'logo' => 'https://via.placeholder.com/100x50/28A745/white?text=Sports+HD'
        ],
        [
            'id' => 3,
            'name' => 'Movie Channel',
            'stream_id' => '1003', 
            'category' => 'Movies',
            'logo' => 'https://via.placeholder.com/100x50/DC3545/white?text=Movies'
        ],
        [
            'id' => 4,
            'name' => 'Music TV',
            'stream_id' => '1004',
            'category' => 'Music',
            'logo' => 'https://via.placeholder.com/100x50/6F42C1/white?text=Music+TV'
        ]
    ];
}

/**
 * Gjeneron URL për streaming live
 */
function createStalkerStreamUrl($portal_url, $stream_id, $extension = 'ts') {
    $mac = isset($_SESSION['user_mac']) ? $_SESSION['user_mac'] : generateMacAddress();
    
    $params = [
        'mac' => $mac,
        'stream' => $stream_id,
        'extension' => $extension
    ];
    
    return rtrim($portal_url, '/') . '/play/live.php?' . http_build_query($params);
}

/**
 * Shfaq listen e kanaleve si HTML
 */
function displayChannelsList($channels, $portal_url = null) {
    if (empty($channels)) {
        return '<div class="alert alert-warning">Nuk u gjetën kanale.</div>';
    }
    
    $html = '<div class="channels-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">';
    
    foreach ($channels as $channel) {
        $stream_url = createStalkerStreamUrl(
            $portal_url ?: getDefaultPortalUrl(),
            $channel['stream_id'] ?? $channel['id']
        );
        
        $html .= '
        <div class="channel-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 8px;">
            <div class="channel-logo" style="text-align: center; margin-bottom: 10px;">
                <img src="' . ($channel['logo'] ?? 'https://via.placeholder.com/100x50') . '" 
                     alt="' . htmlspecialchars($channel['name']) . '"
                     style="max-width: 100px; height: auto;">
            </div>
            <div class="channel-info" style="text-align: center;">
                <h4 style="margin: 10px 0;">' . htmlspecialchars($channel['name']) . '</h4>
                <p class="category" style="color: #666; margin: 5px 0;">' . ($channel['category'] ?? 'General') . '</p>
                <a href="watch.php?stream=' . ($channel['stream_id'] ?? $channel['id']) . '" 
                   class="watch-btn" 
                   style="display: inline-block; padding: 8px 16px; background: #007BFF; color: white; text-decoration: none; border-radius: 4px;">
                   Shiko Live
                </a>
            </div>
        </div>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Shfaq player HTML
 */
function displayStalkerPlayer($stream_url, $width = "100%", $height = "400px") {
    $html = '
    <div class="stalker-player">
        <video controls style="width: ' . $width . '; height: ' . $height . '; background: #000;">
            <source src="' . htmlspecialchars($stream_url) . '" type="video/mp2t">
            Shfletuesi juaj nuk mbështet video player-in.
        </video>
        <div class="player-info" style="margin-top: 10px; padding: 10px; background: #f8f9fa;">
            <small>Stream URL: ' . htmlspecialchars($stream_url) . '</small>
        </div>
    </div>';
    
    return $html;
}

/**
 * Kthen URL default të portalit
 */
function getDefaultPortalUrl() {
    if (isset($_SESSION['portal_url'])) {
        return $_SESSION['portal_url'];
    }
    
    return 'https://stb-webapp.onrender.com';
}

/**
 * Kontrollon nëse MAC address është valide
 */
function isValidMacAddress($mac) {
    return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
}
?>
