<?php
function isLoggedIn() {
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

function getUserMac() {
    return $_SESSION['user_mac'] ?? DEFAULT_MAC;
}

function getChannelsList() {
    // Kjo duhet të kthejë listën e kanaleve nga API ose database
    // Për shembull:
    return [
        ['id' => '1', 'name' => 'RTSH 1', 'logo' => 'images/rtsh1.png'],
        ['id' => '2', 'name' => 'RTSH 2', 'logo' => 'images/rtsh2.png'],
        ['id' => '3', 'name' => 'Top Channel', 'logo' => 'images/top-channel.png'],
        ['id' => '4', 'name' => 'Klan TV', 'logo' => 'images/klan.png'],
        ['id' => '5', 'name' => 'Vizion Plus', 'logo' => 'images/vizion-plus.png'],
        // Shtoni më shumë kanale sipas nevojës
    ];
}

function generatePlayToken($mac, $channelId) {
    // Implementimi i gjenerimit të token-it
    // Kjo varet nga provideri juaj i IPTV
    return md5($mac . $channelId . time() . 'secret_key');
}

function logActivity($activity) {
    // Log user activities
    $log = date('Y-m-d H:i:s') . " - " . $_SESSION['user'] . " - " . $activity . "\n";
    file_put_contents('logs/activity.log', $log, FILE_APPEND);
}
?>