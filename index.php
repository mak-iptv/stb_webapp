<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Kontrollo authentication
if (!isLoggedIn()) {
    header("Location: login.php");
    exit;
}

$channels = getChannelsList();
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stalker Player</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <div class="player-section">
            <div class="video-container">
                <video id="videoPlayer" controls crossorigin="anonymous">
                    Your browser does not support the video tag.
                </video>
                <div class="player-info">
                    <h3 id="currentChannel">Zgjidhni një kanal</h3>
                    <div class="player-controls">
                        <button id="playBtn">Play</button>
                        <button id="pauseBtn">Pause</button>
                        <button id="fullscreenBtn">Fullscreen</button>
                    </div>
                </div>
            </div>
            
            <div class="channels-section">
                <h3>Kanalet</h3>
                <div class="search-box">
                    <input type="text" id="searchChannel" placeholder="Kërko kanal...">
                </div>
                <div class="channels-grid" id="channelsList">
                    <?php foreach ($channels as $channel): ?>
                        <div class="channel-card" data-channel-id="<?= $channel['id'] ?>" 
                             data-channel-name="<?= htmlspecialchars($channel['name']) ?>">
                            <div class="channel-logo">
                                <img src="<?= $channel['logo'] ?: 'images/default-logo.png' ?>" 
                                     alt="<?= $channel['name'] ?>">
                            </div>
                            <div class="channel-info">
                                <h4><?= htmlspecialchars($channel['name']) ?></h4>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/hls.js/0.5.14/hls.min.js"></script>
    <script src="js/player.js"></script>
    <script src="js/main.js"></script>
    <?php include 'includes/footer.php'; ?>
</body>
</html>