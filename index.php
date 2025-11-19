<?php
require_once 'config.php';

// Kontrollo nëse useri është i loguar
$is_logged_in = isset($_SESSION['user']) && isset($_SESSION['portal_url']) && isset($_SESSION['mac_address']);

// Vendos titullin e faqes bazuar në status
$page_title = $is_logged_in ? "Dashboard - Stalker Player" : "Welcome - Stalker Player";
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: rgba(0, 0, 0, 0.8);
            padding: 20px 0;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }

        .container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            text-align: center;
        }

        .welcome-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 40px;
            border-radius: 20px;
            backdrop-filter: blur(10px);
            max-width: 600px;
            width: 100%;
        }

        .btn {
            display: inline-block;
            padding: 15px 30px;
            margin: 10px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-size: 1.1em;
            font-weight: bold;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .btn:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }

        .btn-login {
            background: #27ae60;
        }

        .btn-login:hover {
            background: #219a52;
        }

        .btn-dashboard {
            background: #e74c3c;
        }

        .btn-dashboard:hover {
            background: #c0392b;
        }

        .user-info {
            background: rgba(255,255,255,0.2);
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
        }

        .footer {
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            text-align: center;
            margin-top: auto;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>🎬 Stalker Player</h1>
        <p>Platforma moderne për shikim IPTV</p>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="welcome-card">
            <?php if ($is_logged_in): ?>
                <h2>👋 Mirë se u kthyet!</h2>
                <div class="user-info">
                    <p><strong>Provider:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'N/A') ?></p>
                    <p><strong>Portal:</strong> <?= htmlspecialchars($_SESSION['portal_url'] ?? 'N/A') ?></p>
                    <p><strong>MAC:</strong> <?= htmlspecialchars($_SESSION['mac_address'] ?? 'N/A') ?></p>
                </div>
                <p>Jeni të lidhur me providerin tuaj IPTV.</p>
                <div>
                    <a href="/dashboard" class="btn btn-dashboard">🚀 Vazhdo në Dashboard</a>
                    <a href="/logout" class="btn">🚪 Dil</a>
                </div>
            <?php else: ?>
                <h2>🔐 Mirë se vini</h2>
                <p>Për të filluar, ju lutem lidhuni me providerin tuaj IPTV.</p>
                <a href="/login" class="btn btn-login">🔗 Lidhu me Providerin</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>© 2024 Stalker Player - Të gjitha të drejtat e rezervuara</p>
    </div>
</body>
</html>
