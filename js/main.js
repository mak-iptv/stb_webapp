// Main application functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeApp();
});

function initializeApp() {
    setupChannelEvents();
    setupSearch();
    loadUserPreferences();
}

function setupChannelEvents() {
    const channelCards = document.querySelectorAll('.channel-card');
    
    channelCards.forEach(card => {
        card.addEventListener('click', function() {
            const channelId = this.getAttribute('data-channel-id');
            const channelName = this.getAttribute('data-channel-name');
            
            // Highlight selected channel
            channelCards.forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            
            // Load channel in player
            if (window.stalkerPlayer) {
                window.stalkerPlayer.loadChannel(channelId, channelName);
            }
        });
    });
}

function setupSearch() {
    const searchInput = document.getElementById('searchChannel');
    const channelsList = document.getElementById('channelsList');
    const channelCards = channelsList.getElementsByClassName('channel-card');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        Array.from(channelCards).forEach(card => {
            const channelName = card.getAttribute('data-channel-name').toLowerCase();
            if (channelName.includes(searchTerm)) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    });
}

function loadUserPreferences() {
    // Load volume preference
    const savedVolume = localStorage.getItem('playerVolume');
    if (savedVolume && window.stalkerPlayer) {
        window.stalkerPlayer.setVolume(parseFloat(savedVolume));
    }
    
    // Load last watched channel
    const lastChannel = localStorage.getItem('lastWatchedChannel');
    if (lastChannel) {
        const [channelId, channelName] = lastChannel.split('|');
        // Auto-play last channel if needed
    }
}

// Save user preferences
function saveUserPreferences() {
    if (window.stalkerPlayer && window.stalkerPlayer.videoPlayer) {
        localStorage.setItem('playerVolume', window.stalkerPlayer.videoPlayer.volume);
    }
}

// Save when page is unloaded
window.addEventListener('beforeunload', saveUserPreferences);