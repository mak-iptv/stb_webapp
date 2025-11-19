<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Kontrollo nëse useri është i loguar
if (!isset($_SESSION['user'])) {
    header('Location: /login');
    exit;
}

// Merr kanalet nga provideri
$channels = getChannelsFromProvider();
$categories = array_unique(array_column($channels, 'category'));
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Stalker Player</title>
    <link rel="stylesheet" href="/css/style.css">
    <!-- HLS.js për HLS stream support -->
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            min-height: 100vh;
        }

        .dashboard-header {
            background: rgba(0, 0, 0, 0.8);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-left h1 {
            margin: 0;
            font-size: 24px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-welcome {
            color: #ccc;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Player Section */
        .player-section {
            margin-bottom: 30px;
        }

        .player-container {
            background: #000;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            margin-bottom: 15px;
        }

        #videoPlayer {
            width: 100%;
            height: 500px;
            background: #000;
        }

        .player-info {
            padding: 15px 20px;
            background: rgba(255,255,255,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }

        .channel-info h3 {
            margin: 0;
            color: white;
        }

        .player-controls {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .player-controls button {
            padding: 8px 15px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .player-controls button:hover {
            background: #2980b9;
        }

        .player-controls button:disabled {
            background: #7f8c8d;
            cursor: not-allowed;
        }

        /* Channels Section */
        .channels-section {
            background: rgba(255,255,255,0.1);
            padding: 25px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-header h2 {
            color: white;
            margin: 0;
        }

        .stats {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
        }

        .search-filter-container {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 250px;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px;
            border: none;
            border-radius: 25px;
            font-size: 16px;
            background: rgba(255,255,255,0.9);
        }

        .category-filter {
            flex: 2;
        }

        .category-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .category-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 14px;
        }

        .category-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        .category-btn.active {
            background: #3498db;
            transform: scale(1.05);
        }

        /* Channels Grid */
        .channels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            max-height: 600px;
            overflow-y: auto;
            padding: 10px;
        }

        .channel-card {
            background: rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            position: relative;
            border: 2px solid transparent;
        }

        .channel-card:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-5px);
            border-color: rgba(255,255,255,0.3);
        }

        .channel-card.active {
            background: rgba(52, 152, 219, 0.3);
            border-color: #3498db;
            transform: scale(1.02);
        }

        .channel-number {
            position: absolute;
            top: 8px;
            left: 8px;
            background: rgba(0,0,0,0.7);
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }

        .channel-logo {
            margin-bottom: 10px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .channel-logo img {
            max-width: 80px;
            max-height: 60px;
            object-fit: contain;
            border-radius: 8px;
        }

        .channel-info h4 {
            margin: 5px 0;
            color: white;
            font-size: 14px;
            line-height: 1.3;
        }

        .channel-category {
            font-size: 11px;
            color: #bbb;
            margin-bottom: 5px;
        }

        .stream-id {
            font-size: 10px;
            color: #888;
            font-family: monospace;
        }

        /* Loading & Error States */
        .loading {
            text-align: center;
            padding: 30px;
            color: white;
            display: none;
        }

        .loading-spinner {
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top: 3px solid #3498db;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .error-message {
            background: #e74c3c;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            text-align: center;
            display: none;
        }

        .success-message {
            background: #27ae60;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 10px 0;
            text-align: center;
            display: none;
        }

        /* Volume Control */
        .volume-control {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 15px;
        }

        .volume-slider {
            width: 80px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
                padding: 15px;
            }

            .container {
                padding: 10px;
            }

            #videoPlayer {
                height: 300px;
            }

            .player-info {
                flex-direction: column;
                gap: 15px;
            }

            .player-controls {
                justify-content: center;
            }

            .search-filter-container {
                flex-direction: column;
            }

            .channels-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 10px;
            }

            .channel-card {
                padding: 10px;
            }

            .channel-logo {
                height: 60px;
            }

            .channel-logo img {
                max-width: 60px;
                max-height: 45px;
            }
        }

        @media (max-width: 480px) {
            .channels-grid {
                grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
            }

            .category-buttons {
                justify-content: center;
            }

            .category-btn {
                font-size: 12px;
                padding: 6px 12px;
            }
        }

        /* Scrollbar Styling */
        .channels-grid::-webkit-scrollbar {
            width: 8px;
        }

        .channels-grid::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }

        .channels-grid::-webkit-scrollbar-thumb {
            background: rgba(255,255,255,0.3);
            border-radius: 4px;
        }

        .channels-grid::-webkit-scrollbar-thumb:hover {
            background: rgba(255,255,255,0.5);
        }

        /* Now Playing Badge */
        .now-playing {
            background: #27ae60;
            color: white;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 10px;
            margin-top: 5px;
        }

        /* Favorite Star */
        .favorite-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: none;
            border: none;
            color: #f39c12;
            cursor: pointer;
            font-size: 16px;
        }

        .favorite-btn:hover {
            color: #e67e22;
        }

        /* Stream Info */
        .stream-info {
            font-size: 12px;
            color: #ccc;
            margin-top: 5px;
            font-family: monospace;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-left">
            <h1>🎬 Stalker Player</h1>
        </div>
       <!-- Në pjesën e header-it, zëvendëso: -->
<div class="user-info">
    <span class="user-welcome">Përshëndetje, <strong><?= htmlspecialchars($_SESSION['user']) ?></strong></span>
    <a href="/logout" class="logout-btn">🚪 Dil</a>
</div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Player Section -->
        <div class="player-section">
            <div class="player-container">
                <video id="videoPlayer" controls crossorigin="anonymous">
                    Shfletuesi juaj nuk suporton video.
                </video>
                <div class="player-info">
                    <div class="channel-info">
                        <h3 id="currentChannel">Zgjidhni një kanal për të filluar shikimin</h3>
                        <div id="streamInfo" class="stream-info"></div>
                    </div>
                    <div class="player-controls">
                        <button id="playBtn" disabled>▶️ Play</button>
                        <button id="pauseBtn" disabled>⏸️ Pause</button>
                        <button id="muteBtn">🔊 Mute</button>
                        <button id="fullscreenBtn">⛶ Fullscreen</button>
                        <div class="volume-control">
                            <span>🔊</span>
                            <input type="range" id="volumeSlider" class="volume-slider" min="0" max="1" step="0.1" value="1">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Channels Section -->
        <div class="channels-section">
            <div class="section-header">
                <h2>📡 Kanalet e Disponueshme</h2>
                <div class="stats">
                    <?= count($channels) ?> kanale | <?= count($categories) ?> kategori
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="search-filter-container">
                <div class="search-box">
                    <input type="text" id="searchChannel" placeholder="🔍 Kërko kanale...">
                </div>
                <div class="category-filter">
                    <div class="category-buttons" id="categoryFilters">
                        <button class="category-btn active" data-category="all">Të gjitha</button>
                        <?php foreach ($categories as $category): ?>
                            <button class="category-btn" data-category="<?= htmlspecialchars($category) ?>">
                                <?= htmlspecialchars($category) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Channels Grid -->
            <div id="channelsList" class="channels-grid">
                <?php foreach ($channels as $channel): ?>
                    <div class="channel-card" 
                         data-channel-id="<?= $channel['id'] ?>" 
                         data-stream-id="<?= $channel['stream_id'] ?>"
                         data-channel-name="<?= htmlspecialchars($channel['name']) ?>" 
                         data-category="<?= htmlspecialchars($channel['category']) ?>">
                        
                        <?php if (isset($channel['number'])): ?>
                            <div class="channel-number"><?= $channel['number'] ?></div>
                        <?php endif; ?>

                        <button class="favorite-btn" onclick="toggleFavorite(this, <?= $channel['id'] ?>)">★</button>
                        
                        <div class="channel-logo">
                            <?php if (!empty($channel['logo'])): ?>
                                <img src="<?= $channel['logo'] ?>" 
                                     alt="<?= $channel['name'] ?>" 
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='block'">
                                <div style="font-size: 2em; display: none;">📺</div>
                            <?php else: ?>
                                <div style="font-size: 2em;">📺</div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="channel-info">
                            <h4><?= htmlspecialchars($channel['name']) ?></h4>
                            <div class="channel-category"><?= htmlspecialchars($channel['category']) ?></div>
                            <div class="stream-id">ID: <?= $channel['stream_id'] ?></div>
                            <div class="now-playing" style="display: none;">● LIVE</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loading" class="loading">
            <div class="loading-spinner"></div>
            <p>🔄 Duke ngarkuar kanalin...</p>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="error-message"></div>

        <!-- Success Message -->
        <div id="successMessage" class="success-message"></div>
    </div>

    <script>
        // Variabla globale
        let currentChannel = null;
        let videoPlayer = document.getElementById('videoPlayer');
        let hls = null;
        let allChannels = <?= json_encode($channels) ?>;
        let favorites = JSON.parse(localStorage.getItem('favoriteChannels') || '[]');

        // Initialize kur faqja të jetë e gatshme
        document.addEventListener('DOMContentLoaded', function() {
            initializePlayer();
            setupEventListeners();
            setupSearchAndFilter();
            loadFavorites();
            checkHlsSupport();
        });

        // Initialize video player
        function initializePlayer() {
            // Set volume from localStorage or default
            const savedVolume = localStorage.getItem('playerVolume');
            if (savedVolume) {
                videoPlayer.volume = parseFloat(savedVolume);
                document.getElementById('volumeSlider').value = videoPlayer.volume;
            }

            // Set mute state
            const savedMute = localStorage.getItem('playerMuted');
            if (savedMute === 'true') {
                videoPlayer.muted = true;
                document.getElementById('muteBtn').textContent = '🔇 Unmute';
            }
        }

        // Check HLS support
        function checkHlsSupport() {
            if (Hls.isSupported()) {
                console.log('✅ HLS.js is supported');
            } else if (videoPlayer.canPlayType('application/vnd.apple.mpegurl')) {
                console.log('✅ Native HLS support (Safari)');
            } else {
                console.log('❌ HLS not supported');
                showError('HLS nuk është i suportuar në këtë shfletues. Ju lutem përdorni Chrome, Firefox ose Safari.');
            }
        }

        // Setup event listeners
        function setupEventListeners() {
            // Play/Pause buttons
            document.getElementById('playBtn').addEventListener('click', function() {
                videoPlayer.play().catch(error => {
                    console.error('Play error:', error);
                    showError('Gabim në play: ' + error.message);
                });
            });
            
            document.getElementById('pauseBtn').addEventListener('click', function() {
                videoPlayer.pause();
            });
            
            // Mute button
            document.getElementById('muteBtn').addEventListener('click', function() {
                videoPlayer.muted = !videoPlayer.muted;
                this.textContent = videoPlayer.muted ? '🔇 Unmute' : '🔊 Mute';
                localStorage.setItem('playerMuted', videoPlayer.muted.toString());
            });
            
            // Fullscreen button
            document.getElementById('fullscreenBtn').addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    if (videoPlayer.requestFullscreen) {
                        videoPlayer.requestFullscreen();
                    } else if (videoPlayer.webkitRequestFullscreen) {
                        videoPlayer.webkitRequestFullscreen();
                    } else if (videoPlayer.mozRequestFullScreen) {
                        videoPlayer.mozRequestFullScreen();
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    } else if (document.mozCancelFullScreen) {
                        document.mozCancelFullScreen();
                    }
                }
            });
            
            // Volume slider
            document.getElementById('volumeSlider').addEventListener('input', function() {
                videoPlayer.volume = this.value;
                localStorage.setItem('playerVolume', this.value);
            });
            
            // Video player events
            videoPlayer.addEventListener('play', function() {
                document.getElementById('playBtn').disabled = true;
                document.getElementById('pauseBtn').disabled = false;
                updateNowPlaying(true);
            });
            
            videoPlayer.addEventListener('pause', function() {
                document.getElementById('playBtn').disabled = false;
                document.getElementById('pauseBtn').disabled = true;
                updateNowPlaying(false);
            });
            
            videoPlayer.addEventListener('waiting', function() {
                document.getElementById('loading').style.display = 'block';
            });
            
            videoPlayer.addEventListener('canplay', function() {
                document.getElementById('loading').style.display = 'none';
            });
            
            videoPlayer.addEventListener('error', function(e) {
                console.error('Video error:', e);
                document.getElementById('loading').style.display = 'none';
                showError('Gabim në luajtjen e videos. Kontrollo lidhjen me internet ose provo një kanal tjetër.');
            });
            
            videoPlayer.addEventListener('loadeddata', function() {
                document.getElementById('playBtn').disabled = false;
            });

            // Keyboard shortcuts
            document.addEventListener('keydown', function(e) {
                if (e.target.tagName === 'INPUT') return; // Mos ndërhyn në input fields
                
                switch(e.key.toLowerCase()) {
                    case ' ':
                        e.preventDefault();
                        if (videoPlayer.paused) {
                            videoPlayer.play();
                        } else {
                            videoPlayer.pause();
                        }
                        break;
                    case 'f':
                        e.preventDefault();
                        if (!document.fullscreenElement) {
                            videoPlayer.requestFullscreen();
                        }
                        break;
                    case 'm':
                        e.preventDefault();
                        videoPlayer.muted = !videoPlayer.muted;
                        document.getElementById('muteBtn').textContent = videoPlayer.muted ? '🔇 Unmute' : '🔊 Mute';
                        break;
                    case 'arrowup':
                        e.preventDefault();
                        videoPlayer.volume = Math.min(1, videoPlayer.volume + 0.1);
                        document.getElementById('volumeSlider').value = videoPlayer.volume;
                        break;
                    case 'arrowdown':
                        e.preventDefault();
                        videoPlayer.volume = Math.max(0, videoPlayer.volume - 0.1);
                        document.getElementById('volumeSlider').value = videoPlayer.volume;
                        break;
                }
            });
        }

        // Setup search and filter functionality
        function setupSearchAndFilter() {
            const searchInput = document.getElementById('searchChannel');
            const categoryButtons = document.querySelectorAll('.category-btn');
            
            // Search functionality
            searchInput.addEventListener('input', function() {
                filterChannels();
            });
            
            // Category filter functionality
            categoryButtons.forEach(button => {
                button.addEventListener('click', function() {
                    categoryButtons.forEach(btn => btn.classList.remove('active'));
                    this.classList.add('active');
                    filterChannels();
                });
            });
        }

        // Filter channels based on search and category
        function filterChannels() {
            const searchTerm = document.getElementById('searchChannel').value.toLowerCase();
            const activeCategory = document.querySelector('.category-btn.active').getAttribute('data-category');
            const channels = document.querySelectorAll('.channel-card');
            
            channels.forEach(channel => {
                const channelName = channel.getAttribute('data-channel-name').toLowerCase();
                const channelCategory = channel.getAttribute('data-category');
                const matchesSearch = channelName.includes(searchTerm);
                const matchesCategory = activeCategory === 'all' || channelCategory === activeCategory;
                
                if (matchesSearch && matchesCategory) {
                    channel.style.display = 'block';
                } else {
                    channel.style.display = 'none';
                }
            });
        }

        // Load favorites from localStorage
        function loadFavorites() {
            favorites.forEach(channelId => {
                const favoriteBtn = document.querySelector(`.favorite-btn[onclick*="${channelId}"]`);
                if (favoriteBtn) {
                    favoriteBtn.style.color = '#f39c12';
                }
            });
        }

        // Toggle favorite channel
        function toggleFavorite(button, channelId) {
            const index = favorites.indexOf(channelId);
            
            if (index === -1) {
                // Add to favorites
                favorites.push(channelId);
                button.style.color = '#f39c12';
                showSuccess('Kanali u shtua në të preferuarat!');
            } else {
                // Remove from favorites
                favorites.splice(index, 1);
                button.style.color = '#ccc';
                showSuccess('Kanali u hoq nga të preferuarat!');
            }
            
            localStorage.setItem('favoriteChannels', JSON.stringify(favorites));
        }

        // Select channel function - UPDATED FOR STALKER FORMAT
        async function selectChannel(channelId, channelName, channelElement) {
            try {
                // Show loading
                showLoading();
                hideError();
                hideSuccess();
                
                // Update UI
                document.getElementById('currentChannel').textContent = `🔄 Duke ngarkuar: ${channelName}...`;
                document.getElementById('streamInfo').textContent = '';
                
                // Remove active class from all channels
                document.querySelectorAll('.channel-card').forEach(card => {
                    card.classList.remove('active');
                    card.querySelector('.now-playing').style.display = 'none';
                });
                
                // Add active class to selected channel
                channelElement.classList.add('active');
                
                // Get stream URL from API
                const response = await fetch(`/api/stream?channel_id=${channelId}`);
                const data = await response.json();
                
                if (data.success && data.stream_url) {
                    // Display stream URL info
                    document.getElementById('streamInfo').textContent = 
                        `Stream ID: ${data.stream_id} | Format: ${data.format || 'HLS'}`;
                    
                    // Load HLS stream (m3u8 format)
                    loadHlsStream(data.stream_url, channelName);
                    
                    currentChannel = { 
                        id: channelId, 
                        name: channelName,
                        stream_id: data.stream_id,
                        url: data.stream_url
                    };
                    
                    // Save to history
                    saveToHistory(currentChannel);
                    
                    // Log stream URL for debugging
                    console.log('Stream URL:', data.stream_url);
                    
                } else {
                    throw new Error(data.message || 'Stream nuk u gjet');
                }
                
            } catch (error) {
                console.error('Error loading channel:', error);
                showError(`Gabim në ngarkimin e kanalit: ${error.message}`);
                document.getElementById('currentChannel').textContent = '❌ Gabim në ngarkim';
            } finally {
                hideLoading();
            }
        }

        // Load HLS stream - OPTIMIZED FOR STALKER M3U8
        function loadHlsStream(streamUrl, channelName) {
            // Destroy previous HLS instance
            if (hls) {
                hls.destroy();
                hls = null;
            }

            // Clear previous source
            videoPlayer.src = '';

            if (Hls.isSupported()) {
                console.log('🚀 Using HLS.js for stream:', streamUrl);
                
                hls = new Hls({
                    enableWorker: true,
                    lowLatencyMode: true,
                    backBufferLength: 90,
                    maxBufferLength: 30,
                    maxMaxBufferLength: 60,
                    maxBufferSize: 60 * 1000 * 1000, // 60MB
                    maxBufferHole: 0.5,
                    highBufferWatchdogPeriod: 2,
                    nudgeOffset: 0.1,
                    nudgeMaxRetry: 3,
                    maxFragLookUpTolerance: 0.2,
                    liveSyncDurationCount: 3,
                    liveMaxLatencyDurationCount: 10,
                    liveDurationInfinity: true,
                    liveBackBufferLength: 90,
                    debug: false
                });
                
                hls.loadSource(streamUrl);
                hls.attachMedia(videoPlayer);
                
                hls.on(Hls.Events.MANIFEST_PARSED, function() {
                    console.log('✅ HLS manifest parsed successfully');
                    videoPlayer.play().then(() => {
                        document.getElementById('currentChannel').textContent = `▶️ Duke luajtur: ${channelName}`;
                        showSuccess(`Kanali ${channelName} u ngarkua me sukses!`);
                    }).catch(playError => {
                        console.error('Auto-play failed:', playError);
                        document.getElementById('currentChannel').textContent = `📺 ${channelName} - Kliko Play`;
                        showError('Auto-play dështoi. Ju lutem klikoni butonin Play.');
                    });
                });
                
                hls.on(Hls.Events.LEVEL_LOADED, function(event, data) {
                    console.log('📊 Level loaded:', data.details);
                });
                
                hls.on(Hls.Events.FRAG_LOADED, function(event, data) {
                    // console.log('📦 Fragment loaded:', data.frag.url);
                });
                
                hls.on(Hls.Events.ERROR, function(event, data) {
                    console.error('❌ HLS error:', data);
                    
                    if (data.fatal) {
                        switch(data.type) {
                            case Hls.ErrorTypes.NETWORK_ERROR:
                                console.error('🌐 Network error - trying to recover...');
                                showError('Gabim në rrjet. Duke u rikthyer...');
                                hls.startLoad();
                                break;
                            case Hls.ErrorTypes.MEDIA_ERROR:
                                console.error('📺 Media error - recovering...');
                                showError('Gabim në media. Duke u rikthyer...');
                                hls.recoverMediaError();
                                break;
                            default:
                                console.error('💀 Fatal error - cannot recover');
                                showError('Gabim fatal. Duke ringarkuar...');
                                hls.destroy();
                                // Try direct play as fallback
                                videoPlayer.src = streamUrl;
                                break;
                        }
                    }
                });
                
            } else if (videoPlayer.canPlayType('application/vnd.apple.mpegurl')) {
                // Native HLS support (Safari)
                console.log('🍎 Using native HLS support for Safari');
                videoPlayer.src = streamUrl;
                videoPlayer.addEventListener('loadedmetadata', function() {
                    videoPlayer.play().then(() => {
                        document.getElementById('currentChannel').textContent = `▶️ Duke luajtur: ${channelName}`;
                        showSuccess(`Kanali ${channelName} u ngarkua me sukses!`);
                    }).catch(playError => {
                        console.error('Auto-play failed:', playError);
                        document.getElementById('currentChannel').textContent = `📺 ${channelName} - Kliko Play`;
                    });
                });
            } else {
                showError('HLS nuk është i suportuar në këtë shfletues. Ju lutem përdorni Chrome, Firefox ose Safari.');
            }
        }

        // Save channel to watch history
        function saveToHistory(channel) {
            let history = JSON.parse(localStorage.getItem('watchHistory') || '[]');
            
            // Remove if already exists
            history = history.filter(item => item.id !== channel.id);
            
            // Add to beginning
            history.unshift({
                id: channel.id,
                name: channel.name,
                stream_id: channel.stream_id,
                timestamp: new Date().toISOString(),
                url: channel.url
            });
            
            // Keep only last 20 items
            history = history.slice(0, 20);
            
            localStorage.setItem('watchHistory', JSON.stringify(history));
        }

        // Update now playing indicator
        function updateNowPlaying(isPlaying) {
            if (currentChannel) {
                const nowPlaying = document.querySelector('.channel-card.active .now-playing');
                if (nowPlaying) {
                    nowPlaying.style.display = isPlaying ? 'block' : 'none';
                    nowPlaying.textContent = isPlaying ? '● LIVE' : '';
                }
            }
        }

        // Utility functions
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }

        function hideLoading() {
            document.getElementById('loading').style.display = 'none';
        }

        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => errorDiv.style.display = 'none', 5000);
        }

        function hideError() {
            document.getElementById('errorMessage').style.display = 'none';
        }

        function showSuccess(message) {
            const successDiv = document.getElementById('successMessage');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
            setTimeout(() => successDiv.style.display = 'none', 3000);
        }

        function hideSuccess() {
            document.getElementById('successMessage').style.display = 'none';
        }

        // Auto-attach click events to channel cards
        document.addEventListener('click', function(e) {
            const channelCard = e.target.closest('.channel-card');
            if (channelCard && !e.target.classList.contains('favorite-btn')) {
                const channelId = channelCard.getAttribute('data-channel-id');
                const channelName = channelCard.getAttribute('data-channel-name');
                selectChannel(parseInt(channelId), channelName, channelCard);
            }
        });

        // Auto-reload channels every 10 minutes
        setInterval(async () => {
            try {
                const response = await fetch('/api/channels');
                const data = await response.json();
                if (data.success) {
                    allChannels = data.channels;
                    console.log('🔄 Channels list updated');
                }
            } catch (error) {
                console.error('Error updating channels:', error);
            }
        }, 600000); // 10 minutes

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (hls) {
                hls.destroy();
            }
        });

        // Network status monitoring
        window.addEventListener('online', function() {
            showSuccess('Lidhja u rikthye!');
        });

        window.addEventListener('offline', function() {
            showError('Keni humbur lidhjen me internet!');
        });
    </script>
</body>
</html>
