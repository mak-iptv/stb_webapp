<?php
// Display errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the current request
$request_uri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($request_uri, PHP_URL_PATH);

// Simple routing
switch ($path) {
    case '/':
        showHomePage();
        break;
        
    case '/login':
        showLoginPage();
        break;
        
    case '/api/channels':
        showApiChannels();
        break;
        
    default:
        // Check for static files
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg)$/i', $path)) {
            $file_path = ltrim($path, '/');
            if (file_exists($file_path)) {
                serveStaticFile($file_path);
                exit;
            }
        }
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
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 20px;
                color: white;
                min-height: 100vh;
            }
            .container {
                max-width: 800px;
                margin: 0 auto;
                background: rgba(255,255,255,0.1);
                padding: 30px;
                border-radius: 15px;
                backdrop-filter: blur(10px);
            }
            .btn {
                display: inline-block;
                background: #3498db;
                color: white;
                padding: 12px 25px;
                margin: 10px;
                border-radius: 5px;
                text-decoration: none;
            }
            .status {
                background: rgba(255,255,255,0.2);
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🎬 Stalker Player - IPTV</h1>
            <p>✅ Aplikacioni është duke punuar!</p>
            
            <div class="status">
                <h3>Server Information:</h3>
                <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
                <p><strong>Server:</strong> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'PHP Built-in Server' ?></p>
                <p><strong>Request:</strong> <?= htmlspecialchars($request_uri ?? '/') ?></p>
            </div>
            
            <div>
                <a href="/login" class="btn">🔐 Login Page</a>
                <a href="/api/channels" class="btn">📡 API Test</a>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function showLoginPage() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login - Stalker Player</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 20px;
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
            }
            .login-form {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                width: 100%;
                max-width: 400px;
            }
            .form-group {
                margin-bottom: 20px;
            }
            .form-group input {
                width: 100%;
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 5px;
            }
            .login-btn {
                width: 100%;
                padding: 12px;
                background: #3498db;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
            }
        </style>
    </head>
    <body>
        <div class="login-form">
            <h2>🔐 Login - Stalker Player</h2>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" value="demo" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" value="demo" required>
                </div>
                <button type="submit" class="login-btn">Login</button>
            </form>
            <p style="text-align: center; margin-top: 20px;">
                <a href="/">← Back to Home</a>
            </p>
        </div>
    </body>
    </html>
    <?php
}

function showApiChannels() {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'API is working!',
        'channels' => [
            ['id' => 1, 'name' => 'RTSH 1'],
            ['id' => 2, 'name' => 'RTSH 2'],
            ['id' => 3, 'name' => 'Top Channel']
        ]
    ]);
}

function serveStaticFile($file_path) {
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
}

function show404($path) {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>404 - Not Found</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                margin: 0;
                padding: 20px;
                color: white;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <h1>❌ 404 - Page Not Found</h1>
        <p>The page <?= htmlspecialchars($path) ?> was not found.</p>
        <a href="/" style="color: white;">← Back to Home</a>
    </body>
    </html>
    <?php
}
