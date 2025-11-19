<?php
// includes/functions.php

/**
 * Merr listen e të gjitha kanaleve nga provider-i aktual
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
        if ($channels && count($channels) > 4) { // Nëse ka më shumë se 4 kanale
            error_log("U gjetën " . count($channels) . " kanale nga: " . $api_url);
            return $channels;
        }
    }
    
    // Nëse nuk u gjetën kanale, provo metoda alternative
    return tryAlternativeMethods($portal_url, $mac_address);
}

/**
 * Metoda alternative për marrjen e kanaleve
 */
function tryAlternativeMethods($portal_url, $mac_address) {
    // Metoda 1: Provoni me POST request
    $channels = tryPostRequest($portal_url, $mac_address);
    if ($channels) return $channels;
    
    // Metoda 2: Provoni të merrni nga index file
    $channels = tryIndexFile($portal_url, $mac_address);
    if ($channels) return $channels;
    
    // Metoda 3: Kthe kanale demo POR me mesazh
    error_log("Nuk u gjetën kanale nga provider-i. Duke përdorur kanale demo.");
    $_SESSION['using_demo_channels'] = true;
    return getDemoChannels();
}

/**
 * Provon POST request për kanale
 */
function tryPostRequest($portal_url, $mac_address) {
    $post_data = [
        'mac' => $mac_address,
        'type' => 'itv',
        'action' => 'get_channels'
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim($portal_url, '/') . '/api/',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($post_data),
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && !empty($response)) {
        $data = json_decode($response, true);
        if (!empty($data)) {
            return $data;
        }
    }
    
    return false;
}

/**
 * Provon të marrë kanale nga index file
 */
function tryIndexFile($portal_url, $mac_address) {
    $index_url = rtrim($portal_url, '/') . '/index.php?mac=' . $mac_address;
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $index_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_COOKIEFILE => '' // Enable cookies
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Nëse kthen JSON, process-it
    if ($http_code === 200) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && !empty($data)) {
            return $data;
        }
        
        // Nëse është HTML, provo të extract-ish kanalet
        return extractChannelsFromHtml($response);
    }
    
    return false;
}

/**
 * Extract kanalet nga HTML response
 */
function extractChannelsFromHtml($html) {
    // Ky është një regex i thjeshtë për të gjetur kanale në HTML
    preg_match_all('/data-channel-id=["\'](\d+)["\']|channel.*?["\'](\d+)["\']/i', $html, $matches);
    
    if (!empty($matches[1])) {
        $channels = [];
        foreach ($matches[1] as $channel_id) {
            if (!empty($channel_id)) {
                $channels[] = [
                    'id' => $channel_id,
                    'name' => 'Channel ' . $channel_id,
                    'stream_id' => $channel_id,
                    'category' => 'General'
                ];
            }
        }
        return !empty($channels) ? $channels : false;
    }
    
    return false;
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
    
    echo "<div style='background: #f8f9fa; padding: 20px; margin: 20px 0;'>";
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
        
        echo "<div style='margin-bottom: 15px;'>";
        echo "<strong>Endpoint:</strong> " . $endpoint . "<br>";
        echo "<strong>HTTP Code:</strong> " . $http_code . "<br>";
        echo "<strong>Response:</strong> <pre>" . htmlspecialchars($response) . "</pre>";
        echo "</div>";
    }
    
    echo "</div>";
}

/**
 * Shfaq informacion për kanalet e marra
 */
function displayChannelsInfo($channels) {
    $html = '<div style="background: #e9ecef; padding: 10px; margin: 10px 0; border-radius: 4px;">';
    $html .= '<strong>Kanale të gjetura:</strong> ' . count($channels);
    
    if (isset($_SESSION['using_demo_channels'])) {
        $html .= ' <span style="color: #dc3545;">(Duke përdorur kanale demo)</span>';
        unset($_SESSION['using_demo_channels']);
    }
    
    $html .= '</div>';
    return $html;
}
?>
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

// ... funksionet e tjera ekzistuese ...
?>
