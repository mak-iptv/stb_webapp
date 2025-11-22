// script.js
const SERVER_URL_KEY = 'stb_server_url';
const MAC_ADDRESS_KEY = 'stb_mac_address';
// 🛑 ZËVENDËSONI KËTË: Me adresën tuaj publike të Proxy Serverit në Render!
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

    // Këto variabla ruajnë të dhënat e sesionit për ndërtimin e URL-së së kanalit
    let currentPortalUrl = '';
    let currentMacAddress = '';

    // Funksioni i luajtjes së videos
    function playChannel(url) {
        if (hlsInstance) hlsInstance.destroy();
        videoElement.src = '';
        loginMessage.textContent = '';
        
        if (Hls.isSupported()) {
            hlsInstance = new Hls();
            hlsInstance.loadSource(url);
            hlsInstance.attachMedia(videoElement);
            hlsInstance.on(Hls.Events.MANIFEST_PARSED, function() {
                // Vendoset muted për të shmangur bllokimin e Auto-play
                videoElement.muted = true; 
                videoElement.play().catch(e => console.error('Auto-play u bllokua.'));
            });
            hlsInstance.on(Hls.Events.ERROR, function (event, data) {
                 if (data.fatal) {
                    loginMessage.textContent = `Gabim fatal me HLS: ${data.details}. Provoni një kanal tjetër.`;
                    hlsInstance.destroy();
                }
            });
        } else if (videoElement.canPlayType('application/vnd.apple.mpegurl')) {
            videoElement.src = url;
            videoElement.muted = true;
            videoElement.play();
        }
    }
    
    // Funksioni i renditjes së listës së kanaleve
    function renderChannelList(channels) { 
        channelListElement.innerHTML = '';
        if (channels.length === 0) {
            channelListElement.innerHTML = '<li>Nuk u gjetën kanale.</li>';
            return;
        }
        channels.forEach((channel, index) => {
            const listItem = document.createElement('li');
            listItem.textContent = channel.name;
            // Ruajmë URL-në relative të kanalit
            listItem.dataset.url = channel.url; 
            
            listItem.addEventListener('click', () => {
                document.querySelectorAll('#channelList li').forEach(li => li.classList.remove('active'));
                listItem.classList.add('active');

                // NDËRTOJMË URL-NË E PLOTË TË KANALIT KËTU:
                // Portali juaj e përdor formatin: play/live.php?mac=...&stream=...&extension=ts&play_token=...
                const channelUrl = `${currentPortalUrl.replace(/\/$/, "")}/${channel.url.replace(/^\//, "")}`;
                
                // Zëvendësoni &extension=ts me &extension=m3u8 për HLS, nëse portali e mbështet
                const hlsUrl = channelUrl.replace(/&extension=ts/, '&extension=m3u8');
                
                playChannel(hlsUrl);
            });
            channelListElement.appendChild(listItem);
            if (index === 0) {
                listItem.click(); 
            }
        });
    }

    // =========================================================
    // FUNKSIONI I ANALIZËS (PARSING) PËR PORTALET STB
    // =========================================================
    
    /**
     * Tenton të analizojë kodin HTML/JavaScript të Portalit IPTV StB.
     */
    function extractChannels(portalContent) {
        let channels = [];
        
        try {
            // 🛑 KËRKOHET variabla 'var items' (një nga më të zakonshmet)
            // Kërkon një bllok që fillon me 'var items = ' dhe përfundon para ';'
            const regex = /var items\s*=\s*(\[[^\]]*?\]\s*)/s;
            const match = portalContent.match(regex);

            if (match && match[1]) {
                const jsonString = match[1].trim();
                
                // Përdor 'eval' për të ekzekutuar array-in JavaScript të marrë (Kujdes, por i nevojshëm këtu)
                const allChannelsArray = eval(jsonString); 
                
                // Mapon formatin e portalit në formatin tonë: {name: 'Emri', url: 'URL_Relative'}
                channels = allChannelsArray.map(ch => {
                    // Kjo është URL-ja RELATIVE e kanalit (p.sh., play/live.php?...)
                    const relativeUrl = ch.url || ch.cmd || ''; 
                    return {
                        name: ch.name || ch.title || 'Kanal i Panjohur', 
                        url: relativeUrl 
                    };
                }).filter(ch => ch.url); // Filtrimi i kanaleve pa URL
                
                console.log(`Gjetur ${channels.length} kanale duke përdorur 'var items'.`);
            } else {
                console.error("Nuk u gjet variabla 'var items'. Analiza dështoi.");
            }

        } catch (e) {
            console.error("Gabim fatal në analizën e përmbajtjes së kanalit:", e);
        }
        
        return channels.length > 0 ? channels : [
             { name: "🔴 ERROR: Nuk u gjetën kanale reale. Provoni një Portal tjetër.", url: "https://bitdash-a.akamaihd.net/content/sintel/hls/playlist.m3u8" }
        ];
    }
    
    // =========================================================
    // LOGJIKA E LIDHJES DHE API (PROXY)
    // =========================================================
    
    async function fetchChannelsFromPortal(serverUrl, macAddress) {
        // Ruajmë vlerat për përdorim të mëvonshëm
        currentPortalUrl = serverUrl.trim();
        currentMacAddress = macAddress.trim();
        
        loginMessage.textContent = 'Duke u lidhur me Proxy Server...';
        connectButton.disabled = true;

        const proxyApiUrl = `${PROXY_SERVER_URL}/api/stb-login?portalUrl=${encodeURIComponent(currentPortalUrl)}&macAddress=${currentMacAddress}`;
        
        try {
            const response = await fetch(proxyApiUrl);
            const data = await response.json();

            if (response.ok && data.success) {
                const portalContent = data.rawData;
                const realChannels = extractChannels(portalContent);
                
                renderChannelList(realChannels);
                
                loginMessage.textContent = `Lidhja OK. U gjetën ${realChannels.length} kanale.`;
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
