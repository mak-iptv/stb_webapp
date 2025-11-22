// Konfigurimi i Ruajtjes Lokale
const SERVER_URL_KEY = 'stb_server_url';
const MAC_ADDRESS_KEY = 'stb_mac_address';

// ZËVENDËSOHENI KËTË URL ME ADRESËN PUBLIKE TË SERVERIT TUAJ PROXY NË RENDER!
// P.sh.: 'https://emri-juaj-proxy.onrender.com'
const PROXY_SERVER_URL = 'https://stb-webapp.onrender.com/'; 

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
    
    // =========================================================
    // 1. FUNKSIONET E PLAYER-IT DHE LISTËS
    // =========================================================

    /**
     * Luan një URL HLS duke përdorur HLS.js
     * @param {string} url - URL-ja e transmetimit (.m3u8)
     */
    function playChannel(url) {
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
    
    /**
     * Shfaq listën e kanaleve të marra
     * @param {Array<Object>} channels - Lista e objekteve {name, url}
     */
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
                // Kliko kanalin e parë për ta luajtur
                listItem.click(); 
            }
        });
    }

    // =========================================================
    // 2. LOGJIKA E LIDHJES DHE API (PROXY)
    // =========================================================
    
    /**
     * Bën kërkesën te Proxy Serveri për të marrë të dhënat e portalit.
     */
    async function fetchChannelsFromPortal(serverUrl, macAddress) {
        loginMessage.textContent = 'Duke u lidhur me Proxy Server...';
        connectButton.disabled = true;

        // Kërkesa te Proxy (përdor rrugën e saktë: /api/stb-login)
        const proxyApiUrl = `${PROXY_SERVER_URL}/api/stb-login?portalUrl=${encodeURIComponent(serverUrl)}&macAddress=${macAddress}`;

        try {
            const response = await fetch(proxyApiUrl);
            const data = await response.json();

            if (response.ok && data.success) {
                
                // ⚠️ HAPI TUAJ I VËRTETË I ZHVILLIMIT:
                // Këtu do të duhej të analizonit 'data.rawData' (HTML/JS e portalit)
                // për të nxjerrë listën e vërtetë të kanaleve dhe URL-të e tyre.
                
                // Për demonstrim, ne përdorim listën e simuluar:
                const simulatedChannels = [
                     { name: "Kanali Testi HLS 1 (Mux)", url: "https://test-streams.mux.dev/x36xhzz/x36xhzz.m3u8" },
                     { name: "Kanali Testi HLS 2 (Sintel)", url: "https://bitdash-a.akamaihd.net/content/sintel/hls/playlist.m3u8" }
                ];
                
                renderChannelList(simulatedChannels);
                loginMessage.textContent = 'Lidhja Proxy OK. Kanale testuese të ngarkuara.';
                
                // Kalimi nga hyrja te aplikacioni kryesor
                loginSection.style.display = 'none';
                mainApp.style.display = 'flex';
                
            } else {
                // Gabimi vjen nga Proxy (shkak i 403 nga serveri IPTV)
                throw new Error(data.error || `Gabim i panjohur. Statusi: ${response.status}`);
            }

        } catch (error) {
            console.error("Dështoi lidhja me Proxy:", error);
            // Kjo do të shfaqet nëse ka gabim lidhjeje me proxy ose proxy merr 403 nga portali
            loginMessage.textContent = `Gabim në lidhje/vërtetim. Detajet: ${error.message}.`;
            connectButton.disabled = false;
        }
    }

    /**
     * Kontrollon të dhënat e ruajtura dhe nis aplikacionin ose shfaq hyrjen.
     */
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

    // =========================================================
    // 3. EVENT LISTENERS
    // =========================================================
    
    // Lidhja e butonit "Lidhu me Portalin"
    connectButton.addEventListener('click', () => {
        const serverUrl = serverUrlInput.value.trim();
        const macAddress = macAddressInput.value.trim();
        
        if (!serverUrl || !macAddress) {
            loginMessage.textContent = 'Ju lutemi plotësoni të dy fushat.';
            return;
        }

        // Ruaj kredencialet
        localStorage.setItem(SERVER_URL_KEY, serverUrl);
        localStorage.setItem(MAC_ADDRESS_KEY, macAddress);
        
        fetchChannelsFromPortal(serverUrl, macAddress);
    });

    // Fillon aplikacionin
    checkLoginStatus();
});
