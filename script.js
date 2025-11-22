// script.js
const SERVER_URL_KEY = 'stb_server_url';
const MAC_ADDRESS_KEY = 'stb_mac_address';
// 🛑 Zëvendësojeni këtë URL me adresën tuaj publike të Proxy Serverit në Render!
const PROXY_SERVER_URL = 'https://stb-webapp.onrender.com'; 

document.addEventListener('DOMContentLoaded', () => {
    // Marrja e Elementeve të HTML-së
    const videoElement = document.getElementById('videoPlayer');
    const channelListElement = document.getElementById('channelList');
    const loginSection = document.getElementById('loginSection');
    const mainApp = document.getElementById('mainApp');
    const serverUrlInput = document.getElementById('serverUrl');
    const macAddressInput = document.getElementById('macAddress');
    const connectButton = document.getElementById('connectButton');
    const loginMessage = document.getElementById('loginMessage');
    
    let hlsInstance;

    function playChannel(url) {
        if (hlsInstance) hlsInstance.destroy();
        videoElement.src = '';
        loginMessage.textContent = '';
        
        if (Hls.isSupported()) {
            hlsInstance = new Hls();
            hlsInstance.loadSource(url);
            hlsInstance.attachMedia(videoElement);
            hlsInstance.on(Hls.Events.MANIFEST_PARSED, function() {
                // Zëri vendoset muted për të shmangur bllokimin e Auto-play
                videoElement.muted = true; 
                videoElement.play().catch(e => console.error('Auto-play u bllokua.'));
            });
            hlsInstance.on(Hls.Events.ERROR, function (event, data) {
                 if (data.fatal) {
                    loginMessage.textContent = `Gabim fatal. Provoni një kanal tjetër.`;
                    hlsInstance.destroy();
                }
            });
        } else if (videoElement.canPlayType('application/vnd.apple.mpegurl')) {
            videoElement.src = url;
            videoElement.muted = true; // Zëri muted edhe për Apple native player
            videoElement.play();
        }
    }
    
    function renderChannelList(channels) { 
        channelListElement.innerHTML = '';
        if (channels.length === 0) {
            channelListElement.innerHTML = '<li>Nuk u gjetën kanale.</li>';
            return;
        }
        channels.forEach((channel, index) => {
            const listItem = document.createElement('li');
            listItem.textContent = channel.name;
            listItem.dataset.url = channel.url;
            listItem.addEventListener('click', () => {
                document.querySelectorAll('#channelList li').forEach(li => li.classList.remove('active'));
                listItem.classList.add('active');
                playChannel(channel.url);
            });
            channelListElement.appendChild(listItem);
            if (index === 0) {
                listItem.click(); 
            }
        });
    }

    /**
     * Zëvendësojeni këtë funksion për të analizuar data.rawData reale.
     */
    function extractChannels(portalContent) {
        console.log("Duke analizuar përmbajtjen e portalit...");
        // Kjo listë është vetëm për testim:
        return [
             { name: "Kanali Testi HLS 1 (Mux)", url: "https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8" },
             { name: "Kanali Testi HLS 2 (Sintel)", url: "https://bitdash-a.akamaihd.net/content/sintel/hls/playlist.m3u8" }
        ];
    }
    
    async function fetchChannelsFromPortal(serverUrl, macAddress) {
        const currentUrl = serverUrl.trim();
        const currentMac = macAddress.trim();

        loginMessage.textContent = 'Duke u lidhur me Proxy Server...';
        connectButton.disabled = true;

        const proxyApiUrl = `${PROXY_SERVER_URL}/api/stb-login?portalUrl=${encodeURIComponent(currentUrl)}&macAddress=${currentMac}`;
        
        try {
            const response = await fetch(proxyApiUrl);
            const data = await response.json();

            if (response.ok && data.success) {
                const portalContent = data.rawData;
                const realChannels = extractChannels(portalContent);
                
                renderChannelList(realChannels);
                
                loginMessage.textContent = 'Lidhja Proxy OK. Kanale testuese të ngarkuara.';
                loginSection.style.display = 'none';
                mainApp.style.display = 'flex';
                
            } else {
                throw new Error(data.error || `Gabim i panjohur. Statusi: ${response.status}`);
            }

        } catch (error) {
            console.error("Gabim në lidhjen me serverin IPTV:", error);
            loginMessage.textContent = `Gabim lidhjeje ose vërtetimi. Detajet: ${error.message}.`;
            connectButton.disabled = false;
        }
    }

    function checkLoginStatus() {
        const storedUrl = localStorage.getItem(SERVER_URL_KEY);
        const storedMac = localStorage.getItem(MAC_ADDRESS_KEY);
        
        if (storedUrl && storedMac) {
            serverUrlInput.value = storedUrl;
            macAddressInput.value = storedMac;
            fetchChannelsFromPortal(storedUrl, storedMac);
        } else {
            mainApp.style.display = 'none';
            loginSection.style.display = 'flex';
        }
    }

    connectButton.addEventListener('click', () => {
        // 🛑 Merret Vlera (value) e fushës, jo Objekti (zgjidh gabimin e vjetër)
        const serverUrl = serverUrlInput.value; 
        const macAddress = macAddressInput.value;
        
        if (!serverUrl || !macAddress) {
            loginMessage.textContent = 'Ju lutemi plotësoni të dy fushat.';
            return;
        }

        localStorage.setItem(SERVER_URL_KEY, serverUrl);
        localStorage.setItem(MAC_ADDRESS_KEY, macAddress);
        
        fetchChannelsFromPortal(serverUrl, macAddress);
    });

    checkLoginStatus();
});
