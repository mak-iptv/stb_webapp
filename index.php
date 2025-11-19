<?php
// Display errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the current request
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Remove query string
$path = strtok($path, '?');

// Simple routing
switch ($path) {
    case '/':
    case '/index.php':
        showHomePage();
        break;
        
    case '/login':
        showLoginPage();
        break;
        
    case '/logout':
        handleLogout();
        break;
        
    case '/api/channels':
        handleApiChannels();
        break;
        
    case '/api/stream':
        handleApiStream();
        break;
        
    default:
        // Check for static files (CSS, JS, images)
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/i', $path)) {
            $file_path = ltrim($path, '/');
            serveStaticFile($file_path);
            exit;
        }
        
        // If not found, show 404
        show404($path);
        break;
}

function showHomePage() {
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
        <div class="container">
            <div class="header">
                <h1>🎬 Stalker Player - IPTV</h1>
                <p>✅ Aplikacioni është duke punuar në Apache!</p>
            </div>
            
            <div class="status-box">
                <h3>📊 Informacione të Serverit:</h3>
                <ul>
                    <li><strong>PHP Version:</strong> <?= PHP_VERSION ?></li>
                    <li><strong>Server Software:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' ?></li>
                    <li><strong>Request URI:</strong> <?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/') ?></li>
                    <li><strong>HTTP Host:</strong> <?= $_SERVER['HTTP_HOST'] ?? 'N/A' ?></li>
                    <li><strong>Port:</strong> <?= $_SERVER['SERVER_PORT'] ?? 'N/A' ?></li>
                </ul>
            </div>
            
            <div class="navigation">
                <h3>🧭 Navigim:</h3>
                <a href="/login" class="btn">🔐 Faqja e Login</a>
                <a href="/api/channels" class="btn">📡 Testo API</a>
                <a href="/test" class="btn">❌ Test 404 Page</a>
            </div>
            
            <div class="file-structure">
                <h3>📁 Struktura e Skedarëve:</h3>
                <ul>
                    <?php
                    $files = scandir('.');
                    foreach ($files as $file) {
                        if ($file !== '.' && $file !== '..') {
                            $type = is_dir($file) ? '📁' : '📄';
                            echo "<li>{$type} {$file}</li>";
                        }
                    }
                    ?>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function showLoginPage() {
    // Handle login form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Simple authentication
        if ($username === 'demo' && $password === 'demo') {
            // Start session and redirect
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user'] = $username;
            header('Location: /');
            exit;
        } else {
            $error = "Kredencialet janë të gabuara!";
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="sq">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Stalker Player</title>
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body>
        <div class="login-container">
            <div class="login-form">
                <h1>🔐 Hyrë në Stalker Player</h1>
                
                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="username">Emri i përdoruesit:</label>
                        <input type="text" id="username" name="username" value="demo" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Fjalëkalimi:</label>
                        <input type="password" id="password" name="password" value="demo" required>
                    </div>
                    
                    <button type="submit" class="login-btn">Hyr</button>
                </form>
                
                <div class="demo-info">
                    <strong>Kredenciale demo:</strong><br>
                    Përdoruesi: demo<br>
                    Fjalëkalimi: demo
                </div>
                
                <div class="back-link">
                    <a href="/">← Kthehu në Faqen Kryesore</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function handleLogout() {
    // Clear session
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = array();
    session_destroy();
    
    // Redirect to home
    header('Location: /');
    exit;
}

function handleApiChannels() {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'API is working with Apache!',
        'channels' => [
            ['id' => 1, 'name' => 'RTSH 1', 'category' => 'News'],
            ['id' => 2, 'name' => 'RTSH 2', 'category' => 'Entertainment'],
            ['id' => 3, 'name' => 'Top Channel', 'category' => 'General'],
            ['id' => 4, 'name' => 'Klan TV', 'category' => 'General'],
            ['id' => 5, 'name' => 'Vizion Plus', 'category' => 'General'],
        ]
    ]);
    exit;
}

function handleApiStream() {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Stream API endpoint',
        'stream_url' => 'to_be_implemented'
    ]);
    exit;
}

function serveStaticFile($file_path) {
    if (file_exists($file_path)) {
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'ico' => 'image/x-icon',
            'svg' => 'image/svg+xml'
        ];
        
        $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if (isset($mime_types[$extension])) {
            header('Content-Type: ' . $mime_types[$extension]);
        }
        
        readfile($file_path);
    } else {
        http_response_code(404);
        echo "Static file not found: " . htmlspecialchars($file_path);
    }
    exit;
}

function show404($path) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>404 - Faqja nuk u gjet</title>
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body>
        <div class="container">
            <div class="error-page">
                <h1>❌ 404 - Faqja nuk u gjet</h1>
                <p>Faqja <strong><?= htmlspecialchars($path) ?></strong> nuk ekziston.</p>
                <div class="navigation">
                    <a href="/" class="btn">🏠 Faqja Kryesore</a>
                    <a href="/login" class="btn">🔐 Login</a>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
