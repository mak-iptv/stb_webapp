<?php
// includes/functions.php

/**
 * Kontrollon nëse MAC address është valide
 */
function isValidMacAddress($mac) {
    if (empty($mac)) {
        return false;
    }
    
    // Kontrollo formatet e zakonshme të MAC address:
    // 00:1A:79:29:86:BB (me colon)
    // 00-1A-79-29-86-BB (me dash)  
    // 001A792986BB (pa ndarës)
    $pattern = '/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})|([0-9A-Fa-f]{12})$/';
    return preg_match($pattern, $mac);
}

/**
 * Formatton MAC address në formatin standard
 */
function formatMacAddress($mac) {
    if (empty($mac)) {
        return generateMacAddress();
    }
    
    // Largo karakteret e panevojshme
    $mac = preg_replace('/[^0-9A-Fa-f]/', '', $mac);
    
    // Sigurohu që ka 12 karaktere
    if (strlen($mac) !== 12) {
        return generateMacAddress();
    }
    
    // Formatto si: 00:1A:79:XX:XX:XX
    return implode(':', str_split($mac, 2));
}

/**
 * Gjeneron MAC address të re
 */
function generateMacAddress() {
    if (isset($_SESSION['user_mac'])) {
        return $_SESSION['user_mac'];
    }
    
    // Prefix i zakonshëm për Stalker
    $prefix = "00:1A:79";
    
    // Gjenero 3 byte të fundit
    $suffix = [];
    for ($i = 0; $i < 3; $i++) {
        $suffix[] = sprintf('%02X', mt_rand(0, 255));
    }
    
    $mac = $prefix . ':' . implode(':', $suffix);
    $_SESSION['user_mac'] = $mac;
    
    return $mac;
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
 * Merr listen e kanaleve nga provider-i
 */
function getChannelsFromProvider($portal_url, $mac_address = null) {
    // Nëse nuk ka MAC, gjenero një
    if (!$mac_address) {
        $mac_address = generateMacAddress();
    }
    
    // Endpoint-et e zakonshme për Stalker Portal
    $endpoints = [
        '/api/channels.php',
        '/api/get_channels.php', 
        '/api/live.php',
        '/portal.php?type=itv',
        '/server/load.php',
        '/panel_api.php'
    ];
    
    // Provoni çdo endpoint
    foreach ($endpoints as $endpoint) {
        $api_url = rtrim($portal_url, '/') . $endpoint . '?mac=' . $mac_address;
        
        $channels = tryApiCall($api_url);
        if ($channels && count($channels) > 0) {
            error_log("U gjetën " . count($channels) . " kanale nga: " . $api_url);
            return $channels;
        }
    }
    
    // Nëse nuk u gjetën kanale, kthe kanale demo
    error_log("Nuk u gjetën kanale nga provider-i. Duke përdorur kanale demo.");
    $_SESSION['using_demo_channels'] = true;
    return getDemoChannels();
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
        ],
        [
            'id' => 5,
            'name' => 'Kids Channel',
            'stream_id' => '1005',
            'category' => 'Kids',
            'logo' => 'https://via.placeholder.com/100x50/FFC107/white?text=Kids+TV'
        ],
        [
            'id' => 6,
            'name' => 'Documentary',
            'stream_id' => '1006',
            'category' => 'Education',
            'logo' => 'https://via.placeholder.com/100x50/17A2B8/white?text=Docu'
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
        <div class="channel-card" style="border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: white;">
            <div class="channel-logo" style="text-align: center; margin-bottom: 10px;">
                <img src="' . ($channel['logo'] ?? 'https://via.placeholder.com/100x50') . '" 
                     alt="' . htmlspecialchars($channel['name']) . '"
                     style="max-width: 100px; height: auto; border-radius: 4px;">
            </div>
            <div class="channel-info" style="text-align: center;">
                <h4 style="margin: 10px 0; color: #333;">' . htmlspecialchars($channel['name']) . '</h4>
                <p class="category" style="color: #666; margin: 5px 0; font-size: 14px;">' . ($channel['category'] ?? 'General') . '</p>
                <a href="watch.php?stream=' . ($channel['stream_id'] ?? $channel['id']) . '" 
                   class="watch-btn" 
                   style="display: inline-block; padding: 8px 16px; background: #007BFF; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">
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
        <video controls style="width: ' . $width . '; height: ' . $height . '; background: #000; border-radius: 4px;">
            <source src="' . htmlspecialchars($stream_url) . '" type="video/mp2t">
            Shfletuesi juaj nuk mbështet video player-in.
        </video>
        <div class="player-info" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px;">
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
 * Shfaq informacion për kanalet e marra
 */
function displayChannelsInfo($channels) {
    $html = '<div style="background: #e9ecef; padding: 10px; margin: 10px 0; border-radius: 4px;">';
    $html .= '<strong>Kanale të gjetura:</strong> ' . count($channels);
    
    if (isset($_SESSION['using_demo_channels'])) {
        $html .= ' <span style="color: #dc3545;">(Duke përdorur kanale demo - provider-i nuk u gjet)</span>';
        unset($_SESSION['using_demo_channels']);
    } else {
        $html .= ' <span style="color: #28a745;">(Kanale nga provider-i)</span>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Funksion për debug - shfaq të gjitha të dhënat nga API
 */
function debugApiResponse($portal_url, $mac_address) {
    $endpoints = [
        '/api/channels.php',
        '/api/get_channels.php',
        '/api/live.php',
        '/portal.php?type=itv'
    ];
    
    echo "<div style='background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px;'>";
    echo "<h3>Debug API Responses:</h3>";
    
    foreach ($endpoints as $endpoint) {
        $api_url = rtrim($portal_url, '/') . $endpoint . '?mac=' . $mac_address;
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<div style='margin-bottom: 15px; padding: 15px; background: white; border-radius: 4px;'>";
        echo "<strong>Endpoint:</strong> " . $endpoint . "<br>";
        echo "<strong>URL:</strong> " . $api_url . "<br>";
        echo "<strong>HTTP Code:</strong> " . $http_code . "<br>";
        echo "<strong>Response Length:</strong> " . strlen($response) . " characters<br>";
        echo "<strong>Response:</strong> <pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; overflow: auto;'>" . htmlspecialchars($response) . "</pre>";
        echo "</div>";
    }
    
    echo "</div>";
}
?>
