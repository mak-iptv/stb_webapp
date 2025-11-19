<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    if (getenv('RENDER')) {
        ini_set('session.save_path', '/tmp');
    }
    session_start();
}

// STALKER MIDDLEWARE CONFIG - NDRROJI KËTO ME TË DHËNAT E TUJA!
define('STALKER_PORTAL_URL', 'http://testdi1.proxytx.cloud:80'); // NDRROJE
define('STALKER_MAC_ADDRESS', '00:1A:79:29:86:BB'); // NDRROJE
define('STALKER_USERNAME', 'stb'); // NDRROJE  
define('STALKER_PASSWORD', 'stb'); // NDRROJE

// Application configuration
define('BASE_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('CACHE_ENABLED', false);

// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}
?>
