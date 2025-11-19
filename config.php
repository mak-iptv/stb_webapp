<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Session configuration for different environments
    if (getenv('RENDER')) {
        ini_set('session.save_path', '/tmp');
    }
    
    // Security settings
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    
    session_start();
}

// Set security headers if not already sent
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    
    // For API responses, set CORS
    if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
        header('Content-Type: application/json');
    }
}

// Application configuration
define('BASE_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
define('API_BASE_URL', 'http://testdi1.proxytx.cloud:80');
define('DEFAULT_MAC', '00:1A:79:00:00:00');

// Create necessary directories
$directories = ['logs', 'cache', 'images', 'css', 'js', 'api', 'includes'];
foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
?>
