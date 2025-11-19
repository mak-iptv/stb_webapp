<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// PROVIDER CONFIG - NDRROJI KËTO!
define('IPTV_PROVIDER_URL', 'http://testdi1.proxytx.cloud:80');
define('IPTV_USERNAME', 'username_your');
define('IPTV_PASSWORD', 'password_your'); 
define('IPTV_MAC_ADDRESS', '00:1A:79:29:86:BB');

// API Endpoints të providerit
define('PROVIDER_API_CHANNELS', IPTV_PROVIDER_URL . '/player_api.php');
define('PROVIDER_API_LIVE_STREAMS', IPTV_PROVIDER_URL . '/live/'. IPTV_USERNAME .'/'. IPTV_PASSWORD .'/');
define('PROVIDER_API_EPG', IPTV_PROVIDER_URL . '/xmltv.php');

// Application configuration
define('BASE_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/cache');

// Create necessary directories
$directories = ['logs', 'cache', 'images'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>
