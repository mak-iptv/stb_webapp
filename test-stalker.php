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
        .success { color: green; padding: 10px; background: #d4edda; }
        .error { color: red; padding: 10px; background: #f8d7da; }
        .channel { padding: 10px; border-bottom: 1px solid #ccc; }
        .stream-url { font-size: 12px; color: #666; word-break: break-all; }
        .test-btn { background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px; font-size: 12px; }
    </style>
</head>
<body>
    <h1>🧪 Test Stalker Connection</h1>
    
    <h2>Config:</h2>
    <ul>
        <li>Portal URL: <strong><?= STALKER_PORTAL_URL ?></strong></li>
        <li>MAC: <strong><?= STALKER_MAC_ADDRESS ?></strong></li>
        <li>Username: <strong><?= STALKER_USERNAME ?></strong></li>
    </ul>
    
    <h2>Connection Test:</h2>
    <?php if ($test_result['success']): ?>
        <div class="success">
            ✅ SUCCESS - Gjetur <?= $test_result['channels_count'] ?> kanale
            <br><small>API: <?= $test_result['api_url'] ?></small>
        </div>
        
        <h3>Sample Channels (5 të parët):</h3>
        <?php foreach ($test_result['channels_sample'] as $channel): ?>
            <div class="channel">
                <strong>#<?= $channel['number'] ?? '' ?> <?= $channel['name'] ?></strong>
                (ID: <?= $channel['id'] ?>)<br>
                Category: <?= $channel['category'] ?>
                <br>
                <a href="#" class="test-btn" onclick="testStream(<?= $channel['id'] ?>, '<?= $channel['name'] ?>')">Test Stream</a>
                <div class="stream-url">
                    <?= getStreamUrl($channel) ?>
                </div>
            </div>
        <?php endforeach; ?>
        
    <?php else: ?>
        <div class="error">
            ❌ FAILED - <?= $test_result['error'] ?? 'Unknown error' ?>
        </div>
    <?php endif; ?>
    
    <h2>All Channels (<?= count($channels) ?>):</h2>
    <?php foreach ($channels as $channel): ?>
        <div class="channel">
            <strong>#<?= $channel['number'] ?? '' ?> <?= $channel['name'] ?></strong>
            (ID: <?= $channel['id'] ?>) | Category: <?= $channel['category'] ?>
            <br>
            <a href="#" class="test-btn" onclick="testStream(<?= $channel['id'] ?>, '<?= $channel['name'] ?>')">Test Stream</a>
            <div class="stream-url">
                <?= getStreamUrl($channel) ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <script>
    function testStream(channelId, channelName) {
        fetch(`/api/stream?channel_id=${channelId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const message = `✅ ${channelName}\n\nStream URL: ${data.stream_url}\n\nDëshironi të hapet në tab të ri?`;
                    if (confirm(message)) {
                        window.open(data.stream_url, '_blank');
                    }
                } else {
                    alert(`❌ ${channelName}\nError: ${data.message}`);
                }
            })
            .catch(error => {
                alert(`❌ ${channelName}\nNetwork Error: ${error.message}`);
            });
    }
    </script>
</body>
</html>
