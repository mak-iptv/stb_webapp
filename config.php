<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// STALKER MIDDLEWARE CONFIG - NDRROJI KËTO!
define('STALKER_PORTAL_URL', 'http://testdi1.proxytx.cloud:80'); // NDRROJE URL-në e portalit
define('STALKER_MAC_ADDRESS', '00:1A:79:29:86:BB');
define('STALKER_USERNAME', 'username_your');
define('STALKER_PASSWORD', 'password_your');

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
