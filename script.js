// script.js
const SERVER_URL_KEY = 'stb_server_url';
const MAC_ADDRESS_KEY = 'stb_mac_address';
// URL-ja e Proxy Server-it që po ekzekutoni në localhost:3000
const PROXY_SERVER_URL = 'https://stb-webapp-proxy-server.onrender.com'; 

document.addEventListener('DOMContentLoaded', () => {
    const videoElement = document.getElementById('videoPlayer');
    const channelListElement = document.getElementById('channelList');
    const loginSection = document.getElementById('loginSection');
    const mainApp = document.getElementById('mainApp');
    const serverUrlInput = document.getElementById('serverUrl');
    const macAddressInput = document.getElementById('macAddress');
    const connectButton = document.getElementById('connectButton');
    const loginMessage = document.getElementById('loginMessage');
    
    let hlsInstance;
    
    // =========================================================
    // Player & Render Functions
    // =========================================================

    function playChannel(url) {
        if (hlsInstance) hlsInstance.destroy();
        
        videoElement.src = '';
        loginMessage.textContent = '';

        if (Hls.isSupported()) {
            hlsInstance = new Hls();
            hlsInstance.loadSource(url);
            hlsInstance.attachMedia(videoElement);
            hlsInstance.on(Hls.Events.MANIFEST_PARSED, function() {
                videoElement.play().catch(e => console.log('Auto-play u bllokua.'));
            });
            hlsInstance.on(Hls.Events.ERROR, function (event, data) {
                if (data.fatal) {
                    loginMessage.textContent = `Gabim fatal. Provoni një kanal tjetër.`;
                    hlsInstance.destroy();
                }
            });
        } else if (videoElement.canPlayType('application/vnd.apple.mpegurl')) {
            videoElement.src = url;
            videoElement.addEventListener('loadedmetadata', function() {
                videoElement.play();
            });
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

    // =========================================================
    // API & Login Logic
    // =========================================================
    
    // Funksioni nuk analizon HTML/JS, por vetëm tregon se ku do të vendosen kanalet
    async function fetchChannelsFromPortal(serverUrl, macAddress) {
        loginMessage.textContent = 'Duke u lidhur me serverin proxy...';
        connectButton.disabled = true;

        const proxyApiUrl = `${PROXY_SERVER_URL}/api/stb-login?portalUrl=${encodeURIComponent(serverUrl)}&macAddress=${macAddress}`;

        try {
            const response = await fetch(proxyApiUrl);
            const data = await response.json();

            if (response.ok && data.success) {
                
                // ⚠️ KËTU ËSHTË VENDI KRYESOR PËR ZHVILLIM:
                // Ju duhet të analizoni 'data.rawData' (HTML/JS e portalit) 
                // për të gjetur listën e vërtetë të kanaleve.
                // Kjo është e vështirë dhe ndryshon nga portalit në portal.
                
                // Për demonstrim, ne po përdorim sërish një listë të simuluar:
                const simulatedChannels = [
                     { name: "Kanali Testi 1 (Mux)", url: "https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8" },
                     { name: "Kanali Testi 2 (Sintel)", url: "https://bitdash-a.akamaihd.net/content/sintel/hls/playlist.m3u8" }
                ];
                
                renderChannelList(simulatedChannels);
                loginMessage.textContent = 'Lidhja me Proxy: OK. Duke luajtur kanalin testues.';
                
                loginSection.style.display = 'none';
                mainApp.style.display = 'flex';
                
            } else {
                throw new Error(data.error || `Gabim i panjohur. Statusi: ${response.status}`);
            }

        } catch (error) {
            console.error("Dështoi lidhja me Proxy:", error);
            loginMessage.textContent = `Dështoi lidhja. Sigurohuni që Proxy Serveri (${PROXY_SERVER_URL}) të jetë aktiv. Gabimi: ${error.message}.`;
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
        const serverUrl = serverUrlInput.value.trim();
        const macAddress = macAddressInput.value.trim();
        
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
