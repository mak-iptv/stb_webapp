// Channel Loader - Merr automatikisht listen e kanaleve nga provideri
class ChannelLoader {
    constructor() {
        this.channels = [];
        this.categories = new Set();
    }

    // Merr listen e kanaleve nga provideri IPTV
    async loadChannelsFromProvider(server, port, mac) {
        try {
            this.showLoading(true);
            this.updateStatus('Loading channel list from provider...', 'buffering');

            // Provon disa endpoint-e të ndryshme ku provider-i mund të ketë listen
            const endpoints = [
                `/get.php?mac=${mac}&type=m3u_plus&output=ts`,
                `/panel_api.php?mac=${mac}&action=get_all_channels`,
                `/xmltv.php?mac=${mac}`,
                `/live.php?mac=${mac}&type=m3u`,
                `/channels?mac=${mac}&format=json`
            ];

            let channelsData = null;

            // Provon çdo endpoint deri sa gjen një që funksionon
            for (const endpoint of endpoints) {
                const url = `http://${server}:${port}${endpoint}`;
                console.log(`Trying endpoint: ${url}`);
                
                try {
                    const response = await this.fetchWithTimeout(url, 10000);
                    if (response.ok) {
                        const data = await response.text();
                        
                        if (endpoint.includes('m3u') || data.trim().startsWith('#EXTM3U')) {
                            // Është M3U format
                            channelsData = this.parseM3U(data, server, port, mac);
                        } else if (data.trim().startsWith('{') || data.trim().startsWith('[')) {
                            // Është JSON format
                            channelsData = this.parseJSON(data, server, port, mac);
                        } else {
                            // Provon parsing si XML ose format tjetër
                            channelsData = this.parseOtherFormats(data, server, port, mac);
                        }
                        
                        if (channelsData && channelsData.length > 0) {
                            console.log(`Successfully loaded ${channelsData.length} channels from ${endpoint}`);
                            break;
                        }
                    }
                } catch (error) {
                    console.log(`Endpoint ${endpoint} failed:`, error.message);
                    continue;
                }
            }

            if (!channelsData || channelsData.length === 0) {
                // Nëse asnjë endpoint nuk funksionon, krijo kanale default bazuar në stream ID të njohur
                channelsData = this.createDefaultChannels(server, port, mac);
            }

            this.channels = channelsData;
            this.extractCategories();
            this.displayChannels();
            this.updateStatus(`Successfully loaded ${this.channels.length} channels`, 'online');
            
            return this.channels;

        } catch (error) {
            console.error('Error loading channels:', error);
            this.updateStatus('Error loading channels: ' + error.message, 'offline');
            return [];
        } finally {
            this.showLoading(false);
        }
    }

    // Fetch me timeout
    async fetchWithTimeout(url, timeout = 10000) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), timeout);
        
        try {
            const response = await fetch(url, {
                signal: controller.signal,
                mode: 'cors'
            });
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            throw error;
        }
    }

    // Përson M3U listen
    parseM3U(m3uContent, server, port, mac) {
        const channels = [];
        const lines = m3uContent.split('\n');
        
        let currentChannel = {};
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i].trim();
            
            if (line.startsWith('#EXTINF:')) {
                // EXTINF line: #EXTINF:-1 tvg-id="..." tvg-name="..." tvg-logo="..." group-title="...",Channel Name
                currentChannel = this.parseExtinfLine(line);
            } else if (line.startsWith('http')) {
                // URL line
                if (currentChannel.name) {
                    // Nëse URL-ja është e plotë, përdor atë, përndryshe ndërtoje
                    if (line.includes(server)) {
                        currentChannel.url = line;
                    } else {
                        // Ndërto URL-në në formatin e provider-it
                        currentChannel.streamId = this.extractStreamIdFromUrl(line);
                        currentChannel.url = this.buildStreamUrl(server, port, currentChannel.streamId, mac);
                    }
                    
                    channels.push({...currentChannel});
                    currentChannel = {};
                }
            }
        }
        
        return channels;
    }

    // Përson linjën EXTINF
    parseExtinfLine(extinfLine) {
        const channel = {};
        
        // Merr emrin e kanalit (pas presjes)
        const nameMatch = extinfLine.match(/,(.+)$/);
        if (nameMatch) {
            channel.name = nameMatch[1].trim();
        }
        
        // Merr ID e kanalit
        const idMatch = extinfLine.match(/tvg-id="([^"]*)"/);
        channel.id = idMatch ? idMatch[1] : channel.name;
        
        // Merr logo-n
        const logoMatch = extinfLine.match(/tvg-logo="([^"]*)"/);
        channel.logo = logoMatch ? logoMatch[1] : this.generateLogo(channel.name);
        
        // Merr kategorinë
        const groupMatch = extinfLine.match(/group-title="([^"]*)"/);
        channel.category = groupMatch ? groupMatch[1] : 'General';
        
        return channel;
    }

    // Përson JSON response
    parseJSON(jsonContent, server, port, mac) {
        try {
            const data = JSON.parse(jsonContent);
            const channels = [];
            
            if (Array.isArray(data)) {
                // Nëse data është array direkt
                data.forEach(item => {
                    const channel = this.parseJSONChannel(item, server, port, mac);
                    if (channel) channels.push(channel);
                });
            } else if (data.channels || data.data) {
                // Nëse data ka property channels ose data
                const channelArray = data.channels || data.data;
                if (Array.isArray(channelArray)) {
                    channelArray.forEach(item => {
                        const channel = this.parseJSONChannel(item, server, port, mac);
                        if (channel) channels.push(channel);
                    });
                }
            }
            
            return channels;
        } catch (error) {
            console.error('Error parsing JSON:', error);
            return null;
        }
    }

    // Përson një kanal nga JSON
    parseJSONChannel(item, server, port, mac) {
        if (!item) return null;
        
        const channel = {
            id: item.id || item.channel_id || item.stream_id,
            name: item.name || item.channel_name || item.title,
            category: item.category || item.group || item.genre || 'General',
            logo: item.logo || item.image || this.generateLogo(item.name || 'CH'),
            streamId: item.stream_id || item.id || this.generateStreamId(item.name)
        };
        
        if (channel.streamId) {
            channel.url = this.buildStreamUrl(server, port, channel.streamId, mac);
        }
        
        return channel.name ? channel : null;
    }

    // Përson formate të tjera
    parseOtherFormats(data, server, port, mac) {
        // Mund të shtoni parsing për formate të tjera këtu
        console.log('Trying to parse unknown format:', data.substring(0, 200));
        return null;
    }

    // Krijon kanale default nëse provider-i nuk jep listë
    createDefaultChannels(server, port, mac) {
        console.log('Creating default channel list based on common stream IDs');
        
        const defaultChannels = [
            { name: "News TV", category: "News", streamId: "1" },
            { name: "Sports HD", category: "Sports", streamId: "2" },
            { name: "Movie Channel", category: "Entertainment", streamId: "3" },
            { name: "Music TV", category: "Music", streamId: "4" },
            { name: "Kids World", category: "Kids", streamId: "5" },
            { name: "Documentary", category: "Education", streamId: "6" },
            { name: "Fashion TV", category: "Lifestyle", streamId: "7" },
            { name: "Tech Review", category: "Technology", streamId: "8" }
        ];
        
        return defaultChannels.map(channel => ({
            ...channel,
            id: channel.streamId,
            logo: this.generateLogo(channel.name),
            url: this.buildStreamUrl(server, port, channel.streamId, mac)
        }));
    }

    // Ndërton URL-në e stream-it në formatin e provider-it
    buildStreamUrl(server, port, streamId, mac) {
        return `http://${server}:${port}/play/live.php?mac=${mac}&stream=${streamId}&extension=ts`;
    }

    // Nxjerr ID e stream-it nga URL
    extractStreamIdFromUrl(url) {
        const match = url.match(/stream=([^&]+)/) || url.match(/\/([^\/]+)\.ts/);
        return match ? match[1] : Math.random().toString(36).substr(2, 9);
    }

    // Gjeneron logo bazuar në emrin e kanalit
    generateLogo(channelName) {
        const words = channelName.split(' ');
        if (words.length >= 2) {
            return (words[0][0] + words[1][0]).toUpperCase();
        }
        return channelName.substring(0, 2).toUpperCase();
    }

    // Gjeneron ID stream-i bazuar në emër
    generateStreamId(channelName) {
        return channelName.toLowerCase().replace(/[^a-z0-9]/g, '');
    }

    // Nxjerr kategoritë nga kanalet
    extractCategories() {
        this.categories.clear();
        this.channels.forEach(channel => {
            if (channel.category) {
                this.categories.add(channel.category);
            }
        });
    }

    // Shfaq kanalet në UI
    displayChannels(filter = '', category = '') {
        const channelsList = document.getElementById('channels-list');
        const loadingMessage = document.getElementById('loading-message');
        
        if (this.channels.length === 0) {
            loadingMessage.innerHTML = '<i class="fas fa-exclamation-triangle"></i> No channels loaded';
            return;
        }

        // Filtro kanalet
        const filteredChannels = this.channels.filter(channel => {
            const matchesSearch = channel.name.toLowerCase().includes(filter.toLowerCase()) ||
                                channel.category.toLowerCase().includes(filter.toLowerCase());
            const matchesCategory = !category || channel.category === category;
            return matchesSearch && matchesCategory;
        });

        // Përditëso numrin e kanaleve
        document.getElementById('channels-count').textContent = `${filteredChannels.length} channels`;

        // Krijo HTML për kanalet
        channelsList.innerHTML = '';
        filteredChannels.forEach(channel => {
            const channelElement = this.createChannelElement(channel);
            channelsList.appendChild(channelElement);
        });

        // Përditëso filter-in e kategorive
        this.updateCategoryFilter();

        // Shfaq listën
        loadingMessage.style.display = 'none';
        channelsList.style.display = 'block';
    }

    // Krijon elementin e kanalit
    createChannelElement(channel) {
        const channelElement = document.createElement('div');
        channelElement.className = 'channel-item';
        channelElement.innerHTML = `
            <div class="channel-logo">${channel.logo}</div>
            <div class="channel-info">
                <div class="channel-name">${channel.name}</div>
                <div class="channel-meta">
                    <span class="channel-category">${channel.category}</span>
                    <span class="channel-id">ID: ${channel.streamId || channel.id}</span>
                </div>
            </div>
            <div class="channel-play">
                <i class="fas fa-play-circle"></i>
            </div>
        `;

        channelElement.addEventListener('click', () => {
            this.playChannel(channel);
        });

        return channelElement;
    }

    // Përditëson filter-in e kategorive
    updateCategoryFilter() {
        const categoryFilter = document.getElementById('category-filter');
        const currentValue = categoryFilter.value;
        
        categoryFilter.innerHTML = '<option value="">All Categories</option>';
        
        this.categories.forEach(category => {
            const option = document.createElement('option');
            option.value = category;
            option.textContent = category;
            categoryFilter.appendChild(option);
        });
        
        // Ruaj vlerën e selektuar
        categoryFilter.value = currentValue;
    }

    // Luaj kanalin
    playChannel(channel) {
        if (window.player && channel.url) {
            // Hiq klasën active nga të gjitha kanalet
            document.querySelectorAll('.channel-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Shto klasën active tek kanali i selektuar
            event.currentTarget.classList.add('active');
            
            // Përditëso informacionin e tanishëm
            document.getElementById('current-channel').textContent = channel.name;
            document.getElementById('stream-info').textContent = `Category: ${channel.category} | ID: ${channel.streamId || channel.id}`;
            
            // Luaj stream-in
            window.playStream(channel.url, channel.name);
        }
    }

    // Shfaq/fshih loading message
    showLoading(show) {
        const loadingMessage = document.getElementById('loading-message');
        const channelsList = document.getElementById('channels-list');
        
        if (show) {
            loadingMessage.style.display = 'block';
            channelsList.style.display = 'none';
            loadingMessage.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading channels from provider...';
        }
    }

    // Përditëson statusin
    updateStatus(message, status) {
        if (window.updateStatus) {
            window.updateStatus(message, status);
        }
        
        const connectionInfo = document.getElementById('connection-info');
        if (connectionInfo) {
            connectionInfo.textContent = message;
        }
    }
}

// Krijo instancë globale
const channelLoader = new ChannelLoader();