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
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Stalker Player</title>
    <link rel="stylesheet" href="/css/style.css">
    <style>
        .dashboard-header {
            background: rgba(0, 0, 0, 0.8);
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-radius: 10px;
        }
        
        .user-info {
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 5px;
        }
        
        .player-container {
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        #videoPlayer {
            width: 100%;
            height: 500px;
            background: #000;
        }
        
        .player-info {
            padding: 15px;
            background: rgba(255,255,255,0.1);
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .player-controls button {
            padding: 8px 15px;
            margin-left: 10px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .channels-section {
            background: rgba(255,255,255,0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .category-filter {
            margin-bottom: 20px;
        }
        
        .category-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .category-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 20px;
            cursor: pointer;
        }
        
        .category-btn.active {
            background: #3498db;
        }
        
        .channels-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            max-height: 600px;
            overflow-y: auto;
        }
        
        .channel-card {
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        
        .channel-card:hover {
            background: rgba(255,255,255,0.2);
            transform: translateY(-2px);
        }
        
        .channel-card.active {
            background: rgba(52, 152, 219, 0.3);
            border: 2px solid #3498db;
        }
        
        .channel-logo {
            margin-bottom: 10px;
        }
        
        .channel-logo img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .channel-info h4 {
            margin: 10px 0 5px 0;
            color: white;
        }
        
        .channel-category {
            font-size: 12px;
            color: #bbb;
        }
        
        .loading {
            text-align: center;
            padding: 20px;
            color: white;
        }
        
        .error-message {
            background: #e74c3c;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            text-align: center;
        }
        
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 10px;
                text-align: center;
            }
            
            #videoPlayer {
                height: 300px;
            }
            
            .channels-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-header">
        <h1 style="color: white; margin: 0;">🎬 Stalker Player Dashboard</h1>
        <div class="user-info">
            <span>Përshëndetje, <strong><?= htmlspecialchars($_SESSION['user']) ?></strong></span>
            <a href="/logout" class="logout-btn">🚪 Dil</a>
        </div>
    </div>

    <div class="container">
        <!-- Video Player -->
        <div class="player-container">
            <video id="videoPlayer" controls crossorigin="anonymous">
                Shfletuesi juaj nuk suporton video.
            </video>
            <div class="player-info">
                <h3 id="currentChannel">Zgjidhni një kanal për të filluar shikimin</h3>
                <div class="player-controls">
                    <button id="playBtn">Play</button>
                    <button id="pauseBtn">Pause</button>
                    <button id="fullscreenBtn">Fullscreen</button>
                    <button id="muteBtn">Mute</button>
                </div>
            </div>
        </div>

        <!-- Channels Section -->
        <div class="channels-section">
            <div class="search-box">
                <input type="text" id="searchChannel" placeholder="🔍 Kërko kanale...">
            </div>
            
            <div class="category-filter">
                <h3 style="color: white; margin-bottom: 10px;">Kategoritë:</h3>
                <div class="category-buttons" id="categoryFilters">
                    <button class="category-btn active" data-category="all">Të gjitha</button>
                    <!-- Kategoritë do të shtohen me JavaScript -->
                </div>
            </div>
            
            <h3 style="color: white; margin-bottom: 15px;">📡 Kanalet e Disponueshme (<?= count($channels) ?>)</h3>
            
            <div id="channelsList" class="channels-grid">
                <?php foreach ($channels as $channel): ?>
                    <div class="channel-card" 
                         data-channel-id="<?= $channel['id'] ?>" 
                         data-channel-name="<?= htmlspecialchars($channel['name']) ?>" 
                         data-category="<?= htmlspecialchars($channel['category']) ?>">
                        <div class="channel-logo">
                            <?php if (!empty($channel['logo'])): ?>
                                <img src="<?= $channel['logo'] ?>" alt="<?= $channel['name'] ?>" onerror="this.style.display='none'">
                            <?php else: ?>
                                <div style="font-size: 2em;">📺</div>
                            <?php endif; ?>
                        </div>
                        <div class="channel-info">
                            <h4><?= htmlspecialchars($channel['name']) ?></h4>
                            <div class="channel-category"><?= htmlspecialchars($channel['category']) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loading" class="loading" style="display: none;">
            <p>🔄 Duke ngarkuar kanalin...</p>
        </div>

        <!-- Error Message -->
        <div id="errorMessage" class="error-message" style="display: none;"></div>
    </div>

    <script>
        // Variabla globale
        let currentChannel = null;
        let videoPlayer = document.getElementById('videoPlayer');
        let allChannels = <?= json_encode($channels) ?>;

        // Initialize kur faqja të jetë e gatshme
        document.addEventListener('DOMContentLoaded', function() {
            initializePlayer();
            setupEventListeners();
            setupCategoryFilters();
            setupSearch();
        });

        // Initialize video player
        function initializePlayer() {
            // Kontrollo nëse HLS është i nevojshëm
            if (videoPlayer.canPlayType('application/vnd.apple.mpegurl')) {
                console.log('HLS supported natively');
            } else if (window.Hls && Hls.isSupported()) {
                console.log('HLS supported me Hls.js');
            }
        }

        // Setup event listeners
        function setupEventListeners() {
            // Play/Pause buttons
            document.getElementById('playBtn').addEventListener('click', function() {
                videoPlayer.play();
            });
            
            document.getElementById('pauseBtn').addEventListener('click', function() {
                videoPlayer.pause();
            });
            
            // Fullscreen button
            document.getElementById('fullscreenBtn').addEventListener('click', function() {
                if (!document.fullscreenElement) {
                    if (videoPlayer.requestFullscreen) {
                        videoPlayer.requestFullscreen();
                    } else if (videoPlayer.webkitRequestFullscreen) {
                        videoPlayer.webkitRequestFullscreen();
                    }
                } else {
                    if (document.exitFullscreen) {
                        document.exitFullscreen();
                    } else if (document.webkitExitFullscreen) {
                        document.webkitExitFullscreen();
                    }
                }
            });
            
            // Mute button
            document.getElementById('muteBtn').addEventListener('click', function() {
                videoPlayer.muted = !videoPlayer.muted;
                this.textContent = videoPlayer.muted ? 'Unmute' : 'Mute';
            });
            
            // Channel card clicks
            document.querySelectorAll('.channel-card').forEach(card => {
                card.addEventListener('click', function() {
                    const channelId = this.getAttribute('data-channel-id');
                    const channelName = this.getAttribute('data-channel-name');
                    selectChannel(channelId, channelName, this);
                });
            });
        }

        // Setup category filters
        function setupCategoryFilters() {
            const categories = [...new Set(allChannels.map(channel => channel.category))];
            const filtersContainer = document.getElementById('categoryFilters');
            
            categories.forEach(category => {
                const button = document.createElement('button');
                button.className = 'category-btn';
                button.textContent = category;
                button.setAttribute('data-category', category);
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    document.querySelectorAll('.category-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    // Add active class to clicked button
                    this.classList.add('active');
                    filterChannelsByCategory(this.getAttribute('data-category'));
                });
                filtersContainer.appendChild(button);
            });
        }

        // Setup search functionality
        function setupSearch() {
            const searchInput = document.getElementById('searchChannel');
            searchInput.addEventListener('input', function() {
                filterChannelsBySearch(this.value);
            });
        }

        // Filter channels by category
        function filterChannelsByCategory(category) {
            const channels = document.querySelectorAll('.channel-card');
            channels.forEach(channel => {
                if (category === 'all' || channel.getAttribute('data-category') === category) {
                    channel.style.display = 'block';
                } else {
                    channel.style.display = 'none';
                }
            });
        }

        // Filter channels by search
        function filterChannelsBySearch(searchTerm) {
            const channels = document.querySelectorAll('.channel-card');
            const activeCategory = document.querySelector('.category-btn.active').getAttribute('data-category');
            
            channels.forEach(channel => {
                const channelName = channel.getAttribute('data-channel-name').toLowerCase();
                const channelCategory = channel.getAttribute('data-category');
                const matchesSearch = channelName.includes(searchTerm.toLowerCase());
                const matchesCategory = activeCategory === 'all' || channelCategory === activeCategory;
                
                if (matchesSearch && matchesCategory) {
                    channel.style.display = 'block';
                } else {
                    channel.style.display = 'none';
                }
            });
        }

        // Select channel function
        async function selectChannel(channelId, channelName, channelElement) {
            try {
                // Show loading
                document.getElementById('loading').style.display = 'block';
                document.getElementById('errorMessage').style.display = 'none';
                
                // Update UI
                document.getElementById('currentChannel').textContent = `🔄 Duke ngarkuar: ${channelName}...`;
                
                // Remove active class from all channels
                document.querySelectorAll('.channel-card').forEach(card => {
                    card.classList.remove('active');
                });
                
                // Add active class to selected channel
                channelElement.classList.add('active');
                
                // Get stream URL from API
                const response = await fetch(`/api/stream?channel_id=${channelId}`);
                const data = await response.json();
                
                if (data.success && data.stream_url) {
                    // Set video source
                    videoPlayer.src = data.stream_url;
                    videoPlayer.load();
                    
                    // Play the video
                    videoPlayer.play().then(() => {
                        document.getElementById('currentChannel').textContent = `▶️ Duke luajtur: ${channelName}`;
                        currentChannel = { id: channelId, name: channelName };
                    }).catch(error => {
                        console.error('Play error:', error);
                        document.getElementById('currentChannel').textContent = `📺 ${channelName} - Kliko Play`;
                    });
                    
                } else {
                    throw new Error(data.message || 'Stream nuk u gjet');
                }
                
            } catch (error) {
                console.error('Error loading channel:', error);
                document.getElementById('errorMessage').textContent = 
                    `Gabim në ngarkimin e kanalit: ${error.message}`;
                document.getElementById('errorMessage').style.display = 'block';
                document.getElementById('currentChannel').textContent = '❌ Gabim në ngarkim';
            } finally {
                // Hide loading
                document.getElementById('loading').style.display = 'none';
            }
        }

        // Video player event listeners
        videoPlayer.addEventListener('loadstart', function() {
            console.log('Video loading started');
        });
        
        videoPlayer.addEventListener('canplay', function() {
            console.log('Video can start playing');
        });
        
        videoPlayer.addEventListener('error', function(e) {
            console.error('Video error:', e);
            document.getElementById('errorMessage').textContent = 
                'Gabim në luajtjen e videos. Kontrollo lidhjen me internet.';
            document.getElementById('errorMessage').style.display = 'block';
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            switch(e.key) {
                case ' ':
                    e.preventDefault();
                    if (videoPlayer.paused) {
                        videoPlayer.play();
                    } else {
                        videoPlayer.pause();
                    }
                    break;
                case 'f':
                case 'F':
                    e.preventDefault();
                    if (!document.fullscreenElement) {
                        videoPlayer.requestFullscreen();
                    }
                    break;
                case 'm':
                case 'M':
                    e.preventDefault();
                    videoPlayer.muted = !videoPlayer.muted;
                    break;
            }
        });

        // Auto-reload channels every 5 minutes
        setInterval(async () => {
            try {
                const response = await fetch('/api/channels');
                const data = await response.json();
                if (data.success) {
                    allChannels = data.channels;
                    console.log('Channels list updated');
                }
            } catch (error) {
                console.error('Error updating channels:', error);
            }
        }, 300000); // 5 minutes
    </script>
</body>
</html>
