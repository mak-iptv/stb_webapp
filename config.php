<?php
session_start();

// Detect environment
if (getenv('RENDER')) {
    // Production settings for Render
    define('BASE_URL', getenv('RENDER_EXTERNAL_URL') ?: 'https://stb-webapp.onrender.com/');
    define('API_BASE_URL', 'http://testdi1.proxytx.cloud:80');
    
    // Use Render's file system for sessions
    ini_set('session.save_path', '/tmp');
} else {
    // Local development
    define('BASE_URL', 'http://localhost:8000');
    define('API_BASE_URL', 'http://testdi1.proxytx.cloud:80');
}

// Common configuration
define('DEFAULT_MAC', '00:1A:79:29:86:BB');
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/cache');

// Security headers
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('X-XSS-Protection: 1; mode=block');

// CORS headers
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Create cache directory if it doesn't exist
if (!file_exists(CACHE_DIR)) {
    mkdir(CACHE_DIR, 0755, true);
}
?>
