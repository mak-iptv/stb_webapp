<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    if (getenv('RENDER')) {
        ini_set('session.save_path', '/tmp');
    }
    session_start();
}

// Nuk kemi më konfigurim të fiksuar - do të merret nga login form
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
