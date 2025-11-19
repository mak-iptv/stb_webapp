<?php
// includes/functions.php

/**
 * Funksione të thjeshtuara për Stalker Player
 * Vetëm URL dhe MAC address
 */

class StalkerPlayer {
    private $portal_url;
    private $user_mac;
    
    public function __construct($portal_url, $user_mac = null) {
        $this->portal_url = rtrim($portal_url, '/');
        $this->user_mac = $user_mac ?: $this->getUserMac();
    }
    
    /**
     * Merr MAC address të përdoruesit
     */
    private function getUserMac() {
        // Provoni në këtë renditje:
        
        // 1. Nga session
        if (isset($_SESSION['user_mac'])) {
            return $_SESSION['user_mac'];
        }
        
        // 2. Nga cookie
        if (isset($_COOKIE['user_mac'])) {
            return $_COOKIE['user_mac'];
        }
        
        // 3. Gjenero një të re dhe ruaj
        $new_mac = $this->generateMacAddress();
        $_SESSION['user_mac'] = $new_mac;
        setcookie('user_mac', $new_mac, time() + (86400 * 30), "/"); // 30 ditë
        
        return $new_mac;
    }
    
    /**
     * Gjeneron MAC address unike
     */
    private function generateMacAddress() {
        // Gjeneron MAC në formatin: 00:1A:79:XX:XX:XX
        $prefix = "00:1A:79";
        $suffix = [];
        for ($i = 0; $i < 3; $i++) {
            $suffix[] = sprintf('%02X', mt_rand(0, 255));
        }
        return $prefix . ':' . implode(':', $suffix);
    }
    
    /**
     * Kontrollon nëse MAC address është valide
     */
    public function isValidMac($mac) {
        return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
    }
    
    /**
     * Gjeneron URL për streaming live
     */
    public function generateLiveUrl($stream_id, $extension = 'ts') {
        if (!$this->isValidMac($this->user_mac)) {
            throw new Exception("MAC address jo valide: " . $this->user_mac);
        }
        
        $params = [
            'mac' => $this->user_mac,
            'stream' => $stream_id,
            'extension' => $extension
        ];
        
        return $this->portal_url . '/play/live.php?' . http_build_query($params);
    }
    
    /**
     * Gjeneron URL për VOD
     */
    public function generateVodUrl($video_id, $extension = 'ts') {
        $params = [
            'mac' => $this->user_mac,
            'stream' => $video_id,
            'extension' => $extension
        ];
        
        return $this->portal_url . '/play/vod.php?' . http_build_query($params);
    }
    
    /**
     * Merr URL bazë të portalit
     */
    public function getPortalUrl() {
        return $this->portal_url;
    }
    
    /**
     * Merr MAC address aktuale
     */
    public function getMacAddress() {
        return $this->user_mac;
    }
    
    /**
     * Vendos MAC address të re
     */
    public function setMacAddress($new_mac) {
        if ($this->isValidMac($new_mac)) {
            $this->user_mac = $new_mac;
            $_SESSION['user_mac'] = $new_mac;
            setcookie('user_mac', $new_mac, time() + (86400 * 30), "/");
            return true;
        }
        return false;
    }
}

/**
 * Funksion i thjeshtë për të krijuar player URL
 */
function createStalkerStreamUrl($portal_url, $stream_id, $mac = null, $type = 'live') {
    $player = new StalkerPlayer($portal_url, $mac);
    
    if ($type === 'live') {
        return $player->generateLiveUrl($stream_id);
    } else {
        return $player->generateVodUrl($stream_id);
    }
}

/**
 * Kontrollon nëse stream-i është aktiv
 */
function checkStreamStatus($stream_url, $timeout = 5) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $stream_url,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $http_code === 200;
}

/**
 * Merr listën e kanaleve nga API (nëse është e disponueshme)
 */
function getChannelsList($portal_url, $mac = null) {
    $player = new StalkerPlayer($portal_url, $mac);
    
    // Kjo varet nga API e provider-it tuaj
    $api_url = $portal_url . '/api/channels.php?mac=' . $player->getMacAddress();
    
    $response = @file_get_contents($api_url);
    if ($response) {
        return json_decode($response, true);
    }
    
    return [];
}

/**
 * Shfaq player HTML
 */
function displayStalkerPlayer($stream_url, $width = "100%", $height = "400px") {
    $html = '
    <div class="stalker-player">
        <video controls style="width: ' . $width . '; height: ' . $height . ';">
            <source src="' . htmlspecialchars($stream_url) . '" type="video/mp2t">
            Shfletuesi juaj nuk mbështet video player-in.
        </video>
        <div class="player-info">
            <small>Stream URL: ' . htmlspecialchars($stream_url) . '</small>
        </div>
    </div>';
    
    return $html;
}

/**
 * Funksion i thjeshtë për konfigurim
 */
function getStalkerConfig() {
    return [
        'portal_url' => 'https://stb-webapp.onrender.com', // Ndryshoje këtë
        'default_mac' => isset($_SESSION['user_mac']) ? $_SESSION['user_mac'] : null
    ];
}

// Initialize session nëse nuk është startuar
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
