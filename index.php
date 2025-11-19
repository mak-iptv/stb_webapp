<?php
// Display errors for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        
    case '/dashboard':
        showDashboard();
        break;
        
    case '/api/channels':
        handleApiChannels();
        break;
        
    case '/api/stream':
        handleApiStream();
        break;
        
    case '/logout':
        handleLogout();
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
                <a href="/dashboard" class="btn">📺 Dashboard</a>
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
            $_SESSION['user'] = $username;
            $_SESSION['login_time'] = time();
            header('Location: /dashboard');
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

function showDashboard() {
    // Check if user is logged in
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
        <title>Dashboard - Stalker Player</title>
        <link rel="stylesheet" href="/css/style.css">
        <style>
            .player-container {
                background: #000;
                border-radius: 10px;
                padding: 20px;
                margin: 20px 0;
            }
            #videoPlayer {
                width: 100%;
                height: 400px;
                background: #000;
            }
            .channels-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .channel-card {
                background: rgba(255,255,255,0.1);
                padding: 15px;
                border-radius: 8px;
                cursor: pointer;
                text-align: center;
            }
            .channel-card:hover {
                background: rgba(255,255,255,0.2);
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>📺 Stalker Player Dashboard</h1>
                <p>Përshëndetje, <strong><?= htmlspecialchars($_SESSION['user']) ?></strong>! 
                   <a href="/logout" style="color: #e74c3c; margin-left: 20px;">🚪 Dil</a>
                </p>
            </div>

            <div class="player-container">
                <video id="videoPlayer" controls>
                    Shfletuesi juaj nuk suporton video.
                </video>
                <div class="player-info">
                    <h3 id="currentChannel">Zgjidhni një kanal</h3>
                </div>
            </div>

            <div class="channels-section">
                <h3>📡 Kanalet e Disponueshme</h3>
                <div id="channelsList" class="channels-grid">
                    <!-- Kanale do të ngarkohen me JavaScript -->
                </div>
            </div>
        </div>

        <script>
            // Load channels when page is ready
            document.addEventListener('DOMContentLoaded', function() {
                loadChannels();
            });

            async function loadChannels() {
                try {
                    const response = await fetch('/api/channels');
                    const data = await response.json();
                    
                    if (data.success) {
                        displayChannels(data.channels);
                    }
                } catch (error) {
                    console.error('Error loading channels:', error);
                }
            }

            function displayChannels(channels) {
                const channelsList = document.getElementById('channelsList');
                
                channelsList.innerHTML = channels.map(channel => `
                    <div class="channel-card" onclick="selectChannel(${channel.id}, '${channel.name}')">
                        <div style="font-size: 2em;">📺</div>
                        <h4>${channel.name}</h4>
                        <small>${channel.category}</small>
                    </div>
                `).join('');
            }

            function selectChannel(channelId, channelName) {
                document.getElementById('currentChannel').textContent = `Duke luajtur: ${channelName}`;
                
                // Këtu do të shtohet kodi për të loaduar stream-in aktual
                console.log(`Selected channel: ${channelName} (ID: ${channelId})`);
                
                // Highlight selected channel
                document.querySelectorAll('.channel-card').forEach(card => {
                    card.style.background = 'rgba(255,255,255,0.1)';
                });
                event.currentTarget.style.background = 'rgba(52, 152, 219, 0.3)';
            }
        </script>
    </body>
    </html>
    <?php
}

function handleApiChannels() {
    header('Content-Type: application/json');
    
    $channels = [
        ['id' => 1, 'name' => 'RTSH 1', 'category' => 'Lajme'],
        ['id' => 2, 'name' => 'RTSH 2', 'category' => 'Argëtim'],
        ['id' => 3, 'name' => 'Top Channel', 'category' => 'General'],
        ['id' => 4, 'name' => 'Klan TV', 'category' => 'General'],
        ['id' => 5, 'name' => 'Vizion Plus', 'category' => 'General'],
        ['id' => 6, 'name' => 'ABC News', 'category' => 'Lajme'],
        ['id' => 7, 'name' => 'Film Hits', 'category' => 'Filma'],
        ['id' => 8, 'name' => 'Music TV', 'category' => 'Muzikë'],
    ];
    
    echo json_encode([
        'success' => true,
        'channels' => $channels
    ]);
    exit;
}

function handleApiStream() {
    header('Content-Type: application/json');
    
    $channelId = $_GET['channel_id'] ?? null;
    
    if ($channelId) {
        echo json_encode([
            'success' => true,
            'stream_url' => 'to_be_implemented',
            'channel_id' => $channelId
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Channel ID required'
        ]);
    }
    exit;
}

function handleLogout() {
    $_SESSION = array();
    session_destroy();
    header('Location: /');
    exit;
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
