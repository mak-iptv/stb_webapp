<?php
// index.php
session_start();
require_once __DIR__ . '/includes/functions.php';

// Kontrollo nëse është lidhur
if (!isset($_SESSION['portal_url'])) {
    header('Location: login.php');
    exit;
}

// Merr kanalet
$channels = $_SESSION['channels'] ?? getChannelsFromProvider($_SESSION['portal_url']);

// Shfaq informacion për kanalet
echo "<h1>Kanalet e TV</h1>";
echo displayChannelsInfo($channels);

// Shfaq kanalet
echo displayChannelsList($channels, $_SESSION['portal_url']);

// Lidhje për debug nëse kanalet janë demo
if (isset($_SESSION['using_demo_channels'])) {
    echo '<div style="margin: 20px 0; padding: 15px; background: #fff3cd; border-radius: 4px;">';
    echo '<strong>Problem me lidhjen:</strong> Nuk mund të lidhemi me provider-in. ';
    echo '<a href="debug.php" style="color: #856404;">Klikoni këtu për debug</a>';
    echo '</div>';
}
?>
