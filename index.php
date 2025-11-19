<?php
// index.php
session_start();
require_once 'includes/functions.php';

// Kontrollo nëse është lidhur
if (!isset($_SESSION['portal_url'])) {
    header('Location: login.php');
    exit;
}

// Merr kanalet
$channels = $_SESSION['channels'] ?? getChannelsFromProvider($_SESSION['portal_url']);

// Shfaq kanalet
echo displayChannelsList($channels, $_SESSION['portal_url']);
?>
