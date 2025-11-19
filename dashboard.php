<?php
session_start();

// Kontrollo nëse useri është i loguar
if (!isset($_SESSION['user'])) {
    header('Location: ?page=login');
    exit;
}

// Merr kanalet nga provideri
require_once 'includes/functions.php';
$channels = getChannelsFromProvider();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard - Stalker Player</title>
    <style>
        body { 
            font-family: Arial; 
            margin: 0;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            min-height: 100vh;
        }
        .header { 
            background: rgba(0,0,0,0.8); 
            color: white; 
            padding: 20px; 
            text-align: center;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .user-info {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
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
            text-align: center;
            cursor: pointer;
        }
        .channel-card:hover {
            background: rgba(255,255,255,0.2);
        }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🎬 Dashboard - Stalker Player</h1>
    </div>
    
    <div class="container">
        <div class="user-info">
            <h2>✅ U lidh me sukses!</h2>
            <p><strong>Portal:</strong> <?= htmlspecialchars($_SESSION['portal_url'] ?? 'N/A') ?></p>
            <p><strong>MAC:</strong> <?= htmlspecialchars($_SESSION['mac_address'] ?? 'N/A') ?></p>
            <p><strong>Kanale:</strong> 
                <?php if (!empty($channels)): ?>
                    <span class="success"><?= count($channels) ?> kanale u gjetën</span>
                <?php else: ?>
                    <span class="error">Nuk u gjetën kanale</span>
                <?php endif; ?>
            </p>
        </div>
        
        <?php if (!empty($channels)): ?>
            <h3>📡 Kanalet e gjetura:</h3>
            <div class="channels-grid">
                <?php foreach ($channels as $channel): ?>
                    <div class="channel-card">
                        <div style="font-size: 2em;">📺</div>
                        <h4><?= htmlspecialchars($channel['name']) ?></h4>
                        <small>#<?= $channel['number'] ?> | <?= htmlspecialchars($channel['category']) ?></small>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="background: rgba(231, 76, 60, 0.2); padding: 20px; border-radius: 10px; text-align: center;">
                <h3>❌ Nuk u gjetën kanale</h3>
                <p>Provoni të rifreskoni faqen ose kontrolloni konfigurimin.</p>
                <a href="?page=login" class="btn">⚙️ Ndrysho Konfigurimin</a>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 20px;">
            <a href="?page=home" class="btn">🏠 Home</a>
            <a href="?page=logout" class="btn">🚪 Dil</a>
        </div>
    </div>
</body>
</html>
