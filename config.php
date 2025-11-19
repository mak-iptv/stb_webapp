<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration for Render
    if (getenv('RENDER')) {
        ini_set('session.save_path', '/tmp');
    }
    session_start();
}

// STALKER MIDDLEWARE CONFIG - NDRROJI KËTO!
define('STALKER_PORTAL_URL', 'http://testdi1.proxytx.cloud:80'); // NDRROJE URL-në e portalit
define('STALKER_MAC_ADDRESS', '00:1A:79:29:86:BB');
define('STALKER_USERNAME', 'username_your');
define('STALKER_PASSWORD', 'password_your');

// Application configuration
define('BASE_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));

// Në Render, disable cache directory creation
define('CACHE_ENABLED', false);
define('CACHE_DIR', sys_get_temp_dir()); // Përdor temporary directory

// Security headers - VENDOSI PARA ÇDO OUTPUT!
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
}

// Nuk krijojmë direktoritë në Render - do të përdorim temp directory
?>
