document.addEventListener('DOMContentLoaded', function() {
    loadChannels();
});

async function loadChannels() {
    try {
        const response = await fetch('/api/channels');
        const data = await response.json();
        
        if (data.success) {
            displayChannels(data.channels);
        } else {
            console.error('Failed to load channels');
        }
    } catch (error) {
        console.error('Error loading channels:', error);
    }
}

function displayChannels(channels) {
    const channelsList = document.getElementById('channelsList');
    
    channelsList.innerHTML = channels.map(channel => `
        <div class="channel-card" onclick="selectChannel(${channel.id}, '${channel.name}')">
            <div class="channel-logo">
                ${channel.logo ? `<img src="${channel.logo}" alt="${channel.name}">` : '📺'}
            </div>
            <div class="channel-info">
                <h4>${channel.name}</h4>
                <small>${channel.category}</small>
            </div>
        </div>
    `).join('');
}

function selectChannel(channelId, channelName) {
    document.getElementById('currentChannel').textContent = channelName;
    
    // Këtu do të shtohet kodi për të loaduar stream-in
    console.log(`Selected channel: ${channelName} (ID: ${channelId})`);
    
    // Highlight selected channel
    document.querySelectorAll('.channel-card').forEach(card => {
        card.style.background = 'rgba(255,255,255,0.1)';
    });
    
    event.currentTarget.style.background = 'rgba(52, 152, 219, 0.3)';
}
