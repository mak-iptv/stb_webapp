<?php
// debug.php
session_start();
require_once __DIR__ . '/includes/functions.php';

if (!isset($_SESSION['portal_url'])) {
    header('Location: login.php');
    exit;
}

echo "<h1>Debug Information</h1>";
echo "<p><strong>Portal URL:</strong> " . $_SESSION['portal_url'] . "</p>";
echo "<p><strong>MAC Address:</strong> " . ($_SESSION['user_mac'] ?? 'Auto-generated') . "</p>";

debugApiResponse($_SESSION['portal_url'], $_SESSION['user_mac'] ?? generateMacAddress());

echo '<br><a href="index.php">Kthehu te kanalet</a>';
?>
