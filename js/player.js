class StalkerPlayer {
    constructor() {
        this.videoPlayer = document.getElementById('videoPlayer');
        this.hls = null;
        this.currentChannel = null;
        this.isPlaying = false;
        
        this.initializePlayer();
        this.setupEventListeners();
    }
    
    initializePlayer() {
        // Kontrollo nëse HLS është i suportuar
        if (Hls.isSupported()) {
            this.hls = new Hls({
                enableWorker: true,
                lowLatencyMode: true,
                backBufferLength: 90
            });
            
            this.hls.attachMedia(this.videoPlayer);
            
            this.hls.on(Hls.Events.MEDIA_ATTACHED, () => {
                console.log('Video player u lidh me HLS');
            });
            
            this.hls.on(Hls.Events.MANIFEST_PARSED, () => {
                console.log('Manifesti u parsua');
                this.play();
            });
            
            this.hls.on(Hls.Events.ERROR, (event, data) => {
                console.error('Gabim HLS:', data);
                this.handleError(data);
            });
            
        } else if (this.videoPlayer.canPlayType('application/vnd.apple.mpegurl')) {
            // Për Safari (native HLS support)
            this.videoPlayer.addEventListener('loadedmetadata', () => {
                this.play();
            });
        }
    }
    
    async loadChannel(channelId, channelName) {
        try {
            this.showLoading();
            
            const response = await fetch(`api/stream.php?channel_id=${channelId}`);
            const data = await response.json();
            
            if (data.success && data.stream_url) {
                this.currentChannel = channelName;
                this.updateChannelInfo(channelName);
                
                if (Hls.isSupported()) {
                    this.hls.loadSource(data.stream_url);
                } else if (this.videoPlayer.canPlayType('application/vnd.apple.mpegurl')) {
                    this.videoPlayer.src = data.stream_url;
                }
                
            } else {
                throw new Error(data.message || 'Gabim në marrjen e stream');
            }
            
        } catch (error) {
            console.error('Gabim në loading channel:', error);
            this.showError('Gabim në loading të kanalit: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }
    
    play() {
        this.videoPlayer.play().then(() => {
            this.isPlaying = true;
            this.updatePlayButton();
        }).catch(error => {
            console.error('Gabim në play:', error);
        });
    }
    
    pause() {
        this.videoPlayer.pause();
        this.isPlaying = false;
        this.updatePlayButton();
    }
    
    togglePlay() {
        if (this.isPlaying) {
            this.pause();
        } else {
            this.play();
        }
    }
    
    setVolume(volume) {
        this.videoPlayer.volume = volume;
    }
    
    toggleFullscreen() {
        if (!document.fullscreenElement) {
            if (this.videoPlayer.requestFullscreen) {
                this.videoPlayer.requestFullscreen();
            } else if (this.videoPlayer.webkitRequestFullscreen) {
                this.videoPlayer.webkitRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
        }
    }
    
    updateChannelInfo(channelName) {
        document.getElementById('currentChannel').textContent = channelName;
    }
    
    updatePlayButton() {
        const playBtn = document.getElementById('playBtn');
        if (playBtn) {
            playBtn.textContent = this.isPlaying ? 'Pause' : 'Play';
        }
    }
    
    showLoading() {
        const loading = document.querySelector('.loading');
        if (loading) loading.style.display = 'block';
    }
    
    hideLoading() {
        const loading = document.querySelector('.loading');
        if (loading) loading.style.display = 'none';
    }
    
    showError(message) {
        const errorDiv = document.querySelector('.error-message');
        if (errorDiv) {
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 5000);
        }
    }
    
    handleError(errorData) {
        let errorMessage = 'Gabim në transmetim';
        
        switch (errorData.type) {
            case Hls.ErrorTypes.NETWORK_ERROR:
                errorMessage = 'Gabim në rrjet. Kontrolloni internetin.';
                break;
            case Hls.ErrorTypes.MEDIA_ERROR:
                errorMessage = 'Gabim në media. Rifresko faqen.';
                break;
        }
        
        this.showError(errorMessage);
    }
    
    setupEventListeners() {
        // Play/Pause button
        document.getElementById('playBtn')?.addEventListener('click', () => {
            this.togglePlay();
        });
        
        // Pause button
        document.getElementById('pauseBtn')?.addEventListener('click', () => {
            this.pause();
        });
        
        // Fullscreen button
        document.getElementById('fullscreenBtn')?.addEventListener('click', () => {
            this.toggleFullscreen();
        });
        
        // Video events
        this.videoPlayer.addEventListener('play', () => {
            this.isPlaying = true;
            this.updatePlayButton();
        });
        
        this.videoPlayer.addEventListener('pause', () => {
            this.isPlaying = false;
            this.updatePlayButton();
        });
    }
}

// Initialize player kur faqa të jetë e gatshme
document.addEventListener('DOMContentLoaded', function() {
    window.stalkerPlayer = new StalkerPlayer();
});