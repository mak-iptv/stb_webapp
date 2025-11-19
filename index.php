<?php
// Display errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the current path
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove any base path if application is in subdirectory
$base_path = '';
$path = str_replace($base_path, '', $path);

// Simple routing
switch ($path) {
    case '':
    case '/':
    case '/index.php':
        require_once 'config.php';
        showMainPage();
        break;
    case '/login':
        require_once 'config.php';
        showLoginPage();
        break;
    case '/logout':
        require_once 'config.php';
        handleLogout();
        break;
    case '/api/channels':
        require_once 'config.php';
        handleApiChannels();
        break;
    case '/api/stream':
        require_once 'config.php';
        handleApiStream();
        break;
    default:
        // Check if it's a static file (CSS, JS, images)
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico)$/', $path)) {
            $file_path = ltrim($path, '/');
            if (file_exists($file_path)) {
                $mime_types = [
                    'css' => 'text/css',
                    'js' => 'application/javascript',
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'gif' => 'image/gif',
                    'ico' => 'image/x-icon'
                ];
                $extension = pathinfo($file_path, PATHINFO_EXTENSION);
                if (isset($mime_types[$extension])) {
                    header('Content-Type: ' . $mime_types[$extension]);
                }
                readfile($file_path);
                exit;
            }
        }
        // If not found, show 404
        http_response_code(404);
        echo "404 - Faqja nuk u gjet: " . htmlspecialchars($path);
        break;
}

function showMainPage() {
    // Your existing showMainPage function
    if (!isset($_SESSION['user'])) {
        header('Location: /login');
        exit;
    }
    ?>
    <!DOCTYPE html>
    <html lang="sq">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Stalker Player - IPTV</title>
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body>
        <div class="header">
            <h1>🎬 Stalker Player</h1>
            <div class="user-menu">
                <span>Përshëndetje, <?= htmlspecialchars($_SESSION['user']) ?></span>
                <a href="/logout">Dil</a>
            </div>
        </div>
        
        <div class="container">
            <div class="status-box">
                <h3>✅ Aplikacioni është duke punuar!</h3>
                <p><strong>URL:</strong> <?= $_SERVER['HTTP_HOST'] ?? 'localhost' ?></p>
                <p><strong>Path:</strong> <?= $_SERVER['REQUEST_URI'] ?? '/' ?></p>
                <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
            </div>
            
            <div class="player-section">
                <div class="video-container">
                    <video id="videoPlayer" controls width="100%" height="400">
                        Shfletuesi juaj nuk suporton video.
                    </video>
                    <div class="player-info">
                        <h3 id="currentChannel">Zgjidhni një kanal</h3>
                    </div>
                </div>
                
                <div class="channels-section">
                    <h3>Kanalet</h3>
                    <div id="channelsList" class="channels-grid">
                        <!-- Kanale do të ngarkohen me JavaScript -->
                    </div>
                </div>
            </div>
        </div>

        <script src="/js/main.js"></script>
    </body>
    </html>
    <?php
}

// Include other functions here (showLoginPage, handleLogout, etc.)
// ... your existing functions ...
?>
