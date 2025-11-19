<?php
// includes/functions.php

/**
 * Funksione për Stalker Middleware - Streaming Live
 */

/**
 * Gjeneron URL për streaming live
 */
function generateLiveStreamUrl($mac, $stream_id, $extension = 'ts', $play_token = null, $sn2 = null) {
    $base_url = "https://your-stalker-domain.com/play/live.php";
    
    $params = [
        'mac' => $mac,
        'stream' => $stream_id,
        'extension' => $extension
    ];
    
    // Shto play_token nëse ekziston
    if ($play_token) {
        $params['play_token'] = $play_token;
    }
    
    // Shto sn2 nëse ekziston
    if ($sn2) {
        $params['sn2'] = $sn2;
    }
    
    return $base_url . '?' . http_build_query($params);
}

/**
 * Verifikon MAC address formatin
 */
function isValidMacAddress($mac) {
    return preg_match('/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/', $mac);
}

/**
 * Merr stream URL nga database ose API
 */
function getLiveStreamUrl($stream_id, $user_mac = null) {
    // Nëse nuk ka MAC, përdor default ose gjenero
    if (!$user_mac) {
        $user_mac = getUserMacAddress();
    }
    
    // Merr play_token nga session ose database
    $play_token = getPlayToken($stream_id);
    
    // Merr sn2 nëse nevojitet
    $sn2 = getSerialNumber();
    
    return generateLiveStreamUrl($user_mac, $stream_id, 'ts', $play_token, $sn2);
}

/**
 * Merr MAC address të përdoruesit
 */
function getUserMacAddress() {
    // Mund të merret nga session, database, ose gjenerohet
    if (isset($_SESSION['user_mac'])) {
        return $_SESSION['user_mac'];
    }
    
    // Ose gjenero një MAC të rastësishëm për përdoruesin
    return generateRandomMac();
}

/**
 * Gjeneron MAC address të rastësishme
 */
function generateRandomMac() {
    $mac = [];
    for ($i = 0; $i < 6; $i++) {
        $mac[] = sprintf('%02X', mt_rand(0, 255));
    }
    return implode(':', $mac);
}

/**
 * Merr play token për stream
 */
function getPlayToken($stream_id) {
    // Kjo mund të jetë nga session, database, ose API call
    if (isset($_SESSION['play_tokens'][$stream_id])) {
        return $_SESSION['play_tokens'][$stream_id];
    }
    
    // Ose gjenero një token të ri
    return generatePlayToken($stream_id);
}

/**
 * Gjeneron play token
 */
function generatePlayToken($stream_id) {
    $token = md5($stream_id . time() . uniqid());
    
    // Ruaj në session për përdorim të mëvonshëm
    if (!isset($_SESSION['play_tokens'])) {
        $_SESSION['play_tokens'] = [];
    }
    $_SESSION['play_tokens'][$stream_id] = $token;
    
    return $token;
}

/**
 * Merr serial number (sn2)
 */
function getSerialNumber() {
    // Mund të jetë nga konfigurimi ose database
    return defined('STB_SERIAL') ? STB_SERIAL : 'default_sn';
}

/**
 * Kontrollon nëse stream-i është i disponueshëm
 */
function checkStreamAvailability($stream_url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $stream_url,
        CURLOPT_NOBODY => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ($http_code === 200);
}

/**
 * Merr informacion për stream nga database
 */
function getStreamInfo($stream_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM streams WHERE stream_id = ?");
        $stmt->execute([$stream_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error getting stream info: " . $e->getMessage());
        return false;
    }
}

/**
 * Log stream request për analizë
 */
function logStreamRequest($stream_id, $user_id, $mac, $success = true) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO stream_logs 
            (stream_id, user_id, mac_address, success, created_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$stream_id, $user_id, $mac, $success]);
    } catch (PDOException $e) {
        error_log("Error logging stream request: " . $e->getMessage());
    }
}

/**
 * Merr listën e stream-eve live të disponueshme
 */
function getAvailableLiveStreams($category_id = null) {
    global $pdo;
    
    try {
        $sql = "SELECT * FROM streams WHERE type = 'live' AND status = 'active'";
        $params = [];
        
        if ($category_id) {
            $sql .= " AND category_id = ?";
            $params[] = $category_id;
        }
        
        $sql .= " ORDER BY stream_name ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error getting live streams: " . $e->getMessage());
        return [];
    }
}

/**
 * Kontrollon aksesin e përdoruesit në stream
 */
function checkUserStreamAccess($user_id, $stream_id) {
    // Implementoni logjikën e aksesit tuaj këtu
    // Mund të kontrolloni nëse përdoruesi ka abonim, nëse stream-i është i lirë, etj.
    
    return true; // Ose false nëse nuk ka akses
}

/**
 * Ruan stream në history të përdoruesit
 */
function addToStreamHistory($user_id, $stream_id, $stream_url) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO user_stream_history 
            (user_id, stream_id, stream_url, watched_at) 
            VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE watched_at = NOW()
        ");
        $stmt->execute([$user_id, $stream_id, $stream_url]);
    } catch (PDOException $e) {
        error_log("Error adding to stream history: " . $e->getMessage());
    }
}

/**
 * Merr stream URL të përgatitur për player
 */
function getPlayerStreamUrl($stream_id, $user_id = null) {
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    // Kontrollo aksesin
    if (!checkUserStreamAccess($user_id, $stream_id)) {
        return false;
    }
    
    // Merr informacion për stream
    $stream_info = getStreamInfo($stream_id);
    if (!$stream_info) {
        return false;
    }
    
    // Gjenero URL-në e stream-it
    $stream_url = getLiveStreamUrl($stream_id);
    
    // Shto në history
    addToStreamHistory($user_id, $stream_id, $stream_url);
    
    // Log request
    logStreamRequest($stream_id, $user_id, getUserMacAddress());
    
    return $stream_url;
}
?>
