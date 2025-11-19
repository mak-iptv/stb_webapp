<?php
session_start();

// Përcakto faqen bazuar në query parameter
$page = $_GET['page'] ?? 'home';

switch ($page) {
    case 'login':
        require 'login.php';
        break;
        
    case 'dashboard':
        require 'dashboard.php';
        break;
        
    case 'logout':
        require 'logout.php';
        break;
        
    case 'test':
        require 'test.php';
        break;
        
    case 'home':
    default:
        show_home_page();
        break;
}

function show_home_page() {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Stalker Player</title>
        <style>
            body { font-family: Arial; margin: 40px; text-align: center; }
            .btn { display: inline-block; padding: 15px 30px; margin: 10px; 
                   background: #3498db; color: white; text-decoration: none; 
                   border-radius: 5px; }
            .user-info { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <h1>🎬 Stalker Player</h1>
        
        <?php if (isset($_SESSION['user'])): ?>
            <div class="user-info">
                <h3>✅ Jeni të loguar</h3>
                <p>User: <strong><?= htmlspecialchars($_SESSION['user']) ?></strong></p>
                <p>Portal: <strong><?= htmlspecialchars($_SESSION['portal_url'] ?? 'N/A') ?></strong></p>
            </div>
            <a href="?page=dashboard" class="btn">🚀 Shko në Dashboard</a>
            <a href="?page=logout" class="btn">🚪 Dil</a>
        <?php else: ?>
            <p>Platforma moderne për shikim IPTV</p>
            <a href="?page=login" class="btn">🔐 Hyr në Sistem</a>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="?page=test" class="btn">🧪 Test Page</a>
        </div>
    </body>
    </html>
    <?php
}
?>
