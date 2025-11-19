<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Test Stalker connection
$test_result = testStalkerConnection();
$channels = getChannelsFromProvider();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Test Stalker Connection</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .success { color: green; }
        .error { color: red; }
        .channel { padding: 10px; border-bottom: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>🧪 Test Stalker Connection</h1>
    
    <h2>Config:</h2>
    <ul>
        <li>Portal URL: <?= STALKER_PORTAL_URL ?></li>
        <li>MAC: <?= STALKER_MAC_ADDRESS ?></li>
        <li>Username: <?= STALKER_USERNAME ?></li>
    </ul>
    
    <h2>Connection Test:</h2>
    <?php if ($test_result['success']): ?>
        <div class="success">✅ SUCCESS - Gjetur <?= $test_result['channels_count'] ?> kanale</div>
        
        <h3>Sample Channels:</h3>
        <?php foreach ($test_result['channels_sample'] as $channel): ?>
            <div class="channel">
                <strong><?= $channel['number'] ?? '' ?>. <?= $channel['name'] ?></strong><br>
                ID: <?= $channel['id'] ?> | Stream ID: <?= $channel['stream_id'] ?> | Category: <?= $channel['category'] ?>
            </div>
        <?php endforeach; ?>
        
    <?php else: ?>
        <div class="error">❌ FAILED - <?= $test_result['error'] ?? 'Unknown error' ?></div>
    <?php endif; ?>
    
    <h2>All Channels (<?= count($channels) ?>):</h2>
    <?php foreach ($channels as $channel): ?>
        <div class="channel">
            <strong><?= $channel['number'] ?? '' ?>. <?= $channel['name'] ?></strong><br>
            ID: <?= $channel['id'] ?> | Stream ID: <?= $channel['stream_id'] ?> | Category: <?= $channel['category'] ?>
            <br>
            <small>
                <a href="#" onclick="testStream(<?= $channel['id'] ?>, '<?= $channel['name'] ?>')">Test Stream</a> |
                Stream URL: <?= getStreamUrl($channel) ?>
            </small>
        </div>
    <?php endforeach; ?>
    
    <script>
    function testStream(channelId, channelName) {
        fetch(`/api/stream?channel_id=${channelId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(`✅ ${channelName}\nStream URL: ${data.stream_url}`);
                    // Open in new tab
                    window.open(data.stream_url, '_blank');
                } else {
                    alert(`❌ ${channelName}\nError: ${data.message}`);
                }
            });
    }
    </script>
</body>
</html>
