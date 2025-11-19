<?php
// Në fillim të config.php, para çdo gjëje
if (session_status() === PHP_SESSION_NONE) {
    // Konfigurimi i session para se të startohet
    if (getenv('RENDER')) {
        ini_set('session.save_path', '/tmp');
    }
    ini_set('session.cookie_secure', 1);
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_strict_mode', 1);
    
    // Starto session vetëm nëse nuk është startuar
    session_start();
}

// Environment configuration
if (getenv('RENDER') || (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === false)) {
    // Production on Render/Docker
    $protocol = isset($_SERVER['HTTPS']) ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('BASE_URL', "{$protocol}://{$host}");
    define('API_BASE_URL', 'http://testdi1.proxytx.cloud:80'); // NDRROJE KËTË
} else {
    // Local development
    define('BASE_URL', 'http://localhost:8000');
    define('API_BASE_URL', 'http://localhost:8080');
}

// Application configuration
define('DEFAULT_MAC', '00:1A:79:00:00:00');
define('CACHE_ENABLED', false);

// Krijo direktoritë nëse nuk ekzistojnë
if (!file_exists('logs')) mkdir('logs', 0777, true);
if (!file_exists('cache')) mkdir('cache', 0777, true);
if (!file_exists('images')) mkdir('images', 0755, true);

// Funksion për të dërguar header-et e sigurisë
function sendSecurityHeaders() {
    if (!headers_sent()) {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        
        // CORS headers vetëm për API requests
        if (isset($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], '/api/') === 0) {
            header('Access-Control-Allow-Origin: ' . BASE_URL);
            header('Access-Control-Allow-Methods: GET, POST');
            header('Access-Control-Allow-Headers: Content-Type');
        }
    }
}

// Thirr funksionin për header-et
sendSecurityHeaders();
?>
