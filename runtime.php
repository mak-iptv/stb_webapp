<?php
// Runtime configuration for Render
if (getenv('RENDER')) {
    // Render-specific settings
    define('RENDER_EXTERNAL_HOST', getenv('RENDER_EXTERNAL_HOST') ?: 'localhost');
    define('RENDER_EXTERNAL_URL', getenv('RENDER_EXTERNAL_URL') ?: 'http://localhost:10000');
    
    // Use Render's port
    $port = getenv('PORT') ?: 10000;
    
    // Adjust base URL for production
    define('BASE_URL', getenv('RENDER_EXTERNAL_URL') ?: ('http://localhost:' . $port));
} else {
    // Local development
    define('BASE_URL', 'http://localhost:8000');
}
?>
