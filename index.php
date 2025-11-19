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

// Ose për debug, shfaq response-t e API
// debugApiResponse($_SESSION['portal_url'], $_SESSION['user_mac'] ?? generateMacAddress());

// Shfaq kanalet
echo displayChannelsList($channels, $_SESSION['portal_url']);
?>
