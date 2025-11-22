// server.js
const express = require('express');
const fetch = require('node-fetch');
const cors = require('cors');

const app = express();
// Render përdor variablën e mjedisit PORT, por ne përdorim 3000 si parazgjedhje lokale
const PORT = process.env.PORT || 3000; 

app.use(cors()); 
app.use(express.json());
app.use(express.urlencoded({ extended: true }));

// --- Rruga Bazë (Testi i Statusit) ---
app.get('/', (req, res) => {
    res.status(200).send('Proxy Serveri StB eshte aktiv dhe funksionon.');
});

// --- Endpoint-i Kryesor për Lidhjen me Portalin IPTV ---
app.get('/api/stb-login', async (req, res) => {
    // Marrja e parametrave nga Frontend
    const { portalUrl, macAddress } = req.query;

    if (!portalUrl || !macAddress) {
        return res.status(400).json({ error: 'Portal URL ose MAC Address mungon.' });
    }

    // 🛑 KORRIGJIM PËR GABIMIN 'Invalid URL' (Shton protokollin nqs mungon)
    let correctedUrl = portalUrl.trim();
    if (!correctedUrl.startsWith('http://') && !correctedUrl.startsWith('https://')) {
        // Supozojmë HTTP si parazgjedhje për serverat IPTV
        correctedUrl = 'http://' + correctedUrl;
    }

    // URL-ja e saktë e portalit StB, duke përdorur URL-në e korrigjuar
    const stbApiUrl = `${correctedUrl.replace(/\/$/, "")}/c/index.html`; 

    try {
        const response = await fetch(stbApiUrl, {
            method: 'GET',
            headers: {
                // Header-at e nevojshëm për të simuluar një Mag Box
                'User-Agent': 'Mozilla/5.0 (QtEmbedded; IGMP service; Linu)',
                'Cookie': `mac=${macAddress}`,
                'X-User-Agent': 'Model: MAG254; Link: WiFi', 
                'Referer': `${correctedUrl.replace(/\/$/, "")}/c/`, 
                // Kjo linjë tani përdor correctedUrl, i cili është një URL i vlefshëm
                'Host': new URL(correctedUrl).hostname, 
            },
        });

        if (!response.ok) {
            console.error(`[PROXY] Gabim i serverit IPTV: ${response.status}`);
            return res.status(response.status).json({ 
                error: `Serveri IPTV ktheu gabim: ${response.status}. Kontrolloni URL-në ose MAC-un.`,
                status: response.status 
            });
        }

        // Kthen përmbajtjen e papërpunuar te Frontend-i për analizë (parsing)
        const rawData = await response.text();
        
        res.json({ 
            success: true, 
            message: 'Përgjigja u mor nga portali IPTV. Duhet analizuar përmbajtja.', 
            rawData: rawData 
        });

    } catch (error) {
        console.error('[PROXY] Gabim fatal gjatë kërkesës:', error);
        // Kthehet gabimi i koneksionit nëse nuk mund të arrihet serveri IPTV
        res.status(500).json({ error: 'Gabim në lidhjen me serverin IPTV (Nuk mund të arrihet).', details: error.message });
    }
});

app.listen(PORT, () => {
    console.log(`Proxy serveri po dëgjon në portën ${PORT}`);
});
