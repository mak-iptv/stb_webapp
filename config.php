<?php
// Start session pa headers
if (session_status() === PHP_SESSION_NONE) {
    if (getenv('RENDER')) {
        ini_set('session.save_path', '/tmp');
    }
    session_start();
}

// Application configuration
define('BASE_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('CACHE_ENABLED', false);

// Mos dërgo headers këtu - do të dërgohen në faqe individuale
?>
