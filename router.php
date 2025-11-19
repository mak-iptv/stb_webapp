<?php
// Router për Stalker Player
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove base path nëse është në subdirectory
$base_path = '';
$path = str_replace($base_path, '', $path);

session_start();

// Routing
switch ($path) {
    case '/':
    case '':
        require 'index.php';
        break;
        
    case '/login':
        require 'login.php';
        break;
        
    case '/dashboard':
        require 'dashboard.php';
        break;
        
    case '/logout':
        require 'logout.php';
        break;
        
    case '/test':
        require 'test.php';
        break;
        
    case '/login-simple':
        require 'login-simple.php';
        break;
        
    case '/api/channels':
        require 'api/channels.php';
        break;
        
    case '/api/stream':
        require 'api/stream.php';
        break;
        
    default:
        // Kontrollo nëse është skedar fizik
        if (file_exists(ltrim($path, '/'))) {
            return false; // Lëre web server-in të trajtojë
        } else {
            http_response_code(404);
            echo "404 - Faqja nuk u gjet: " . htmlspecialchars($path);
        }
        break;
}
?>
