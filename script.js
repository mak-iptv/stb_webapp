// script.js
const SERVER_URL_KEY = 'stb_server_url';
const MAC_ADDRESS_KEY = 'stb_mac_address';
// Kjo është URL-ja e Proxy Serverit tuaj në Render
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

    // Funksionet e luajtjes së videos (të paprekura)
    function playChannel(url) {
        // ... kodi i playChannel ...
        if (hlsInstance) hlsInstance.destroy();
        videoElement.src = '';
        loginMessage.textContent = '';
        if (Hls.isSupported()) {
            hlsInstance = new Hls();
            hlsInstance.loadSource(url);
            hlsInstance.attachMedia(videoElement);
            hlsInstance.on(Hls.Events.MANIFEST_PARSED, function() {
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
            videoElement.play();
        }
    }
    
    function renderChannelList(channels) { 
        // ... kodi i renderChannelList ...
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

    // Funksioni i analizës (Parsing)
    function extractChannels(portalContent) {
        console.log("Duke analizuar përmbajtjen e portalit...");
        
        // 🛑 KËTU ZGJIDHET PROBLEMI JUATOR ME KANALET 🛑
        // Për momentin, kthejmë listën testuese, por tani jemi gati të marrim përmbajtjen e portalit.
        return [
             { name: "Kanali Testi HLS 1 (Mux)", url: "https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8" },
             { name: "Kanali Testi HLS 2 (Sintel)", url: "https://bitdash-a.akamaihd.net/content/sintel/hls/playlist.m3u8" }
        ];
    }
    
    // Funksioni i marrjes së të dhënave nga Proxy
    async function fetchChannelsFromPortal(serverUrl, macAddress) {
        // Kjo linjë merr vlerat string (sakte)
        const currentUrl = serverUrl.trim();
        const currentMac = macAddress.trim();

        loginMessage.textContent = 'Duke u lidhur me Proxy Server...';
        connectButton.disabled = true;

        // Kërkesa te Proxy (përdor rrugën e saktë: /api/stb-login)
        const proxyApiUrl = `${PROXY_SERVER_URL}/api/stb-login?portalUrl=${encodeURIComponent(currentUrl)}&macAddress=${currentMac}`;
        
        console.log("Kërkesa API te Proxy:", proxyApiUrl); 

        try {
            const response = await fetch(proxyApiUrl);
            const data = await response.json();

            if (response.ok && data.success) {
                const portalContent = data.rawData;
                const realChannels = extractChannels(portalContent);
                
                renderChannelList(realChannels);
                
                loginMessage.textContent = 'Lidhja Proxy OK. Kanale të ngarkuara.';
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
            // 🛑 Kjo thirrje përdor vlerat string nga localStorage (sakte)
            fetchChannelsFromPortal(storedUrl, storedMac);
        } else {
            mainApp.style.display = 'none';
            loginSection.style.display = 'flex';
        }
    }

    // Lidhja e butonit "Lidhu me Portalin"
    connectButton.addEventListener('click', () => {
        // 🛑 Këtu merren vlerat string duke përdorur .value (sakte)
        const serverUrl = serverUrlInput.value; 
        const macAddress = macAddressInput.value;
        
        if (!serverUrl || !macAddress) {
            loginMessage.textContent = 'Ju lutemi plotësoni të dy fushat.';
            return;
        }

        localStorage.setItem(SERVER_URL_KEY, serverUrl);
        localStorage.setItem(MAC_ADDRESS_KEY, macAddress);
        
        // 🛑 Këtu kalojnë vlerat string (sakte)
        fetchChannelsFromPortal(serverUrl, macAddress); 
    });

    checkLoginStatus();
});
