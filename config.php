<?php
session_start();

// Konfigurimi bazë
define('BASE_URL', 'http://localhost/stalker-player');
define('API_BASE_URL', ' http://line.tivi-ott.net:80');
define('DEFAULT_MAC', '00:1A:79:BB:4D:AA');

// Caching
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
?>