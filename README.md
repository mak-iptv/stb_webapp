# IPTV Render Starter

Minimal starter project for hosting a simple web IPTV player (PHP + static frontend) on Render.com.

## Features
- Basic AJAX channel list (demo)
- Simple token middleware (demo-token-123)
- M3U generator, EPG sample
- HLS.js frontend player

## How to deploy to Render
1. Push this repository to GitHub.
2. Create a **Web Service** on Render, link the repo.
3. Set environment:
   - Environment: PHP
   - Build command: `composer install`
   - Start command: `php -S 0.0.0.0:10000 -t public`
4. Make sure root directory is set to the repository root (Render will serve from public).

## Security
This starter uses a demo token and is NOT production secure. Replace `auth_middleware.php` with a real JWT/session implementation and use HTTPS.

## Files of interest
- public/index.php  -- player frontend
- public/api/*      -- api endpoints (channels, playlist, epg)
- public/.htaccess  -- MIME types for HLS
- render.yaml       -- example Render configuration
