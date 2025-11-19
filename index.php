<?php
// Include config para çdo gjëje
require_once 'config.php';

// Funksion për të handle routing
function handleRequest() {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    
    // Routing
    switch ($path) {
        case '/':
        case '/index.php':
            showMainPage();
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
            show404();
            break;
    }
}

function showMainPage() {
    // Kontrollo nëse useri është i loguar
    $isLoggedIn = isset($_SESSION['user']);
    
    if (!$isLoggedIn) {
        header('Location: /login');
        exit;
    }
    
    // Shfaq faqen kryesore
    ?>
    <!DOCTYPE html>
    <html lang="sq">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Stalker Player - IPTV</title>
        <link rel="stylesheet" href="css/style.css">
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
            <div class="player-section">
                <div class="video-container">
                    <video id="videoPlayer" controls>
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

        <script src="js/main.js"></script>
    </body>
    </html>
    <?php
}

function showLoginPage() {
    // Nëse është bërë login, ridrejto
    if (isset($_SESSION['user'])) {
        header('Location: /');
        exit;
    }
    
    // Process login form
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        // Verifikim i thjeshtë (ndërroje me verifikim të sigurt)
        if ($username === 'demo' && $password === 'demo') {
            $_SESSION['user'] = $username;
            $_SESSION['login_time'] = time();
            
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
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                padding: 20px;
            }
            
            .login-container {
                background: white;
                padding: 40px;
                border-radius: 10px;
                box-shadow: 0 15px 35px rgba(0,0,0,0.1);
                width: 100%;
                max-width: 400px;
            }
            
            .login-title {
                text-align: center;
                margin-bottom: 30px;
                color: #333;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                display: block;
                margin-bottom: 5px;
                color: #555;
                font-weight: bold;
            }
            
            .form-group input {
                width: 100%;
                padding: 12px;
                border: 2px solid #ddd;
                border-radius: 5px;
                font-size: 16px;
                transition: border-color 0.3s;
            }
            
            .form-group input:focus {
                border-color: #667eea;
                outline: none;
            }
            
            .login-btn {
                width: 100%;
                padding: 12px;
                background: #667eea;
                color: white;
                border: none;
                border-radius: 5px;
                font-size: 16px;
                cursor: pointer;
                transition: background 0.3s;
            }
            
            .login-btn:hover {
                background: #5a6fd8;
            }
            
            .error-message {
                background: #f8d7da;
                color: #721c24;
                padding: 10px;
                border-radius: 5px;
                margin-bottom: 20px;
                text-align: center;
            }
            
            .demo-info {
                background: #d1ecf1;
                color: #0c5460;
                padding: 10px;
                border-radius: 5px;
                margin-top: 20px;
                text-align: center;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h1 class="login-title">Hyrë në Stalker Player</h1>
            
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
        </div>
    </body>
    </html>
    <?php
}

function handleLogout() {
    // Fshi session
    $_SESSION = array();
    
    // Fshi cookie-in e session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Shkatërro session
    session_destroy();
    
    // Ridrejto në login
    header('Location: /login');
    exit;
}

function handleApiChannels() {
    // Kontrollo authentication për API
    if (!isset($_SESSION['user'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Jo i autorizuar']);
        exit;
    }
    
    // Dërgo header-et e duhura për API
    if (!headers_sent()) {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: ' . BASE_URL);
    }
    
    // Kthe listën e kanaleve
    echo json_encode([
        'success' => true,
        'channels' => [
            ['id' => 1, 'name' => 'RTSH 1', 'category' => 'News', 'logo' => ''],
            ['id' => 2, 'name' => 'RTSH 2', 'category' => 'Entertainment', 'logo' => ''],
            ['id' => 3, 'name' => 'Top Channel', 'category' => 'General', 'logo' => ''],
            ['id' => 4, 'name' => 'Klan TV', 'category' => 'General', 'logo' => ''],
            ['id' => 5, 'name' => 'Vizion Plus', 'category' => 'General', 'logo' => ''],
        ]
    ]);
    exit;
}

function handleApiStream() {
    // Implemento stream API këtu
    if (!headers_sent()) {
        header('Content-Type: application/json');
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Stream API ende nuk është implementuar'
    ]);
    exit;
}

function show404() {
    http_response_code(404);
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>404 - Faqja nuk u gjet</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                display: flex;
                justify-content: center;
                align-items: center;
                min-height: 100vh;
                margin: 0;
                color: white;
                text-align: center;
            }
            .container {
                background: rgba(255,255,255,0.1);
                padding: 40px;
                border-radius: 15px;
                backdrop-filter: blur(10px);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>404 - Faqja nuk u gjet</h1>
            <p>Faqja që kërkoni nuk ekziston.</p>
            <a href="/" style="color: white; text-decoration: underline;">Kthehu në faqen kryesore</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Start aplikacionin
handleRequest();
?>
