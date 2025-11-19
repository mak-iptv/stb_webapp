<?php
// watch.php
session_start();
require_once __DIR__ . '/includes/functions.php';

$stream_id = $_GET['stream'] ?? null;
if (!$stream_id) {
    die("Stream ID nuk është specifikuar!");
}

$portal_url = $_SESSION['portal_url'] ?? getDefaultPortalUrl();
$stream_url = createStalkerStreamUrl($portal_url, $stream_id);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Shiko TV Live</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f4f4f4; }
        .player-container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        video { width: 100%; height: auto; background: #000; border-radius: 4px; }
        .back-btn { display: inline-block; margin-bottom: 20px; padding: 10px 15px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="player-container">
        <a href="index.php" class="back-btn">← Kthehu te kanalet</a>
        <h2>TV Live</h2>
        <?= displayStalkerPlayer($stream_url, "100%", "600px") ?>
    </div>
</body>
</html>
