<?php
// watch.php
require_once 'includes/functions.php';

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
        .player-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        video {
            width: 100%;
            height: auto;
            background: #000;
        }
    </style>
</head>
<body>
    <div class="player-container">
        <h2>TV Live</h2>
        <?= displayStalkerPlayer($stream_url, "100%", "600px") ?>
        <br>
        <a href="index.php">Kthehu te kanalet</a>
    </div>
</body>
</html>
